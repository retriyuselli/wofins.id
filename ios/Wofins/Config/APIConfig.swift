import Foundation

enum APIConfig {
    /// Mac LAN IP untuk device fisik (ubah jika IP Mac berubah).
    private static let deviceHostURL = URL(string: "http://192.168.1.3:8000")!

    static var baseURL: URL {
        #if targetEnvironment(simulator)
        return resolvedPlistURL() ?? URL(string: "http://127.0.0.1:8000")!
        #else
        // Di iPhone fisik, 127.0.0.1 = HP sendiri — jangan pakai localhost dari Info.plist.
        if let plistURL = resolvedPlistURL(), !isLoopback(plistURL) {
            return plistURL
        }
        return deviceHostURL
        #endif
    }

    static let apiPrefix = "/api/v1"

    static var environmentLabel: String {
        #if targetEnvironment(simulator)
        return "Simulator → \(baseURL.absoluteString)"
        #else
        return "Device → \(baseURL.absoluteString)"
        #endif
    }

    private static func resolvedPlistURL() -> URL? {
        guard let raw = Bundle.main.object(forInfoDictionaryKey: "BASE_URL") as? String else {
            return nil
        }
        let trimmed = raw.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty, !trimmed.contains("$("), let url = URL(string: trimmed) else {
            return nil
        }
        return url
    }

    private static func isLoopback(_ url: URL) -> Bool {
        let host = (url.host ?? "").lowercased()
        return host == "127.0.0.1" || host == "localhost" || host == "::1"
    }
}
