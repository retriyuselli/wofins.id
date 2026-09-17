import Foundation
import Combine

@MainActor
final class AppState: ObservableObject {
    @Published var isBootstrapping = true
    @Published var isAuthenticated = false
    @Published var currentUser: UserProfile?
    @Published var globalError: String?

    let api: APIClient
    private let keychain = KeychainStore()

    init(api: APIClient? = nil) {
        self.api = api ?? APIClient()
        self.api.onUnauthorized = { [weak self] in
            Task { @MainActor in
                self?.forceLogout(message: "Sesi berakhir. Silakan login lagi.")
            }
        }
    }

    func bootstrap() async {
        defer { isBootstrapping = false }
        guard let token = keychain.readToken(), !token.isEmpty else {
            isAuthenticated = false
            return
        }
        api.token = token
        // A transient bootstrap failure must not destroy a valid local session.
        // Keep the authenticated shell available and surface a recoverable error.
        isAuthenticated = true
        do {
            currentUser = try await api.me()
            globalError = nil
        } catch {
            if APILoadFailure.isCancellation(error) { return }
            if case .unauthorized? = error as? APIError {
                forceLogout(message: "Sesi berakhir. Silakan login lagi.")
            } else {
                globalError = APILoadFailure.userMessage(for: error)
                    ?? "Data akun belum dapat diperbarui. Tarik layar untuk mencoba lagi."
            }
        }
    }

    func login(email: String, password: String) async throws {
        let response = try await api.login(email: email, password: password, deviceName: "ios-wofins")
        applySession(response)
    }

    func loginWithGoogle() async throws {
        let google = try await GoogleSignInService.shared.signIn()
        let response = try await api.loginWithGoogle(
            idToken: google.idToken,
            pictureURL: google.pictureURL,
            deviceName: "ios-wofins-google"
        )
        applySession(response)
    }

    func loginWithApple(identityToken: String, accountEmail: String?, accountPassword: String?) async throws {
        let response = try await api.loginWithApple(
            identityToken: identityToken,
            accountEmail: accountEmail,
            accountPassword: accountPassword,
            deviceName: "ios-wofins-apple"
        )
        applySession(response)
    }

    private func applySession(_ response: LoginResponse) {
        keychain.saveToken(response.token)
        api.token = response.token
        currentUser = response.user
        isAuthenticated = true
        globalError = nil
    }

    func refreshMe() async {
        do {
            currentUser = try await api.me()
        } catch {
            APILoadFailure.assign(error, to: &globalError)
        }
    }

    func allows(_ feature: PlanFeature) -> Bool {
        currentUser?.allows(feature) ?? PlanFeature.starterDefaults.contains(feature)
    }

    func logout() async {
        do {
            try await api.logout()
        } catch {
            // Ignore network errors on logout; clear local session anyway.
        }
        forceLogout()
    }

    func forceLogout(message: String? = nil) {
        keychain.clearToken()
        keychain.clearCredentials()
        GoogleSignInService.shared.signOut()
        api.token = nil
        currentUser = nil
        isAuthenticated = false
        globalError = message
    }

    func selectAPIHost(_ option: APIHostOption) {
        guard APIConfig.selectedHost != option else { return }
        forceLogout()
        APIConfig.selectedHost = option
    }
}
