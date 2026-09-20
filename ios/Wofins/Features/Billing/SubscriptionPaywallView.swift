import SwiftUI
import StoreKit

struct SubscriptionPaywallView: View {
    @EnvironmentObject private var appState: AppState
    @ObservedObject private var store = StoreKitSubscriptionService.shared

    var title: String = "Pilih paket"
    var subtitle: String = "Paket membuka akses dashboard WOFINS untuk perusahaan Anda."
    var showsLogout: Bool = false
    var onFinished: (() -> Void)? = nil

    @State private var purchasingId: String?
    @State private var isRestoring = false
    @State private var notice: String?
    @State private var errorMessage: String?

    private var canPurchase: Bool {
        appState.currentUser?.canManageSubscription == true
    }

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            VStack(alignment: .leading, spacing: 6) {
                Text(title)
                    .font(.poppins(.title3, weight: .bold))
                    .foregroundStyle(WofinsTheme.ink)
                    .fixedSize(horizontal: false, vertical: true)
                Text(subtitle)
                    .font(.poppins(.subheadline))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
            }

            if !canPurchase {
                Text("Pembelian paket hanya tersedia untuk pemilik perusahaan. Setelah paket aktif, tarik layar untuk memperbarui status.")
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
                    .padding(12)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .background(WofinsTheme.primary.opacity(0.06), in: RoundedRectangle(cornerRadius: 14, style: .continuous))
            }

            if store.isLoading && store.products.isEmpty {
                ProgressView("Memuat paket…")
                    .frame(maxWidth: .infinity)
                    .padding(.vertical, 20)
            }

            ForEach(store.products) { item in
                productRow(item)
            }

            if let errorMessage {
                Text(errorMessage)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.danger)
                    .fixedSize(horizontal: false, vertical: true)
            }
            if let notice {
                Text(notice)
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.success)
                    .fixedSize(horizontal: false, vertical: true)
            }
            if let storeError = store.lastError, store.products.isEmpty {
                Text(storeError)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.danger)
                    .fixedSize(horizontal: false, vertical: true)
            }

            if canPurchase {
                Button {
                    Task { await restore() }
                } label: {
                    if isRestoring {
                        ProgressView()
                            .frame(maxWidth: .infinity)
                            .frame(height: 44)
                    } else {
                        Text("Pulihkan pembelian")
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                            .frame(maxWidth: .infinity)
                            .frame(height: 44)
                    }
                }
                .buttonStyle(.plain)
                .disabled(isRestoring || purchasingId != nil)
            }

            if showsLogout {
                Button {
                    Task { await appState.logout() }
                } label: {
                    Text("Keluar dari akun")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.danger)
                        .frame(maxWidth: .infinity)
                        .frame(height: 44)
                }
                .buttonStyle(.plain)
            }
        }
        .task {
            if store.products.isEmpty {
                await store.loadProducts()
            }
        }
    }

    private func productRow(_ item: WofinsStoreProduct) -> some View {
        let busy = purchasingId == item.id
        return Button {
            Task { await buy(item) }
        } label: {
            HStack(alignment: .center, spacing: 12) {
                VStack(alignment: .leading, spacing: 2) {
                    Text("\(item.displayName) · \(item.periodLabel)")
                        .font(.poppins(.subheadline, weight: .bold))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                    Text(item.displayPrice)
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.muted)
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .frame(minWidth: 0)

                if busy {
                    ProgressView()
                } else {
                    Text("Beli")
                        .font(.poppins(.caption, weight: .bold))
                        .foregroundStyle(.white)
                        .padding(.horizontal, 14)
                        .padding(.vertical, 8)
                        .background(canPurchase ? WofinsTheme.primary : WofinsTheme.muted, in: Capsule())
                }
            }
            .padding(14)
            .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 16, style: .continuous)
                    .stroke(WofinsTheme.border.opacity(0.75), lineWidth: 1)
            }
        }
        .buttonStyle(.plain)
        .disabled(!canPurchase || purchasingId != nil || isRestoring)
    }

    private func buy(_ item: WofinsStoreProduct) async {
        errorMessage = nil
        notice = nil
        purchasingId = item.id
        defer { purchasingId = nil }

        do {
            let jws = try await store.purchase(item)
            let response = try await appState.api.verifyAppleSubscription(signedTransaction: jws)
            if let user = response.user {
                appState.currentUser = user
            } else {
                await appState.refreshMe()
            }
            notice = response.message ?? "Langganan aktif."
            onFinished?()
        } catch let error as StoreKitSubscriptionError where error == .userCancelled {
            // no banner
        } catch {
            errorMessage = APILoadFailure.userMessage(for: error) ?? error.localizedDescription
        }
    }

    private func restore() async {
        errorMessage = nil
        notice = nil
        isRestoring = true
        defer { isRestoring = false }

        do {
            try await AppStore.sync()
            let tokens = await store.currentEntitlementJWS()
            guard !tokens.isEmpty else {
                errorMessage = "Tidak ada pembelian Apple yang dapat dipulihkan."
                return
            }
            let response = try await appState.api.restoreAppleSubscriptions(signedTransactions: tokens)
            if let user = response.user {
                appState.currentUser = user
            } else {
                await appState.refreshMe()
            }
            notice = response.message ?? "Pembelian dipulihkan."
            onFinished?()
        } catch {
            errorMessage = APILoadFailure.userMessage(for: error) ?? error.localizedDescription
        }
    }
}
