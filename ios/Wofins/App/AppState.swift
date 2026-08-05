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

    init(api: APIClient = APIClient()) {
        self.api = api
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
        do {
            currentUser = try await api.me()
            isAuthenticated = true
        } catch {
            keychain.clearToken()
            api.token = nil
            isAuthenticated = false
        }
    }

    func login(email: String, password: String) async throws {
        let response = try await api.login(email: email, password: password, deviceName: "ios-wofins")
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
            globalError = error.localizedDescription
        }
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
        api.token = nil
        currentUser = nil
        isAuthenticated = false
        globalError = message
    }
}
