import SwiftUI

struct OrderOverviewProjectsView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let widgetKey: String
    var fallbackTitle: String = "Daftar Proyek"
    var month: String? = nil

    @State private var projects: [FinanceProjectItem] = []
    @State private var meta: FinanceProjectsClosingMeta?
    @State private var isLoading = false
    @State private var errorMessage: String?

    private var title: String {
        meta?.title ?? fallbackTitle
    }

    private var subtitle: String {
        if let subtitle = meta?.subtitle, !subtitle.isEmpty {
            return subtitle
        }
        return "\(meta?.total ?? projects.count) proyek"
    }

    private var showsDocBadge: Bool {
        widgetKey == "agreement_files" || widgetKey == "contract_docs"
    }

    private var emphasizesExpenses: Bool {
        widgetKey == "customer_expenses"
    }

    private var emphasizesNet: Bool {
        widgetKey == "net_received_processing"
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                LazyVStack(spacing: 12) {
                    if let meta {
                        summaryBar(meta)
                    }
                    if isLoading && projects.isEmpty {
                        ProgressView("Memuat proyek…")
                            .frame(maxWidth: .infinity)
                            .padding(24)
                            .projectSurface()
                    } else if let errorMessage, projects.isEmpty {
                        Text(errorMessage)
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.danger)
                            .padding(16)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .projectSurface()
                    } else if projects.isEmpty {
                        Text("Belum ada data untuk widget ini.")
                            .font(.poppins(.subheadline))
                            .foregroundStyle(WofinsTheme.muted)
                            .padding(16)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .projectSurface()
                    } else {
                        ForEach(projects) { project in
                            NavigationLink {
                                ProjectDetailView(projectId: project.id, preview: project)
                            } label: {
                                projectCard(project)
                            }
                            .buttonStyle(.plain)
                        }
                    }
                }
                .padding(.horizontal, 16)
                .padding(.top, 16)
                .padding(.bottom, 28)
            }
            .refreshable { await load() }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsHidesNavigationBar()
        .task { await load() }
    }

    private var header: some View {
        HStack(spacing: 12) {
            Button { dismiss() } label: {
                Image(systemName: "chevron.left")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Kembali")

            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                    .lineLimit(1)
                    .minimumScaleFactor(0.85)
                Text(subtitle)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(2)
                    .minimumScaleFactor(0.85)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private func summaryBar(_ meta: FinanceProjectsClosingMeta) -> some View {
        HStack(spacing: 10) {
            if emphasizesExpenses {
                summaryChip("Pengeluaran", MoneyFormat.idr(meta.total_expenses))
                summaryChip("Dibayar", MoneyFormat.idr(meta.total_payments))
                summaryChip("Net", MoneyFormat.idr(meta.total_net_cash_flow))
            } else if emphasizesNet {
                summaryChip("Net", MoneyFormat.idr(meta.total_net_cash_flow))
                summaryChip("Dibayar", MoneyFormat.idr(meta.total_payments))
                summaryChip("Keluar", MoneyFormat.idr(meta.total_expenses))
            } else if showsDocBadge {
                summaryChip("Proyek", "\(meta.total ?? projects.count)")
                summaryChip("Upload", "\(meta.uploaded ?? 0)")
                summaryChip("Belum", "\(meta.pending ?? 0)")
            } else {
                summaryChip("Total", MoneyFormat.idr(meta.total_grand_total))
                summaryChip("Dibayar", MoneyFormat.idr(meta.total_payments))
                summaryChip("Net", MoneyFormat.idr(meta.total_net_cash_flow))
            }
        }
    }

    private func summaryChip(_ label: String, _ value: String) -> some View {
        VStack(alignment: .leading, spacing: 2) {
            Text(label)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
            Text(value)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(10)
        .projectSurface()
    }

    private func projectCard(_ project: FinanceProjectItem) -> some View {
        let appearance = projectStatusAppearance(project.status)
        return VStack(alignment: .leading, spacing: 10) {
            HStack(alignment: .top, spacing: 12) {
                VStack(alignment: .leading, spacing: 3) {
                    Text(project.displayName)
                        .font(.poppins(.subheadline, weight: .bold))
                        .foregroundStyle(WofinsTheme.ink)
                        .lineLimit(2)
                        .minimumScaleFactor(0.85)
                    Text(project.number ?? "Proyek #\(project.id)")
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.muted)
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

                VStack(alignment: .trailing, spacing: 4) {
                    Text(appearance.label)
                        .font(.poppins(.caption2, weight: .semibold))
                        .foregroundStyle(appearance.color)
                        .padding(.horizontal, 8)
                        .padding(.vertical, 4)
                        .background(appearance.color.opacity(0.12), in: Capsule())

                    if showsDocBadge {
                        let uploaded = widgetKey == "agreement_files"
                            ? (project.has_agreement == true)
                            : (project.has_contract == true)
                        Text(uploaded ? "Sudah upload" : "Belum upload")
                            .font(.poppins(.caption2, weight: .semibold))
                            .foregroundStyle(uploaded ? WofinsTheme.success : WofinsTheme.danger)
                    }
                }
            }

            HStack(spacing: 12) {
                if let closing = project.closing_date, !closing.isEmpty {
                    Label(closing, systemImage: "calendar")
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                }
                Spacer(minLength: 0)
                if emphasizesExpenses, let expenses = project.expenses_total {
                    Text(MoneyFormat.idr(expenses))
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.danger)
                        .lineLimit(1)
                        .minimumScaleFactor(0.75)
                } else if emphasizesNet, let net = project.net_cash_flow {
                    Text(MoneyFormat.idr(net))
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(net >= 0 ? WofinsTheme.success : WofinsTheme.danger)
                        .lineLimit(1)
                        .minimumScaleFactor(0.75)
                } else if let total = project.grand_total {
                    Text(MoneyFormat.idr(total))
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .lineLimit(1)
                        .minimumScaleFactor(0.75)
                }
            }
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .contentShape(Rectangle())
        .projectSurface()
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let response = try await appState.api.financeProjectsOverviewWidget(key: widgetKey, month: month)
            projects = response.data
            meta = response.meta
            errorMessage = nil
        } catch {
            if let message = APILoadFailure.userMessage(for: error) {
                errorMessage = message
            }
        }
    }
}
