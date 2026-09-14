import SwiftUI

struct PiutangListView: View {
    @EnvironmentObject private var appState: AppState
    @State private var items: [FinancePiutangItem] = []
    @State private var meta: FinancePiutangMeta?
    @State private var statusFilter: String?
    @State private var openOnly = true
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var listPage = 1
    @State private var isLoadingMore = false

    private let filters: [(label: String, value: String?, openOnly: Bool)] = [
        ("Terbuka", nil, true),
        ("Aktif", "aktif", false),
        ("Sebagian", "dibayar_sebagian", false),
        ("Jatuh tempo", "jatuh_tempo", false),
        ("Lunas", "lunas", false),
    ]

    var body: some View {
        Group {
            if isLoading && items.isEmpty {
                ProgressView("Memuat piutang…")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if let errorMessage, items.isEmpty {
                Text(errorMessage)
                    .foregroundStyle(WofinsTheme.danger)
                    .padding()
            } else {
                List {
                    if let meta {
                        Section {
                            Text("Sisa terbuka \(MoneyFormat.idr(meta.open_sisa))")
                                .font(.poppins(.headline, weight: .semibold))
                                .foregroundStyle(WofinsTheme.danger)
                            Text("\(meta.open_count ?? 0) piutang belum lunas · sudah bayar \(MoneyFormat.idr(meta.open_paid))")
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.muted)
                        }
                    }

                    Section {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                ForEach(filters, id: \.label) { item in
                                    let selected = statusFilter == item.value && openOnly == item.openOnly
                                    Button {
                                        statusFilter = item.value
                                        openOnly = item.openOnly
                                        Task { await load(reset: true) }
                                    } label: {
                                        Text(item.label)
                                            .font(.poppins(.caption, weight: .medium))
                                            .padding(.horizontal, 12)
                                            .padding(.vertical, 7)
                                            .background(selected ? WofinsTheme.accent : Color(.systemGray6))
                                            .foregroundStyle(selected ? Color.white : WofinsTheme.muted)
                                            .clipShape(Capsule())
                                    }
                                    .buttonStyle(.plain)
                                }
                            }
                        }
                        .listRowInsets(EdgeInsets(top: 8, leading: 16, bottom: 8, trailing: 16))
                    }

                    Section("Daftar") {
                        if items.isEmpty {
                            Text("Belum ada piutang.").foregroundStyle(WofinsTheme.muted)
                        } else {
                            ForEach(items) { item in
                                NavigationLink {
                                    PiutangDetailView(piutangId: item.id)
                                } label: {
                                    VStack(alignment: .leading, spacing: 4) {
                                        HStack {
                                            Text(item.displayName)
                                                .font(.poppins(.subheadline, weight: .semibold))
                                            Spacer()
                                            if item.is_overdue == true {
                                                Text("Overdue")
                                                    .font(.poppins(.caption2, weight: .bold))
                                                    .foregroundStyle(WofinsTheme.danger)
                                            }
                                        }
                                        Text(item.status_label ?? item.status ?? "-")
                                            .font(.poppins(.caption))
                                            .foregroundStyle(WofinsTheme.muted)
                                        HStack {
                                            Text("Sisa \(MoneyFormat.idr(item.sisa_piutang))")
                                                .font(.poppins(.caption, weight: .semibold))
                                                .foregroundStyle(WofinsTheme.danger)
                                            Spacer()
                                            Text(item.tanggal_jatuh_tempo ?? "-")
                                                .font(.poppins(.caption2))
                                                .foregroundStyle(WofinsTheme.muted)
                                        }
                                    }
                                    .padding(.vertical, 2)
                                }
                            }
                            if canLoadMore {
                                Button {
                                    Task { await load(reset: false) }
                                } label: {
                                    HStack {
                                        if isLoadingMore {
                                            ProgressView()
                                        } else {
                                            Text("Muat lebih banyak")
                                                .font(.poppins(.subheadline, weight: .semibold))
                                        }
                                    }
                                    .foregroundStyle(WofinsTheme.primary)
                                    .frame(maxWidth: .infinity)
                                    .frame(height: 44)
                                }
                                .buttonStyle(.plain)
                                .disabled(isLoadingMore)
                            }
                            if let errorMessage, !items.isEmpty {
                                Text(errorMessage)
                                    .font(.poppins(.caption))
                                    .foregroundStyle(WofinsTheme.danger)
                                    .fixedSize(horizontal: false, vertical: true)
                            }
                        }
                    }
                }
                .listStyle(.insetGrouped)
                .refreshable { await load(reset: true) }
            }
        }
        .task { await load() }
    }

    private var canLoadMore: Bool {
        ListPaging.canLoadMore(current: meta?.current_page, last: meta?.last_page)
    }

    private func load(reset: Bool = true) async {
        if reset {
            isLoading = true
            listPage = 1
        } else {
            guard canLoadMore, !isLoadingMore else { return }
            isLoadingMore = true
            listPage += 1
        }
        defer {
            isLoading = false
            isLoadingMore = false
        }
        do {
            let response = try await appState.api.financePiutangs(
                status: statusFilter,
                openOnly: openOnly && statusFilter == nil,
                perPage: 50,
                page: listPage
            )
            if reset {
                items = response.data
            } else {
                let existing = Set(items.map(\.id))
                items.append(contentsOf: response.data.filter { !existing.contains($0.id) })
            }
            meta = response.meta
            errorMessage = nil
        } catch {
            if !reset { listPage = max(1, listPage - 1) }
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

struct PiutangDetailView: View {
    @EnvironmentObject private var appState: AppState
    let piutangId: Int

    @State private var detail: FinancePiutangDetail?
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
                        LabeledContent("Nomor", value: detail.nomor ?? "-")
                        LabeledContent("Status", value: detail.status_label ?? detail.status ?? "-")
                        LabeledContent("Total", value: MoneyFormat.idr(detail.total_piutang))
                        LabeledContent("Dibayar", value: MoneyFormat.idr(detail.sudah_dibayar))
                        LabeledContent("Sisa", value: MoneyFormat.idr(detail.sisa_piutang))
                        LabeledContent("Jatuh tempo", value: detail.tanggal_jatuh_tempo ?? "-")
                    }

                    if let ket = detail.keterangan, !ket.isEmpty {
                        Section("Keterangan") {
                            Text(ket).font(.poppins(.subheadline))
                        }
                    }

                    if let payments = detail.payments, !payments.isEmpty {
                        Section("Pembayaran") {
                            ForEach(payments) { p in
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(MoneyFormat.idr(p.total ?? p.amount))
                                        .font(.poppins(.subheadline, weight: .semibold))
                                    Text("\(p.date ?? "-") · \(p.payment_method ?? p.nomor ?? "-")")
                                        .font(.poppins(.caption))
                                        .foregroundStyle(WofinsTheme.muted)
                                }
                            }
                        }
                    }
                }
            }
        }
        .navigationTitle(detail?.displayName ?? "Detail Piutang")
        .navigationBarTitleDisplayMode(.inline)
        .wofinsSwipeBack()
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financePiutang(id: piutangId)
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}
