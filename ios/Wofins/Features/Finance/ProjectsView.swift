import SwiftUI

struct ProjectsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var projects: [FinanceProjectItem] = []
    @State private var meta: FinanceProjectMeta?
    @State private var statusFilter: String? = "processing"
    @State private var isLoading = false
    @State private var errorMessage: String?

    private let filters: [(label: String, value: String?)] = [
        ("Berjalan", "processing"),
        ("Selesai", "done"),
        ("Semua", nil),
    ]

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                ScrollView(.horizontal, showsIndicators: false) {
                    HStack(spacing: 8) {
                        ForEach(filters, id: \.label) { item in
                            Button {
                                statusFilter = item.value
                                Task { await load() }
                            } label: {
                                Text(item.label)
                                    .font(.poppins(.subheadline, weight: .medium))
                                    .padding(.horizontal, 14)
                                    .padding(.vertical, 8)
                                    .background(statusFilter == item.value ? WofinsTheme.accent : WofinsTheme.card)
                                    .foregroundStyle(statusFilter == item.value ? Color.white : WofinsTheme.muted)
                                    .clipShape(Capsule())
                            }
                        }
                    }
                    .padding(.horizontal)
                    .padding(.vertical, 12)
                }

                if let meta {
                    HStack {
                        Text("Net kas: \(MoneyFormat.idr(meta.total_net_cash_flow))")
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(WofinsTheme.accent)
                        Spacer()
                        Text("\(meta.total ?? projects.count) proyek")
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                    .padding(.horizontal)
                    .padding(.bottom, 8)
                }

                Group {
                    if isLoading && projects.isEmpty {
                        ProgressView("Memuat proyek…")
                            .frame(maxWidth: .infinity, maxHeight: .infinity)
                    } else if let errorMessage {
                        Text(errorMessage)
                            .foregroundStyle(WofinsTheme.danger)
                            .padding()
                            .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
                    } else if projects.isEmpty {
                        Text("Belum ada proyek.")
                            .foregroundStyle(WofinsTheme.muted)
                            .frame(maxWidth: .infinity, maxHeight: .infinity)
                    } else {
                        List(projects) { project in
                            NavigationLink {
                                ProjectDetailView(projectId: project.id)
                            } label: {
                                projectRow(project)
                            }
                        }
                        .listStyle(.plain)
                    }
                }
            }
            .background(WofinsTheme.background)
            .navigationTitle("Proyek")
            .refreshable { await load() }
            .task { await load() }
        }
    }

    private func projectRow(_ project: FinanceProjectItem) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack {
                Text(project.displayName)
                    .font(.poppins(.headline, weight: .semibold))
                    .lineLimit(2)
                Spacer()
                Text(project.statusLabel)
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.accent)
            }
            if let number = project.number {
                Text(number)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
            }
            HStack {
                Text("Bayar \(MoneyFormat.idr(project.paid_amount))")
                    .font(.poppins(.caption))
                Spacer()
                Text("Net \(MoneyFormat.idr(project.net_cash_flow))")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle((project.net_cash_flow ?? 0) >= 0 ? WofinsTheme.accent : WofinsTheme.danger)
            }
        }
        .padding(.vertical, 4)
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let response = try await appState.api.financeProjects(status: statusFilter)
            projects = response.data
            meta = response.meta
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct ProjectDetailView: View {
    @EnvironmentObject private var appState: AppState
    let projectId: Int

    @State private var detail: FinanceProjectDetail?
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        Group {
            if isLoading && detail == nil {
                ProgressView("Memuat…")
            } else if let errorMessage {
                Text(errorMessage).foregroundStyle(WofinsTheme.danger).padding()
            } else if let detail {
                List {
                    Section("Ringkasan") {
                        LabeledContent("Grand total", value: MoneyFormat.idr(detail.totals?.grand_total ?? detail.grand_total))
                        LabeledContent("Dibayar", value: MoneyFormat.idr(detail.totals?.paid ?? detail.paid_amount))
                        LabeledContent("Sisa", value: MoneyFormat.idr(detail.totals?.remaining ?? detail.remaining))
                        LabeledContent("Pengeluaran", value: MoneyFormat.idr(detail.totals?.expenses ?? detail.expenses_total))
                        LabeledContent("Net kas", value: MoneyFormat.idr(detail.totals?.net_cash ?? detail.net_cash_flow))
                    }

                    if let payments = detail.payments, !payments.isEmpty {
                        Section("Pembayaran") {
                            ForEach(payments) { item in
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(MoneyFormat.idr(item.amount))
                                        .font(.poppins(.subheadline, weight: .semibold))
                                    Text("\(item.date ?? "-") · \(item.keterangan ?? item.payment_method ?? "-")")
                                        .font(.poppins(.caption))
                                        .foregroundStyle(WofinsTheme.muted)
                                }
                            }
                        }
                    }

                    if let expenses = detail.expenses, !expenses.isEmpty {
                        Section("Pengeluaran") {
                            ForEach(expenses) { item in
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(MoneyFormat.idr(item.amount))
                                        .font(.poppins(.subheadline, weight: .semibold))
                                    Text("\(item.date ?? "-") · \(item.vendor ?? item.note ?? "-")")
                                        .font(.poppins(.caption))
                                        .foregroundStyle(WofinsTheme.muted)
                                }
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle(detail?.displayName ?? "Detail Proyek")
        .navigationBarTitleDisplayMode(.inline)
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financeProject(id: projectId)
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
