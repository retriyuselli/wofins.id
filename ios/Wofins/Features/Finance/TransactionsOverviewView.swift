import SwiftUI
import UIKit

enum TransactionChipFilter: String, CaseIterable, Identifiable, Hashable {
    case all = "Semua"
    case income = "Masuk"
    case expense = "Keluar"
    case wedding = "Wedding"
    case operational = "Operasional"

    var id: String { rawValue }
    var title: String { rawValue }

    var emptyTitle: String {
        switch self {
        case .all: return "Belum ada transaksi"
        case .income: return "Belum ada uang masuk"
        case .expense: return "Belum ada uang keluar"
        case .wedding: return "Belum ada transaksi wedding"
        case .operational: return "Belum ada biaya operasional"
        }
    }

    var emptySubtitle: String {
        switch self {
        case .all: return "Transaksi pada periode ini akan muncul di sini."
        case .income: return "Pembayaran wedding dan pendapatan lain akan tampil di halaman ini."
        case .expense: return "Pengeluaran wedding, operasional, dan lainnya akan tampil di halaman ini."
        case .wedding: return "Pembayaran dan pengeluaran proyek wedding akan tampil di halaman ini."
        case .operational: return "Biaya operasional pada periode ini akan tampil di halaman ini."
        }
    }

    var apiType: String? {
        self == .operational ? "operational_expense" : nil
    }

    var direction: String? {
        switch self {
        case .income: return "in"
        case .expense: return "out"
        default: return nil
        }
    }

    func matches(_ item: FinanceTransactionItem) -> Bool {
        switch self {
        case .all:
            return true
        case .income:
            return item.direction == "in"
        case .expense:
            return item.direction == "out"
        case .wedding:
            return item.type == "wedding_payment" || item.type == "wedding_expense"
        case .operational:
            return item.type == "operational_expense"
        }
    }
}

struct TransactionsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var period = FinancePeriodSelection()
    @State private var searchText = ""
    @State private var items: [FinanceTransactionItem] = []
    @State private var meta: FinanceTxnMeta?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0
    @State private var page = 0
    @State private var catalog: [MobileModuleCatalogItem] = []
    @State private var createItem: MobileModuleCatalogItem?
    @State private var showAddSheet = false

    private var visibleItems: [FinanceTransactionItem] {
        let keyword = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !keyword.isEmpty else { return items }
        return items.filter {
            $0.transactionTitle.localizedCaseInsensitiveContains(keyword)
                || $0.typeLabel.localizedCaseInsensitiveContains(keyword)
                || ($0.payment_method?.localizedCaseInsensitiveContains(keyword) ?? false)
        }
    }

    private var pagedItems: [FinanceTransactionItem] {
        TransactionPaging.slice(visibleItems, page: page)
    }

    private var groups: [(String, [FinanceTransactionItem])] {
        TransactionPaging.groups(pagedItems)
    }

    private var addableItems: [MobileModuleCatalogItem] {
        let keys = ["expenses", "expense_ops", "pendapatan_lains", "pengeluaran_lains", "piutangs"]
        return catalog.filter { keys.contains($0.key) && $0.canCreate }
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                header

                if appState.allows(.basicFinance) {
                    ScrollViewReader { proxy in
                        ScrollView(showsIndicators: false) {
                            LazyVStack(spacing: 16) {
                                periodMenu
                                balanceCard
                                searchBar
                                filters
                                ModuleShortcutsView(
                                    keys: ["piutangs", "payment_methods", "expenses", "expense_ops", "pendapatan_lains", "pengeluaran_lains"],
                                    title: "Kas & piutang"
                                )
                                Color.clear.frame(height: 0).id("transaction-list")
                                content
                            }
                            .padding(.top, 16)
                            .padding(.bottom, 28)
                        }
                        .refreshable { page = 0; await load() }
                        .scrollDismissesKeyboard(.immediately)
                        .onChange(of: page) { _, _ in
                            withAnimation(.easeInOut(duration: 0.2)) {
                                proxy.scrollTo("transaction-list", anchor: .top)
                            }
                        }
                    }
                } else {
                    PlanLockedView(feature: .basicFinance, title: "Transaksi")
                    Spacer()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .wofinsHidesNavigationBar()
            .task(id: reloadToken) {
                guard appState.allows(.basicFinance) else { return }
                page = 0
                await load()
            }
            .onChange(of: searchText) { _, _ in page = 0 }
            .onChange(of: visibleItems.count) { _, count in
                page = TransactionPaging.clamped(page: page, total: count)
            }
            .overlay {
                addTransactionOverlay
            }
            .sheet(item: $createItem) { item in
                ModuleCreateView(item: item) {
                    page = 0
                    Task { await load() }
                }
                .environmentObject(appState)
            }
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) { Text("Transaksi").font(.poppins(.headline, weight: .bold)).foregroundStyle(.white); Text(appState.currentUser?.companyDisplayName ?? "Arus kas bisnis").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.72)).lineLimit(1) }
            Spacer()
            Button { showAddSheet = true } label: { Image(systemName: "plus").font(.system(size: 17, weight: .bold)).foregroundStyle(.white).frame(width: 40, height: 40).background(.white.opacity(0.11), in: Circle()) }
                .accessibilityLabel("Tambah transaksi")
        }
        .padding(.horizontal, 20).padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    @ViewBuilder
    private var addTransactionOverlay: some View {
        ZStack {
            if showAddSheet {
                Color.black.opacity(0.28)
                    .ignoresSafeArea()
                    .onTapGesture { showAddSheet = false }
                    .transition(.opacity)

                VStack(spacing: 14) {
                    VStack(alignment: .leading, spacing: 6) {
                        Text("Tambah Transaksi")
                            .font(.poppins(.headline, weight: .bold))
                            .foregroundStyle(WofinsTheme.ink)
                        Text("Pilih jenis transaksi. Data tersimpan hanya untuk company Anda.")
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                            .fixedSize(horizontal: false, vertical: true)
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)

                    VStack(spacing: 10) {
                        ForEach(addableItems) { item in
                            Button {
                                showAddSheet = false
                                createItem = item
                            } label: {
                                Text(item.title)
                                    .font(.poppins(.subheadline, weight: .semibold))
                                    .foregroundStyle(.white)
                                    .lineLimit(1)
                                    .minimumScaleFactor(0.85)
                                    .frame(maxWidth: .infinity)
                                    .padding(.vertical, 13)
                                    .padding(.horizontal, 12)
                                    .background(WofinsTheme.primary, in: Capsule())
                            }
                            .buttonStyle(.plain)
                        }
                    }

                    Button("Batal") { showAddSheet = false }
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 10)
                        .padding(.top, 2)
                }
                .padding(20)
                .frame(maxWidth: 360)
                .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 24, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 24, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
                .shadow(color: Color.black.opacity(0.16), radius: 28, y: 12)
                .padding(.horizontal, 20)
                .padding(.vertical, 16)
                .transition(.scale(scale: 0.94).combined(with: .opacity))
                .accessibilityAddTraits(.isModal)
            }
        }
        .allowsHitTesting(showAddSheet)
        .animation(.easeInOut(duration: 0.22), value: showAddSheet)
    }

    private var periodMenu: some View {
        Menu {
            Button("Bulan ini") { setPeriod(.thisMonth) }
            Button("Bulan lalu") { setPeriod(.lastMonth) }
            Button("Tahun ini") { setPeriod(.thisYear) }
        } label: {
            HStack(spacing: 13) {
                Image(systemName: "calendar").foregroundStyle(WofinsTheme.primary).font(.system(size: 18, weight: .semibold))
                    .frame(width: 40, height: 40).background(WofinsTheme.primary.opacity(0.09), in: RoundedRectangle(cornerRadius: 11))
                VStack(alignment: .leading, spacing: 2) {
                    Text("Periode").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    Text(period.preset.label).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.ink)
                }
                Spacer()
                Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
            }
            .padding(13).transactionSurface()
        }
        .buttonStyle(.plain).padding(.horizontal, 16)
    }

    private var balanceCard: some View {
        VStack(alignment: .leading, spacing: 17) {
            HStack(spacing: 13) {
                Image(systemName: "banknote.fill").font(.system(size: 21, weight: .semibold)).foregroundStyle(WofinsTheme.yellow)
                    .frame(width: 48, height: 48).background(.white.opacity(0.10), in: Circle())
                VStack(alignment: .leading, spacing: 3) {
                    Text("Saldo Bersih").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.74))
                    Text(MoneyFormat.idr(meta?.net)).font(.poppins(size: 27, weight: .bold)).foregroundStyle(.white)
                        .lineLimit(1).minimumScaleFactor(0.65)
                }
            }
            Divider().overlay(.white.opacity(0.16))
            HStack(spacing: 14) {
                balanceMetric("Uang Masuk", meta?.total_in, "arrow.down.left", WofinsTheme.success)
                Divider().frame(height: 42).overlay(.white.opacity(0.17))
                balanceMetric("Uang Keluar", meta?.total_out, "arrow.up.right", WofinsTheme.danger)
            }
        }
        .padding(19)
        .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 22, style: .continuous))
        .wofinsHeroShadow()
        .padding(.horizontal, 16)
    }

    private func balanceMetric(_ title: String, _ value: Int?, _ icon: String, _ color: Color) -> some View {
        HStack(spacing: 8) {
            Image(systemName: icon).font(.system(size: 12, weight: .bold)).foregroundStyle(color)
                .frame(width: 29, height: 29).background(.white.opacity(0.95), in: Circle())
            VStack(alignment: .leading, spacing: 2) {
                Text(title).font(.poppins(.caption2)).foregroundStyle(.white.opacity(0.72))
                Text(MoneyFormat.idr(value)).font(.poppins(.caption, weight: .bold)).foregroundStyle(.white)
                    .lineLimit(1).minimumScaleFactor(0.58)
            }
        }.frame(maxWidth: .infinity, alignment: .leading)
    }

    private var searchBar: some View {
        HStack(spacing: 12) {
            Image(systemName: "magnifyingglass").font(.system(size: 19, weight: .medium)).foregroundStyle(WofinsTheme.muted)
            TextField("Cari transaksi", text: $searchText).font(.poppins(.subheadline)).autocorrectionDisabled()
            if !searchText.isEmpty {
                Button { searchText = "" } label: { Image(systemName: "xmark.circle.fill").foregroundStyle(WofinsTheme.muted) }
            }
        }
        .padding(.horizontal, 16).frame(height: 54).transactionSurface().padding(.horizontal, 16)
    }

    private var filters: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 9) {
                ForEach(TransactionChipFilter.allCases) { item in
                    if item == .all {
                        chipLabel(item, selected: true)
                    } else {
                        NavigationLink {
                            TransactionCategoryView(filter: item, period: period)
                        } label: {
                            chipLabel(item, selected: false)
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
            .padding(.horizontal, 16)
        }
    }

    private func chipLabel(_ item: TransactionChipFilter, selected: Bool) -> some View {
        Text(item.title)
            .font(.poppins(.caption, weight: .semibold))
            .foregroundStyle(selected ? .white : WofinsTheme.primary)
            .padding(.horizontal, 16)
            .frame(height: 39)
            .background(selected ? WofinsTheme.primary : WofinsTheme.card, in: Capsule())
            .overlay {
                Capsule().stroke(selected ? Color.clear : WofinsTheme.border, lineWidth: 1)
            }
            .contentShape(Capsule())
    }

    @ViewBuilder private var content: some View {
        if isLoading && items.isEmpty {
            HStack { ProgressView(); Text("Memuat transaksi…").font(.poppins(.subheadline)).foregroundStyle(WofinsTheme.muted) }
                .frame(maxWidth: .infinity, alignment: .leading).padding(18).transactionSurface().padding(.horizontal, 16)
        } else if let errorMessage {
            VStack(alignment: .leading, spacing: 12) {
                Label("Transaksi belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                    .font(.poppins(.headline, weight: .semibold)).foregroundStyle(WofinsTheme.danger)
                Text(errorMessage).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
                Button("Coba lagi") { reloadToken += 1 }.foregroundStyle(.white).padding(.horizontal, 15).padding(.vertical, 9)
                    .background(WofinsTheme.primary, in: Capsule())
            }.frame(maxWidth: .infinity, alignment: .leading).padding(18).transactionSurface().padding(.horizontal, 16)
        } else if visibleItems.isEmpty {
            VStack(spacing: 10) {
                Image(systemName: "arrow.left.arrow.right.circle").font(.system(size: 34)).foregroundStyle(WofinsTheme.primary)
                Text("Belum ada transaksi").font(.poppins(.headline, weight: .bold)).foregroundStyle(WofinsTheme.ink)
                Text("Transaksi pada periode ini akan muncul di sini.").font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
            }.frame(maxWidth: .infinity).padding(.vertical, 32).transactionSurface().padding(.horizontal, 16)
        } else {
            ForEach(groups, id: \.0) { date, rows in
                VStack(alignment: .leading, spacing: 9) {
                    Text(sectionTitle(date)).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.primary)
                    VStack(spacing: 0) {
                        ForEach(Array(rows.enumerated()), id: \.element.id) { index, item in
                            NavigationLink {
                                TransactionDetailView(item: item)
                            } label: {
                                transactionRow(item)
                            }
                            .buttonStyle(.plain)
                            if index < rows.count - 1 { Divider().padding(.leading, 58) }
                        }
                    }.transactionSurface()
                }.padding(.horizontal, 16)
            }
            TransactionPageBar(page: $page, total: visibleItems.count)
        }
    }

    private func transactionRow(_ item: FinanceTransactionItem) -> some View {
        let color = item.isInflow ? WofinsTheme.success : WofinsTheme.danger
        return HStack(spacing: 12) {
            Image(systemName: item.isInflow ? "arrow.down.left" : "arrow.up.right").font(.system(size: 15, weight: .bold))
                .foregroundStyle(color).frame(width: 42, height: 42).background(color.opacity(0.11), in: Circle())
            VStack(alignment: .leading, spacing: 3) {
                Text(item.transactionTitle).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.ink).lineLimit(1)
                Text(item.typeLabel).font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
            }
            Spacer(minLength: 5)
            VStack(alignment: .trailing, spacing: 3) {
                Text("\(item.isInflow ? "+ " : "− ")\(MoneyFormat.idr(item.amount))")
                    .font(.poppins(.caption, weight: .bold)).foregroundStyle(color).lineLimit(1).minimumScaleFactor(0.68)
                Text(item.payment_method ?? "-").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted).lineLimit(1)
            }
            Image(systemName: "chevron.right")
                .font(.system(size: 12, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
        }.padding(.horizontal, 14).padding(.vertical, 13).contentShape(Rectangle())
    }

    private func setPeriod(_ preset: FinancePeriodPreset) { period.preset = preset; reloadToken += 1 }

    private func sectionTitle(_ value: String) -> String {
        guard let date = TransactionDates.api.date(from: value) else { return value.isEmpty ? "Tanpa tanggal" : value }
        if Calendar.current.isDateInToday(date) { return "Hari ini, \(TransactionDates.display.string(from: date))" }
        if Calendar.current.isDateInYesterday(date) { return "Kemarin, \(TransactionDates.display.string(from: date))" }
        return TransactionDates.display.string(from: date)
    }

    private func load() async {
        isLoading = true; defer { isLoading = false }
        do {
            let response = try await appState.api.financeTransactions(from: period.fromString, to: period.toString,
                type: nil, direction: nil, limit: 200)
            items = response.data; meta = response.meta; errorMessage = nil
        } catch {
            if let message = APILoadFailure.userMessage(for: error) {
                errorMessage = message
            }
        }
        if catalog.isEmpty {
            catalog = (try? await appState.api.moduleCatalog()) ?? []
        }
    }
}

struct TransactionCategoryView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let filter: TransactionChipFilter

    @State private var period: FinancePeriodSelection
    @State private var searchText = ""
    @State private var items: [FinanceTransactionItem] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0
    @State private var page = 0

    init(filter: TransactionChipFilter, period: FinancePeriodSelection) {
        self.filter = filter
        _period = State(initialValue: period)
    }

    private var visibleItems: [FinanceTransactionItem] {
        let keyword = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        return items.filter { item in
            guard filter.matches(item) else { return false }
            guard !keyword.isEmpty else { return true }
            return item.transactionTitle.localizedCaseInsensitiveContains(keyword)
                || item.typeLabel.localizedCaseInsensitiveContains(keyword)
                || (item.payment_method?.localizedCaseInsensitiveContains(keyword) ?? false)
        }
    }

    private var pagedItems: [FinanceTransactionItem] {
        TransactionPaging.slice(visibleItems, page: page)
    }

    private var groups: [(String, [FinanceTransactionItem])] {
        TransactionPaging.groups(pagedItems)
    }

    private var totalAmount: Int {
        visibleItems.reduce(0) { $0 + abs($1.amount ?? 0) }
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollViewReader { proxy in
                ScrollView(showsIndicators: false) {
                    LazyVStack(spacing: 16) {
                        periodMenu
                        if totalAmount != 0 {
                            summaryCard
                        }
                        searchBar
                        Color.clear.frame(height: 0).id("transaction-list")
                        content
                    }
                    .padding(.top, 16)
                    .padding(.bottom, 28)
                }
                .refreshable { page = 0; await load() }
                .scrollDismissesKeyboard(.immediately)
                .onChange(of: page) { _, _ in
                    withAnimation(.easeInOut(duration: 0.2)) {
                        proxy.scrollTo("transaction-list", anchor: .top)
                    }
                }
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsHidesNavigationBar()
        .task(id: reloadToken) { page = 0; await load() }
        .onChange(of: searchText) { _, _ in page = 0 }
        .onChange(of: visibleItems.count) { _, count in
            page = TransactionPaging.clamped(page: page, total: count)
        }
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
                Text(filter.title)
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                Text(period.preset.label)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var periodMenu: some View {
        Menu {
            Button("Bulan ini") { setPeriod(.thisMonth) }
            Button("Bulan lalu") { setPeriod(.lastMonth) }
            Button("Tahun ini") { setPeriod(.thisYear) }
        } label: {
            HStack(spacing: 13) {
                Image(systemName: "calendar").foregroundStyle(WofinsTheme.primary).font(.system(size: 18, weight: .semibold))
                    .frame(width: 40, height: 40).background(WofinsTheme.primary.opacity(0.09), in: RoundedRectangle(cornerRadius: 11))
                VStack(alignment: .leading, spacing: 2) {
                    Text("Periode").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    Text(period.preset.label).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.ink)
                }
                Spacer()
                Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
            }
            .padding(13).transactionSurface()
        }
        .buttonStyle(.plain).padding(.horizontal, 16)
    }

    private var summaryCard: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text("Total \(filter.title)")
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
            Text(MoneyFormat.idr(totalAmount))
                .font(.poppins(.title3, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(17)
        .transactionSurface()
        .padding(.horizontal, 16)
    }

    private var searchBar: some View {
        HStack(spacing: 12) {
            Image(systemName: "magnifyingglass").font(.system(size: 19, weight: .medium)).foregroundStyle(WofinsTheme.muted)
            TextField("Cari transaksi", text: $searchText).font(.poppins(.subheadline)).autocorrectionDisabled()
            if !searchText.isEmpty {
                Button { searchText = "" } label: { Image(systemName: "xmark.circle.fill").foregroundStyle(WofinsTheme.muted) }
            }
        }
        .padding(.horizontal, 16).frame(height: 54).transactionSurface().padding(.horizontal, 16)
    }

    @ViewBuilder private var content: some View {
        if isLoading && items.isEmpty {
            HStack { ProgressView(); Text("Memuat transaksi…").font(.poppins(.subheadline)).foregroundStyle(WofinsTheme.muted) }
                .frame(maxWidth: .infinity, alignment: .leading).padding(18).transactionSurface().padding(.horizontal, 16)
        } else if let errorMessage {
            VStack(alignment: .leading, spacing: 12) {
                Label("Transaksi belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                    .font(.poppins(.headline, weight: .semibold)).foregroundStyle(WofinsTheme.danger)
                Text(errorMessage).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
                Button("Coba lagi") { reloadToken += 1 }.foregroundStyle(.white).padding(.horizontal, 15).padding(.vertical, 9)
                    .background(WofinsTheme.primary, in: Capsule())
            }.frame(maxWidth: .infinity, alignment: .leading).padding(18).transactionSurface().padding(.horizontal, 16)
        } else if visibleItems.isEmpty {
            VStack(spacing: 10) {
                Image(systemName: "arrow.left.arrow.right.circle").font(.system(size: 34)).foregroundStyle(WofinsTheme.primary)
                Text(filter.emptyTitle).font(.poppins(.headline, weight: .bold)).foregroundStyle(WofinsTheme.ink)
                Text(filter.emptySubtitle).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
                    .multilineTextAlignment(.center)
                    .padding(.horizontal, 12)
            }.frame(maxWidth: .infinity).padding(.vertical, 32).transactionSurface().padding(.horizontal, 16)
        } else {
            ForEach(groups, id: \.0) { date, rows in
                VStack(alignment: .leading, spacing: 9) {
                    Text(sectionTitle(date)).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.primary)
                    VStack(spacing: 0) {
                        ForEach(Array(rows.enumerated()), id: \.element.id) { index, item in
                            NavigationLink {
                                TransactionDetailView(item: item)
                            } label: {
                                categoryRow(item)
                            }
                            .buttonStyle(.plain)
                            if index < rows.count - 1 { Divider().padding(.leading, 58) }
                        }
                    }.transactionSurface()
                }.padding(.horizontal, 16)
            }
            TransactionPageBar(page: $page, total: visibleItems.count)
        }
    }

    private func categoryRow(_ item: FinanceTransactionItem) -> some View {
        let color = item.isInflow ? WofinsTheme.success : WofinsTheme.danger
        return HStack(spacing: 12) {
            Image(systemName: item.isInflow ? "arrow.down.left" : "arrow.up.right").font(.system(size: 15, weight: .bold))
                .foregroundStyle(color).frame(width: 42, height: 42).background(color.opacity(0.11), in: Circle())
            VStack(alignment: .leading, spacing: 3) {
                Text(item.transactionTitle).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.ink).lineLimit(1)
                Text(item.typeLabel).font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            VStack(alignment: .trailing, spacing: 3) {
                Text("\(item.isInflow ? "+ " : "− ")\(MoneyFormat.idr(item.amount))")
                    .font(.poppins(.caption, weight: .bold)).foregroundStyle(color).lineLimit(1).minimumScaleFactor(0.68)
                if let method = item.payment_method, !method.isEmpty {
                    Text(method).font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted).lineLimit(1)
                }
            }
            Image(systemName: "chevron.right")
                .font(.system(size: 12, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
        }
        .padding(.horizontal, 14)
        .padding(.vertical, 13)
        .contentShape(Rectangle())
    }

    private func setPeriod(_ preset: FinancePeriodPreset) { period.preset = preset; reloadToken += 1 }

    private func sectionTitle(_ value: String) -> String {
        guard let date = TransactionDates.api.date(from: value) else { return value.isEmpty ? "Tanpa tanggal" : value }
        if Calendar.current.isDateInToday(date) { return "Hari ini, \(TransactionDates.display.string(from: date))" }
        if Calendar.current.isDateInYesterday(date) { return "Kemarin, \(TransactionDates.display.string(from: date))" }
        return TransactionDates.display.string(from: date)
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let response = try await appState.api.financeTransactions(
                from: period.fromString,
                to: period.toString,
                type: filter.apiType,
                direction: filter.direction,
                limit: 200
            )
            items = response.data
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

struct TransactionDetailView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let item: FinanceTransactionItem

    private var color: Color { item.isInflow ? WofinsTheme.success : WofinsTheme.danger }
    private var showsPaymentProof: Bool {
        item.type == "wedding_payment"
            && item.proof_url != nil
            && (item.source_id ?? 0) != 0
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                LazyVStack(spacing: 16) {
                    amountCard
                    detailsCard
                    if showsPaymentProof, let paymentId = item.source_id {
                        NavigationLink {
                            PaymentProofView(paymentId: paymentId, proofURL: item.proof_url)
                        } label: {
                            HStack(spacing: 12) {
                                Image(systemName: "photo.fill")
                                    .foregroundStyle(WofinsTheme.primary)
                                    .frame(width: 40, height: 40)
                                    .background(WofinsTheme.primary.opacity(0.10), in: RoundedRectangle(cornerRadius: 10, style: .continuous))
                                VStack(alignment: .leading, spacing: 2) {
                                    Text("Payment Proof")
                                        .font(.poppins(.subheadline, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.ink)
                                    Text("Bukti pembayaran")
                                        .font(.poppins(.caption2))
                                        .foregroundStyle(WofinsTheme.muted)
                                        .lineLimit(1)
                                }
                                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                                Text("View")
                                    .font(.poppins(.caption, weight: .semibold))
                                    .foregroundStyle(.white)
                                    .padding(.horizontal, 14)
                                    .padding(.vertical, 8)
                                    .background(WofinsTheme.primary, in: Capsule())
                            }
                            .padding(17)
                            .contentShape(Rectangle())
                            .transactionSurface()
                        }
                        .buttonStyle(.plain)
                        .padding(.horizontal, 16)
                    }
                    if let orderId = item.order_id, orderId != 0 {
                        NavigationLink {
                            ProjectDetailView(projectId: orderId)
                        } label: {
                            HStack(spacing: 12) {
                                Image(systemName: "folder.fill")
                                    .foregroundStyle(WofinsTheme.primary)
                                    .frame(width: 40, height: 40)
                                    .background(WofinsTheme.primary.opacity(0.10), in: RoundedRectangle(cornerRadius: 10, style: .continuous))
                                VStack(alignment: .leading, spacing: 2) {
                                    Text("Lihat proyek")
                                        .font(.poppins(.subheadline, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.ink)
                                    Text(item.prospect_name ?? "Detail proyek wedding")
                                        .font(.poppins(.caption2))
                                        .foregroundStyle(WofinsTheme.muted)
                                        .lineLimit(1)
                                }
                                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                                Image(systemName: "chevron.right")
                                    .font(.system(size: 12, weight: .semibold))
                                    .foregroundStyle(WofinsTheme.muted)
                            }
                            .padding(17)
                            .transactionSurface()
                        }
                        .buttonStyle(.plain)
                        .padding(.horizontal, 16)
                    }
                }
                .padding(.top, 16)
                .padding(.bottom, 28)
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsHidesNavigationBar()
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
                Text("Detail transaksi")
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                Text(item.typeLabel)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var amountCard: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(item.isInflow ? "Uang masuk" : "Uang keluar")
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
            Text("\(item.isInflow ? "+ " : "− ")\(MoneyFormat.idr(item.amount))")
                .font(.poppins(.title2, weight: .bold))
                .foregroundStyle(color)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
            RoundedRectangle(cornerRadius: 2)
                .fill(color)
                .frame(width: 28, height: 3)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(17)
        .transactionSurface()
        .padding(.horizontal, 16)
    }

    private var detailsCard: some View {
        VStack(alignment: .leading, spacing: 14) {
            if !item.transactionTitle.isEmpty {
                labeledRow("Keterangan", item.transactionTitle, icon: "text.alignleft")
            }
            labeledRow("Jenis", item.typeLabel, icon: "tag.fill")
            if let date = formattedDate(item.date) {
                labeledRow("Tanggal", date, icon: "calendar")
            }
            if let method = item.payment_method, !method.isEmpty {
                labeledRow("Rekening", method, icon: "creditcard.fill")
            }
            if let prospect = item.prospect_name, !prospect.isEmpty {
                labeledRow("Proyek", prospect, icon: "heart.fill")
            }
            if let vendor = item.vendor_name, !vendor.isEmpty {
                labeledRow("Vendor", vendor, icon: "building.2.fill")
            }
            if let description = item.description, !description.isEmpty, description != item.transactionTitle {
                labeledRow("Catatan", description, icon: "note.text")
            }
        }
        .padding(17)
        .transactionSurface()
        .padding(.horizontal, 16)
    }

    private func labeledRow(_ title: String, _ value: String, icon: String) -> some View {
        HStack(alignment: .top, spacing: 10) {
            Image(systemName: icon)
                .font(.system(size: 13, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .frame(width: 18)
            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
                Text(value)
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                    .fixedSize(horizontal: false, vertical: true)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
    }

    private func formattedDate(_ raw: String?) -> String? {
        guard let raw, let date = TransactionDates.api.date(from: String(raw.prefix(10))) else { return nil }
        return TransactionDates.display.string(from: date)
    }
}

struct PaymentProofView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let paymentId: Int
    var proofURL: String? = nil

    @State private var image: UIImage?
    @State private var isLoading = true
    @State private var failed = false

    var body: some View {
        VStack(spacing: 0) {
            HStack(spacing: 12) {
                Button { dismiss() } label: {
                    Image(systemName: "chevron.left")
                        .font(.system(size: 16, weight: .bold))
                        .foregroundStyle(.white)
                        .frame(width: 40, height: 40)
                        .background(.white.opacity(0.11), in: Circle())
                }
                .accessibilityLabel("Kembali")
                .fixedSize()

                VStack(alignment: .leading, spacing: 2) {
                    Text("Payment Proof")
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(.white)
                        .lineLimit(1)
                    Text("Bukti pembayaran")
                        .font(.poppins(.caption))
                        .foregroundStyle(.white.opacity(0.72))
                        .lineLimit(1)
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

                Button {
                    Task { await load() }
                } label: {
                    Image(systemName: "arrow.clockwise")
                        .font(.system(size: 16, weight: .bold))
                        .foregroundStyle(.white)
                        .frame(width: 40, height: 40)
                        .background(.white.opacity(0.11), in: Circle())
                }
                .accessibilityLabel("Muat ulang")
                .disabled(isLoading)
                .fixedSize()
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))

            Group {
                if let image {
                    ScrollView([.horizontal, .vertical], showsIndicators: false) {
                        Image(uiImage: image)
                            .resizable()
                            .scaledToFit()
                            .frame(maxWidth: .infinity)
                            .padding(16)
                    }
                    .refreshable { await load() }
                } else if isLoading {
                    VStack(spacing: 12) {
                        ProgressView().tint(WofinsTheme.primary)
                        Text("Memuat payment proof…")
                            .font(.poppins(.subheadline))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else {
                    VStack(spacing: 12) {
                        Text("Payment proof belum dapat dimuat.")
                            .font(.poppins(.subheadline))
                            .foregroundStyle(WofinsTheme.muted)
                            .multilineTextAlignment(.center)
                        Button("Coba lagi") { Task { await load() } }
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                    }
                    .padding(16)
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                }
            }
            .background(WofinsTheme.background)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsHidesNavigationBar()
        .task { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let data = try await appState.api.financePaymentProof(id: paymentId, urlString: proofURL)
            image = UIImage(data: data)
            failed = image == nil
        } catch {
            if !APILoadFailure.isCancellation(error) {
                failed = true
            }
        }
    }
}

private extension FinanceTransactionItem {
    var transactionTitle: String { prospect_name ?? description ?? vendor_name ?? "Transaksi" }
}

private enum TransactionPaging {
    static let pageSize = 10

    static func clamped(page: Int, total: Int) -> Int {
        let last = max(0, Int(ceil(Double(max(total, 0)) / Double(pageSize))) - 1)
        return min(max(0, page), last)
    }

    static func slice(_ items: [FinanceTransactionItem], page: Int) -> [FinanceTransactionItem] {
        let sorted = items.sorted {
            if ($0.date ?? "") != ($1.date ?? "") {
                return ($0.date ?? "") > ($1.date ?? "")
            }
            return $0.id > $1.id
        }
        let current = clamped(page: page, total: sorted.count)
        let start = current * pageSize
        guard start < sorted.count else { return [] }
        return Array(sorted[start..<min(start + pageSize, sorted.count)])
    }

    static func groups(_ items: [FinanceTransactionItem]) -> [(String, [FinanceTransactionItem])] {
        let grouped = Dictionary(grouping: items) { $0.date ?? "" }
        return grouped.keys.sorted(by: >).map { ($0, grouped[$0] ?? []) }
    }
}

private struct TransactionPageBar: View {
    @Binding var page: Int
    let total: Int

    private var pageCount: Int {
        max(1, Int(ceil(Double(max(total, 0)) / Double(TransactionPaging.pageSize))))
    }

    private var rangeText: String {
        guard total > 0 else { return "" }
        let start = page * TransactionPaging.pageSize + 1
        let end = min(total, (page + 1) * TransactionPaging.pageSize)
        return "Menampilkan \(start)–\(end) dari \(total)"
    }

    var body: some View {
        if total > TransactionPaging.pageSize {
            VStack(spacing: 12) {
                Text(rangeText)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .multilineTextAlignment(.center)
                    .frame(maxWidth: .infinity)
                    .minimumScaleFactor(0.85)

                HStack(spacing: 12) {
                    Button {
                        page = max(0, page - 1)
                    } label: {
                        Image(systemName: "chevron.left")
                            .font(.system(size: 15, weight: .bold))
                            .foregroundStyle(page <= 0 ? WofinsTheme.muted : WofinsTheme.primary)
                            .frame(width: 44, height: 44)
                            .background(WofinsTheme.primary.opacity(page <= 0 ? 0.06 : 0.10), in: Circle())
                    }
                    .disabled(page <= 0)
                    .accessibilityLabel("Sebelumnya")

                    Text("\(page + 1) / \(pageCount)")
                        .font(.poppins(.subheadline, weight: .bold))
                        .foregroundStyle(WofinsTheme.ink)
                        .frame(minWidth: 0, maxWidth: .infinity)

                    Button {
                        page = min(pageCount - 1, page + 1)
                    } label: {
                        Image(systemName: "chevron.right")
                            .font(.system(size: 15, weight: .bold))
                            .foregroundStyle(page >= pageCount - 1 ? WofinsTheme.muted : WofinsTheme.primary)
                            .frame(width: 44, height: 44)
                            .background(WofinsTheme.primary.opacity(page >= pageCount - 1 ? 0.06 : 0.10), in: Circle())
                    }
                    .disabled(page >= pageCount - 1)
                    .accessibilityLabel("Berikutnya")
                }
            }
            .padding(14)
            .transactionSurface()
            .padding(.horizontal, 16)
        }
    }
}

private enum TransactionDates {
    static let api: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "en_US_POSIX"); f.dateFormat = "yyyy-MM-dd"; return f }()
    static let display: DateFormatter = { let f = DateFormatter(); f.locale = Locale(identifier: "id_ID"); f.dateFormat = "d MMMM yyyy"; return f }()
}

private extension View {
    func transactionSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay { RoundedRectangle(cornerRadius: 18).stroke(WofinsTheme.border.opacity(0.75), lineWidth: 1) }
            .wofinsSoftShadow()
    }
}
