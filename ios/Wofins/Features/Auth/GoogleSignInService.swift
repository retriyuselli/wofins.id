import GoogleSignIn
import UIKit

enum GoogleSignInError: LocalizedError, Equatable {
    case notConfigured
    case missingPresenter
    case missingIdToken
    case cancelled

    var errorDescription: String? {
        switch self {
        case .notConfigured:
            return "Login Google belum dikonfigurasi."
        case .missingPresenter:
            return "Tidak bisa membuka login Google."
        case .missingIdToken:
            return "Token Google tidak tersedia."
        case .cancelled:
            return "Login Google dibatalkan."
        }
    }
}

@MainActor
final class GoogleSignInService {
    static let shared = GoogleSignInService()

    struct SignInResult: Sendable {
        let idToken: String
        let pictureURL: String?
    }

    private var isConfigured = false

    private init() {}

    func configureIfNeeded() {
        guard !isConfigured else { return }
        guard let clientID = googleClientID() else { return }

        if let serverClientID = googleServerClientID(), serverClientID != clientID {
            GIDSignIn.sharedInstance.configuration = GIDConfiguration(
                clientID: clientID,
                serverClientID: serverClientID
            )
        } else {
            GIDSignIn.sharedInstance.configuration = GIDConfiguration(clientID: clientID)
        }
        isConfigured = true
    }

    var isAvailable: Bool {
        googleClientID() != nil
    }

    func signIn() async throws -> SignInResult {
        configureIfNeeded()
        guard isConfigured else { throw GoogleSignInError.notConfigured }
        guard let presenter = topViewController() else { throw GoogleSignInError.missingPresenter }

        let result: GIDSignInResult
        do {
            result = try await GIDSignIn.sharedInstance.signIn(withPresenting: presenter)
        } catch {
            if (error as NSError).code == GIDSignInError.canceled.rawValue {
                throw GoogleSignInError.cancelled
            }
            throw error
        }

        guard let idToken = result.user.idToken?.tokenString else {
            throw GoogleSignInError.missingIdToken
        }

        let pictureURL = result.user.profile?.imageURL(withDimension: 400)?.absoluteString

        return SignInResult(idToken: idToken, pictureURL: pictureURL)
    }

    func handle(url: URL) -> Bool {
        GIDSignIn.sharedInstance.handle(url)
    }

    func signOut() {
        GIDSignIn.sharedInstance.signOut()
    }

    private func googleClientID() -> String? {
        plistString("GIDClientID")
    }

    private func googleServerClientID() -> String? {
        plistString("GIDServerClientID")
    }

    private func plistString(_ key: String) -> String? {
        guard let raw = Bundle.main.object(forInfoDictionaryKey: key) as? String else {
            return nil
        }
        let trimmed = raw.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty, !trimmed.contains("$("), !trimmed.contains("REPLACE_WITH") else {
            return nil
        }
        return trimmed
    }

    private func topViewController(base: UIViewController? = nil) -> UIViewController? {
        let resolvedBase = base ?? keyWindowRootViewController()

        if let navigationController = resolvedBase as? UINavigationController {
            return topViewController(base: navigationController.visibleViewController)
        }
        if let tabController = resolvedBase as? UITabBarController {
            return topViewController(base: tabController.selectedViewController)
        }
        if let presented = resolvedBase?.presentedViewController {
            return topViewController(base: presented)
        }
        return resolvedBase
    }

    private func keyWindowRootViewController() -> UIViewController? {
        for scene in UIApplication.shared.connectedScenes {
            guard let windowScene = scene as? UIWindowScene else { continue }
            for window in windowScene.windows where window.isKeyWindow {
                return window.rootViewController
            }
        }
        return nil
    }
}
