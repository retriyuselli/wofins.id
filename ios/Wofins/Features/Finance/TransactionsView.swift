import SwiftUI

struct TransactionsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var tab = "cash"
    @State private var period = FinancePeriodSelection()
    @State private var typeFilter: String?
    @State private var directionFilter: String?
    @State private var items: [FinanceTransactionItem] = []
    @State private var meta: FinanceTxnMeta?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0

    private let typeFilters: [(label: String, type: String?, direction: String?)] = [
        ("Semua", nil, nil),
        ("Masuk", nil, "in"),
        ("Keluar", nil, "out"),
        ("Ops", "operational_expense", nil),
        ("Lain keluar", "other_expense", nil),
    ]

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                Picker("Tab", selection: $tab) {
                    Text("Kas").tag("cash")
                    Text("Piutang").tag("piutang")
                }
                .pickerStyle(.segmented)
                .padding()

                if tab == "cash" {
                    cashContent
                } else {
                    PiutangListView()
                }
            }
            .background(WofinsTheme.background)
            .navigationTitle("Transaksi")
        }
    }

    private var cashContent: some View {
        Group {
            if isLoading && items.isEmpty {
                ProgressView("Memuat transaksi…")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
            } else if let errorMessage {
                Text(errorMessage)
                    .foregroundStyle(WofinsTheme.danger)
                    .padding()
                    .frame(maxWidth: .infinity, maxHeight: .infinity, alignment: .top)
            } else {
                List {
                    Section {
                        FinancePeriodPicker(selection: $period) {
                            reloadToken += 1
                        }
                    }

                    Section {
                        ScrollView(.horizontal, showsIndicators: false) {
                            HStack(spacing: 8) {
                                ForEach(typeFilters, id: \.label) { item in
                                    let selected = typeFilter == item.type && directionFilter == item.direction
                                    Button {
                                        typeFilter = item.type
                                        directionFilter = item.direction
                                        reloadToken += 1
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

                    if let meta {
                        Section {
                            HStack {
                                VStack(alignment: .leading) {
                                    Text("Masuk").font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
                                    Text(MoneyFormat.idr(meta.total_in))
                                        .font(.poppins(.subheadline, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.accent)
                                }
                                Spacer()
                                VStack(alignment: .trailing) {
                                    Text("Keluar").font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
                                    Text(MoneyFormat.idr(meta.total_out))
                                        .font(.poppins(.subheadline, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.danger)
                                }
                            }
                            Text("Net \(MoneyFormat.idr(meta.net))")
                                .font(.poppins(.caption, weight: .medium))
                                .foregroundStyle(WofinsTheme.muted)
                        }
                    }

                    Section("Transaksi") {
                        if items.isEmpty {
                            Text("Belum ada transaksi.").foregroundStyle(WofinsTheme.muted)
                        } else {
                            ForEach(items) { item in
                                HStack(alignment: .top) {
                                    VStack(alignment: .leading, spacing: 4) {
                                        Text(item.typeLabel)
                                            .font(.poppins(.subheadline, weight: .semibold))
                                        Text(item.prospect_name ?? item.description ?? item.vendor_name ?? "-")
                                            .font(.poppins(.caption))
                                            .foregroundStyle(WofinsTheme.muted)
                                            .lineLimit(2)
                                        Text(item.date ?? "-")
                                            .font(.poppins(.caption2))
                                            .foregroundStyle(WofinsTheme.muted)
                                    }
                                    Spacer()
                                    Text((item.isInflow ? "+" : "-") + MoneyFormat.idr(item.amount))
                                        .font(.poppins(.subheadline, weight: .semibold))
                                        .foregroundStyle(item.isInflow ? WofinsTheme.accent : WofinsTheme.danger)
                                }
                                .padding(.vertical, 2)
                            }
                        }
                    }
                }
                .listStyle(.insetGrouped)
                .refreshable { await loadCash() }
            }
        }
        .task(id: reloadToken) { await loadCash() }
    }

    private func loadCash() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let response = try await appState.api.financeTransactions(
                from: period.fromString,
                to: period.toString,
                type: typeFilter,
                direction: directionFilter
            )
            items = response.data
            meta = response.meta
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct PiutangListView: View {
    @EnvironmentObject private var appState: AppState
    @State private var items: [FinancePiutangItem] = []
    @State private var meta: FinancePiutangMeta?
    @State private var statusFilter: String?
    @State private var openOnly = true
    @State private var isLoading = false
    @State private var errorMessage: String?

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
            } else if let errorMessage {
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
                                        Task { await load() }
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
                        }
                    }
                }
                .listStyle(.insetGrouped)
                .refreshable { await load() }
            }
        }
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let response = try await appState.api.financePiutangs(
                status: statusFilter,
                openOnly: openOnly && statusFilter == nil,
                perPage: 50
            )
            items = response.data
            meta = response.meta
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
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
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financePiutang(id: piutangId)
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct ReportsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var mode = "cash"
    @State private var period = FinancePeriodSelection()
    @State private var report: FinanceReportSummary?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0

    var body: some View {
        NavigationStack {
            List {
                Section {
                    FinancePeriodPicker(selection: $period) {
                        reloadToken += 1
                    }
                }

                Section {
                    Picker("Mode", selection: $mode) {
                        Text("Kas").tag("cash")
                        Text("Laba Rugi").tag("profit_loss")
                    }
                    .pickerStyle(.segmented)
                    .onChange(of: mode) { _, _ in
                        reloadToken += 1
                    }
                }

                if isLoading && report == nil {
                    ProgressView("Memuat laporan…")
                } else if let errorMessage {
                    Text(errorMessage).foregroundStyle(WofinsTheme.danger)
                } else if let report {
                    if mode == "cash" {
                        Section("Ringkasan kas") {
                            if let byType = report.by_type {
                                ForEach(byType.keys.sorted(), id: \.self) { key in
                                    LabeledContent(key, value: MoneyFormat.idr(byType[key]))
                                }
                            }
                            LabeledContent("Total masuk", value: MoneyFormat.idr(report.total_in))
                            LabeledContent("Total keluar", value: MoneyFormat.idr(report.total_out))
                            LabeledContent("Net", value: MoneyFormat.idr(report.net))
                        }
                    } else {
                        Section("Laba rugi (event periode)") {
                            LabeledContent("Jumlah proyek", value: "\(report.orders_count ?? 0)")
                            LabeledContent("Nilai order", value: MoneyFormat.idr(report.total_order_value))
                            LabeledContent("Pembayaran", value: MoneyFormat.idr(report.total_payments_on_orders))
                            LabeledContent("Pengeluaran wedding", value: MoneyFormat.idr(report.total_wedding_expenses))
                            LabeledContent("Laba kotor", value: MoneyFormat.idr(report.net_profit))
                            LabeledContent("Ops", value: MoneyFormat.idr(report.operational_expenses))
                            LabeledContent("Lain keluar", value: MoneyFormat.idr(report.other_expenses))
                            LabeledContent("Lain masuk", value: MoneyFormat.idr(report.other_income))
                        }
                    }
                }
            }
            .navigationTitle("Laporan")
            .refreshable { await load() }
            .task(id: reloadToken) { await load() }
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            report = try await appState.api.financeReportSummary(
                from: period.fromString,
                to: period.toString,
                mode: mode
            )
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
