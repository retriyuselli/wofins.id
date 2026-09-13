import Foundation

enum APIHostOption: String, CaseIterable, Identifiable {
    case wofins
    case makna

    var id: String { rawValue }

    var title: String {
        switch self {
        case .wofins: return "WOFINS"
        case .makna: return "Makna"
        }
    }

    var subtitle: String {
        switch self {
        case .wofins: return "app.wofins.id"
        case .makna: return "maknafinance.id"
        }
    }

    var productionURL: URL {
        switch self {
        case .wofins: return URL(string: "https://app.wofins.id")!
        case .makna: return URL(string: "https://maknafinance.id")!
        }
    }
}

enum APIConfig {
    static let apiPrefix = "/api/v1"
    static let hostSelectionKey = "wofins.apiHost"

    static var selectedHost: APIHostOption {
        get {
            APIHostOption(rawValue: UserDefaults.standard.string(forKey: hostSelectionKey) ?? "") ?? .wofins
        }
        set {
            UserDefaults.standard.set(newValue.rawValue, forKey: hostSelectionKey)
        }
    }

    static var baseURL: URL {
        if selectedHost == .makna, isAllowedAPIHost(APIHostOption.makna.productionURL) {
            return APIHostOption.makna.productionURL
        }
        if let plistURL = resolvedPlistURL() {
            #if DEBUG
            let device = resolvedPlistURL(key: "DEVICE_BASE_URL")
            if let chosen = preferredDebugBaseURL(
                plist: plistURL,
                device: device,
                onSimulator: isSimulator
            ) {
                return chosen
            }
            return plistURL
            #else
            if isTrustedReleaseURL(plistURL) {
                return plistURL
            }
            #endif
        }
        // Unconfigured or unsafe Release URL — fail closed, never fall back to LAN HTTP.
        return URL(string: "https://invalid.invalid")!
    }

    static var isSimulator: Bool {
        #if targetEnvironment(simulator)
        true
        #else
        false
        #endif
    }

    /// Simulator keeps loopback. A physical iPhone cannot reach the Mac at 127.0.0.1.
    static func preferredDebugBaseURL(plist: URL?, device: URL?, onSimulator: Bool) -> URL? {
        if onSimulator {
            return plist
        }
        if let device, !isLoopback(device) {
            return device
        }
        if let plist, !isLoopback(plist) {
            return plist
        }
        return plist
    }

    static var connectionErrorMessage: String {
        #if DEBUG
        if !isSimulator && isLoopback(baseURL) {
            return "iPhone tidak bisa memakai 127.0.0.1 (itu HP Anda, bukan Mac). Build ulang Debug dari Mac, satu Wi‑Fi, lalu izinkan Jaringan Lokal."
        }
        return "Tidak terhubung ke \(baseURL.absoluteString). Pastikan API Mac nyala, satu Wi‑Fi, dan izinkan Jaringan Lokal."
        #else
        return "Tidak terhubung ke server. Periksa koneksi internet."
        #endif
    }

    static var environmentLabel: String {
        #if DEBUG
        if resolvedPlistURL() == nil {
            return "Debug — BASE_URL belum di-set"
        }
        return "Debug → \(baseURL.absoluteString)"
        #else
        return isTrustedReleaseURL(baseURL) ? "Release" : "Release — BASE_URL tidak aman"
        #endif
    }

    /// Laravel `url()` often emits localhost; rewrite that host to the API the app actually uses.
    static func mediaURL(from raw: String?) -> URL? {
        guard let raw, !raw.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty,
              let url = URL(string: raw) else { return nil }
        guard isLoopback(url) else { return url }
        #if DEBUG
        var components = URLComponents(url: url, resolvingAgainstBaseURL: false)
        components?.scheme = baseURL.scheme
        components?.host = baseURL.host
        components?.port = baseURL.port
        return components?.url ?? url
        #else
        return nil
        #endif
    }

    static func endpoint(_ path: String) -> URL? {
        let root = baseURL.absoluteString.trimmingCharacters(in: CharacterSet(charactersIn: "/"))
        let suffix = path.hasPrefix("/") ? path : "/" + path
        return URL(string: root + apiPrefix + suffix)
    }

    /// Halaman web Laravel di host yang sama dengan API (bukan `/api/v1`).
    static func websiteURL(_ path: String) -> URL? {
        guard var components = URLComponents(url: baseURL, resolvingAgainstBaseURL: false) else {
            return nil
        }
        components.path = path.hasPrefix("/") ? path : "/" + path
        components.query = nil
        components.fragment = nil
        return components.url
    }

    static func shouldAttachAuthorization(to url: URL, apiBase: URL = baseURL) -> Bool {
        guard let apiHost = apiBase.host?.lowercased(), !apiHost.isEmpty else { return false }
        let host = (url.host ?? "").lowercased()
        guard host == apiHost else { return false }
        return normalizedPort(url) == normalizedPort(apiBase)
    }

    static func isAllowedAPIHost(_ url: URL) -> Bool {
        let host = (url.host ?? "").lowercased()
        if host == "app.wofins.id" || host == "maknafinance.id" {
            return url.scheme?.lowercased() == "https" && !isLoopback(url)
        }
        #if DEBUG
        if isLoopback(url) { return true }
        if url.scheme?.lowercased() == "http", !isLoopback(url) {
            return true
        }
        #endif
        return false
    }

    static func isLoopback(_ url: URL) -> Bool {
        let host = (url.host ?? "").lowercased()
        return host == "127.0.0.1" || host == "localhost" || host == "::1"
    }

    static func isTrustedReleaseURL(_ url: URL) -> Bool {
        url.scheme?.lowercased() == "https" && !isLoopback(url)
    }

    private static func resolvedPlistURL(key: String = "BASE_URL") -> URL? {
        guard let raw = Bundle.main.object(forInfoDictionaryKey: key) as? String else {
            return nil
        }
        let trimmed = raw.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty, !trimmed.contains("$("), let url = URL(string: trimmed) else {
            return nil
        }
        return url
    }

    private static func normalizedPort(_ url: URL) -> Int {
        if let port = url.port { return port }
        switch url.scheme?.lowercased() {
        case "https": return 443
        case "http": return 80
        default: return -1
        }
    }
}
