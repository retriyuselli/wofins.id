import Foundation

struct APIHostOption: Identifiable, Hashable, Codable {
    let host: String
    var title: String
    var companyName: String?

    var id: String { host }
    var subtitle: String { host }
    var productionURL: URL { URL(string: "https://\(host)")! }
    var isInternalHost: Bool { host != Self.wofinsHost }

    static let wofinsHost = "app.wofins.id"
    static let licenseServerHost = "maknafinance.id"

    static let wofins = APIHostOption(host: wofinsHost, title: "WOFINS")

    static func == (lhs: APIHostOption, rhs: APIHostOption) -> Bool {
        lhs.host == rhs.host
    }

    func hash(into hasher: inout Hasher) {
        hasher.combine(host)
    }

    static func displayTitle(host: String, companyName: String?) -> String {
        switch host {
        case wofinsHost: return "WOFINS"
        case "maknafinance.id": return "Makna"
        case "saranafinance.com": return "Sarana"
        default:
            let name = companyName?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
            if !name.isEmpty {
                return String(name.prefix(18))
            }
            return host
        }
    }

    /// Migrasi key lama (`wofins` / `makna` / `sarana`) + hostname.
    static func resolveStored(_ raw: String) -> APIHostOption {
        let value = raw.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        switch value {
        case "", "wofins", wofinsHost:
            return .wofins
        case "makna", "maknafinance.id":
            return APIHostOption(host: "maknafinance.id", title: "Makna")
        case "sarana", "saranafinance.com":
            return APIHostOption(host: "saranafinance.com", title: "Sarana")
        default:
            guard let host = normalizeHost(value) else { return .wofins }
            return APIHostOption(host: host, title: displayTitle(host: host, companyName: nil))
        }
    }

    static func fromPurchaseDomain(_ domain: String, companyName: String?) -> APIHostOption? {
        guard let host = normalizeHost(domain), host != wofinsHost else { return nil }
        return APIHostOption(
            host: host,
            title: displayTitle(host: host, companyName: companyName),
            companyName: companyName
        )
    }

    static func normalizeHost(_ value: String?) -> String? {
        var value = (value ?? "").trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        guard !value.isEmpty else { return nil }
        if let schemeRange = value.range(of: "://") {
            value = String(value[schemeRange.upperBound...])
        }
        if value.hasPrefix("www.") {
            value = String(value.dropFirst(4))
        }
        value = value.split(separator: "/").first.map(String.init) ?? value
        value = value.split(separator: ":").first.map(String.init) ?? value
        guard value.contains("."), !value.contains(" "), !value.hasPrefix(".") else { return nil }
        return value
    }
}

enum LoginHostPolicy {
    static let unlockTapCount = 7
    static let unlockKey = "wofins.internalHostUnlocked"
    static let revealedHostsKey = "wofins.revealedInternalHosts"

    static var isInternalUnlocked: Bool {
        get { UserDefaults.standard.bool(forKey: unlockKey) }
        set { UserDefaults.standard.set(newValue, forKey: unlockKey) }
    }

    static var revealedInternalHosts: Set<APIHostOption> {
        get {
            if let data = UserDefaults.standard.data(forKey: revealedHostsKey),
               let decoded = try? JSONDecoder().decode([APIHostOption].self, from: data) {
                return Set(decoded.filter(\.isInternalHost))
            }
            let legacy = UserDefaults.standard.stringArray(forKey: revealedHostsKey) ?? []
            return Set(legacy.map(APIHostOption.resolveStored).filter(\.isInternalHost))
        }
        set {
            let list = Array(newValue.filter(\.isInternalHost)).sorted { $0.title < $1.title }
            if let data = try? JSONEncoder().encode(list) {
                UserDefaults.standard.set(data, forKey: revealedHostsKey)
            }
        }
    }

    static var revealedHostnames: Set<String> {
        Set(revealedInternalHosts.map(\.host))
    }

    static func resolvedHost(unlocked: Bool, current: APIHostOption) -> APIHostOption {
        unlocked ? current : .wofins
    }

    static func accountHint(for host: APIHostOption, unlocked: Bool) -> String {
        if unlocked, host.isInternalHost {
            return "Gunakan email akun internal"
        }
        return "Gunakan email akun WOFINS Anda"
    }

    @discardableResult
    static func reveal(_ host: APIHostOption) -> APIHostOption? {
        guard host.isInternalHost else { return nil }
        var revealed = revealedInternalHosts
        if let existing = revealed.first(where: { $0.host == host.host }) {
            var updated = existing
            if let companyName = host.companyName, !companyName.isEmpty {
                updated.companyName = companyName
                updated.title = APIHostOption.displayTitle(host: host.host, companyName: companyName)
            }
            revealed.remove(existing)
            revealed.insert(updated)
            revealedInternalHosts = revealed
            return updated
        }
        revealed.insert(host)
        revealedInternalHosts = revealed
        return host
    }

    static func clearRevealedHosts() {
        UserDefaults.standard.removeObject(forKey: revealedHostsKey)
    }

    /// Host yang boleh dipilih di picker: WOFINS + yang sudah dibuka via purchase code.
    static func visibleHostOptions(revealed: Set<APIHostOption> = revealedInternalHosts) -> [APIHostOption] {
        [.wofins] + revealed.filter(\.isInternalHost).sorted { $0.title < $1.title }
    }
}

enum ItemPurchaseCodeClient {
    struct VerifyResponse: Decodable {
        let valid: Bool
        let status: String
        let message: String
        let domain: String?
        let company_name: String?
    }

    static func isPurchaseCodeFormat(_ raw: String) -> Bool {
        UUID(uuidString: raw.trimmingCharacters(in: .whitespacesAndNewlines)) != nil
    }

    /// Verifikasi ke maknafinance.id tanpa bind domain (hanya untuk membuka host di app).
    static func verify(code: String, session: URLSession = .shared) async throws -> VerifyResponse {
        let trimmed = code.trimmingCharacters(in: .whitespacesAndNewlines)
        guard isPurchaseCodeFormat(trimmed) else {
            throw APIError.message("Purchase code harus berupa UUID.")
        }
        guard let url = URL(string: "https://\(APIHostOption.licenseServerHost)/api/item-purchase-codes/verify") else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.timeoutInterval = 20
        // domain kosong + bind false: jangan ikat kode ke host request iOS.
        request.httpBody = try JSONSerialization.data(withJSONObject: [
            "code": trimmed,
            "domain": "",
            "bind": false,
        ])

        let (data, response): (Data, URLResponse)
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APITransportMapper.map(error)
        }

        let http = response as? HTTPURLResponse
        let decoded = try? JSONDecoder().decode(VerifyResponse.self, from: data)
        if let decoded {
            return decoded
        }
        let body = String(data: data, encoding: .utf8)
        throw APIError.http(http?.statusCode ?? 0, body)
    }
}

enum APIConfig {
    static let apiPrefix = "/api/v1"
    static let hostSelectionKey = "wofins.apiHost"
    private static let builtinProductionHosts: Set<String> = [
        APIHostOption.wofinsHost,
        "maknafinance.id",
        "saranafinance.com",
    ]

    static var allowedProductionHosts: Set<String> {
        builtinProductionHosts.union(LoginHostPolicy.revealedHostnames)
    }

    static var selectedHost: APIHostOption {
        get {
            let raw = UserDefaults.standard.string(forKey: hostSelectionKey) ?? ""
            let resolved = APIHostOption.resolveStored(raw)
            if let revealed = LoginHostPolicy.revealedInternalHosts.first(where: { $0.host == resolved.host }) {
                return revealed
            }
            return resolved
        }
        set {
            UserDefaults.standard.set(newValue.host, forKey: hostSelectionKey)
        }
    }

    static var baseURL: URL {
        if selectedHost.isInternalHost, isAllowedAPIHost(selectedHost.productionURL) {
            return selectedHost.productionURL
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
        "Tidak terhubung. Periksa koneksi internet Anda."
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
        if allowedProductionHosts.contains(host) {
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
        if allowedProductionHosts.contains(host) {
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
            && allowedProductionHosts.contains(host)
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
