import Foundation

enum APIHostOption: String, CaseIterable, Identifiable {
    case wofins
    case makna

    var id: String { rawValue }

    var title: String {
        switch self {
        case .wofins: return "WOFINS"
        case .makna: return "Internal"
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

enum LoginHostPolicy {
    static let unlockTapCount = 7
    static let unlockKey = "wofins.internalHostUnlocked"

    static var isInternalUnlocked: Bool {
        get { UserDefaults.standard.bool(forKey: unlockKey) }
        set { UserDefaults.standard.set(newValue, forKey: unlockKey) }
    }

    static func resolvedHost(unlocked: Bool, current: APIHostOption) -> APIHostOption {
        unlocked ? current : .wofins
    }

    static func accountHint(for host: APIHostOption, unlocked: Bool) -> String {
        if unlocked, host == .makna {
            return "Gunakan email akun internal"
        }
        return "Gunakan email akun WOFINS Anda"
    }
}

enum APIConfig {
    static let apiPrefix = "/api/v1"
    static let hostSelectionKey = "wofins.apiHost"
    private static let productionHosts: Set<String> = ["app.wofins.id", "maknafinance.id"]

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
        if isAllowedMediaURL(url) { return url }
        guard isLoopback(url) else { return nil }
        #if DEBUG
        var components = URLComponents(url: url, resolvingAgainstBaseURL: false)
        components?.scheme = baseURL.scheme
        components?.host = baseURL.host
        components?.port = baseURL.port
        guard let rewritten = components?.url, isAllowedMediaURL(rewritten) else { return nil }
        return rewritten
        #else
        return nil
        #endif
    }

    static func endpoint(_ path: String) -> URL? {
        endpoint(path, queryItems: [])
    }

    static func endpoint(_ path: String, queryItems: [URLQueryItem]) -> URL? {
        guard isAllowedAPIHost(baseURL),
              var components = URLComponents(url: baseURL, resolvingAgainstBaseURL: false) else {
            return nil
        }
        let rawPath: String
        var combinedItems = queryItems
        if let separator = path.firstIndex(of: "?") {
            rawPath = String(path[..<separator])
            let rawQuery = String(path[path.index(after: separator)...])
            combinedItems.insert(contentsOf: URLComponents(string: "?\(rawQuery)")?.queryItems ?? [], at: 0)
        } else {
            rawPath = path
        }
        components.path = apiPrefix + (rawPath.hasPrefix("/") ? rawPath : "/" + rawPath)
        components.queryItems = combinedItems.isEmpty ? nil : combinedItems
        components.fragment = nil
        return components.url
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
        if productionHosts.contains(host) {
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

    static func isAllowedMediaURL(_ url: URL, apiBase: URL = baseURL) -> Bool {
        let host = (url.host ?? "").lowercased()
        if productionHosts.contains(host) {
            return url.scheme?.lowercased() == "https" && normalizedPort(url) == 443
        }
        #if DEBUG
        guard let apiHost = apiBase.host?.lowercased(), host == apiHost else { return false }
        return normalizedPort(url) == normalizedPort(apiBase)
        #else
        return false
        #endif
    }

    static func isLoopback(_ url: URL) -> Bool {
        let host = (url.host ?? "").lowercased()
        return host == "127.0.0.1" || host == "localhost" || host == "::1"
    }

    static func isTrustedReleaseURL(_ url: URL) -> Bool {
        let host = (url.host ?? "").lowercased()
        return url.scheme?.lowercased() == "https"
            && productionHosts.contains(host)
            && normalizedPort(url) == 443
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
