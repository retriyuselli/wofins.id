import SwiftUI
import UIKit

struct ModulesHubView: View {
    @EnvironmentObject private var appState: AppState
    @State private var items: [MobileModuleCatalogItem] = []
    @State private var isLoading = false
    @State private var errorMessage: String?

    private var grouped: [(String, [MobileModuleCatalogItem])] {
        Dictionary(grouping: items, by: \.groupTitle)
            .sorted { lhs, rhs in
                order(lhs.key) < order(rhs.key)
            }
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                LazyVStack(spacing: 16) {
                    if isLoading && items.isEmpty {
                        ProgressView("Memuat modul…")
                            .frame(maxWidth: .infinity)
                            .padding(24)
                            .moduleSurface()
                    } else if let errorMessage {
                        Text(errorMessage)
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.danger)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .padding(16)
                            .moduleSurface()
                    } else {
                        ForEach(grouped, id: \.0) { group, rows in
                            VStack(alignment: .leading, spacing: 10) {
                                Text(group)
                                    .font(.poppins(.caption, weight: .semibold))
                                    .foregroundStyle(WofinsTheme.muted)
                                    .padding(.horizontal, 4)
                                ForEach(rows) { item in
                                    NavigationLink {
                                        ModuleListView(item: item)
                                    } label: {
                                        moduleRow(item)
                                    }
                                    .buttonStyle(.plain)
                                }
                            }
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
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) {
                Text("Modul").font(.poppins(.headline, weight: .bold)).foregroundStyle(.white)
                Text(appState.currentUser?.companyDisplayName ?? "Sesuai paket company")
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            Spacer(minLength: 8)
        }
        .padding(.horizontal, 20)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private func moduleRow(_ item: MobileModuleCatalogItem) -> some View {
        HStack(spacing: 12) {
            Image(systemName: item.iconName)
                .font(.system(size: 15, weight: .semibold))
                .foregroundStyle(item.isAllowed ? WofinsTheme.primary : WofinsTheme.muted)
                .frame(width: 36, height: 36)
                .background((item.isAllowed ? WofinsTheme.primary : WofinsTheme.muted).opacity(0.1), in: Circle())
            VStack(alignment: .leading, spacing: 2) {
                Text(item.title)
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                    .lineLimit(1)
                    .minimumScaleFactor(0.85)
                Text(item.subtitle ?? "")
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
                    .lineLimit(2)
                    .fixedSize(horizontal: false, vertical: true)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            if let badge = item.plan_badge, !item.isAllowed {
                Text(badge)
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 4)
                    .background(WofinsTheme.yellow.opacity(0.2), in: Capsule())
            } else if let count = item.count {
                Text("\(count)")
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
            }
            Image(systemName: item.isAllowed ? "chevron.right" : "lock.fill")
                .font(.system(size: 12, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
        }
        .padding(14)
        .moduleSurface()
    }

    private func order(_ group: String) -> Int {
        switch group {
        case "Penjualan": return 0
        case "Keuangan": return 1
        case "Professional": return 2
        case "Business": return 3
        default: return 4
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            items = try await appState.api.moduleCatalog()
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

struct ModuleListView: View {
    @EnvironmentObject private var appState: AppState
    let item: MobileModuleCatalogItem

    @State private var records: [ModuleRecord] = []
    @State private var meta: ModuleListMeta?
    @State private var searchText = ""
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showCreate = false
    @State private var showDesktopOnlyCreate = false
    @State private var listPage = 1
    @State private var isLoadingMore = false

    private var canCreateNow: Bool { meta?.can_create ?? item.canCreate }
    private var isBankStatement: Bool { item.key == "bank_statements" }
    private var showsCreateAction: Bool { canCreateNow || isBankStatement }

    private var desktopCreateMessage: String {
        "Rekonsiliasi hanya bisa dibuat di desktop (admin web). Unggah rekening koran dan file perbandingan membutuhkan layar yang lebih besar."
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            if item.isAllowed {
                ScrollView(showsIndicators: false) {
                    LazyVStack(spacing: 12) {
                        searchBar
                        if isLoading && records.isEmpty {
                            ProgressView("Memuat…")
                                .frame(maxWidth: .infinity)
                                .padding(20)
                                .moduleSurface()
                        } else if let errorMessage {
                            Text(errorMessage)
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.danger)
                                .padding(16)
                                .moduleSurface()
                        } else if records.isEmpty {
                            emptyState
                        } else {
                            ForEach(records) { record in
                                NavigationLink {
                                    ModuleDetailView(item: item, recordId: record.id)
                                } label: {
                                    recordRow(record)
                                }
                                .buttonStyle(.plain)
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
                                    .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
                                }
                                .buttonStyle(.plain)
                                .disabled(isLoadingMore)
                            }
                        }
                    }
                    .padding(.horizontal, 16)
                    .padding(.top, 16)
                    .padding(.bottom, 28)
                }
                .refreshable { await load(reset: true) }
                .scrollDismissesKeyboard(.immediately)
            } else if let feature = item.planFeature {
                PlanLockedView(feature: feature, title: item.title)
                Spacer()
            } else {
                PlanLockedView(feature: .projects, title: item.title)
                Spacer()
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsHidesNavigationBar()
        .task { await load() }
        .sheet(isPresented: $showCreate) {
            Group {
                if item.key == "simulasi" {
                    SimulasiFormView {
                        Task { await load() }
                    }
                } else {
                    ModuleCreateView(item: item) {
                        Task { await load() }
                    }
                }
            }
            .environmentObject(appState)
            .presentationDetents([.large])
        }
        .alert("Buat di desktop", isPresented: $showDesktopOnlyCreate) {
            Button("Buka admin web") { openDesktopBankStatements() }
            Button("Mengerti", role: .cancel) {}
        } message: {
            Text(desktopCreateMessage)
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) {
                Text(item.title).font(.poppins(.headline, weight: .bold)).foregroundStyle(.white).lineLimit(1)
                Text("\(meta?.total ?? item.count ?? records.count) data · \(appState.currentUser?.companyDisplayName ?? "Company")")
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
                    .minimumScaleFactor(0.8)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            if showsCreateAction {
                Button { requestCreate() } label: {
                    Image(systemName: "plus")
                        .font(.system(size: 17, weight: .bold))
                        .foregroundStyle(.white)
                        .frame(width: 40, height: 40)
                        .background(.white.opacity(0.11), in: Circle())
                }
                .accessibilityLabel("Tambah \(item.title)")
                .fixedSize()
            }
        }
        .padding(.horizontal, 20)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var searchBar: some View {
        HStack(spacing: 10) {
            Image(systemName: "magnifyingglass").foregroundStyle(WofinsTheme.muted)
            TextField("Cari \(item.title.lowercased())", text: $searchText)
                .font(.poppins(.subheadline))
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()
                .onSubmit { Task { await load() } }
            if !searchText.isEmpty {
                Button {
                    searchText = ""
                    Task { await load() }
                } label: {
                    Image(systemName: "xmark.circle.fill").foregroundStyle(WofinsTheme.muted)
                }
            }
        }
        .padding(.horizontal, 14)
        .frame(height: 50)
        .moduleSurface()
    }

    private var emptyState: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Belum ada data").font(.poppins(.subheadline, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            Text("Data \(item.title.lowercased()) untuk company ini akan tampil di sini.")
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            if showsCreateAction {
                Button(isBankStatement ? "Info buat rekonsiliasi" : "Tambah \(item.title)") {
                    requestCreate()
                }
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(.white)
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                    .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 12))
                    .padding(.top, 6)
            }
        }
        .padding(16)
        .moduleSurface()
    }

    private func requestCreate() {
        if isBankStatement {
            showDesktopOnlyCreate = true
        } else {
            showCreate = true
        }
    }

    private func openDesktopBankStatements() {
        if let url = APIConfig.websiteURL("/admin/bank-statements") {
            UIApplication.shared.open(url)
        }
    }

    private func recordRow(_ record: ModuleRecord) -> some View {
        HStack(alignment: .top, spacing: 12) {
            VStack(alignment: .leading, spacing: 4) {
                Text(record.displayTitle)
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                    .lineLimit(2)
                    .minimumScaleFactor(0.85)
                if let subtitle = record.subtitle, !subtitle.isEmpty {
                    Text(subtitle)
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                        .lineLimit(2)
                }
                HStack(spacing: 8) {
                    if let date = record.date {
                        Text(date).font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    }
                    if let status = record.status, !status.isEmpty {
                        Text(status.replacingOccurrences(of: "_", with: " "))
                            .font(.poppins(.caption2, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                            .padding(.horizontal, 7)
                            .padding(.vertical, 3)
                            .background(WofinsTheme.primary.opacity(0.08), in: Capsule())
                    }
                }
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            HStack(spacing: 8) {
                if let amount = record.amount {
                    Text(MoneyFormat.idr(amount))
                        .font(.poppins(.caption, weight: .bold))
                        .foregroundStyle(WofinsTheme.ink)
                        .lineLimit(1)
                        .minimumScaleFactor(0.7)
                }
                if item.key == "simulasi" {
                    Image(systemName: "doc.richtext.fill")
                        .font(.system(size: 14, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .accessibilityLabel("Ada draft kontrak PDF")
                }
            }
            .fixedSize(horizontal: true, vertical: false)
        }
        .padding(14)
        .moduleSurface()
    }

    private var canLoadMore: Bool {
        ListPaging.canLoadMore(current: meta?.current_page, last: meta?.last_page)
    }

    private func load(reset: Bool = true) async {
        guard item.isAllowed else { return }
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
            let response = try await appState.api.moduleList(key: item.key, query: searchText, page: listPage)
            if reset {
                records = response.data
            } else {
                let existing = Set(records.map(\.id))
                records.append(contentsOf: response.data.filter { !existing.contains($0.id) })
            }
            meta = response.meta
            errorMessage = nil
        } catch {
            if !reset { listPage = max(1, listPage - 1) }
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

struct ModuleDetailView: View {
    @EnvironmentObject private var appState: AppState
    let item: MobileModuleCatalogItem
    let recordId: Int

    @State private var record: ModuleRecord?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var actionMessage: String?
    @State private var isOpeningDraft = false
    @State private var isSharingDraft = false
    @State private var showPdfPreview = false
    @State private var pdfPreviewURL: URL?
    @State private var isDownloadingPdf = false
    @State private var shareItem: ModulePdfShareItem?
    @State private var showEdit = false
    @State private var showDesktopOnlyEdit = false
    @State private var productDetail: FinanceProductDetail?
    @State private var isLoadingProduct = false

    private var isSimulasi: Bool { item.key == "simulasi" }
    private var isProduct: Bool { item.key == "products" }
    private var isBankStatement: Bool { item.key == "bank_statements" }
    private var isBusy: Bool { isOpeningDraft || isSharingDraft || isDownloadingPdf }
    private var childrenTitle: String {
        if isProduct { return "Fasilitas Dasar" }
        if isBankStatement { return record?.children_title ?? "Mutasi rekening" }
        return "Rincian"
    }
    private var desktopEditMessage: String {
        "Rekonsiliasi hanya bisa diedit di desktop (admin web). Unggah file, penyesuaian periode, dan perbandingan membutuhkan layar yang lebih besar."
    }
    private var resolvedProduct: FinanceProductDetail? { productDetail ?? record?.product }

    private var headerAmount: Int? {
        if isProduct {
            return resolvedProduct?.pricing?.total_publish
                ?? resolvedProduct?.price
                ?? record?.amount
        }
        return record?.amount
    }

    private var displayFields: [ModuleFieldRow] {
        let fields = record?.fields ?? []
        guard isProduct, let pricing = resolvedProduct?.pricing else { return fields }
        return fields.map { field in
            switch field.label.lowercased() {
            case "harga", "total paket":
                return ModuleFieldRow(label: "Total Paket", value: MoneyFormat.idr(pricing.total_publish))
            case "harga vendor", "total vendor":
                return ModuleFieldRow(label: "Total Vendor", value: MoneyFormat.idr(pricing.total_vendor))
            default:
                return field
            }
        }
    }

    private var draftFileName: String {
        let raw = record?.displayTitle ?? "simulasi-\(recordId)"
        let safe = raw
            .replacingOccurrences(of: "/", with: "-")
            .replacingOccurrences(of: ":", with: "-")
            .replacingOccurrences(of: " ", with: "_")
        return "Draft_Kontrak_\(safe).pdf"
    }

    private var productPdfFileName: String {
        let slug = resolvedProduct?.slug ?? "paket-\(recordId)"
        return "Paket_\(slug).pdf"
    }

    private var actionAlertTitle: String {
        if isSimulasi { return "Draft Kontrak" }
        if isProduct { return "Paket" }
        return item.title
    }

    var body: some View {
        VStack(spacing: 0) {
            HStack(spacing: 12) {
                WofinsCompactMark()
                VStack(alignment: .leading, spacing: 2) {
                    Text(record?.displayTitle ?? item.title)
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(.white)
                        .lineLimit(2)
                        .minimumScaleFactor(0.8)
                    Text(item.title)
                        .font(.poppins(.caption))
                        .foregroundStyle(.white.opacity(0.72))
                        .lineLimit(1)
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                if record != nil {
                    headerActions
                }
                if isSimulasi, record != nil {
                    Button {
                        Task { await openDraftKontrak() }
                    } label: {
                        Group {
                            if isOpeningDraft {
                                ProgressView().tint(.white)
                            } else {
                                Image(systemName: "doc.richtext.fill")
                                    .font(.system(size: 16, weight: .bold))
                                    .foregroundStyle(.white)
                            }
                        }
                        .frame(width: 40, height: 40)
                        .background(.white.opacity(0.11), in: Circle())
                    }
                    .accessibilityLabel("Lihat draft kontrak PDF")
                    .disabled(isBusy)
                    .fixedSize()
                }
            }
            .padding(.horizontal, 20)
            .padding(.vertical, 12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))

            ScrollView(showsIndicators: false) {
                LazyVStack(alignment: .leading, spacing: 12) {
                    if isLoading && record == nil {
                        ProgressView("Memuat detail…").frame(maxWidth: .infinity).padding(24)
                    } else if let errorMessage, record == nil {
                        Text(errorMessage).font(.poppins(.caption)).foregroundStyle(WofinsTheme.danger).padding(16).moduleSurface()
                    } else if let record {
                        if let amount = headerAmount {
                            Text(MoneyFormat.idr(amount))
                                .font(.poppins(size: 24, weight: .bold))
                                .foregroundStyle(WofinsTheme.primary)
                                .padding(16)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .moduleSurface()
                        }
                        VStack(alignment: .leading, spacing: 0) {
                            ForEach(Array(displayFields.enumerated()), id: \.offset) { index, field in
                                VStack(alignment: .leading, spacing: 4) {
                                    Text(field.label)
                                        .font(.poppins(.caption2))
                                        .foregroundStyle(WofinsTheme.muted)
                                    Text(field.displayText)
                                        .font(.poppins(.subheadline))
                                        .foregroundStyle(WofinsTheme.ink)
                                        .fixedSize(horizontal: false, vertical: true)
                                }
                                .padding(.vertical, 10)
                                if index < displayFields.count - 1 {
                                    Divider()
                                }
                            }
                        }
                        .padding(16)
                        .moduleSurface()

                        if isSimulasi {
                            draftKontrakActions
                        }

                        if isBankStatement, let comparison = record.reconciliation {
                            BankReconciliationComparisonView(comparison: comparison)
                        }

                        if isProduct {
                            if let product = resolvedProduct {
                                ProductBreakdownView(detail: product)
                            } else if isLoadingProduct {
                                ProgressView("Memuat fasilitas paket…")
                                    .frame(maxWidth: .infinity)
                                    .padding(24)
                            } else {
                                childrenSection(record.children ?? [])
                            }
                        } else if isBankStatement || !(record.children ?? []).isEmpty {
                            childrenSection(record.children ?? [])
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
        .sheet(isPresented: $showEdit) {
            Group {
                if isSimulasi {
                    SimulasiFormView(recordId: recordId) {
                        Task { await load() }
                    }
                } else {
                    ModuleCreateView(item: item, recordId: recordId) {
                        Task { await load() }
                    }
                }
            }
            .environmentObject(appState)
            .presentationDetents([.large])
        }
        .fullScreenCover(isPresented: $showPdfPreview) {
            if let pdfPreviewURL {
                ProjectDocumentView(
                    url: pdfPreviewURL,
                    title: "Draft Kontrak",
                    fileName: draftFileName
                )
            }
        }
        .sheet(item: $shareItem) { item in
            ModulePdfShareSheet(items: [item.url])
        }
        .alert(actionAlertTitle, isPresented: Binding(
            get: { actionMessage != nil },
            set: { if !$0 { actionMessage = nil } }
        )) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(actionMessage ?? "")
        }
        .alert("Edit di desktop", isPresented: $showDesktopOnlyEdit) {
            Button("Buka admin web") { openDesktopBankStatementEdit() }
            Button("Mengerti", role: .cancel) {}
        } message: {
            Text(desktopEditMessage)
        }
    }

    @ViewBuilder
    private var headerActions: some View {
        if isProduct {
            Menu {
                Button {
                    showEdit = true
                } label: {
                    Label("Edit", systemImage: "pencil")
                }
                Button {
                    Task { await downloadProductPdf() }
                } label: {
                    Label("Download PDF", systemImage: "arrow.down.doc.fill")
                }
                .disabled(isBusy)
            } label: {
                Group {
                    if isDownloadingPdf {
                        ProgressView().tint(.white)
                    } else {
                        Image(systemName: "ellipsis")
                            .font(.system(size: 16, weight: .bold))
                            .foregroundStyle(.white)
                    }
                }
                .frame(width: 40, height: 40)
                .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Aksi paket")
            .fixedSize()
        } else {
            Button { requestEdit() } label: {
                Image(systemName: "slider.horizontal.3")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel(isSimulasi ? "Edit simulasi" : "Edit \(item.title)")
            .fixedSize()
        }
    }

    private func requestEdit() {
        if isBankStatement {
            showDesktopOnlyEdit = true
        } else {
            showEdit = true
        }
    }

    private func openDesktopBankStatementEdit() {
        if let url = APIConfig.websiteURL("/admin/bank-statements/\(recordId)/edit") {
            UIApplication.shared.open(url)
        }
    }

    private func childrenSection(_ children: [ModuleRecord]) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(childrenTitle)
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
            if children.isEmpty {
                Text(isProduct
                    ? "Belum ada fasilitas dasar pada paket ini."
                    : (isBankStatement ? "Belum ada mutasi pada rekening koran ini." : "Belum ada rincian"))
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
                    .padding(14)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .moduleSurface()
            } else {
                ForEach(children) { child in
                    childCard(child)
                }
            }
        }
    }

    @ViewBuilder
    private func childCard(_ child: ModuleRecord) -> some View {
        let card = childCardContent(child)
        if isProduct, let vendorId = child.vendor_id {
            NavigationLink {
                VendorDetailView(vendorId: vendorId, previewName: child.title)
            } label: {
                card
            }
            .buttonStyle(.plain)
        } else {
            card
        }
    }

    private func childCardContent(_ child: ModuleRecord) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack(alignment: .top, spacing: 10) {
                VStack(alignment: .leading, spacing: 3) {
                    Text(child.displayTitle)
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                    if let subtitle = child.subtitle, !subtitle.isEmpty {
                        Text(subtitle)
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                VStack(alignment: .trailing, spacing: 6) {
                    if let amount = child.amount, amount != 0 {
                        Text(MoneyFormat.idr(amount))
                            .font(.poppins(.caption, weight: .bold))
                            .foregroundStyle(WofinsTheme.ink)
                            .lineLimit(1)
                            .minimumScaleFactor(0.7)
                    }
                    if isBankStatement, let status = child.status, !status.isEmpty {
                        Text(bankStatementStatusLabel(status))
                            .font(.poppins(.caption2, weight: .semibold))
                            .foregroundStyle(bankStatementStatusColor(status))
                            .padding(.horizontal, 8)
                            .padding(.vertical, 3)
                            .background(bankStatementStatusColor(status).opacity(0.12), in: Capsule())
                    }
                    if isProduct, child.vendor_id != nil {
                        Image(systemName: "chevron.right")
                            .font(.system(size: 12, weight: .semibold))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                }
                .fixedSize(horizontal: true, vertical: false)
            }

            if let fields = child.fields, !fields.isEmpty {
                VStack(alignment: .leading, spacing: 6) {
                    ForEach(fields) { field in
                        VStack(alignment: .leading, spacing: 2) {
                            Text(field.label)
                                .font(.poppins(.caption2))
                                .foregroundStyle(WofinsTheme.muted)
                            Text(field.displayText)
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.ink)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                    }
                }
            }
        }
        .padding(14)
        .frame(maxWidth: .infinity, alignment: .leading)
        .moduleSurface()
    }

    private func bankStatementStatusLabel(_ status: String) -> String {
        switch status.lowercased() {
        case "matched": return "Cocok"
        case "unmatched": return "Belum cocok"
        default: return DisplayText.titleCase(status)
        }
    }

    private func bankStatementStatusColor(_ status: String) -> Color {
        switch status.lowercased() {
        case "matched": return WofinsTheme.success
        case "unmatched": return WofinsTheme.muted
        default: return WofinsTheme.primary
        }
    }

    private var draftKontrakActions: some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Dokumen")
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
            Text("PDF kontrak kerja dari data simulasi ini — sama seperti di web.")
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            Button { showEdit = true } label: {
                HStack(spacing: 10) {
                    Image(systemName: "slider.horizontal.3")
                    Text("Edit simulasi")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .lineLimit(1)
                        .minimumScaleFactor(0.85)
                }
                .foregroundStyle(WofinsTheme.primary)
                .frame(maxWidth: .infinity)
                .frame(height: 48)
                .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                .overlay {
                    RoundedRectangle(cornerRadius: 14).stroke(WofinsTheme.primary.opacity(0.3))
                }
            }
            .buttonStyle(.plain)
            Button {
                Task { await openDraftKontrak() }
            } label: {
                HStack(spacing: 10) {
                    if isOpeningDraft {
                        ProgressView().tint(.white)
                    } else {
                        Image(systemName: "doc.richtext.fill")
                    }
                    Text(isOpeningDraft ? "Menyiapkan PDF…" : "Lihat Draft Kontrak")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .lineLimit(1)
                        .minimumScaleFactor(0.85)
                }
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .frame(height: 48)
                .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 14))
            }
            .buttonStyle(.plain)
            .disabled(isBusy)
            Button {
                Task { await shareDraftKontrak() }
            } label: {
                HStack(spacing: 10) {
                    if isSharingDraft {
                        ProgressView()
                    } else {
                        Image(systemName: "square.and.arrow.up")
                    }
                    Text(isSharingDraft ? "Menyiapkan PDF…" : "Bagikan / Simpan PDF")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .lineLimit(1)
                        .minimumScaleFactor(0.85)
                }
                .foregroundStyle(WofinsTheme.primary)
                .frame(maxWidth: .infinity)
                .frame(height: 48)
                .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                .overlay {
                    RoundedRectangle(cornerRadius: 14).stroke(WofinsTheme.primary.opacity(0.3))
                }
            }
            .buttonStyle(.plain)
            .disabled(isBusy)
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .moduleSurface()
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let detail = try await appState.api.moduleDetail(key: item.key, id: recordId)
            record = detail
            errorMessage = nil
            if isProduct {
                await loadProductBreakdown(preferring: detail.product)
            } else {
                productDetail = nil
            }
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func loadProductBreakdown(preferring bundled: FinanceProductDetail?) async {
        if productDetail == nil {
            productDetail = bundled
        }
        isLoadingProduct = productDetail == nil
        defer { isLoadingProduct = false }
        do {
            productDetail = try await appState.api.financeProduct(id: recordId)
        } catch {
            if productDetail == nil {
                productDetail = bundled
            }
        }
    }

    private func downloadProductPdf() async {
        guard !isBusy else { return }
        isDownloadingPdf = true
        defer { isDownloadingPdf = false }
        do {
            let data = try await appState.api.financeProductPdf(id: recordId)
            let safeName = productPdfFileName
                .replacingOccurrences(of: "/", with: "-")
                .replacingOccurrences(of: ":", with: "-")
            let url = FileManager.default.temporaryDirectory.appendingPathComponent(safeName)
            try data.write(to: url, options: .atomic)
            shareItem = ModulePdfShareItem(url: url)
        } catch {
            APILoadFailure.assign(error, to: &actionMessage)
        }
    }

    private func openDraftKontrak() async {
        guard !isBusy else { return }
        isOpeningDraft = true
        defer { isOpeningDraft = false }
        do {
            pdfPreviewURL = try await cachedDraftPdf()
            showPdfPreview = true
        } catch {
            APILoadFailure.assign(error, to: &actionMessage)
        }
    }

    private func shareDraftKontrak() async {
        guard !isBusy else { return }
        isSharingDraft = true
        defer { isSharingDraft = false }
        do {
            let url = try await cachedDraftPdf()
            shareItem = ModulePdfShareItem(url: url)
        } catch {
            APILoadFailure.assign(error, to: &actionMessage)
        }
    }

    private func cachedDraftPdf() async throws -> URL {
        if let pdfPreviewURL, FileManager.default.fileExists(atPath: pdfPreviewURL.path) {
            return pdfPreviewURL
        }
        let data = try await appState.api.moduleDraftKontrakPdf(id: recordId)
        let url = FileManager.default.temporaryDirectory.appendingPathComponent(draftFileName)
        try data.write(to: url, options: .atomic)
        pdfPreviewURL = url
        return url
    }
}

private struct ModulePdfShareItem: Identifiable {
    let id = UUID()
    let url: URL
}

private struct ModulePdfShareSheet: UIViewControllerRepresentable {
    let items: [Any]

    func makeUIViewController(context: Context) -> UIActivityViewController {
        UIActivityViewController(activityItems: items, applicationActivities: nil)
    }

    func updateUIViewController(_ uiViewController: UIActivityViewController, context: Context) {}
}

struct ModuleCreateView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    let item: MobileModuleCatalogItem
    var recordId: Int? = nil
    var onSaved: () -> Void

    @State private var schema: ModuleFormSchema?
    @State private var values: [String: String] = [:]
    @State private var isLoading = false
    @State private var isSaving = false
    @State private var errorMessage: String?

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && schema == nil {
                    ProgressView("Menyiapkan form…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let errorMessage, schema == nil {
                    Text(errorMessage)
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.danger)
                        .padding()
                } else {
                    ScrollView(showsIndicators: false) {
                        LazyVStack(alignment: .leading, spacing: 14) {
                            if let errorMessage {
                                Text(errorMessage)
                                    .font(.poppins(.caption))
                                    .foregroundStyle(WofinsTheme.danger)
                                    .padding(12)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .background(WofinsTheme.danger.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
                            }
                            ForEach(schema?.fields ?? []) { field in
                                fieldView(field)
                            }
                            Button(action: save) {
                                if isSaving {
                                    ProgressView().tint(.white)
                                } else {
                                    Text("Simpan")
                                        .font(.poppins(.subheadline, weight: .semibold))
                                }
                            }
                            .foregroundStyle(.white)
                            .frame(maxWidth: .infinity)
                            .frame(height: 48)
                            .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 14))
                            .disabled(isSaving)
                        }
                        .padding(16)
                        .padding(.bottom, 28)
                    }
                    .scrollDismissesKeyboard(.immediately)
                    .wofinsKeyboardDoneButton()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle(schema?.title ?? "Tambah \(item.title)")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Tutup") { dismiss() }
                }
            }
            .wofinsSwipeBack()
            .task { await loadForm() }
        }
    }

    @ViewBuilder
    private func fieldView(_ field: ModuleFormField) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(field.label + (field.isRequired ? " *" : ""))
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
            switch field.fieldType {
            case "select":
                Menu {
                    ForEach(field.options ?? []) { option in
                        Button(option.label) { values[field.name] = option.value }
                    }
                } label: {
                    HStack {
                        Text(selectedLabel(field) ?? "Pilih")
                            .font(.poppins(.subheadline))
                            .foregroundStyle(selectedLabel(field) == nil ? WofinsTheme.muted : WofinsTheme.ink)
                            .lineLimit(1)
                        Spacer()
                        Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
                    }
                    .padding(.horizontal, 12)
                    .frame(minHeight: 46)
                    .moduleSurface()
                }
            case "toggle":
                Toggle("", isOn: toggleBinding(field.name))
                    .labelsHidden()
                    .tint(WofinsTheme.primary)
            case "date":
                DatePicker("", selection: dateBinding(field.name), displayedComponents: .date)
                    .labelsHidden()
                    .datePickerStyle(.compact)
                    .frame(maxWidth: .infinity, alignment: .leading)
            case "textarea":
                TextField(field.placeholder ?? field.label, text: stringBinding(field.name), axis: .vertical)
                    .font(.poppins(.subheadline))
                    .lineLimit(3...6)
                    .padding(12)
                    .moduleSurface()
            case "number":
                if field.usesThousandSeparator {
                    TextField(field.placeholder ?? "0", text: MoneyFormat.groupedBinding(stringBinding(field.name)))
                        .font(.poppins(.subheadline))
                        .keyboardType(.numberPad)
                        .padding(.horizontal, 12)
                        .frame(height: 46)
                        .moduleSurface()
                } else {
                    TextField(field.placeholder ?? "0", text: stringBinding(field.name))
                        .font(.poppins(.subheadline))
                        .keyboardType(.numberPad)
                        .padding(.horizontal, 12)
                        .frame(height: 46)
                        .moduleSurface()
                }
            case "email":
                TextField(field.placeholder ?? field.label, text: stringBinding(field.name))
                    .font(.poppins(.subheadline))
                    .keyboardType(.emailAddress)
                    .textInputAutocapitalization(.never)
                    .autocorrectionDisabled()
                    .padding(.horizontal, 12)
                    .frame(height: 46)
                    .moduleSurface()
            default:
                TextField(field.placeholder ?? field.label, text: stringBinding(field.name))
                    .font(.poppins(.subheadline))
                    .padding(.horizontal, 12)
                    .frame(height: 46)
                    .moduleSurface()
            }
        }
    }

    private func selectedLabel(_ field: ModuleFormField) -> String? {
        guard let value = values[field.name], !value.isEmpty else { return nil }
        return field.options?.first(where: { $0.value == value })?.label ?? value
    }

    private func stringBinding(_ name: String) -> Binding<String> {
        Binding(
            get: { values[name] ?? "" },
            set: { values[name] = $0 }
        )
    }

    private func toggleBinding(_ name: String) -> Binding<Bool> {
        Binding(
            get: { values[name] == "1" || values[name] == "true" },
            set: { values[name] = $0 ? "1" : "0" }
        )
    }

    private func dateBinding(_ name: String) -> Binding<Date> {
        Binding(
            get: {
                let formatter = DateFormatter()
                formatter.calendar = Calendar(identifier: .gregorian)
                formatter.locale = Locale(identifier: "en_US_POSIX")
                formatter.dateFormat = "yyyy-MM-dd"
                if let raw = values[name], let date = formatter.date(from: raw) {
                    return date
                }
                return Date()
            },
            set: { date in
                let formatter = DateFormatter()
                formatter.calendar = Calendar(identifier: .gregorian)
                formatter.locale = Locale(identifier: "en_US_POSIX")
                formatter.dateFormat = "yyyy-MM-dd"
                values[name] = formatter.string(from: date)
            }
        )
    }

    private func loadForm() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let loaded = try await appState.api.moduleForm(key: item.key, id: recordId)
            schema = loaded
            var seed: [String: String] = loaded.defaults ?? [:]
            let formatter = DateFormatter()
            formatter.calendar = Calendar(identifier: .gregorian)
            formatter.locale = Locale(identifier: "en_US_POSIX")
            formatter.dateFormat = "yyyy-MM-dd"
            for field in loaded.fields ?? [] {
                if seed[field.name] != nil { continue }
                if field.fieldType == "date" {
                    seed[field.name] = formatter.string(from: Date())
                } else if field.fieldType == "toggle" {
                    seed[field.name] = "0"
                }
            }
            if let recordId {
                let detail = try await appState.api.moduleDetail(key: item.key, id: recordId)
                if let stored = detail.values {
                    for (key, value) in stored {
                        seed[key] = value
                    }
                }
            }
            values = seed
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func save() {
        guard !isSaving else { return }
        isSaving = true
        errorMessage = nil
        let payload = values.filter { !$0.value.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty }
        Task {
            defer { isSaving = false }
            do {
                if let recordId {
                    _ = try await appState.api.updateModule(key: item.key, id: recordId, values: payload)
                } else {
                    _ = try await appState.api.createModule(key: item.key, values: payload)
                }
                onSaved()
                dismiss()
            } catch {
                APILoadFailure.assign(error, to: &errorMessage)
            }
        }
    }
}

struct ModuleShortcutsView: View {
    @EnvironmentObject private var appState: AppState
    var keys: [String]? = nil
    var title: String = "Modul"

    @State private var items: [MobileModuleCatalogItem] = []

    private var visible: [MobileModuleCatalogItem] {
        guard let keys, !keys.isEmpty else { return items }
        return items.filter { keys.contains($0.key) }
    }

    var body: some View {
        if !visible.isEmpty {
            VStack(alignment: .leading, spacing: 10) {
                HStack {
                    Text(title)
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(WofinsTheme.primary)
                    Spacer()
                    NavigationLink {
                        ModulesHubView()
                    } label: {
                        Text("Semua")
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                    }
                }
                LazyVGrid(columns: [GridItem(.flexible(), spacing: 10), GridItem(.flexible(), spacing: 10)], spacing: 10) {
                    ForEach(visible) { item in
                        NavigationLink {
                            ModuleListView(item: item)
                        } label: {
                            VStack(alignment: .leading, spacing: 8) {
                                HStack {
                                    Image(systemName: item.iconName)
                                        .font(.system(size: 14, weight: .semibold))
                                        .foregroundStyle(item.isAllowed ? WofinsTheme.primary : WofinsTheme.muted)
                                    Spacer()
                                    if let badge = item.plan_badge, !item.isAllowed {
                                        Text(badge)
                                            .font(.poppins(.caption2, weight: .semibold))
                                            .foregroundStyle(WofinsTheme.primary)
                                    }
                                }
                                Text(item.title)
                                    .font(.poppins(.caption, weight: .semibold))
                                    .foregroundStyle(WofinsTheme.ink)
                                    .lineLimit(2)
                                    .minimumScaleFactor(0.85)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                Text(item.isAllowed ? "\(item.count ?? 0) data" : "Terkunci")
                                    .font(.poppins(.caption2))
                                    .foregroundStyle(WofinsTheme.muted)
                            }
                            .padding(12)
                            .frame(maxWidth: .infinity, minHeight: 88, alignment: .topLeading)
                            .moduleSurface()
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
            .padding(.horizontal, 16)
            .task { await load() }
        } else {
            Color.clear.frame(height: 0).task { await load() }
        }
    }

    private func load() async {
        do {
            items = try await appState.api.moduleCatalog()
        } catch {
            if !APILoadFailure.isCancellation(error) {
                items = []
            }
        }
    }
}

extension View {
    func moduleSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
            .overlay { RoundedRectangle(cornerRadius: 16, style: .continuous).stroke(WofinsTheme.border.opacity(0.75)) }
            .wofinsSoftShadow()
    }
}
