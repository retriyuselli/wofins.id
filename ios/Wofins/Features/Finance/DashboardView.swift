import SwiftUI

struct DashboardView: View {
    @EnvironmentObject private var appState: AppState
    @State private var period = FinancePeriodSelection()
    @State private var data: FinanceDashboardData?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0

    private var firstName: String {
        appState.currentUser?.name.split(separator: " ").first.map(String.init) ?? "Pengguna"
    }

    private var hasCashMovement: Bool {
        (data?.inflow?.total ?? 0) != 0 || (data?.outflow?.total ?? 0) != 0
    }

    var body: some View {
        VStack(spacing: 0) {
            header

            NavigationStack {
                Group {
                    if appState.allows(.basicFinance) {
                        ScrollView(showsIndicators: false) {
                            LazyVStack(spacing: 16) {
                                periodCard

                                if let errorMessage, data == nil {
                                    errorCard(errorMessage)
                                } else if isLoading && data == nil {
                                    loadingCard
                                } else if let data {
                                    if let errorMessage {
                                        errorCard(errorMessage)
                                    }

                                    balanceCard(data)
                                    cashFlowCards(data)

                                    if !hasCashMovement {
                                        emptyPeriodCard
                                    }

                                    cashBreakdown(data)

                                    ModuleShortcutsView(
                                        keys: ["nota_dinas", "products", "vendors", "piutangs", "payment_methods", "simulasi", "fixed_assets", "documents"],
                                        title: "Modul"
                                    )

                                    if let comparison = data.comparison {
                                        comparisonCard(comparison, currentNet: data.net_cash ?? 0)
                                    }
                                } else {
                                    loadingCard
                                }
                            }
                            .padding(.top, 16)
                            .padding(.bottom, 28)
                            .opacity(isLoading && data != nil ? 0.72 : 1)
                            .animation(.easeInOut(duration: 0.15), value: isLoading)
                        }
                        .refreshable { await refreshDashboard() }
                    } else {
                        PlanLockedView(feature: .basicFinance, title: "Dashboard")
                        Spacer()
                    }
                }
                .background(WofinsTheme.background.ignoresSafeArea())
                .toolbar(.hidden, for: .navigationBar)
                .toolbarBackground(.hidden, for: .navigationBar)
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .task(id: reloadToken) {
            await refreshDashboard()
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) {
                Text(firstName).font(.poppins(.headline, weight: .bold)).foregroundStyle(.white)
                Text(appState.currentUser?.companyDisplayName ?? "Dashboard").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.72)).lineLimit(1)
            }
            Spacer(minLength: 8)
            Button(action: reloadTapped) {
                Image(systemName: "arrow.clockwise")
                    .font(.system(size: 16, weight: .semibold))
                    .foregroundStyle(.white)
                    .modifier(ReloadRotateEffect(isActive: isLoading))
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(isLoading ? 0.22 : 0.11), in: Circle())
            }
            .buttonStyle(.plain)
            .contentShape(Circle())
            .disabled(!appState.allows(.basicFinance))
            .accessibilityLabel("Muat ulang dashboard")
        }
        .padding(.horizontal, 20)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private func reloadTapped() {
        reloadToken += 1
    }

    private var periodCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Periode laporan", systemImage: "calendar")
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)

            FinancePeriodPicker(selection: $period) {
                reloadToken += 1
            }
        }
        .dashboardCard()
        .padding(.horizontal, 16)
    }

    private func balanceCard(_ data: FinanceDashboardData) -> some View {
        let amount = data.net_cash ?? 0

        return VStack(alignment: .leading, spacing: 16) {
            HStack {
                VStack(alignment: .leading, spacing: 6) {
                    Text("KAS BERSIH")
                        .font(.poppins(.caption, weight: .semibold))
                        .tracking(1)
                        .foregroundStyle(.white.opacity(0.72))
                    Text(MoneyFormat.idr(amount))
                        .font(.poppins(size: 29, weight: .bold))
                        .foregroundStyle(.white)
                        .lineLimit(1)
                        .minimumScaleFactor(0.65)
                }
                Spacer(minLength: 12)
                Image(systemName: amount >= 0 ? "chart.line.uptrend.xyaxis" : "chart.line.downtrend.xyaxis")
                    .font(.system(size: 24, weight: .semibold))
                    .foregroundStyle(WofinsTheme.yellow)
                    .frame(width: 52, height: 52)
                    .background(.white.opacity(0.11), in: RoundedRectangle(cornerRadius: 15))
            }

            Divider().overlay(.white.opacity(0.16))

            Text(amount >= 0 ? "Arus kas Anda berada dalam kondisi positif" : "Arus kas perlu mendapatkan perhatian")
                .font(.poppins(.caption))
                .foregroundStyle(.white.opacity(0.78))
        }
        .padding(20)
        .background(
            LinearGradient(
                colors: [WofinsTheme.primary, WofinsTheme.primaryLight],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            ),
            in: RoundedRectangle(cornerRadius: 22, style: .continuous)
        )
        .shadow(color: WofinsTheme.primary.opacity(0.18), radius: 16, y: 7)
        .padding(.horizontal, 16)
    }

    private func cashFlowCards(_ data: FinanceDashboardData) -> some View {
        HStack(spacing: 12) {
            metricCard(
                title: "Uang masuk",
                amount: data.inflow?.total,
                icon: "arrow.down.left",
                color: WofinsTheme.success
            )
            metricCard(
                title: "Uang keluar",
                amount: data.outflow?.total,
                icon: "arrow.up.right",
                color: WofinsTheme.danger
            )
        }
        .padding(.horizontal, 16)
    }

    private func metricCard(title: String, amount: Int?, icon: String, color: Color) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            HStack {
                Image(systemName: icon)
                    .font(.system(size: 15, weight: .bold))
                    .foregroundStyle(color)
                    .frame(width: 36, height: 36)
                    .background(color.opacity(0.10), in: RoundedRectangle(cornerRadius: 11))
                Spacer()
            }
            Text(title)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.62)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .dashboardCard()
    }

    private func cashBreakdown(_ data: FinanceDashboardData) -> some View {
        VStack(alignment: .leading, spacing: 16) {
            sectionTitle("Rincian arus kas", icon: "list.bullet.rectangle")

            cashRow("Pembayaran wedding", value: data.inflow?.wedding_payments, direction: .inflow)
            cashRow("Pendapatan lainnya", value: data.inflow?.other_income, direction: .inflow)
            Divider()
            cashRow("Pengeluaran wedding", value: data.outflow?.wedding_expenses, direction: .outflow)
            cashRow("Biaya operasional", value: data.outflow?.operational, direction: .outflow)
            cashRow("Pengeluaran lainnya", value: data.outflow?.other_expenses, direction: .outflow)
        }
        .dashboardCard()
        .padding(.horizontal, 16)
    }

    private enum CashDirection { case inflow, outflow }

    private func cashRow(_ title: String, value: Int?, direction: CashDirection) -> some View {
        let color = direction == .inflow ? WofinsTheme.success : WofinsTheme.danger
        return HStack(spacing: 12) {
            Circle()
                .fill(color.opacity(0.12))
                .frame(width: 34, height: 34)
                .overlay {
                    Image(systemName: direction == .inflow ? "plus" : "minus")
                        .font(.system(size: 12, weight: .bold))
                        .foregroundStyle(color)
                }
            Text(title)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(2)
                .minimumScaleFactor(0.85)
                .layoutPriority(1)
            Spacer(minLength: 8)
            Text(MoneyFormat.idr(value))
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
                .layoutPriority(0)
        }
    }

    private func comparisonCard(_ comparison: FinanceComparison, currentNet: Int) -> some View {
        let previous = comparison.previous_net_cash ?? 0
        let delta = currentNet - previous
        let isUp = delta >= 0
        let color = isUp ? WofinsTheme.success : WofinsTheme.danger

        return VStack(alignment: .leading, spacing: 15) {
            sectionTitle("Perbandingan periode", icon: "chart.bar.xaxis")

            VStack(alignment: .leading, spacing: 10) {
                previousPeriodBlock(previous: previous, comparison: comparison)
                deltaBadge(delta: delta, isUp: isUp, color: color)
            }
        }
        .dashboardCard()
        .padding(.horizontal, 16)
    }

    private func previousPeriodBlock(previous: Int, comparison: FinanceComparison) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text("Periode sebelumnya")
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
            if let from = comparison.period?.from, let to = comparison.period?.to {
                Text(MoneyFormat.dateRange(from, to))
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
            }
            Text(MoneyFormat.idr(previous))
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
        .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
    }

    private func deltaBadge(delta: Int, isUp: Bool, color: Color) -> some View {
        Label(
            "\(isUp ? "+" : "−")\(MoneyFormat.idr(abs(delta)))",
            systemImage: isUp ? "arrow.up.right" : "arrow.down.right"
        )
        .font(.poppins(.caption, weight: .semibold))
        .foregroundStyle(color)
        .padding(.horizontal, 10)
        .padding(.vertical, 7)
        .background(color.opacity(0.10), in: Capsule())
        .fixedSize(horizontal: true, vertical: false)
    }

    private func sectionTitle(_ title: String, icon: String) -> some View {
        HStack(spacing: 9) {
            Image(systemName: icon)
                .foregroundStyle(WofinsTheme.primary)
            Text(title)
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
        }
    }

    private var emptyPeriodCard: some View {
        VStack(alignment: .leading, spacing: 8) {
            Label("Belum ada transaksi", systemImage: "tray")
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
            Text("Tidak ada uang masuk atau keluar pada \(period.displayLabel) untuk \(appState.currentUser?.companyDisplayName ?? "company Anda").")
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .dashboardCard()
        .padding(.horizontal, 16)
    }

    private var loadingCard: some View {
        HStack(spacing: 12) {
            ProgressView().tint(WofinsTheme.primary)
            Text("Memuat ringkasan keuangan…")
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .dashboardCard()
        .padding(.horizontal, 16)
    }

    private func errorCard(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Dashboard belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(WofinsTheme.danger)
            Text(message)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            Button("Coba lagi") { reloadToken += 1 }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(.white)
                .padding(.horizontal, 16)
                .padding(.vertical, 10)
                .background(WofinsTheme.primary, in: Capsule())
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .dashboardCard()
        .padding(.horizontal, 16)
    }

    private func refreshDashboard() async {
        guard appState.allows(.basicFinance) else { return }
        let requestID = reloadToken
        isLoading = true
        defer {
            if requestID == reloadToken {
                isLoading = false
            }
        }

        async let profile: Void = appState.refreshMe()
        do {
            data = try await appState.api.financeDashboard(
                from: period.fromString,
                to: period.toString
            )
            guard requestID == reloadToken else { return }
            errorMessage = nil
        } catch {
            guard requestID == reloadToken else { return }
            if let message = APILoadFailure.userMessage(for: error) {
                errorMessage = message
            }
        }
        if !Task.isCancelled {
            await profile
        }
    }
}

private struct ReloadRotateEffect: ViewModifier {
    let isActive: Bool

    func body(content: Content) -> some View {
        if #available(iOS 18.0, *) {
            content.symbolEffect(.rotate, options: .repeating, isActive: isActive)
        } else {
            content
                .rotationEffect(.degrees(isActive ? 360 : 0))
                .animation(isActive ? .linear(duration: 0.9).repeatForever(autoreverses: false) : .default, value: isActive)
        }
    }
}

private extension View {
    func dashboardCard() -> some View {
        padding(16)
            .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 18, style: .continuous)
                    .stroke(WofinsTheme.border.opacity(0.75), lineWidth: 1)
            }
            .shadow(color: WofinsTheme.primary.opacity(0.055), radius: 12, y: 5)
    }
}
