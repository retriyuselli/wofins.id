import Foundation

enum APIError: LocalizedError {
    case invalidURL
    case unauthorized
    case responseTooLarge
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
        case .responseTooLarge:
            return "Respons server terlalu besar."
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

enum APITransportMapper {
    static func map(_ error: Error) -> APIError {
        if APILoadFailure.isCancellation(error) {
            return .transport(error)
        }
        let urlError = error as? URLError
        switch urlError?.code {
        case .notConnectedToInternet, .dataNotAllowed, .networkConnectionLost:
            return .message("Tidak ada koneksi internet.")
        case .timedOut:
            return .message("Koneksi ke server habis waktu. Coba lagi.")
        default:
            return .transport(error)
        }
    }
}

enum APILoadFailure {
    static func isCancellation(_ error: Error) -> Bool {
        if error is CancellationError { return true }
        if (error as? URLError)?.code == .cancelled { return true }
        let nsError = error as NSError
        if nsError.domain == NSURLErrorDomain && nsError.code == NSURLErrorCancelled {
            return true
        }
        if case .transport(let inner)? = error as? APIError {
            return isCancellation(inner)
        }
        let text = error.localizedDescription.trimmingCharacters(in: .whitespacesAndNewlines).lowercased()
        return text == "cancelled" || text == "canceled" || text == "dibatalkan"
    }

    static func userMessage(for error: Error) -> String? {
        guard !isCancellation(error) else { return nil }
        if let api = error as? APIError {
            switch api {
            case .unauthorized:
                return "Sesi berakhir. Silakan masuk lagi."
            case .message(let text):
                return text
            case .transport:
                return APIConfig.connectionErrorMessage
            case .http, .validation, .decoding, .invalidURL, .responseTooLarge:
                return api.errorDescription
            }
        }
        return error.localizedDescription
    }

    static func assign(_ error: Error, to message: inout String?) {
        if let text = userMessage(for: error) {
            message = text
        }
    }
}

@MainActor
final class APIClient {
    var token: String? {
        didSet {
            guard token != oldValue else { return }
            catalogTask?.cancel()
            catalogTask = nil
            catalogCache = nil
            proofCache.removeAllObjects()
        }
    }
    var onUnauthorized: (() -> Void)?

    private static let maximumJSONBytes = 5 * 1_024 * 1_024
    private static let maximumProofBytes = 20 * 1_024 * 1_024
    private static let requestTimeout: TimeInterval = 30
    private static let downloadTimeout: TimeInterval = 180
    private let session: URLSession
    private let decoder: JSONDecoder
    private let encoder: JSONEncoder
    private var catalogTask: Task<[MobileModuleCatalogItem], Error>?
    private var catalogCache: (items: [MobileModuleCatalogItem], date: Date)?
    private let proofCache = NSCache<NSString, NSData>()

    init(session: URLSession? = nil) {
        self.session = session ?? APIClient.makeSession()
        self.decoder = JSONDecoder()
        self.encoder = JSONEncoder()
        proofCache.totalCostLimit = 24 * 1_024 * 1_024
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

    func loginWithGoogle(idToken: String, pictureURL: String? = nil, deviceName: String) async throws -> LoginResponse {
        struct Body: Encodable {
            let id_token: String
            let picture_url: String?
            let device_name: String
        }
        return try await request(
            method: "POST",
            path: "/auth/google",
            body: Body(id_token: idToken, picture_url: pictureURL, device_name: deviceName),
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

    func authDevices() async throws -> [AuthSessionDevice] {
        let envelope: DataEnvelope<[AuthSessionDevice]> = try await request(method: "GET", path: "/me/devices")
        return envelope.data
    }

    func compensation(period: String = "year") async throws -> CompensationData {
        let envelope: DataEnvelope<CompensationData> = try await request(
            method: "GET",
            path: "/me/compensation",
            queryItems: [URLQueryItem(name: "period", value: period)]
        )
        return envelope.data
    }

    // MARK: - Finance

    func financeDashboard(from: String? = nil, to: String? = nil) async throws -> FinanceDashboardData {
        let envelope: DataEnvelope<FinanceDashboardData> = try await request(
            method: "GET",
            path: "/finance/dashboard",
            queryItems: queryItems(("from", from), ("to", to))
        )
        return envelope.data
    }

    func financeProjects(status: String? = nil, perPage: Int = 20, page: Int = 1) async throws -> FinanceProjectsResponse {
        try await request(
            method: "GET",
            path: "/finance/projects",
            queryItems: queryItems(
                ("per_page", String(perPage)),
                ("status", status),
                ("page", page > 1 ? String(page) : nil)
            )
        )
    }

    func financeProjectsOverview() async throws -> [FinanceOrderOverviewWidget] {
        let envelope: FinanceOrderOverviewEnvelope = try await request(
            method: "GET",
            path: "/finance/projects/overview"
        )
        return envelope.data.widgets ?? []
    }

    func financeProjectsClosing(month: String? = nil) async throws -> FinanceProjectsClosingResponse {
        try await request(
            method: "GET",
            path: "/finance/projects/closing",
            queryItems: queryItems(("month", month?.isEmpty == false ? month : nil))
        )
    }

    func financeProjectsOverviewWidget(key: String, month: String? = nil) async throws -> FinanceProjectsClosingResponse {
        try await request(
            method: "GET",
            path: "/finance/projects/widgets/\(key)",
            queryItems: queryItems(("month", month?.isEmpty == false ? month : nil))
        )
    }

    func financeProspects(status: String? = nil, perPage: Int = 50, page: Int = 1) async throws -> FinanceProspectsResponse {
        try await request(
            method: "GET",
            path: "/finance/prospects",
            queryItems: queryItems(
                ("per_page", String(perPage)),
                ("status", status),
                ("page", page > 1 ? String(page) : nil)
            )
        )
    }

    func financeProspect(id: Int) async throws -> FinanceProspectItem {
        let envelope: DataEnvelope<FinanceProspectItem> = try await request(
            method: "GET",
            path: "/finance/prospects/\(id)"
        )
        return envelope.data
    }

    func projectFormOptions(orderId: Int? = nil) async throws -> ProjectFormOptions {
        let envelope: DataEnvelope<ProjectFormOptions> = try await request(
            method: "GET",
            path: "/finance/projects/options",
            queryItems: queryItems(("order_id", orderId.map(String.init)))
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

    func financeProjectInvoiceFile(id: Int, fileName: String) async throws -> URL {
        try await fetchPDFFile(path: "/finance/projects/\(id)/invoice", fileName: fileName)
    }

    func financeProduct(id: Int) async throws -> FinanceProductDetail {
        let envelope: DataEnvelope<FinanceProductDetail> = try await request(
            method: "GET",
            path: "/finance/products/\(id)"
        )
        return envelope.data
    }

    func financeProductPdf(id: Int) async throws -> Data {
        try await fetchPDF(path: "/finance/products/\(id)/pdf")
    }

    func financeProductPdfFile(id: Int, fileName: String) async throws -> URL {
        try await fetchPDFFile(path: "/finance/products/\(id)/pdf", fileName: fileName)
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
        limit: Int = 200
    ) async throws -> FinanceTransactionsResponse {
        try await request(
            method: "GET",
            path: "/finance/transactions",
            queryItems: queryItems(
                ("limit", String(limit)),
                ("from", from),
                ("to", to),
                ("type", type),
                ("direction", direction)
            )
        )
    }

    func financePaymentProof(id: Int, urlString: String? = nil) async throws -> Data {
        let url: URL
        if let urlString, let parsed = APIConfig.mediaURL(from: urlString) {
            url = parsed
        } else if let fallback = APIConfig.endpoint("/finance/payments/\(id)/proof") {
            url = fallback
        } else {
            throw APIError.invalidURL
        }
        let cacheKey = url.absoluteString as NSString
        if let cached = proofCache.object(forKey: cacheKey) {
            return cached as Data
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = Self.requestTimeout
        request.cachePolicy = .reloadIgnoringLocalCacheData
        request.setValue("image/*,application/pdf", forHTTPHeaderField: "Accept")
        if APIConfig.shouldAttachAuthorization(to: url) {
            guard let token, !token.isEmpty else { throw APIError.unauthorized }
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }

        let (data, http) = try await boundedResponse(for: request, maximumBytes: Self.maximumProofBytes)
        try validate(http: http, data: data)
        proofCache.setObject(data as NSData, forKey: cacheKey, cost: data.count)
        return data
    }

    func financeReportSummary(
        from: String? = nil,
        to: String? = nil,
        mode: String = "cash"
    ) async throws -> FinanceReportSummary {
        let envelope: DataEnvelope<FinanceReportSummary> = try await request(
            method: "GET",
            path: "/finance/reports/summary",
            queryItems: queryItems(("mode", mode), ("from", from), ("to", to))
        )
        return envelope.data
    }

    func financeReportPdfFile(
        from: String? = nil,
        to: String? = nil,
        mode: String = "cash",
        fileName: String
    ) async throws -> URL {
        try await downloadFile(
            path: "/finance/reports/pdf",
            queryItems: queryItems(("mode", mode), ("from", from), ("to", to)),
            accept: "application/pdf",
            fileName: fileName,
            requiresPDF: true
        )
    }

    func financeReportExcelFile(
        from: String? = nil,
        to: String? = nil,
        mode: String = "cash",
        fileName: String
    ) async throws -> URL {
        try await downloadFile(
            path: "/finance/reports/excel",
            queryItems: queryItems(("mode", mode), ("from", from), ("to", to)),
            accept: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            fileName: fileName
        )
    }

    func financePiutangs(status: String? = nil, openOnly: Bool = false, perPage: Int = 20, page: Int = 1) async throws -> FinancePiutangsResponse {
        try await request(
            method: "GET",
            path: "/finance/piutangs",
            queryItems: queryItems(
                ("per_page", String(perPage)),
                ("status", status),
                ("open_only", openOnly ? "1" : nil),
                ("page", page > 1 ? String(page) : nil)
            )
        )
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
        if let cache = catalogCache, Date().timeIntervalSince(cache.date) < 60 {
            return cache.items
        }
        if let catalogTask { return try await catalogTask.value }
        let task = Task { @MainActor [weak self] in
            guard let self else { throw CancellationError() }
            let envelope: DataEnvelope<[MobileModuleCatalogItem]> = try await self.request(
                method: "GET",
                path: "/modules"
            )
            return envelope.data
        }
        catalogTask = task
        defer { catalogTask = nil }
        let items = try await task.value
        catalogCache = (items, Date())
        return items
    }

    func moduleList(
        key: String,
        query: String? = nil,
        perPage: Int = 30,
        page: Int = 1,
        filters: [String: String] = [:]
    ) async throws -> ModuleListResponse {
        var items = queryItems(
            ("per_page", String(perPage)),
            ("page", page > 1 ? String(page) : nil),
            ("q", query?.isEmpty == false ? query : nil)
        )
        items.append(contentsOf: filters.compactMap { key, value in
            value.isEmpty ? nil : URLQueryItem(name: key, value: value)
        })
        return try await request(method: "GET", path: "/modules/\(key)", queryItems: items)
    }

    func moduleForm(key: String, id: Int? = nil) async throws -> ModuleFormSchema {
        let envelope: DataEnvelope<ModuleFormSchema> = try await request(
            method: "GET",
            path: "/modules/\(key)/form",
            queryItems: queryItems(("id", id.map(String.init)))
        )
        return envelope.data
    }

    func productForm(id: Int? = nil) async throws -> ProductFormSchema {
        let envelope: DataEnvelope<ProductFormSchema> = try await request(
            method: "GET",
            path: "/modules/products/form",
            queryItems: queryItems(("id", id.map(String.init)))
        )
        return envelope.data
    }

    func createProduct(_ payload: CreateProductPayload) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "POST",
            path: "/modules/products",
            body: payload
        )
        return envelope.data
    }

    func updateProduct(id: Int, _ payload: CreateProductPayload) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "PATCH",
            path: "/modules/products/\(id)",
            body: payload
        )
        return envelope.data
    }

    func vendorForm(id: Int? = nil) async throws -> VendorFormSchema {
        let envelope: DataEnvelope<VendorFormSchema> = try await request(
            method: "GET",
            path: "/modules/vendors/form",
            queryItems: queryItems(("id", id.map(String.init)))
        )
        return envelope.data
    }

    func createVendor(_ payload: CreateVendorPayload) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "POST",
            path: "/modules/vendors",
            body: payload
        )
        return envelope.data
    }

    func updateVendor(id: Int, _ payload: CreateVendorPayload) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "PATCH",
            path: "/modules/vendors/\(id)",
            body: payload
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

    func createSimulasi(_ payload: CreateSimulasiPayload) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "POST",
            path: "/modules/simulasi",
            body: payload
        )
        return envelope.data
    }

    func updateSimulasi(id: Int, _ payload: CreateSimulasiPayload) async throws -> ModuleRecord {
        let envelope: MessageDataEnvelope<ModuleRecord> = try await request(
            method: "PATCH",
            path: "/modules/simulasi/\(id)",
            body: payload
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

    func moduleDraftKontrakPdfFile(id: Int, fileName: String) async throws -> URL {
        try await fetchPDFFile(path: "/modules/simulasi/\(id)/draft-kontrak", fileName: fileName)
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
        guard let url = APIConfig.endpoint(path) else {
            throw APIError.invalidURL
        }

        let boundary = "Boundary-\(UUID().uuidString)"
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.timeoutInterval = timeout
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")

        if authorized {
            try applyAuthorization(to: &request)
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

    private func fetchDocument(path: String) async throws -> Data {
        guard let url = APIConfig.endpoint(path) else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = Self.downloadTimeout
        request.setValue("application/pdf,text/html,application/json", forHTTPHeaderField: "Accept")
        try applyAuthorization(to: &request)

        let (data, http) = try await boundedResponse(for: request, maximumBytes: Self.maximumProofBytes)
        if !(200...299).contains(http.statusCode) {
            if http.statusCode == 401 {
                onUnauthorized?()
                throw APIError.unauthorized
            }
            let classified = DocumentPayload.classify(data)
            if case .jsonMessage(let message) = classified {
                throw APIError.http(http.statusCode, message)
            }
            let message = (try? decoder.decode(MessageResponse.self, from: data))?.message
            throw APIError.http(http.statusCode, message)
        }
        return data
    }

    private func fetchPDF(path: String) async throws -> Data {
        let data = try await fetchDocument(path: path)
        switch DocumentPayload.classify(data) {
        case .pdf:
            return DocumentPayload.pdfBytes(in: data) ?? data
        case .jsonMessage(let message):
            throw APIError.message(message)
        case .missingEndpoint:
            throw APIError.message("Server belum mengirim dokumen. Deploy API, lalu coba lagi.")
        default:
            throw APIError.message("PDF tidak dapat dibuat. Coba lagi.")
        }
    }

    private func fetchPDFFile(path: String, fileName: String) async throws -> URL {
        try await downloadFile(
            path: path,
            queryItems: [],
            accept: "application/pdf,text/html,application/json",
            fileName: fileName,
            requiresPDF: true
        )
    }

    private func request<T: Decodable>(
        method: String,
        path: String,
        authorized: Bool = true,
        queryItems: [URLQueryItem] = []
    ) async throws -> T {
        try await request(
            method: method,
            path: path,
            body: Optional<EmptyBody>.none,
            authorized: authorized,
            queryItems: queryItems
        )
    }

    private func request<T: Decodable, B: Encodable>(
        method: String,
        path: String,
        body: B?,
        authorized: Bool = true,
        queryItems: [URLQueryItem] = []
    ) async throws -> T {
        guard let url = APIConfig.endpoint(path, queryItems: queryItems) else {
            throw APIError.invalidURL
        }

        var request = URLRequest(url: url)
        request.httpMethod = method
        request.timeoutInterval = Self.requestTimeout
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")

        if authorized {
            try applyAuthorization(to: &request)
        }

        if let body {
            request.httpBody = try encoder.encode(body)
        }

        return try await perform(request)
    }

    private func perform<T: Decodable>(_ request: URLRequest) async throws -> T {
        let (data, http) = try await boundedResponse(for: request, maximumBytes: Self.maximumJSONBytes)
        try validate(http: http, data: data)

        do {
            return try decoder.decode(T.self, from: data)
        } catch {
            throw APIError.decoding(error)
        }
    }

    nonisolated private static func makeSession() -> URLSession {
        let config = URLSessionConfiguration.default
        config.timeoutIntervalForRequest = 30
        config.timeoutIntervalForResource = 90
        config.waitsForConnectivity = false
        return URLSession(configuration: config)
    }

    private func boundedResponse(
        for request: URLRequest,
        maximumBytes: Int
    ) async throws -> (Data, HTTPURLResponse) {
        var attempt = 0
        while true {
            do {
                let (bytes, response) = try await session.bytes(for: request)
                guard let http = response as? HTTPURLResponse else {
                    throw APIError.message("Respons tidak valid.")
                }
                if shouldRetry(http.statusCode, request: request, attempt: attempt) {
                    attempt += 1
                    try await retryDelay(attempt: attempt, response: http)
                    continue
                }
                if response.expectedContentLength > Int64(maximumBytes) {
                    throw APIError.responseTooLarge
                }
                var data = Data()
                if response.expectedContentLength > 0 {
                    data.reserveCapacity(min(maximumBytes, Int(response.expectedContentLength)))
                }
                for try await byte in bytes {
                    guard data.count < maximumBytes else { throw APIError.responseTooLarge }
                    data.append(byte)
                }
                return (data, http)
            } catch let api as APIError {
                throw api
            } catch {
                if shouldRetry(error, request: request, attempt: attempt) {
                    attempt += 1
                    try await retryDelay(attempt: attempt, response: nil)
                    continue
                }
                throw mapTransport(error)
            }
        }
    }

    private func validate(http: HTTPURLResponse, data: Data) throws {
        if http.statusCode == 401 {
            onUnauthorized?()
            throw APIError.unauthorized
        }
        guard (200...299).contains(http.statusCode) else {
            if let validation = try? decoder.decode(ValidationErrorResponse.self, from: data),
               let errors = validation.errors {
                throw APIError.validation(errors)
            }
            let message = (try? decoder.decode(MessageResponse.self, from: data))?.message
                ?? String(data: data.prefix(64 * 1_024), encoding: .utf8)
            throw APIError.http(http.statusCode, message)
        }
    }

    private func shouldRetry(_ status: Int, request: URLRequest, attempt: Int) -> Bool {
        request.httpMethod == "GET" && attempt < 2
            && (status == 429 || status == 502 || status == 503 || status == 504)
    }

    private func shouldRetry(_ error: Error, request: URLRequest, attempt: Int) -> Bool {
        guard request.httpMethod == "GET", attempt < 2, let error = error as? URLError else { return false }
        return [.timedOut, .networkConnectionLost, .cannotConnectToHost, .cannotFindHost]
            .contains(error.code)
    }

    private func retryDelay(attempt: Int, response: HTTPURLResponse?) async throws {
        let retryAfter = response.flatMap { Self.retryAfterDelay($0.value(forHTTPHeaderField: "Retry-After")) }
        let delay = min(4, retryAfter ?? pow(2, Double(attempt - 1)) * 0.5)
        try await Task.sleep(nanoseconds: UInt64(delay * 1_000_000_000))
    }

    static func retryAfterDelay(_ value: String?, now: Date = Date()) -> TimeInterval? {
        guard let value = value?.trimmingCharacters(in: .whitespacesAndNewlines), !value.isEmpty else {
            return nil
        }
        if let seconds = TimeInterval(value) { return max(0, min(4, seconds)) }
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(secondsFromGMT: 0)
        formatter.dateFormat = "EEE',' dd MMM yyyy HH':'mm':'ss z"
        guard let date = formatter.date(from: value) else { return nil }
        return max(0, min(4, date.timeIntervalSince(now)))
    }

    private func downloadFile(
        path: String,
        queryItems: [URLQueryItem],
        accept: String,
        fileName: String,
        requiresPDF: Bool = false
    ) async throws -> URL {
        guard let url = APIConfig.endpoint(path, queryItems: queryItems) else { throw APIError.invalidURL }
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.timeoutInterval = Self.downloadTimeout
        request.setValue(accept, forHTTPHeaderField: "Accept")
        try applyAuthorization(to: &request)

        var attempt = 0
        while true {
            do {
                let (temporaryURL, response) = try await session.download(for: request)
                guard let http = response as? HTTPURLResponse else {
                    throw APIError.message("Respons tidak valid.")
                }
                if shouldRetry(http.statusCode, request: request, attempt: attempt) {
                    attempt += 1
                    try await retryDelay(attempt: attempt, response: http)
                    continue
                }
                if http.statusCode == 401 {
                    onUnauthorized?()
                    throw APIError.unauthorized
                }
                guard (200...299).contains(http.statusCode) else {
                    let body = (try? Data(contentsOf: temporaryURL, options: [.mappedIfSafe])) ?? Data()
                    try validate(http: http, data: Data(body.prefix(Self.maximumJSONBytes)))
                    throw APIError.http(http.statusCode, nil)
                }
                if requiresPDF {
                    let prefix = try FileHandle(forReadingFrom: temporaryURL)
                    defer { try? prefix.close() }
                    guard try prefix.read(upToCount: 5)?.starts(with: Data("%PDF-".utf8)) == true else {
                        throw APIError.message("PDF tidak dapat dibuat. Coba lagi.")
                    }
                }
                return try TemporaryExportStore.persistDownloadedFile(at: temporaryURL, fileName: fileName)
            } catch let api as APIError {
                throw api
            } catch {
                if shouldRetry(error, request: request, attempt: attempt) {
                    attempt += 1
                    try await retryDelay(attempt: attempt, response: nil)
                    continue
                }
                throw mapTransport(error)
            }
        }
    }

    private func queryItems(_ pairs: (String, String?)...) -> [URLQueryItem] {
        pairs.compactMap { name, value in value.map { URLQueryItem(name: name, value: $0) } }
    }

    private func applyAuthorization(to request: inout URLRequest) throws {
        guard let url = request.url, APIConfig.shouldAttachAuthorization(to: url) else {
            throw APIError.unauthorized
        }
        guard let token, !token.isEmpty else { throw APIError.unauthorized }
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
    }

    private func mapTransport(_ error: Error) -> APIError {
        APITransportMapper.map(error)
    }
}
