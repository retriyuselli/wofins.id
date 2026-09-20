import Foundation
import StoreKit

enum StoreKitSubscriptionError: LocalizedError, Equatable {
    case productUnavailable
    case verificationFailed
    case userCancelled
    case pending
    case message(String)

    var errorDescription: String? {
        switch self {
        case .productUnavailable:
            return "Produk langganan belum tersedia. Coba lagi nanti."
        case .verificationFailed:
            return "Transaksi Apple tidak dapat diverifikasi."
        case .userCancelled:
            return nil
        case .pending:
            return "Pembelian menunggu persetujuan."
        case .message(let text):
            return text
        }
    }
}

struct WofinsStoreProduct: Identifiable, Hashable {
    let id: String
    let planKey: String
    let billing: String
    let displayName: String
    let periodLabel: String
    let displayPrice: String
    let product: Product

    func hash(into hasher: inout Hasher) {
        hasher.combine(id)
    }

    static func == (lhs: WofinsStoreProduct, rhs: WofinsStoreProduct) -> Bool {
        lhs.id == rhs.id
    }

    var sortRank: Int {
        let planRank: Int = switch planKey {
        case "starter": 0
        case "professional": 1
        case "business": 2
        default: 9
        }
        let billingRank = billing == "monthly" ? 0 : 1
        return planRank * 10 + billingRank
    }
}

@MainActor
final class StoreKitSubscriptionService: ObservableObject {
    static let shared = StoreKitSubscriptionService()

    static let productIDs: [String] = [
        "wofins.starter.monthly",
        "wofins.starter.yearly",
        "wofins.professional.monthly",
        "wofins.professional.yearly",
        "wofins.business.monthly",
        "wofins.business.yearly",
    ]

    @Published private(set) var products: [WofinsStoreProduct] = []
    @Published private(set) var isLoading = false
    @Published var lastError: String?

    private var updatesTask: Task<Void, Never>?

    private init() {
        updatesTask = Task { await listenForTransactions() }
    }

    func loadProducts() async {
        isLoading = true
        lastError = nil
        defer { isLoading = false }

        do {
            let storeProducts = try await Product.products(for: Self.productIDs)
            let mapped = storeProducts.compactMap { product -> WofinsStoreProduct? in
                guard let meta = Self.meta(for: product.id) else { return nil }
                return WofinsStoreProduct(
                    id: product.id,
                    planKey: meta.plan,
                    billing: meta.billing,
                    displayName: meta.label,
                    periodLabel: meta.period,
                    displayPrice: product.displayPrice,
                    product: product
                )
            }
            products = mapped.sorted { $0.sortRank < $1.sortRank }
            if products.isEmpty {
                lastError = "Katalog langganan belum siap di App Store Connect / StoreKit config."
            }
        } catch {
            lastError = error.localizedDescription
        }
    }

    /// Returns JWS signed transaction for server verify.
    func purchase(_ item: WofinsStoreProduct) async throws -> String {
        let result = try await item.product.purchase()
        switch result {
        case .success(let verification):
            let transaction = try checkVerified(verification)
            let jws = verification.jwsRepresentation
            await transaction.finish()
            return jws
        case .userCancelled:
            throw StoreKitSubscriptionError.userCancelled
        case .pending:
            throw StoreKitSubscriptionError.pending
        @unknown default:
            throw StoreKitSubscriptionError.message("Hasil pembelian tidak dikenali.")
        }
    }

    /// Current entitlements as JWS strings for restore.
    func currentEntitlementJWS() async -> [String] {
        var tokens: [String] = []
        for await result in Transaction.currentEntitlements {
            guard Self.productIDs.contains(checkVerifiedProductID(result) ?? "") else { continue }
            tokens.append(result.jwsRepresentation)
        }
        return tokens
    }

    private func checkVerifiedProductID(_ result: VerificationResult<Transaction>) -> String? {
        switch result {
        case .verified(let transaction):
            return transaction.productID
        case .unverified:
            return nil
        }
    }

    private func listenForTransactions() async {
        for await result in Transaction.updates {
            if case .verified(let transaction) = result {
                await transaction.finish()
            }
        }
    }

    private func checkVerified<T>(_ result: VerificationResult<T>) throws -> T {
        switch result {
        case .unverified:
            throw StoreKitSubscriptionError.verificationFailed
        case .verified(let safe):
            return safe
        }
    }

    private static func meta(for productId: String) -> (plan: String, billing: String, label: String, period: String)? {
        switch productId {
        case "wofins.starter.monthly":
            return ("starter", "monthly", "Starter", "Bulanan")
        case "wofins.starter.yearly":
            return ("starter", "annual", "Starter", "Tahunan")
        case "wofins.professional.monthly":
            return ("professional", "monthly", "Professional", "Bulanan")
        case "wofins.professional.yearly":
            return ("professional", "annual", "Professional", "Tahunan")
        case "wofins.business.monthly":
            return ("business", "monthly", "Business", "Bulanan")
        case "wofins.business.yearly":
            return ("business", "annual", "Business", "Tahunan")
        default:
            return nil
        }
    }
}
