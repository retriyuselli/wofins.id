import Foundation
import LocalAuthentication
import Security

enum SavedCredentialCodec {
    static func encode(email: String, password: String) -> Data {
        Data("\(email)\n\(password)".utf8)
    }

    static func decode(_ data: Data) -> (email: String, password: String)? {
        guard let text = String(data: data, encoding: .utf8) else { return nil }
        let parts = text.split(separator: "\n", maxSplits: 1, omittingEmptySubsequences: false)
        guard parts.count == 2 else { return nil }
        return (String(parts[0]), String(parts[1]))
    }
}

final class KeychainStore {
    private let service = "id.wofins.app"
    private let account = "sanctum_token"

    func saveToken(_ token: String) {
        clearToken()
        let data = Data(token.utf8)
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecValueData as String: data,
            kSecAttrAccessible as String: kSecAttrAccessibleAfterFirstUnlockThisDeviceOnly,
        ]
        SecItemAdd(query as CFDictionary, nil)
    }

    func readToken() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne,
        ]
        var item: CFTypeRef?
        let status = SecItemCopyMatching(query as CFDictionary, &item)
        guard status == errSecSuccess, let data = item as? Data else { return nil }
        return String(data: data, encoding: .utf8)
    }

    func clearToken() {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
        ]
        SecItemDelete(query as CFDictionary)
    }

    private var credentialsService: String { "id.wofins.app.credentials" }
    private var legacyCredentialsAccount: String { "saved_credentials" }
    private var protectedCredentialsAccount: String { "saved_credentials_v2" }

    func saveCredentials(email: String, password: String) {
        clearCredentials()
        guard canUseBiometrics, let access = biometricAccessControl() else { return }

        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: credentialsService,
            kSecAttrAccount as String: protectedCredentialsAccount,
            kSecValueData as String: SavedCredentialCodec.encode(email: email, password: password),
            kSecAttrAccessControl as String: access,
        ]
        SecItemAdd(query as CFDictionary, nil)
    }

    /// Reads biometry-protected credentials. May prompt Face ID / Touch ID.
    func readProtectedCredentials(context: LAContext? = nil) -> (email: String, password: String)? {
        let auth = context ?? makeAuthContext(prompt: "Masuk ke WOFINS dengan Face ID", interactionNotAllowed: false)
        if auth.localizedReason.isEmpty {
            auth.localizedReason = "Masuk ke WOFINS dengan Face ID"
        }
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: credentialsService,
            kSecAttrAccount as String: protectedCredentialsAccount,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne,
            kSecUseAuthenticationContext as String: auth,
        ]
        var item: CFTypeRef?
        let status = SecItemCopyMatching(query as CFDictionary, &item)
        guard status == errSecSuccess, let data = item as? Data else { return nil }
        return SavedCredentialCodec.decode(data)
    }

    /// Unprotected credentials from earlier app versions. Prefer migrating via `saveCredentials`.
    func readLegacyCredentials() -> (email: String, password: String)? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: credentialsService,
            kSecAttrAccount as String: legacyCredentialsAccount,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne,
        ]
        var item: CFTypeRef?
        let status = SecItemCopyMatching(query as CFDictionary, &item)
        guard status == errSecSuccess, let data = item as? Data else { return nil }
        return SavedCredentialCodec.decode(data)
    }

    var hasAnySavedCredentials: Bool {
        hasProtectedCredentials || hasItem(account: legacyCredentialsAccount)
    }

    var canUseBiometrics: Bool {
        LAContext().canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: nil)
    }

    func clearCredentials() {
        for account in [legacyCredentialsAccount, protectedCredentialsAccount] {
            let query: [String: Any] = [
                kSecClass as String: kSecClassGenericPassword,
                kSecAttrService as String: credentialsService,
                kSecAttrAccount as String: account,
            ]
            SecItemDelete(query as CFDictionary)
        }
    }

    private var hasProtectedCredentials: Bool {
        let context = makeAuthContext(prompt: nil, interactionNotAllowed: true)
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: credentialsService,
            kSecAttrAccount as String: protectedCredentialsAccount,
            kSecReturnData as String: false,
            kSecMatchLimit as String: kSecMatchLimitOne,
            kSecUseAuthenticationContext as String: context,
        ]
        let status = SecItemCopyMatching(query as CFDictionary, nil)
        return status == errSecSuccess || status == errSecInteractionNotAllowed
    }

    private func hasItem(account: String) -> Bool {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: credentialsService,
            kSecAttrAccount as String: account,
            kSecReturnData as String: false,
            kSecMatchLimit as String: kSecMatchLimitOne,
        ]
        return SecItemCopyMatching(query as CFDictionary, nil) == errSecSuccess
    }

    private func makeAuthContext(prompt: String?, interactionNotAllowed: Bool) -> LAContext {
        let context = LAContext()
        context.interactionNotAllowed = interactionNotAllowed
        if let prompt, !prompt.isEmpty {
            context.localizedReason = prompt
        }
        return context
    }

    private func biometricAccessControl() -> SecAccessControl? {
        SecAccessControlCreateWithFlags(
            nil,
            kSecAttrAccessibleWhenUnlockedThisDeviceOnly,
            .biometryCurrentSet,
            nil
        )
    }
}
