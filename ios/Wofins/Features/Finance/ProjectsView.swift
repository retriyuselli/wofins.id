import PhotosUI
import SwiftUI
import UniformTypeIdentifiers
import UIKit

struct ProjectsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var projects: [FinanceProjectItem] = []
    @State private var meta: FinanceProjectMeta?
    @State private var selectedFilter: ProjectFilter = .all
    @State private var searchText = ""
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showCreateProspect = false
    @State private var showCreateProject = false
    @State private var section: SalesWorkspace = .projects
    @State private var selectedProspectFilter: ProspectFilter = .all
    @State private var prospects: [FinanceProspectItem] = []
    @State private var prospectMeta: FinanceProspectMeta?
    @State private var listPage = 1
    @State private var isLoadingMore = false
    @State private var loadGeneration = 0
    @State private var orderWidgets: [FinanceOrderOverviewWidget] = []
    @State private var isLoadingOverview = false
    @State private var showAllOrderWidgets = false

    private enum SalesWorkspace: String, CaseIterable, Identifiable {
        case projects, prospects

        var id: String { rawValue }

        var title: String {
            switch self {
            case .projects: return "Proyek"
            case .prospects: return "Prospek"
            }
        }
    }

    private enum ProjectFilter: String, CaseIterable, Identifiable {
        case all, pending, processing, done, cancelled

        var id: String { rawValue }

        var label: String {
            switch self {
            case .all: return "All"
            case .pending: return "Pending"
            case .processing: return "Processing"
            case .done: return "Done"
            case .cancelled: return "Cancelled"
            }
        }

        var apiValue: String? { self == .all ? nil : rawValue }
    }

    private enum ProspectFilter: String, CaseIterable, Identifiable {
        case all, no_order, pending, processing, done, cancelled

        var id: String { rawValue }

        var label: String {
            switch self {
            case .all: return "Semua"
            case .no_order: return "Hangat"
            case .pending: return "Pending"
            case .processing: return "Berjalan"
            case .done: return "Selesai"
            case .cancelled: return "Batal"
            }
        }

        var apiValue: String? { self == .all ? nil : rawValue }
    }

    private var isProspects: Bool { section == .prospects }

    private var visibleProjects: [FinanceProjectItem] {
        let keyword = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !keyword.isEmpty else { return projects }

        return projects.filter {
            $0.displayName.localizedCaseInsensitiveContains(keyword)
                || ($0.number?.localizedCaseInsensitiveContains(keyword) ?? false)
                || ($0.account_manager?.localizedCaseInsensitiveContains(keyword) ?? false)
        }
    }

    private var visibleProspects: [FinanceProspectItem] {
        let keyword = searchText.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !keyword.isEmpty else { return prospects }

        return prospects.filter {
            $0.displayName.localizedCaseInsensitiveContains(keyword)
                || ($0.coupleLabel?.localizedCaseInsensitiveContains(keyword) ?? false)
                || ($0.venue?.localizedCaseInsensitiveContains(keyword) ?? false)
                || ($0.phone?.localizedCaseInsensitiveContains(keyword) ?? false)
                || ($0.account_manager?.localizedCaseInsensitiveContains(keyword) ?? false)
        }
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                header

                if appState.allows(.projects) {
                    ScrollView(showsIndicators: false) {
                        LazyVStack(spacing: 16) {
                            sectionSwitcher
                            if !isProspects {
                                orderOverviewSection
                            }
                            searchBar
                            filterBar
                            if isProspects {
                                overviewCards
                            }
                            content
                        }
                        .padding(.top, 16)
                        .padding(.bottom, 28)
                    }
                    .refreshable { await load(reset: true) }
                    .scrollDismissesKeyboard(.immediately)
                } else {
                    PlanLockedView(feature: .projects)
                    Spacer()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .wofinsHidesNavigationBar()
            .task {
                guard appState.allows(.projects) else { return }
                await load()
            }
            .fullScreenCover(isPresented: $showCreateProspect) {
                CreateProspectView {
                    selectedProspectFilter = .all
                    Task { await load() }
                }
                .environmentObject(appState)
            }
            .fullScreenCover(isPresented: $showCreateProject) {
                CreateProjectView {
                    selectedFilter = .all
                    Task { await load() }
                }
                .environmentObject(appState)
            }
        }
    }

    private var header: some View {
        compactHeader(
            title: section.title,
            subtitle: appState.currentUser?.companyDisplayName ?? (isProspects ? "Calon klien" : "Kelola proyek"),
            icon: "plus"
        ) {
            if isProspects {
                showCreateProspect = true
            } else {
                showCreateProject = true
            }
        }
    }

    private var sectionSwitcher: some View {
        HStack(spacing: 6) {
            ForEach(SalesWorkspace.allCases) { item in
                Button {
                    selectSection(item)
                } label: {
                    Text(item.title)
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(section == item ? .white : WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 40)
                        .background(section == item ? WofinsTheme.primary : Color.clear, in: Capsule())
                }
                .buttonStyle(.plain)
            }
        }
        .padding(4)
        .background(WofinsTheme.card, in: Capsule())
        .overlay { Capsule().stroke(WofinsTheme.border.opacity(0.85), lineWidth: 1) }
        .padding(.horizontal, 16)
    }

    private func selectSection(_ item: SalesWorkspace) {
        guard section != item else { return }
        section = item
        searchText = ""
        errorMessage = nil
        Task { await load() }
    }

    private func compactHeader(title: String, subtitle: String, icon: String, action: @escaping () -> Void) -> some View {
        HStack(spacing: 12) {
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) {
                Text(title).font(.poppins(.headline, weight: .bold)).foregroundStyle(.white).lineLimit(1)
                Text(subtitle).font(.poppins(.caption)).foregroundStyle(.white.opacity(0.72)).lineLimit(1)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            Button(action: action) { Image(systemName: icon).font(.system(size: 17, weight: .bold)).foregroundStyle(.white).frame(width: 40, height: 40).background(.white.opacity(0.11), in: Circle()) }
                .disabled(!appState.allows(.projects))
                .fixedSize()
        }
        .padding(.horizontal, 20).padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var searchBar: some View {
        HStack(spacing: 12) {
            Image(systemName: "magnifyingglass")
                .font(.system(size: 20, weight: .medium))
                .foregroundStyle(WofinsTheme.muted)

            TextField(isProspects ? "Cari nama acara, pasangan, atau venue" : "Cari nama klien atau nomor proyek", text: $searchText)
                .font(.poppins(.subheadline))
                .textInputAutocapitalization(.never)
                .autocorrectionDisabled()

            if !searchText.isEmpty {
                Button {
                    searchText = ""
                } label: {
                    Image(systemName: "xmark.circle.fill")
                        .foregroundStyle(WofinsTheme.muted.opacity(0.7))
                }
            }
        }
        .padding(.horizontal, 16)
        .frame(height: 56)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 17, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 17, style: .continuous)
                .stroke(WofinsTheme.border, lineWidth: 1)
        }
        .wofinsSoftShadow()
        .padding(.horizontal, 16)
    }

    private var filterBar: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 2) {
                if isProspects {
                    ForEach(ProspectFilter.allCases) { filter in
                        filterChip(
                            title: filter.label,
                            selected: selectedProspectFilter == filter
                        ) {
                            guard selectedProspectFilter != filter else { return }
                            selectedProspectFilter = filter
                            Task { await load() }
                        }
                    }
                } else {
                    ForEach(ProjectFilter.allCases) { filter in
                        filterChip(
                            title: filter.label,
                            selected: selectedFilter == filter
                        ) {
                            guard selectedFilter != filter else { return }
                            selectedFilter = filter
                            Task { await load() }
                        }
                    }
                }
            }
            .padding(4)
            .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 16, style: .continuous)
                    .stroke(WofinsTheme.border.opacity(0.85), lineWidth: 1)
            }
            .padding(.horizontal, 16)
        }
    }

    private func filterChip(title: String, selected: Bool, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            Text(title)
                .font(.poppins(.subheadline, weight: selected ? .semibold : .regular))
                .foregroundStyle(selected ? Color(red: 0.00, green: 0.48, blue: 1.00) : Color(red: 0.42, green: 0.42, blue: 0.45))
                .lineLimit(1)
                .fixedSize()
                .padding(.horizontal, 14)
                .padding(.vertical, 8)
                .background {
                    if selected {
                        RoundedRectangle(cornerRadius: 10, style: .continuous)
                            .fill(Color(red: 0.96, green: 0.96, blue: 0.97))
                    }
                }
        }
        .buttonStyle(.plain)
    }

    private var orderWidgetDisplayKeys: [String] {
        [
            "new_projects_month",
            "monthly_revenue",
            "net_received_processing",
            "agreement_files",
            "customer_expenses",
            "contract_docs",
            "total_revenue",
            "total_expenses",
        ]
    }

    private var primaryOrderWidgetKeys: [String] {
        Array(orderWidgetDisplayKeys.prefix(4))
    }

    private var visibleOrderWidgets: [FinanceOrderOverviewWidget] {
        let ordered = orderWidgetDisplayKeys.compactMap { key in
            orderWidgets.first(where: { $0.key == key })
        }
        let remaining = orderWidgets.filter { widget in
            !orderWidgetDisplayKeys.contains(widget.key)
        }
        let all = ordered + remaining
        guard !showAllOrderWidgets else { return all }
        let prioritized = primaryOrderWidgetKeys.compactMap { key in
            all.first(where: { $0.key == key })
        }
        return prioritized.isEmpty ? Array(all.prefix(4)) : prioritized
    }

    private var orderOverviewSection: some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack(alignment: .firstTextBaseline) {
                Text("Ringkasan Orders")
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(WofinsTheme.primary)
                Spacer(minLength: 8)
                if orderWidgets.count > 4 {
                    Button {
                        withAnimation(.easeInOut(duration: 0.2)) {
                            showAllOrderWidgets.toggle()
                        }
                    } label: {
                        Text(showAllOrderWidgets ? "Sembunyikan" : "Lihat semua")
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(.horizontal, 16)

            if isLoadingOverview && orderWidgets.isEmpty {
                ProgressView("Memuat ringkasan…")
                    .frame(maxWidth: .infinity)
                    .padding(20)
                    .projectSurface()
                    .padding(.horizontal, 16)
            } else if !orderWidgets.isEmpty {
                LazyVGrid(
                    columns: [
                        GridItem(.flexible(), spacing: 10),
                        GridItem(.flexible(), spacing: 10),
                    ],
                    spacing: 10
                ) {
                    ForEach(visibleOrderWidgets) { widget in
                        orderWidgetCard(widget)
                    }
                }
                .padding(.horizontal, 16)
            }
        }
    }

    private func orderWidgetCard(_ widget: FinanceOrderOverviewWidget) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(widget.title)
                .font(.poppins(.caption2, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
                .lineLimit(2)
                .minimumScaleFactor(0.85)
                .fixedSize(horizontal: false, vertical: true)

            Text(widget.value)
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(2)
                .minimumScaleFactor(0.7)
                .fixedSize(horizontal: false, vertical: true)

            if let description = widget.description, !description.isEmpty {
                Text(description)
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
                    .lineLimit(2)
                    .minimumScaleFactor(0.85)
                    .fixedSize(horizontal: false, vertical: true)
            }
        }
        .padding(12)
        .frame(maxWidth: .infinity, minHeight: 108, alignment: .topLeading)
        .projectSurface()
    }

    private var overviewCards: some View {
        HStack(spacing: 12) {
            overviewCard(
                value: prospectMeta?.all_count ?? prospects.count,
                label: "Semua Prospek",
                icon: "person.2.fill",
                color: WofinsTheme.primary
            )
            overviewCard(
                value: prospectMeta?.warm_count ?? 0,
                label: "Masih Hangat",
                icon: "flame.fill",
                color: Color(red: 0.78, green: 0.55, blue: 0.00)
            )
        }
        .padding(.horizontal, 16)
    }

    private func overviewCard(value: Int, label: String, icon: String, color: Color) -> some View {
        HStack(spacing: 12) {
            Image(systemName: icon)
                .font(.system(size: 18, weight: .semibold))
                .foregroundStyle(color)
                .frame(width: 43, height: 43)
                .background(color.opacity(0.10), in: Circle())

            VStack(alignment: .leading, spacing: 2) {
                Text("\(value)")
                    .font(.poppins(.title2, weight: .bold))
                    .foregroundStyle(WofinsTheme.ink)
                Text(label)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .lineLimit(2)
                    .minimumScaleFactor(0.85)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .frame(maxWidth: .infinity, minHeight: 70, alignment: .leading)
        .padding(13)
        .projectSurface()
    }

    @ViewBuilder
    private var content: some View {
        if isProspects {
            prospectContent
        } else {
            projectContent
        }
    }

    @ViewBuilder
    private var projectContent: some View {
        if isLoading && projects.isEmpty {
            loadingRow("Memuat proyek…")
        } else if let errorMessage, projects.isEmpty {
            errorState(errorMessage)
        } else if visibleProjects.isEmpty {
            emptyState
        } else {
            ForEach(visibleProjects) { project in
                NavigationLink {
                    ProjectDetailView(projectId: project.id, preview: project)
                } label: {
                    projectCard(project)
                }
                .buttonStyle(.plain)
                .padding(.horizontal, 16)
            }
            if let errorMessage {
                errorState(errorMessage)
            }
            if canLoadMore {
                loadMoreButton
            }
        }
    }

    @ViewBuilder
    private var prospectContent: some View {
        if isLoading && prospects.isEmpty {
            loadingRow("Memuat prospek…")
        } else if let errorMessage, prospects.isEmpty {
            errorState(errorMessage)
        } else if visibleProspects.isEmpty {
            emptyState
        } else {
            ForEach(visibleProspects) { prospect in
                NavigationLink {
                    ProspectDetailView(prospectId: prospect.id, preview: prospect) {
                        Task { await load() }
                    }
                } label: {
                    prospectCard(prospect)
                }
                .buttonStyle(.plain)
                .padding(.horizontal, 16)
            }
            if let errorMessage {
                errorState(errorMessage)
            }
            if canLoadMore {
                loadMoreButton
            }
        }
    }

    private func loadingRow(_ text: String) -> some View {
        HStack(spacing: 12) {
            ProgressView().tint(WofinsTheme.primary)
            Text(text)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(18)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func projectCard(_ project: FinanceProjectItem) -> some View {
        let paid = project.paid_amount ?? 0
        let total = project.grand_total ?? 0
        let progress = total > 0 ? min(max(Double(paid) / Double(total), 0), 1) : 0
        let appearance = projectStatusAppearance(project.status)
        let showTotal = total != 0
        let showPaid = paid != 0
        let percent = Int(progress * 100)
        let showPercent = percent != 0
        let manager = project.account_manager?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        let dateRows = project.eventDateRows

        return VStack(alignment: .leading, spacing: 15) {
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

                VStack(alignment: .trailing, spacing: 8) {
                    HStack(spacing: 6) {
                        Circle().fill(appearance.color).frame(width: 7, height: 7)
                        Text(appearance.label)
                            .font(.poppins(.caption2, weight: .semibold))
                    }
                    .foregroundStyle(appearance.color)
                    .padding(.horizontal, 10)
                    .padding(.vertical, 7)
                    .background(appearance.color.opacity(0.11), in: Capsule())
                }
            }

            if !dateRows.isEmpty || !manager.isEmpty {
                VStack(alignment: .leading, spacing: 8) {
                    if !dateRows.isEmpty {
                        HStack(alignment: .top, spacing: 0) {
                            ForEach(Array(dateRows.enumerated()), id: \.element.label) { index, row in
                                if index > 0 {
                                    Divider()
                                        .frame(height: 32)
                                        .padding(.horizontal, 8)
                                }
                                VStack(alignment: .leading, spacing: 2) {
                                    Text(row.label)
                                        .font(.poppins(.caption2))
                                        .foregroundStyle(WofinsTheme.muted)
                                        .lineLimit(1)
                                    Text(row.compact)
                                        .font(.poppins(.caption, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.ink)
                                        .lineLimit(1)
                                        .minimumScaleFactor(0.65)
                                }
                                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                            }
                        }
                    }
                    if !manager.isEmpty {
                        Label("PIC: \(manager)", systemImage: "person.fill")
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                }
            }

            if showTotal || showPaid || showPercent {
                Divider()

                HStack(spacing: 12) {
                    if showTotal {
                        projectAmount("Nilai Proyek", project.grand_total)
                    }
                    if showTotal && showPaid {
                        Divider().frame(height: 38)
                    }
                    if showPaid {
                        projectAmount("Terbayar", project.paid_amount)
                    }
                    Spacer(minLength: 2)
                    if showPercent {
                        Text("\(percent)%")
                            .font(.poppins(.caption, weight: .bold))
                            .foregroundStyle(appearance.color)
                            .padding(.horizontal, 10)
                            .padding(.vertical, 7)
                            .background(appearance.color.opacity(0.11), in: Capsule())
                    }
                }

                if showPercent {
                    GeometryReader { proxy in
                        ZStack(alignment: .leading) {
                            Capsule().fill(WofinsTheme.border)
                            Capsule()
                                .fill(WofinsTheme.primary)
                                .frame(width: proxy.size.width * progress)
                        }
                    }
                    .frame(height: 8)
                }
            }
        }
        .padding(17)
        .projectSurface()
    }

    private func prospectCard(_ prospect: FinanceProspectItem) -> some View {
        let appearance = prospectStatusAppearance(prospect.order_status)
        let manager = prospect.account_manager?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        let dateRows = prospect.eventDateRows
        let offer = prospect.total_penawaran ?? 0

        return VStack(alignment: .leading, spacing: 15) {
            HStack(alignment: .top, spacing: 12) {
                Text(prospect.initials)
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(appearance.color)
                    .frame(width: 50, height: 50)
                    .background(appearance.color.opacity(0.11), in: Circle())

                VStack(alignment: .leading, spacing: 3) {
                    Text(prospect.displayName)
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(WofinsTheme.ink)
                        .lineLimit(2)
                        .minimumScaleFactor(0.8)
                    if let couple = prospect.coupleLabel {
                        Text(couple)
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                            .lineLimit(1)
                    } else if let venue = prospect.venue, !venue.isEmpty {
                        Text(venue)
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                            .lineLimit(1)
                    }
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

                VStack(alignment: .trailing, spacing: 8) {
                    HStack(spacing: 6) {
                        Circle().fill(appearance.color).frame(width: 7, height: 7)
                        Text(appearance.label)
                            .font(.poppins(.caption2, weight: .semibold))
                    }
                    .foregroundStyle(appearance.color)
                    .padding(.horizontal, 10)
                    .padding(.vertical, 7)
                    .background(appearance.color.opacity(0.11), in: Capsule())

                    Image(systemName: "chevron.right")
                        .font(.system(size: 12, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                }
            }

            if let venue = prospect.venue, !venue.isEmpty, prospect.coupleLabel != nil {
                Label(venue, systemImage: "mappin.and.ellipse")
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .lineLimit(2)
                    .fixedSize(horizontal: false, vertical: true)
            }

            if !dateRows.isEmpty || !manager.isEmpty {
                VStack(alignment: .leading, spacing: 8) {
                    if !dateRows.isEmpty {
                        HStack(alignment: .top, spacing: 0) {
                            ForEach(Array(dateRows.enumerated()), id: \.element.label) { index, row in
                                if index > 0 {
                                    Divider()
                                        .frame(height: 32)
                                        .padding(.horizontal, 8)
                                }
                                VStack(alignment: .leading, spacing: 2) {
                                    Text(row.label)
                                        .font(.poppins(.caption2))
                                        .foregroundStyle(WofinsTheme.muted)
                                        .lineLimit(1)
                                    Text(row.compact)
                                        .font(.poppins(.caption, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.ink)
                                        .lineLimit(1)
                                        .minimumScaleFactor(0.65)
                                }
                                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                            }
                        }
                    }
                    if !manager.isEmpty {
                        Label("PIC: \(manager)", systemImage: "person.fill")
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.muted)
                            .lineLimit(1)
                    }
                }
            }

            if offer != 0 {
                Divider()
                projectAmount("Penawaran", offer)
            }
        }
        .padding(17)
        .projectSurface()
    }

    private func projectAmount(_ title: String, _ amount: Int?) -> some View {
        VStack(alignment: .leading, spacing: 3) {
            Text(title)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.62)
        }
    }

    private func errorState(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label(isProspects ? "Prospek belum dapat dimuat" : "Proyek belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(WofinsTheme.danger)
            Text(message)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            Button("Coba lagi") { Task { await load() } }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(.white)
                .padding(.horizontal, 16)
                .padding(.vertical, 10)
                .background(WofinsTheme.primary, in: Capsule())
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(18)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var emptyState: some View {
        VStack(spacing: 12) {
            Image(systemName: searchText.isEmpty ? (isProspects ? "person.badge.plus" : "folder.badge.plus") : "magnifyingglass")
                .font(.system(size: 34, weight: .medium))
                .foregroundStyle(WofinsTheme.primary)
            Text(emptyTitle)
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
            Text(emptySubtitle)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .multilineTextAlignment(.center)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 34)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var emptyTitle: String {
        if !searchText.isEmpty {
            return isProspects ? "Prospek tidak ditemukan" : "Proyek tidak ditemukan"
        }
        return isProspects ? "Belum ada prospek" : "Belum ada proyek"
    }

    private var emptySubtitle: String {
        if !searchText.isEmpty {
            return isProspects
                ? "Coba gunakan nama acara, pasangan, atau venue lain."
                : "Coba gunakan nama atau nomor proyek lain."
        }
        return isProspects
            ? "Calon klien baru akan muncul di halaman ini."
            : "Proyek baru akan muncul di halaman ini."
    }

    private var canLoadMore: Bool {
        if isProspects {
            return ListPaging.canLoadMore(current: prospectMeta?.current_page, last: prospectMeta?.last_page)
        }
        return ListPaging.canLoadMore(current: meta?.current_page, last: meta?.last_page)
    }

    private var loadMoreButton: some View {
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
        .padding(.horizontal, 16)
        .padding(.top, 4)
    }

    private func load(reset: Bool = true) async {
        guard appState.allows(.projects) else { return }
        if reset {
            isLoading = true
            isLoadingOverview = !isProspects
            listPage = 1
        } else {
            guard canLoadMore, !isLoadingMore else { return }
            isLoadingMore = true
            listPage += 1
        }
        loadGeneration += 1
        let requestID = loadGeneration
        defer {
            if requestID == loadGeneration {
                isLoading = false
                isLoadingMore = false
                isLoadingOverview = false
            }
        }

        do {
            if isProspects {
                let response = try await appState.api.financeProspects(
                    status: selectedProspectFilter.apiValue,
                    perPage: 50,
                    page: listPage
                )
                guard requestID == loadGeneration else { return }
                if reset {
                    prospects = response.data
                } else {
                    let existing = Set(prospects.map(\.id))
                    prospects.append(contentsOf: response.data.filter { !existing.contains($0.id) })
                }
                prospectMeta = response.meta
            } else {
                async let overviewTask: [FinanceOrderOverviewWidget] = {
                    do { return try await appState.api.financeProjectsOverview() }
                    catch { return orderWidgets }
                }()
                let response = try await appState.api.financeProjects(
                    status: selectedFilter.apiValue,
                    perPage: 50,
                    page: listPage
                )
                let widgets = await overviewTask
                guard requestID == loadGeneration else { return }
                if reset {
                    projects = response.data
                    orderWidgets = widgets
                } else {
                    let existing = Set(projects.map(\.id))
                    projects.append(contentsOf: response.data.filter { !existing.contains($0.id) })
                }
                meta = response.meta
            }
            errorMessage = nil
        } catch {
            guard requestID == loadGeneration else { return }
            if !reset { listPage = max(1, listPage - 1) }
            if let message = APILoadFailure.userMessage(for: error) {
                errorMessage = message
            }
        }
    }
}
