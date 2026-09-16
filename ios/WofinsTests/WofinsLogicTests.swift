import XCTest
@testable import Wofins

final class APIConfigTests: XCTestCase {
    func testLoopbackDetection() {
        XCTAssertTrue(APIConfig.isLoopback(URL(string: "http://127.0.0.1:8000")!))
        XCTAssertTrue(APIConfig.isLoopback(URL(string: "http://localhost/storage/a.jpg")!))
        XCTAssertFalse(APIConfig.isLoopback(URL(string: "https://cdn.example.com/a.jpg")!))
    }

    func testTrustedReleaseURL() {
        XCTAssertTrue(APIConfig.isTrustedReleaseURL(URL(string: "https://app.wofins.id")!))
        XCTAssertTrue(APIConfig.isTrustedReleaseURL(URL(string: "https://maknafinance.id")!))
        XCTAssertFalse(APIConfig.isTrustedReleaseURL(URL(string: "https://app.example.com")!))
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

    func testEndpointEncodesQueryItemsWithoutChangingMeaning() {
        let url = APIConfig.endpoint(
            "/modules/products",
            queryItems: [
                URLQueryItem(name: "q", value: "Paket & Vendor"),
                URLQueryItem(name: "status", value: "aktif/belum"),
            ]
        )
        let components = url.flatMap { URLComponents(url: $0, resolvingAgainstBaseURL: false) }
        XCTAssertEqual(components?.queryItems?.first(where: { $0.name == "q" })?.value, "Paket & Vendor")
        XCTAssertEqual(components?.queryItems?.first(where: { $0.name == "status" })?.value, "aktif/belum")
    }

    func testWebsiteURLStaysOffAPIPrefix() {
        let url = APIConfig.websiteURL("/forgot-password")
        XCTAssertNotNil(url)
        XCTAssertTrue(url?.path == "/forgot-password")
        XCTAssertFalse(url?.absoluteString.contains("/api/v1/") == true)
    }

    func testProductPreviewUsesWebsiteHostNotAPIPrefix() {
        let url = APIConfig.websiteURL("/products/silvi-resepsi-aryaduta-palembang-1000-pax-platinum-2026-2027/details/preview")
        XCTAssertNotNil(url)
        XCTAssertEqual(
            url?.path,
            "/products/silvi-resepsi-aryaduta-palembang-1000-pax-platinum-2026-2027/details/preview"
        )
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

    func testMediaAllowlistRejectsForeignAndLookalikeHosts() {
        XCTAssertTrue(APIConfig.isAllowedMediaURL(URL(string: "https://app.wofins.id/storage/a.jpg")!))
        XCTAssertTrue(APIConfig.isAllowedMediaURL(URL(string: "https://maknafinance.id/storage/a.jpg")!))
        XCTAssertFalse(APIConfig.isAllowedMediaURL(URL(string: "https://cdn.example.com/a.jpg")!))
        XCTAssertFalse(APIConfig.isAllowedMediaURL(URL(string: "https://app.wofins.id.evil.test/a.jpg")!))
        XCTAssertFalse(APIConfig.isAllowedMediaURL(URL(string: "http://app.wofins.id/a.jpg")!))
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

    func testPublicLoginStaysOnWofinsUntilInternalUnlock() {
        XCTAssertEqual(LoginHostPolicy.resolvedHost(unlocked: false, current: .makna), .wofins)
        XCTAssertEqual(LoginHostPolicy.resolvedHost(unlocked: true, current: .makna), .makna)
        XCTAssertEqual(LoginHostPolicy.accountHint(for: .makna, unlocked: false), "Gunakan email akun WOFINS Anda")
        XCTAssertEqual(LoginHostPolicy.accountHint(for: .makna, unlocked: true), "Gunakan email akun internal")
        XCTAssertEqual(APIHostOption.makna.title, "Internal")
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

final class DocumentPayloadTests: XCTestCase {
    func testClassifiesPdfMagicBytes() {
        let data = Data("%PDF-1.4\n...".utf8)
        XCTAssertEqual(DocumentPayload.classify(data), .pdf)
    }

    func testClassifiesJsonMessage() throws {
        let data = try JSONSerialization.data(withJSONObject: ["message": "Paket tidak ditemukan."])
        XCTAssertEqual(DocumentPayload.classify(data), .jsonMessage("Paket tidak ditemukan."))
    }

    func testClassifiesMarketingHomepageAsMissingEndpoint() {
        let html = "<html><body>Kelola Wedding Organizer Lebih Rapi Jadwalkan Demo Gratis</body></html>"
        XCTAssertEqual(DocumentPayload.classify(Data(html.utf8)), .missingEndpoint)
    }

    func testClassifiesProductPreviewHTML() {
        let html = "<!DOCTYPE html><html><head><title>Product Details - Silvi</title></head></html>"
        XCTAssertEqual(DocumentPayload.classify(Data(html.utf8)), .html)
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

final class ProjectProfitDisplayTests: XCTestCase {
    func testUsesApiProfitWhenPresent() {
        XCTAssertEqual(ProjectProfitDisplay.amount(api: 19_599_500, grandTotal: 277_500_000, expenses: 257_900_500), 19_599_500)
    }

    func testFallsBackToGrandTotalMinusExpenses() {
        XCTAssertEqual(ProjectProfitDisplay.amount(api: nil, grandTotal: 277_500_000, expenses: 257_900_500), 19_599_500)
    }

    func testLabelsLossAsRugi() {
        XCTAssertEqual(ProjectProfitDisplay.title(for: 1), "Keuntungan")
        XCTAssertEqual(ProjectProfitDisplay.title(for: 0), "Keuntungan")
        XCTAssertEqual(ProjectProfitDisplay.title(for: -500), "Rugi")
    }
}

final class DisplayTextTests: XCTestCase {
    func testTitleCasesExpenseAndIncomeLabels() {
        XCTAssertEqual(DisplayText.titleCase("MASTER OF CEREMONY 1 PASANG"), "Master Of Ceremony 1 Pasang")
        XCTAssertEqual(DisplayText.titleCase("[MASTER] THE SULTAN CONVENTION 1000 PAX"), "[Master] The Sultan Convention 1000 Pax")
        XCTAssertEqual(DisplayText.titleCase("WEDDING ORGANIZER"), "Wedding Organizer")
        XCTAssertEqual(DisplayText.titleCase("Fee Konsumen Wedding"), "Fee Konsumen Wedding")
        XCTAssertEqual(DisplayText.titleCase("Special for Qiqi Erlan"), "Special For Qiqi Erlan")
        XCTAssertEqual(DisplayText.titleCase("HARPER PALEMBANG 700 PAX Copy"), "Harper Palembang 700 Pax Copy")
        XCTAssertEqual(DisplayText.titleCase("(MASTER) HARPER PALEMBANG 1000 PAX 2027"), "(Master) Harper Palembang 1000 Pax 2027")
        XCTAssertEqual(DisplayText.titleCase("WEDDING ORGANIZER EDO ZHIRA"), "Wedding Organizer Edo Zhira")
        XCTAssertEqual(DisplayText.titleCase("LAMARAN SANTIKA PREMIER 150 PAX 2026-2027"), "Lamaran Santika Premier 150 Pax 2026-2027")
        XCTAssertEqual(DisplayText.titleCase("VERIN"), "Verin")
        XCTAssertEqual(DisplayText.titleCase("PALEMBANG"), "Palembang")
        XCTAssertEqual(DisplayText.titleCase("BCA"), "BCA")
        XCTAssertEqual(DisplayText.titleCase("Rp 37.000.000"), "Rp 37.000.000")
        XCTAssertEqual(DisplayText.titleCase("812"), "812")
    }
}

final class HTMLTextListTests: XCTestCase {
    func testListItemsFromUnorderedHTML() {
        let html = """
        <ul>
          <li>Grand Ballroom Aryaduta</li>
          <li>1 Honeymoon Suite room for 1 night stay</li>
          <li>Ice Carving</li>
        </ul>
        """
        XCTAssertEqual(HTMLText.listItems(html), [
            "Grand Ballroom Aryaduta",
            "1 Honeymoon Suite room for 1 night stay",
            "Ice Carving",
        ])
        XCTAssertFalse(HTMLText.isOrderedList(html))
    }

    func testListItemsFromOrderedHTML() {
        let html = "<ol><li>Food testing for 6 persons</li><li>Ice Carving</li></ol>"
        XCTAssertEqual(HTMLText.listItems(html), [
            "Food testing for 6 persons",
            "Ice Carving",
        ])
        XCTAssertTrue(HTMLText.isOrderedList(html))
    }

    func testListItemsFromPlainDashes() {
        let text = "- Grand Ballroom Aryaduta\n- Ice Carving\n- Transit refreshment access"
        XCTAssertEqual(HTMLText.listItems(text), [
            "Grand Ballroom Aryaduta",
            "Ice Carving",
            "Transit refreshment access",
        ])
    }

    func testRecoversGluedStripTagsList() {
        let glued = "Grand Ballroom Aryaduta1 Honeymoon Suite room for 1 night stay5 Superior rooms for 1 night stayIce Carving"
        XCTAssertEqual(HTMLText.listItems(glued), [
            "Grand Ballroom Aryaduta",
            "1 Honeymoon Suite room for 1 night stay",
            "5 Superior rooms for 1 night stay",
            "Ice Carving",
        ])
    }
}

final class FinanceProductDetailDecodingTests: XCTestCase {
    func testDecodesVendorsAdditionsAndDiscountsWithLooseTypes() throws {
        let json = """
        {
          "id": "42",
          "name": "Paket Silvi",
          "is_active": 1,
          "vendors": [{
            "id": 1,
            "vendor_id": "9",
            "name": "HOTEL ARYADUTA",
            "phone": 812,
            "quantity": "1",
            "harga_publish": "274000000",
            "harga_vendor": 249000000,
            "description": "Ballroom"
          }],
          "additions": [{
            "id": 2,
            "vendor_id": 8,
            "name": "Penambahan Pax",
            "harga_publish": 7500000,
            "harga_vendor": 3000000
          }],
          "discounts": [{
            "id": 3,
            "description": "Diskon venue",
            "amount": "249000000"
          }],
          "pricing": {
            "harga_awal_publish": 459500000,
            "pengurangan": 249000000,
            "profit": 55600000
          }
        }
        """.data(using: .utf8)!

        let detail = try JSONDecoder().decode(FinanceProductDetail.self, from: json)
        XCTAssertEqual(detail.id, 42)
        XCTAssertEqual(detail.is_active, true)
        XCTAssertEqual(detail.vendors?.count, 1)
        XCTAssertEqual(detail.vendors?.first?.phone, "812")
        XCTAssertEqual(detail.vendors?.first?.harga_publish, 274_000_000)
        XCTAssertEqual(detail.additions?.count, 1)
        XCTAssertEqual(detail.discounts?.first?.amount, 249_000_000)
        XCTAssertEqual(detail.pricing?.profit, 55_600_000)
    }

    func testKeepsValidVendorsWhenOneLineIsMalformed() throws {
        let json = """
        {
          "id": 7,
          "vendors": [
            {"id": 1, "name": "Aryaduta", "harga_publish": 1000},
            "broken",
            {"id": 3, "name": "WO", "harga_publish": 2000}
          ],
          "additions": [{"id": 9, "name": 12, "harga_publish": "500"}],
          "discounts": [{"id": 4, "description": 11, "amount": 100}]
        }
        """.data(using: .utf8)!

        let detail = try JSONDecoder().decode(FinanceProductDetail.self, from: json)
        XCTAssertEqual(detail.vendors?.count, 2)
        XCTAssertEqual(detail.vendors?.first?.name, "Aryaduta")
        XCTAssertEqual(detail.additions?.first?.name, "12")
        XCTAssertEqual(detail.discounts?.first?.description, "11")
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

@MainActor
final class APIRetryPolicyTests: XCTestCase {
    func testRetryAfterSecondsIsBounded() {
        XCTAssertEqual(APIClient.retryAfterDelay("2"), 2)
        XCTAssertEqual(APIClient.retryAfterDelay("120"), 4)
        XCTAssertEqual(APIClient.retryAfterDelay("-2"), 0)
        XCTAssertNil(APIClient.retryAfterDelay("not-a-date"))
    }

    func testRetryAfterHTTPDateIsBounded() {
        let now = Date(timeIntervalSince1970: 0)
        XCTAssertEqual(APIClient.retryAfterDelay("Thu, 01 Jan 1970 00:00:03 GMT", now: now), 3)
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

final class SwipeBackPolicyTests: XCTestCase {
    func testPopRequiresAPushedScreen() {
        XCTAssertFalse(WofinsSwipeBackPolicy.canPop(controllerCount: 0))
        XCTAssertFalse(WofinsSwipeBackPolicy.canPop(controllerCount: 1))
        XCTAssertTrue(WofinsSwipeBackPolicy.canPop(controllerCount: 2))
    }

    func testDismissFinishesFromDistanceOrFling() {
        XCTAssertTrue(WofinsSwipeBackPolicy.shouldFinishDismiss(translation: 120, velocity: 0))
        XCTAssertTrue(WofinsSwipeBackPolicy.shouldFinishDismiss(translation: 10, velocity: 900))
        XCTAssertFalse(WofinsSwipeBackPolicy.shouldFinishDismiss(translation: 20, velocity: 100))
    }
}
