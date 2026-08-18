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

    func financeProject(id: Int) async throws -> FinanceProjectDetail {
        let envelope: DataEnvelope<FinanceProjectDetail> = try await request(
            method: "GET",
            path: "/finance/projects/\(id)"
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

    // MARK: - Core request

    private struct EmptyBody: Encodable {}

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
        guard let url = URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + path) else {
            throw APIError.invalidURL
        }

        let boundary = "Boundary-\(UUID().uuidString)"
        var request = URLRequest(url: url)
        request.httpMethod = method
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
        body.append("--\(boundary)\r\n".data(using: .utf8)!)
        body.append("Content-Disposition: form-data; name=\"\(fileField)\"; filename=\"\(fileName)\"\r\n".data(using: .utf8)!)
        body.append("Content-Type: \(mimeType)\r\n\r\n".data(using: .utf8)!)
        body.append(fileData)
        body.append("\r\n".data(using: .utf8)!)
        body.append("--\(boundary)--\r\n".data(using: .utf8)!)
        request.httpBody = body

        return try await perform(request)
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
