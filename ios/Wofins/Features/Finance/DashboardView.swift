import SwiftUI

struct DashboardView: View {
    @EnvironmentObject private var appState: AppState
    @State private var period = FinancePeriodSelection()
    @State private var data: FinanceDashboardData?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    if let user = appState.currentUser {
                        Text("Halo, \(user.name)")
                            .font(.poppins(.title2, weight: .bold))
                    }

                    FinancePeriodPicker(selection: $period) {
                        reloadToken += 1
                    }

                    if isLoading && data == nil {
                        ProgressView("Memuat dashboard…")
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 40)
                    } else if let errorMessage {
                        Text(errorMessage)
                            .foregroundStyle(WofinsTheme.danger)
                    } else if let data {
                        kpiCard(
                            title: "Kas bersih",
                            value: MoneyFormat.idr(data.net_cash),
                            tone: (data.net_cash ?? 0) >= 0 ? WofinsTheme.accent : WofinsTheme.danger
                        )

                        HStack(spacing: 12) {
                            miniKPI(title: "Masuk", value: MoneyFormat.idr(data.inflow?.total), color: WofinsTheme.accent)
                            miniKPI(title: "Keluar", value: MoneyFormat.idr(data.outflow?.total), color: WofinsTheme.danger)
                        }

                        breakdownCard(data)

                        if let cmp = data.comparison {
                            comparisonCard(cmp, currentNet: data.net_cash ?? 0)
                        }
                    }
                }
                .padding()
            }
            .background(WofinsTheme.background)
            .navigationTitle("Dashboard")
            .refreshable { await load() }
            .task(id: reloadToken) { await load() }
        }
    }

    private func kpiCard(title: String, value: String, tone: Color) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
            Text(value)
                .font(.poppins(.title, weight: .bold))
                .foregroundStyle(tone)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
        .shadow(color: .black.opacity(0.04), radius: 8, y: 2)
    }

    private func miniKPI(title: String, value: String, color: Color) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
            Text(value)
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(color)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding()
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    private func breakdownCard(_ data: FinanceDashboardData) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Rincian kas")
                .font(.poppins(.headline))
            row("Pembayaran wedding", data.inflow?.wedding_payments)
            row("Pendapatan lain", data.inflow?.other_income)
            Divider()
            row("Pengeluaran wedding", data.outflow?.wedding_expenses)
            row("Operasional", data.outflow?.operational)
            row("Pengeluaran lain", data.outflow?.other_expenses)
        }
        .padding()
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private func comparisonCard(_ cmp: FinanceComparison, currentNet: Int) -> some View {
        let prev = cmp.previous_net_cash ?? 0
        let delta = currentNet - prev
        return VStack(alignment: .leading, spacing: 8) {
            Text("Vs periode sebelumnya")
                .font(.poppins(.headline))
            Text("Net sebelumnya: \(MoneyFormat.idr(prev))")
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
            Text(delta >= 0 ? "Naik \(MoneyFormat.idr(delta))" : "Turun \(MoneyFormat.idr(abs(delta)))")
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(delta >= 0 ? WofinsTheme.accent : WofinsTheme.danger)
        }
        .padding()
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private func row(_ title: String, _ value: Int?) -> some View {
        HStack {
            Text(title)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
            Spacer()
            Text(MoneyFormat.idr(value))
                .font(.poppins(.subheadline, weight: .medium))
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            data = try await appState.api.financeDashboard(
                from: period.fromString,
                to: period.toString
            )
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
