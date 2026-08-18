import SwiftUI

struct CompensationView: View {
    @EnvironmentObject private var appState: AppState
    @State private var data: CompensationData?
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

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            data = try await appState.api.compensation()
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
