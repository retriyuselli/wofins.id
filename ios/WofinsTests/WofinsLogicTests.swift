import XCTest
@testable import Wofins

final class APIConfigTests: XCTestCase {
    func testLoopbackDetection() {
        XCTAssertTrue(APIConfig.isLoopback(URL(string: "http://127.0.0.1:8000")!))
        XCTAssertTrue(APIConfig.isLoopback(URL(string: "http://localhost/storage/a.jpg")!))
        XCTAssertFalse(APIConfig.isLoopback(URL(string: "https://cdn.example.com/a.jpg")!))
    }

    func testTrustedReleaseURL() {
        XCTAssertTrue(APIConfig.isTrustedReleaseURL(URL(string: "https://app.example.com")!))
        XCTAssertFalse(APIConfig.isTrustedReleaseURL(URL(string: "http://app.example.com")!))
        XCTAssertFalse(APIConfig.isTrustedReleaseURL(URL(string: "https://127.0.0.1")!))
        XCTAssertFalse(APIConfig.isTrustedReleaseURL(URL(string: "http://localhost:8000")!))
    }

    func testAuthorizationStaysOnAPIHost() {
        let api = URL(string: "https://app.example.com")!
        XCTAssertTrue(APIConfig.shouldAttachAuthorization(to: URL(string: "https://app.example.com/api/v1/me")!, apiBase: api))
        XCTAssertFalse(APIConfig.shouldAttachAuthorization(to: URL(string: "https://files.example.net/doc.pdf")!, apiBase: api))
        XCTAssertFalse(APIConfig.shouldAttachAuthorization(to: URL(string: "http://127.0.0.1:8000/storage/x")!, apiBase: api))
    }

    func testAuthorizationMatchesPort() {
        let api = URL(string: "https://app.example.com:8443")!
        XCTAssertTrue(APIConfig.shouldAttachAuthorization(to: URL(string: "https://app.example.com:8443/file")!, apiBase: api))
        XCTAssertFalse(APIConfig.shouldAttachAuthorization(to: URL(string: "https://app.example.com/file")!, apiBase: api))
    }

    func testEndpointJoinsPrefix() {
        let url = APIConfig.endpoint("/me")
        XCTAssertNotNil(url)
        XCTAssertTrue(url?.absoluteString.contains("/api/v1/me") == true)
    }

    func testWebsiteURLStaysOffAPIPrefix() {
        let url = APIConfig.websiteURL("/forgot-password")
        XCTAssertNotNil(url)
        XCTAssertTrue(url?.path == "/forgot-password")
        XCTAssertFalse(url?.absoluteString.contains("/api/v1/") == true)
    }

    func testPhysicalDevicePrefersNonLoopbackDebugURL() {
        let loopback = URL(string: "http://127.0.0.1:8000")!
        let lan = URL(string: "http://192.0.2.10:8000")!
        XCTAssertEqual(
            APIConfig.preferredDebugBaseURL(plist: loopback, device: lan, onSimulator: true),
            loopback
        )
        XCTAssertEqual(
            APIConfig.preferredDebugBaseURL(plist: loopback, device: lan, onSimulator: false),
            lan
        )
        XCTAssertEqual(
            APIConfig.preferredDebugBaseURL(plist: loopback, device: nil, onSimulator: false),
            loopback
        )
    }

    func testAllowlistAcceptsProductionHosts() {
        XCTAssertTrue(APIConfig.isAllowedAPIHost(URL(string: "https://app.wofins.id")!))
        XCTAssertTrue(APIConfig.isAllowedAPIHost(URL(string: "https://maknafinance.id")!))
        XCTAssertTrue(APIConfig.isAllowedAPIHost(URL(string: "https://app.wofins.id/api/v1/me")!))
    }

    func testAllowlistRejectsForeignURLs() {
        XCTAssertFalse(APIConfig.isAllowedAPIHost(URL(string: "https://evil.test")!))
        XCTAssertFalse(APIConfig.isAllowedAPIHost(URL(string: "https://wofins.id")!))
        XCTAssertFalse(APIConfig.isAllowedAPIHost(URL(string: "http://maknafinance.id")!))
        XCTAssertFalse(APIConfig.isAllowedAPIHost(URL(string: "https://maknafinance.id.evil.test")!))
    }

    func testSelectedHostDefaultsToWofins() {
        let previous = UserDefaults.standard.string(forKey: APIConfig.hostSelectionKey)
        UserDefaults.standard.removeObject(forKey: APIConfig.hostSelectionKey)
        defer {
            if let previous {
                UserDefaults.standard.set(previous, forKey: APIConfig.hostSelectionKey)
            } else {
                UserDefaults.standard.removeObject(forKey: APIConfig.hostSelectionKey)
            }
        }
        XCTAssertEqual(APIConfig.selectedHost, .wofins)
    }

    func testMaknaHostUsesAllowlistedProductionURL() {
        let previous = APIConfig.selectedHost
        defer { APIConfig.selectedHost = previous }
        APIConfig.selectedHost = .makna
        XCTAssertEqual(APIConfig.baseURL.scheme, "https")
        XCTAssertEqual(APIConfig.baseURL.host, "maknafinance.id")
        XCTAssertTrue(APIConfig.endpoint("/auth/login")?.absoluteString.hasPrefix("https://maknafinance.id/api/v1/auth/login") == true)
    }
}

@MainActor
final class APIHostSwitchTests: XCTestCase {
    func testSwitchingHostClearsTokenAndSession() {
        let previous = APIConfig.selectedHost
        defer { APIConfig.selectedHost = previous }

        APIConfig.selectedHost = .wofins
        let state = AppState()
        state.api.token = "host-switch-token"
        state.isAuthenticated = true

        state.selectAPIHost(.wofins)
        XCTAssertEqual(state.api.token, "host-switch-token")
        XCTAssertTrue(state.isAuthenticated)

        state.selectAPIHost(.makna)
        XCTAssertNil(state.api.token)
        XCTAssertFalse(state.isAuthenticated)
        XCTAssertNil(state.currentUser)
        XCTAssertEqual(APIConfig.selectedHost, .makna)
    }
}

final class SavedCredentialCodecTests: XCTestCase {
    func testRoundTrip() {
        let data = SavedCredentialCodec.encode(email: "a@b.c", password: "secret")
        let decoded = SavedCredentialCodec.decode(data)
        XCTAssertEqual(decoded?.email, "a@b.c")
        XCTAssertEqual(decoded?.password, "secret")
    }

    func testPasswordMayContainNewlinesAfterFirstSplit() {
        let data = SavedCredentialCodec.encode(email: "a@b.c", password: "one\ntwo")
        let decoded = SavedCredentialCodec.decode(data)
        XCTAssertEqual(decoded?.email, "a@b.c")
        XCTAssertEqual(decoded?.password, "one\ntwo")
    }

    func testRejectsMissingPasswordLine() {
        XCTAssertNil(SavedCredentialCodec.decode(Data("only-email".utf8)))
    }
}

final class MoneyFormatTests: XCTestCase {
    func testGroupedIndonesianThousands() {
        XCTAssertEqual(MoneyFormat.grouped(14_790_000), "14.790.000")
        XCTAssertEqual(MoneyFormat.groupedInput("14790000"), "14.790.000")
        XCTAssertEqual(MoneyFormat.digits(in: "14.790.000"), "14790000")
        XCTAssertEqual(MoneyFormat.groupedInput(""), "")
    }
}

final class ListPagingTests: XCTestCase {
    func testCanLoadMore() {
        XCTAssertTrue(ListPaging.canLoadMore(current: 1, last: 3))
        XCTAssertFalse(ListPaging.canLoadMore(current: 3, last: 3))
        XCTAssertFalse(ListPaging.canLoadMore(current: nil, last: nil))
        XCTAssertFalse(ListPaging.canLoadMore(current: 1, last: 1))
    }
}

final class APITransportMapperTests: XCTestCase {
    func testOfflineMessage() {
        let mapped = APITransportMapper.map(URLError(.notConnectedToInternet))
        XCTAssertEqual(mapped.localizedDescription, "Tidak ada koneksi internet.")
    }

    func testTimeoutMessage() {
        let mapped = APITransportMapper.map(URLError(.timedOut))
        XCTAssertEqual(mapped.localizedDescription, "Koneksi ke server habis waktu. Coba lagi.")
    }

    func testOtherTransportKeepsSystemDescription() {
        let mapped = APITransportMapper.map(URLError(.cannotFindHost))
        XCTAssertEqual(mapped.localizedDescription, URLError(.cannotFindHost).localizedDescription)
    }

    func testCancellationIsNotAUserFacingError() {
        XCTAssertTrue(APILoadFailure.isCancellation(CancellationError()))
        XCTAssertTrue(APILoadFailure.isCancellation(URLError(.cancelled)))
        XCTAssertTrue(APILoadFailure.isCancellation(APIError.transport(URLError(.cancelled))))
        XCTAssertNil(APILoadFailure.userMessage(for: URLError(.cancelled)))
        XCTAssertNil(APILoadFailure.userMessage(for: CancellationError()))

        var message: String? = "lama"
        APILoadFailure.assign(URLError(.cancelled), to: &message)
        XCTAssertEqual(message, "lama")
        APILoadFailure.assign(APIError.message("Tidak ada koneksi internet."), to: &message)
        XCTAssertEqual(message, "Tidak ada koneksi internet.")
    }
}

final class LoginResponseDecodingTests: XCTestCase {
    func testDecodesSanctumPayload() throws {
        let json = """
        {
          "message": "Login berhasil.",
          "token": "test-token",
          "token_type": "Bearer",
          "user": {
            "id": 1,
            "name": "Demo",
            "email": "demo@example.com"
          }
        }
        """.data(using: .utf8)!
        let decoded = try JSONDecoder().decode(LoginResponse.self, from: json)
        XCTAssertEqual(decoded.token, "test-token")
        XCTAssertEqual(decoded.user.email, "demo@example.com")
        XCTAssertEqual(decoded.user.id, 1)
    }
}

final class SplashTimingTests: XCTestCase {
    func testReduceMotionIsNearlyImmediate() {
        XCTAssertEqual(SplashTiming.minimumHoldNanoseconds(reduceMotion: true), 120_000_000)
        XCTAssertEqual(SplashTiming.minimumHoldNanoseconds(reduceMotion: false), 800_000_000)
        XCTAssertLessThan(
            SplashTiming.minimumHoldNanoseconds(reduceMotion: false),
            3_200_000_000
        )
    }
}

final class AccountSupportLogicTests: XCTestCase {
    func testDeviceDisplayNames() {
        XCTAssertEqual(AuthSessionDevice(id: 1, name: "ios-wofins", last_used_at: nil, created_at: nil, is_current: true).displayName, "iPhone (email)")
        XCTAssertEqual(AuthSessionDevice(id: 2, name: "ios-wofins-google", last_used_at: nil, created_at: nil, is_current: false).displayName, "iPhone (Google)")
        XCTAssertEqual(AuthSessionDevice(id: 3, name: "MacBook", last_used_at: nil, created_at: nil, is_current: false).displayName, "MacBook")
        XCTAssertEqual(AuthSessionDevice(id: 4, name: nil, last_used_at: nil, created_at: nil, is_current: false).displayName, "Perangkat")
    }

    func testAccountDateFormatEmptyIsDash() {
        XCTAssertEqual(AccountDateFormat.display(nil), "—")
        XCTAssertEqual(AccountDateFormat.display("   "), "—")
    }

    func testPlanFeatureTitleFallback() {
        XCTAssertEqual(PlanFeature.title(for: "nota_dinas"), "Nota Dinas")
        XCTAssertEqual(PlanFeature.title(for: "custom_module"), "Custom Module")
    }

    func testPlanCatalogMatchesPublicPricing() {
        XCTAssertEqual(WofinsPlanCatalog.plans.map(\.id), ["starter", "professional", "business", "enterprise"])
        XCTAssertEqual(WofinsPlanCatalog.plans.first { $0.popular }?.id, "professional")
        XCTAssertTrue(SupportContact.email.contains("wofins.id"))
    }
}
