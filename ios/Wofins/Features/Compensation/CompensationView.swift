import SwiftUI

struct CompensationView: View {
    @EnvironmentObject private var appState: AppState
    @State private var data: CompensationData?
    @State private var balances: LeaveBalancesData?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    if isLoading && data == nil {
                        ProgressView("Memuat kompensasi…")
                            .frame(maxWidth: .infinity)
                            .padding()
                    } else if let errorMessage, data == nil {
                        Text(errorMessage).foregroundStyle(WofinsTheme.danger)
                    } else if let data {
                        payrollCard(data)
                        leaveQuotaCard(data)
                        if let balances, !balances.balances.isEmpty {
                            balancesCard(balances)
                        }
                    }
                }
                .padding()
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle("Kompensasi")
            .refreshable { await load() }
            .task { await load() }
        }
    }

    @ViewBuilder
    private func payrollCard(_ data: CompensationData) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Payroll terakhir")
                .font(.poppins(.headline))
            if let payroll = data.payroll {
                Text(payroll.period_name ?? "-")
                    .font(.poppins(.subheadline))
                    .foregroundStyle(WofinsTheme.muted)
                LabeledContent("Gaji bulanan", value: payroll.formatted?.monthly_salary ?? "—")
                LabeledContent("Tahunan", value: payroll.formatted?.annual_salary ?? "—")
                LabeledContent("Bonus", value: payroll.formatted?.bonus ?? "—")
                LabeledContent("Total kompensasi", value: payroll.formatted?.total_compensation ?? "—")
                    .font(.poppins(.body, weight: .semibold))
            } else {
                Text("Belum ada data payroll.")
                    .foregroundStyle(WofinsTheme.muted)
            }
        }
        .padding()
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    @ViewBuilder
    private func leaveQuotaCard(_ data: CompensationData) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Kuota cuti \(data.current_year.map(String.init) ?? "")")
                .font(.poppins(.headline))
            HStack {
                metric("Allowance", "\(data.annual_leave_allowance ?? 0)")
                metric("Terpakai", "\(data.used_leave ?? 0)")
                metric("Sisa", "\(data.remaining_leave ?? 0)")
            }
            if let stats = data.leave_stats {
                Text("Approved \(stats.approved ?? 0) · Pending \(stats.pending ?? 0) · Rejected \(stats.rejected ?? 0)")
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
            }
            if (data.carry_over ?? 0) > 0 {
                Text("Carry over: \(data.carry_over ?? 0) hari")
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.accent)
            }
        }
        .padding()
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    @ViewBuilder
    private func balancesCard(_ balances: LeaveBalancesData) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text("Saldo per tipe")
                .font(.poppins(.headline))
            ForEach(balances.balances) { row in
                HStack {
                    VStack(alignment: .leading) {
                        Text(row.leave_type?.name ?? "Tipe")
                            .font(.poppins(.subheadline, weight: .semibold))
                        Text("Alokasi \(row.allocated_days ?? 0) · Terpakai \(row.used_days ?? 0)")
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                    Spacer()
                    Text("\(row.remaining_days ?? 0)")
                        .font(.poppins(.title3, weight: .bold))
                        .foregroundStyle(WofinsTheme.accent)
                }
                if row.id != balances.balances.last?.id {
                    Divider()
                }
            }
        }
        .padding()
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card)
        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
    }

    private func metric(_ title: String, _ value: String) -> some View {
        VStack(spacing: 4) {
            Text(value).font(.poppins(.title3, weight: .bold))
            Text(title).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 8)
        .background(WofinsTheme.accent.opacity(0.08))
        .clipShape(RoundedRectangle(cornerRadius: 10, style: .continuous))
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            async let compensation = appState.api.compensation()
            async let leaveBalances = appState.api.leaveBalances()
            data = try await compensation
            balances = try await leaveBalances
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
