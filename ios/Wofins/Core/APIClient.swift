import Foundation

enum APIError: LocalizedError {
    case invalidURL
    case unauthorized
    case http(Int, String?)
    case decoding(Error)
    case transport(Error)
    case validation([String: [String]])
    case message(String)

    var errorDescription: String? {
        switch self {
        case .invalidURL:
            return "URL API tidak valid."
        case .unauthorized:
            return "Tidak terautentikasi."
        case .http(let code, let body):
            return body?.isEmpty == false ? body : "Server error (\(code))."
        case .decoding(let error):
            return "Gagal membaca respons: \(error.localizedDescription)"
        case .transport(let error):
            return error.localizedDescription
        case .validation(let errors):
            return errors.values.flatMap { $0 }.joined(separator: "\n")
        case .message(let text):
            return text
        }
    }
}

final class APIClient {
    var token: String?
    var onUnauthorized: (() -> Void)?

    private let session: URLSession
    private let decoder: JSONDecoder
    private let encoder: JSONEncoder

    init(session: URLSession = .shared) {
        self.session = session
        self.decoder = JSONDecoder()
        self.encoder = JSONEncoder()
    }

    // MARK: - Auth

    func login(email: String, password: String, deviceName: String) async throws -> LoginResponse {
        struct Body: Encodable {
            let email: String
            let password: String
            let device_name: String
        }
        return try await request(
            method: "POST",
            path: "/auth/login",
            body: Body(email: email, password: password, device_name: deviceName),
            authorized: false
        )
    }

    func loginWithGoogle(idToken: String, deviceName: String) async throws -> LoginResponse {
        struct Body: Encodable {
            let id_token: String
            let device_name: String
        }
        return try await request(
            method: "POST",
            path: "/auth/google",
            body: Body(id_token: idToken, device_name: deviceName),
            authorized: false
        )
    }

    func logout() async throws {
        let _: MessageResponse = try await request(method: "POST", path: "/auth/logout")
    }

    func me() async throws -> UserProfile {
        let envelope: DataEnvelope<UserProfile> = try await request(method: "GET", path: "/me")
        return envelope.data
    }

    func updateProfile(_ payload: UpdateProfilePayload) async throws -> UserProfile {
        let envelope: MessageDataEnvelope<UserProfile> = try await request(
            method: "PATCH",
            path: "/me",
            body: payload
        )
        return envelope.data
    }

    func updateAvatar(imageData: Data, fileName: String = "avatar.jpg") async throws -> UserProfile {
        let envelope: MessageDataEnvelope<UserProfile> = try await multipart(
            method: "POST",
            path: "/me/avatar",
            fields: [:],
            fileField: "avatar",
            fileName: fileName,
            mimeType: "image/jpeg",
            fileData: imageData
        )
        return envelope.data
    }

    func updatePassword(current: String, password: String, confirmation: String) async throws {
        struct Body: Encodable {
            let current_password: String
            let password: String
            let password_confirmation: String
        }
        let _: MessageResponse = try await request(
            method: "PUT",
            path: "/me/password",
            body: Body(current_password: current, password: password, password_confirmation: confirmation)
        )
    }

    func compensation(period: String = "year") async throws -> CompensationData {
        let envelope: DataEnvelope<CompensationData> = try await request(
            method: "GET",
            path: "/me/compensation?period=\(period)"
        )
        return envelope.data
    }

    // MARK: - Finance

    func financeDashboard(from: String? = nil, to: String? = nil) async throws -> FinanceDashboardData {
        var query: [String] = []
        if let from { query.append("from=\(from)") }
        if let to { query.append("to=\(to)") }
        var path = "/finance/dashboard"
        if !query.isEmpty { path += "?" + query.joined(separator: "&") }
        let envelope: DataEnvelope<FinanceDashboardData> = try await request(method: "GET", path: path)
        return envelope.data
    }

    func financeProjects(status: String? = nil, perPage: Int = 20) async throws -> FinanceProjectsResponse {
        var query: [String] = ["per_page=\(perPage)"]
        if let status { query.append("status=\(status)") }
        let path = "/finance/projects?" + query.joined(separator: "&")
        return try await request(method: "GET", path: path)
    }

    func financeProspects(status: String? = nil, perPage: Int = 50) async throws -> FinanceProspectsResponse {
        var query: [String] = ["per_page=\(perPage)"]
        if let status { query.append("status=\(status)") }
        let path = "/finance/prospects?" + query.joined(separator: "&")
        return try await request(method: "GET", path: path)
    }

    func financeProspect(id: Int) async throws -> FinanceProspectItem {
        let envelope: DataEnvelope<FinanceProspectItem> = try await request(
            method: "GET",
            path: "/finance/prospects/\(id)"
        )
        return envelope.data
    }

    func projectFormOptions(orderId: Int? = nil) async throws -> ProjectFormOptions {
        var path = "/finance/projects/options"
        if let orderId {
            path += "?order_id=\(orderId)"
        }
        let envelope: DataEnvelope<ProjectFormOptions> = try await request(
            method: "GET",
            path: path
        )
        return envelope.data
    }

    func createProject(
        fields: [String: String],
        files: [MultipartFile]
    ) async throws -> FinanceProjectItem {
        let envelope: MessageDataEnvelope<FinanceProjectItem> = try await multipart(
            method: "POST",
            path: "/finance/projects",
            fields: fields,
            files: files,
            timeout: 120
        )
        return envelope.data
    }

    func updateProject(
        id: Int,
        fields: [String: String],
        files: [MultipartFile]
    ) async throws -> FinanceProjectItem {
        let envelope: MessageDataEnvelope<FinanceProjectItem> = try await multipart(
            method: "POST",
            path: "/finance/projects/\(id)",
            fields: fields,
            files: files,
            timeout: 120
        )
        return envelope.data
    }

    func createProspect(_ payload: CreateProspectPayload) async throws -> FinanceProspectItem {
        let envelope: MessageDataEnvelope<FinanceProspectItem> = try await request(
            method: "POST",
            path: "/finance/prospects",
            body: payload
        )
        return envelope.data
    }

    func updateProspect(id: Int, _ payload: CreateProspectPayload) async throws -> FinanceProspectItem {
        let envelope: MessageDataEnvelope<FinanceProspectItem> = try await request(
            method: "PATCH",
            path: "/finance/prospects/\(id)",
            body: payload
        )
        return envelope.data
    }

    func financeProject(id: Int) async throws -> FinanceProjectDetail {
        let envelope: DataEnvelope<FinanceProjectDetail> = try await request(
            method: "GET",
            path: "/finance/projects/\(id)"
        )
        return envelope.data
    }

    func financeProjectInvoice(id: Int) async throws -> Data {
        try await fetchPDF(path: "/finance/projects/\(id)/invoice")
    }

    func financeProduct(id: Int) async throws -> FinanceProductDetail {
        let envelope: DataEnvelope<FinanceProductDetail> = try await request(
            method: "GET",
            path: "/finance/products/\(id)"
        )
        return envelope.data
    }

    func financeVendor(id: Int) async throws -> FinanceVendorDetail {
        let envelope: DataEnvelope<FinanceVendorDetail> = try await request(
            method: "GET",
            path: "/finance/vendors/\(id)"
        )
        return envelope.data
    }

    func financeTransactions(
        from: String? = nil,
        to: String? = nil,
        type: String? = nil,
        direction: String? = nil,
        limit: Int = 100
    ) async throws -> FinanceTransactionsResponse {
        var query: [String] = ["limit=\(limit)"]
        if let from { query.append("from=\(from)") }
        if let to { query.append("to=\(to)") }
        if let type { query.append("type=\(type)") }
        if let direction { query.append("direction=\(direction)") }
        let path = "/finance/transactions?" + query.joined(separator: "&")
        return try await request(method: "GET", path: path)
    }

    func financePaymentProof(id: Int, urlString: String? = nil) async throws -> Data {
        let url: URL
        if let urlString, let parsed = APIConfig.mediaURL(from: urlString) {
            url = parsed
        } else if let fallback = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + "/finance/payments/\(id)/proof") {
            url = fallback
        } else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.cachePolicy = .reloadIgnoringLocalCacheData
        request.setValue("image/*,application/pdf", forHTTPHeaderField: "Accept")
        request.setValue("no-cache", forHTTPHeaderField: "Cache-Control")
        request.setValue("no-cache", forHTTPHeaderField: "Pragma")
        guard let token, !token.isEmpty else { throw APIError.unauthorized }
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.message("Respons tidak valid.")
        }
        if http.statusCode == 401 {
            onUnauthorized?()
            throw APIError.unauthorized
        }
        if !(200...299).contains(http.statusCode) {
            throw APIError.http(http.statusCode, nil)
        }
        return data
    }

    func financeReportSummary(
        from: String? = nil,
        to: String? = nil,
        mode: String = "cash"
    ) async throws -> FinanceReportSummary {
        var query: [String] = ["mode=\(mode)"]
        if let from { query.append("from=\(from)") }
        if let to { query.append("to=\(to)") }
        let path = "/finance/reports/summary?" + query.joined(separator: "&")
        let envelope: DataEnvelope<FinanceReportSummary> = try await request(method: "GET", path: path)
        return envelope.data
    }

    func financeReportPdf(from: String? = nil, to: String? = nil, mode: String = "cash") async throws -> Data {
        var query: [String] = ["mode=\(mode)"]
        if let from { query.append("from=\(from)") }
        if let to { query.append("to=\(to)") }
        let path = "/finance/reports/pdf?" + query.joined(separator: "&")
        guard let url = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + path) else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = 120
        request.setValue("application/pdf", forHTTPHeaderField: "Accept")
        guard let token, !token.isEmpty else { throw APIError.unauthorized }
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.message("Respons tidak valid.")
        }
        if http.statusCode == 401 {
            onUnauthorized?()
            throw APIError.unauthorized
        }
        if !(200...299).contains(http.statusCode) {
            throw APIError.http(http.statusCode, nil)
        }
        return data
    }

    func financeReportExcel(from: String? = nil, to: String? = nil, mode: String = "cash") async throws -> Data {
        var query: [String] = ["mode=\(mode)"]
        if let from { query.append("from=\(from)") }
        if let to { query.append("to=\(to)") }
        let path = "/finance/reports/excel?" + query.joined(separator: "&")
        guard let url = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + path) else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = 120
        request.setValue("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet", forHTTPHeaderField: "Accept")
        guard let token, !token.isEmpty else { throw APIError.unauthorized }
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.message("Respons tidak valid.")
        }
        if http.statusCode == 401 {
            onUnauthorized?()
            throw APIError.unauthorized
        }
        if !(200...299).contains(http.statusCode) {
            throw APIError.http(http.statusCode, nil)
        }
        return data
    }

    func financePiutangs(status: String? = nil, openOnly: Bool = false, perPage: Int = 20) async throws -> FinancePiutangsResponse {
        var query: [String] = ["per_page=\(perPage)"]
        if let status { query.append("status=\(status)") }
        if openOnly { query.append("open_only=1") }
        let path = "/finance/piutangs?" + query.joined(separator: "&")
        return try await request(method: "GET", path: path)
    }

    func financePiutang(id: Int) async throws -> FinancePiutangDetail {
        let envelope: DataEnvelope<FinancePiutangDetail> = try await request(
            method: "GET",
            path: "/finance/piutangs/\(id)"
        )
        return envelope.data
    }

    // MARK: - Company modules

    func moduleCatalog() async throws -> [MobileModuleCatalogItem] {
        let envelope: DataEnvelope<[MobileModuleCatalogItem]> = try await request(
            method: "GET",
            path: "/modules"
        )
        return envelope.data
    }

    func moduleList(key: String, query: String? = nil, perPage: Int = 30) async throws -> ModuleListResponse {
        var parts = ["per_page=\(perPage)"]
        if let query, !query.isEmpty, let encoded = query.addingPercentEncoding(withAllowedCharacters: .urlQueryAllowed) {
            parts.append("q=\(encoded)")
        }
        return try await request(method: "GET", path: "/modules/\(key)?" + parts.joined(separator: "&"))
    }

    func moduleForm(key: String) async throws -> ModuleFormSchema {
        let envelope: DataEnvelope<ModuleFormSchema> = try await request(
            method: "GET",
            path: "/modules/\(key)/form"
        )
        return envelope.data
    }

    func moduleDetail(key: String, id: Int) async throws -> ModuleRecord {
        let envelope: DataEnvelope<ModuleRecord> = try await request(
            method: "GET",
            path: "/modules/\(key)/\(id)"
        )
        return envelope.data
    }

    func createModule(key: String, values: [String: String]) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "POST",
            path: "/modules/\(key)",
            body: JSONDictionary(values: values)
        )
        return envelope.data
    }

    func updateModule(key: String, id: Int, values: [String: String]) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "PATCH",
            path: "/modules/\(key)/\(id)",
            body: JSONDictionary(values: values)
        )
        return envelope.data
    }

    func moduleDraftKontrakPdf(id: Int) async throws -> Data {
        try await fetchPDF(path: "/modules/simulasi/\(id)/draft-kontrak")
    }

    // MARK: - Core request

    private struct EmptyBody: Encodable {}

    struct MultipartFile {
        let field: String
        let fileName: String
        let mimeType: String
        let data: Data
    }

    private func multipart<T: Decodable>(
        method: String,
        path: String,
        fields: [String: String],
        fileField: String,
        fileName: String,
        mimeType: String,
        fileData: Data,
        authorized: Bool = true
    ) async throws -> T {
        try await multipart(
            method: method,
            path: path,
            fields: fields,
            files: [
                MultipartFile(field: fileField, fileName: fileName, mimeType: mimeType, data: fileData)
            ],
            authorized: authorized
        )
    }

    private func multipart<T: Decodable>(
        method: String,
        path: String,
        fields: [String: String],
        files: [MultipartFile],
        authorized: Bool = true,
        timeout: TimeInterval = 60
    ) async throws -> T {
        guard let url = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + path) else {
            throw APIError.invalidURL
        }

        let boundary = "Boundary-\(UUID().uuidString)"
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.timeoutInterval = timeout
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")

        if authorized {
            guard let token, !token.isEmpty else { throw APIError.unauthorized }
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        var body = Data()
        for (key, value) in fields {
            body.append("--\(boundary)\r\n".data(using: .utf8)!)
            body.append("Content-Disposition: form-data; name=\"\(key)\"\r\n\r\n".data(using: .utf8)!)
            body.append("\(value)\r\n".data(using: .utf8)!)
        }
        for file in files {
            body.append("--\(boundary)\r\n".data(using: .utf8)!)
            body.append("Content-Disposition: form-data; name=\"\(file.field)\"; filename=\"\(file.fileName)\"\r\n".data(using: .utf8)!)
            body.append("Content-Type: \(file.mimeType)\r\n\r\n".data(using: .utf8)!)
            body.append(file.data)
            body.append("\r\n".data(using: .utf8)!)
        }
        body.append("--\(boundary)--\r\n".data(using: .utf8)!)
        request.httpBody = body

        return try await perform(request)
    }

    private func fetchPDF(path: String) async throws -> Data {
        guard let url = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + path) else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = 180
        request.setValue("application/pdf", forHTTPHeaderField: "Accept")
        guard let token, !token.isEmpty else { throw APIError.unauthorized }
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")

        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.message("Respons tidak valid.")
        }
        if http.statusCode == 401 {
            onUnauthorized?()
            throw APIError.unauthorized
        }
        if !(200...299).contains(http.statusCode) {
            let message = (try? decoder.decode(MessageResponse.self, from: data))?.message
            throw APIError.http(http.statusCode, message)
        }
        let isPdf = data.starts(with: [0x25, 0x50, 0x44, 0x46])
        if !isPdf {
            throw APIError.message("PDF tidak dapat dibuat. Coba lagi.")
        }
        return data
    }

    private func request<T: Decodable>(
        method: String,
        path: String,
        authorized: Bool = true
    ) async throws -> T {
        try await request(method: method, path: path, body: Optional<EmptyBody>.none, authorized: authorized)
    }

    private func request<T: Decodable, B: Encodable>(
        method: String,
        path: String,
        body: B?,
        authorized: Bool = true
    ) async throws -> T {
        guard let url = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + path) else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = method
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")

        if authorized {
            guard let token, !token.isEmpty else { throw APIError.unauthorized }
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        if let body {
            request.httpBody = try encoder.encode(body)
        }

        return try await perform(request)
    }

    private func perform<T: Decodable>(_ request: URLRequest) async throws -> T {
        let data: Data
        let response: URLResponse
        do {
            (data, response) = try await session.data(for: request)
        } catch {
            throw APIError.transport(error)
        }

        guard let http = response as? HTTPURLResponse else {
            throw APIError.message("Respons tidak valid.")
        }

        if http.statusCode == 401 {
            onUnauthorized?()
            throw APIError.unauthorized
        }

        if !(200...299).contains(http.statusCode) {
            if let validation = try? decoder.decode(ValidationErrorResponse.self, from: data),
               let errors = validation.errors {
                throw APIError.validation(errors)
            }
            let message = (try? decoder.decode(MessageResponse.self, from: data))?.message
                ?? String(data: data, encoding: .utf8)
            throw APIError.http(http.statusCode, message)
        }

        do {
            return try decoder.decode(T.self, from: data)
        } catch {
            throw APIError.decoding(error)
        }
    }
}
