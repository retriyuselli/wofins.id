import PhotosUI
import SwiftUI
import UniformTypeIdentifiers
import UIKit
import WebKit

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

    private var eventsThisMonth: Int {
        let calendar = Calendar.current
        return projects.filter { project in
            guard let date = project.eventDate else { return false }
            return calendar.isDate(date, equalTo: Date(), toGranularity: .month)
                && calendar.isDate(date, equalTo: Date(), toGranularity: .year)
        }.count
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
                            ModuleShortcutsView(
                                keys: ["products", "vendors", "categories", "nota_dinas", "simulasi"],
                                title: "Katalog & operasional"
                            )
                            searchBar
                            filterBar
                            overviewCards
                            content
                        }
                        .padding(.top, 16)
                        .padding(.bottom, 28)
                    }
                    .refreshable { await load() }
                    .scrollDismissesKeyboard(.immediately)
                } else {
                    PlanLockedView(feature: .projects)
                    Spacer()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .toolbar(.hidden, for: .navigationBar)
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
        .shadow(color: WofinsTheme.primary.opacity(0.06), radius: 12, y: 5)
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

    private var overviewCards: some View {
        HStack(spacing: 12) {
            if isProspects {
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
            } else {
                overviewCard(
                    value: meta?.total ?? projects.count,
                    label: "Proyek Aktif",
                    icon: "list.clipboard.fill",
                    color: WofinsTheme.primary
                )
                overviewCard(
                    value: eventsThisMonth,
                    label: "Acara Bulan Ini",
                    icon: "calendar",
                    color: Color(red: 0.78, green: 0.55, blue: 0.00)
                )
            }
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
        } else if let errorMessage {
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
        }
    }

    @ViewBuilder
    private var prospectContent: some View {
        if isLoading && prospects.isEmpty {
            loadingRow("Memuat prospek…")
        } else if let errorMessage {
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
                Text(project.initials)
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(appearance.color)
                    .frame(width: 50, height: 50)
                    .background(appearance.color.opacity(0.11), in: Circle())

                VStack(alignment: .leading, spacing: 3) {
                    Text(project.displayName)
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(WofinsTheme.ink)
                        .lineLimit(2)
                    Text(project.number ?? "Proyek #\(project.id)")
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.muted)
                }

                Spacer(minLength: 4)

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
                                .fill(
                                    LinearGradient(
                                        colors: [WofinsTheme.primary, WofinsTheme.primaryLight, WofinsTheme.yellow],
                                        startPoint: .leading,
                                        endPoint: .trailing
                                    )
                                )
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

    private func load() async {
        guard appState.allows(.projects) else { return }
        isLoading = true
        defer { isLoading = false }

        do {
            if isProspects {
                let response = try await appState.api.financeProspects(
                    status: selectedProspectFilter.apiValue,
                    perPage: 50
                )
                prospects = response.data
                prospectMeta = response.meta
            } else {
                let response = try await appState.api.financeProjects(
                    status: selectedFilter.apiValue,
                    perPage: 50
                )
                projects = response.data
                meta = response.meta
            }
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct CreateProjectView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    var project: FinanceProjectDetail? = nil
    var onSaved: () -> Void = {}

    @State private var options: ProjectFormOptions?
    @State private var isLoading = true
    @State private var isSaving = false
    @State private var errorMessage: String?
    @State private var step: Step = .info

    @State private var number = ""
    @State private var prospectId = 0
    @State private var userId = 0
    @State private var employeeId = 0
    @State private var noKontrak = ""
    @State private var paxText = "1000"
    @State private var status = "pending"
    @State private var note = ""
    @State private var items: [DraftItem] = [DraftItem()]
    @State private var payments: [DraftPayment] = []
    @State private var contractPDF: PickedFile?
    @State private var agreementPDF: PickedFile?
    @State private var pdfTarget: PDFTarget = .contract
    @State private var isPickingPDF = false
    @State private var paxEdited = false
    @FocusState private var focused: Field?

    private enum PDFTarget {
        case contract, agreement
    }

    private enum Field: Hashable {
        case kontrak, pax, note, quantity(UUID), keterangan(UUID), nominal(UUID)
    }

    private enum Step: Int, CaseIterable, Identifiable {
        case info, products, payments, summary

        var id: Int { rawValue }

        var title: String {
            switch self {
            case .info: return "Info"
            case .products: return "Paket"
            case .payments: return "Bayar"
            case .summary: return "Ringkas"
            }
        }

        var fullTitle: String {
            switch self {
            case .info: return "Informasi Proyek"
            case .products: return "Paket Dipesan"
            case .payments: return "Data Pembayaran"
            case .summary: return "Informasi Keuangan"
            }
        }
    }

    private struct DraftItem: Identifiable {
        let id = UUID()
        var productId = 0
        var quantity = 1
    }

    private struct DraftPayment: Identifiable {
        let id = UUID()
        var recordId: Int? = nil
        var keterangan = ""
        var paymentMethodId = 0
        var nominalText = ""
        var kategori = "uang_masuk"
        var date = Date()
        var proofItem: PhotosPickerItem?
        var proofName: String?
        var proofData: Data?
        var existingProofName: String?
    }

    private struct PickedFile {
        let name: String
        let data: Data
    }

    private var selectedProspect: ProjectFormProspectOption? {
        options?.prospects.first { $0.id == prospectId }
    }

    private var paxValue: Int { max(1, Int(paxText.filter(\.isNumber)) ?? 1) }

    private var filledItems: [(product: ProjectFormProductOption, quantity: Int)] {
        items.compactMap { item in
            guard let product = options?.products.first(where: { $0.id == item.productId }) else { return nil }
            return (product, max(1, item.quantity))
        }
    }

    private var totalPrice: Int {
        filledItems.reduce(0) { $0 + $1.product.unitPrice * $1.quantity }
    }

    private var totalPengurangan: Int {
        filledItems.reduce(0) { $0 + ($1.product.pengurangan ?? 0) * $1.quantity }
    }

    private var totalPenambahan: Int {
        filledItems.reduce(0) { $0 + ($1.product.penambahan_publish ?? 0) * $1.quantity }
    }

    private var grandTotal: Int { totalPrice + totalPenambahan - totalPengurangan }

    private var paidAmount: Int {
        payments.reduce(0) { sum, payment in
            guard payment.kategori != "uang_keluar" else { return sum }
            return sum + (Int(payment.nominalText.filter(\.isNumber)) ?? 0)
        }
    }

    private var remaining: Int { grandTotal - paidAmount }

    private var closingDate: Date? {
        payments.map(\.date).min()
    }

    private var isEditing: Bool { project != nil }

    private var hasExistingContract: Bool { project?.has_doc_kontrak == true || project?.doc_kontrak_url != nil }
    private var hasExistingAgreement: Bool { project?.has_agreement_product == true }

    private var canCreate: Bool { isEditing || (options?.can_create ?? true) }

    private var infoReady: Bool {
        prospectId > 0
            && userId > 0
            && employeeId > 0
            && !trimmed(noKontrak).isEmpty
            && paxValue >= 1
            && (contractPDF != nil || isEditing)
            && (agreementPDF != nil || isEditing)
            && !status.isEmpty
    }

    private var productsReady: Bool { !filledItems.isEmpty }

    private var paymentsReady: Bool {
        payments.allSatisfy { payment in
            !trimmed(payment.keterangan).isEmpty
                && payment.paymentMethodId > 0
                && (Int(payment.nominalText.filter(\.isNumber)) ?? 0) > 0
        }
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            stepBar
            if isLoading {
                Spacer()
                ProgressView("Memuat form…")
                    .font(.poppins(.subheadline))
                    .tint(WofinsTheme.primary)
                Spacer()
            } else {
                ScrollView(showsIndicators: false) {
                    VStack(spacing: 16) {
                        if let errorMessage {
                            Text(errorMessage)
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.danger)
                                .fixedSize(horizontal: false, vertical: true)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .padding(14)
                                .background(WofinsTheme.danger.opacity(0.08), in: RoundedRectangle(cornerRadius: 16, style: .continuous))
                        }
                        switch step {
                        case .info: infoSection
                        case .products: productsSection
                        case .payments: paymentsSection
                        case .summary: summarySection
                        }
                    }
                    .padding(.horizontal, 16)
                    .padding(.top, 16)
                    .padding(.bottom, 8)
                }
                .scrollDismissesKeyboard(.immediately)
                footer
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsDismissKeyboardOnOutsideTap()
        .wofinsKeyboardDoneButton()
        .toolbar(.hidden, for: .navigationBar)
        .task { await loadOptions() }
        .fileImporter(isPresented: $isPickingPDF, allowedContentTypes: [.pdf]) { result in
            switch pdfTarget {
            case .contract: handlePDF(result, assign: $contractPDF)
            case .agreement: handlePDF(result, assign: $agreementPDF)
            }
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            Button { dismiss() } label: {
                Image(systemName: "xmark")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Tutup")
            .fixedSize()

            VStack(alignment: .leading, spacing: 2) {
                Text(isEditing ? "Edit Proyek" : "Tambah Proyek")
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                    .lineLimit(1)
                Text(step.fullTitle)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
        .contentShape(Rectangle())
        .onTapGesture { dismissKeyboard() }
    }

    private var stepBar: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                ForEach(Step.allCases) { item in
                    Button { step = item } label: {
                        Text(item.title)
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(step == item ? .white : WofinsTheme.primary)
                            .padding(.horizontal, 14)
                            .frame(height: 34)
                            .background(step == item ? WofinsTheme.primary : WofinsTheme.card, in: Capsule())
                    }
                    .buttonStyle(.plain)
                    .fixedSize()
                }
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 10)
        }
        .background(WofinsTheme.background)
    }

    private var infoSection: some View {
        VStack(spacing: 16) {
            formSection("Informasi Proyek") {
                readOnlyField("Nomor Proyek", number.isEmpty ? "Otomatis" : number)
                helper("Otomatis dari inisial WO perusahaan (\(options?.number_prefix ?? "-")).")

                if isEditing {
                    readOnlyField("Prospek", selectedProspect?.title ?? project?.prospect?.name_event ?? "Prospek terkunci")
                    helper("Prospek tidak dapat diganti setelah proyek dibuat.")
                } else {
                    menuPicker(
                        "Prospek",
                        selection: $prospectId,
                        placeholder: "Pilih prospek Hangat",
                        options: (options?.prospects ?? []).map { ($0.id, $0.title) }
                    )
                    helper("Hanya prospek yang belum punya proyek. Prospek mengisi nama acara dan slug.")

                    if options?.prospects.isEmpty == true {
                        helper("Belum ada prospek Hangat. Tutup form ini, buat prospek di tab Prospek, lalu kembali.")
                    }
                }

                readOnlyField("Nama Acara", selectedProspect?.title ?? "Mengikuti prospek")
                menuPicker(
                    "Account Manager",
                    selection: $userId,
                    placeholder: "Pilih Account Manager",
                    options: (options?.account_managers ?? []).map { ($0.id, $0.title) }
                )
                helper(options?.single_seat == true
                       ? "Paket 1 seat: pilih akun Anda sendiri sebagai penanggung jawab proyek."
                       : "Pilih Account Manager dari tim Anda.")

                readOnlyField("Slug", slugValue)
                menuPicker(
                    "Event Manager",
                    selection: $employeeId,
                    placeholder: "Pilih Event Manager",
                    options: (options?.event_managers ?? []).map { ($0.id, $0.title) }
                )
                helper(options?.single_seat == true
                       ? "Paket 1 seat: pilih akun Anda sendiri sebagai Event Manager."
                       : "Pilih Event Manager dari tim Anda.")

                field("No. Kontrak", text: $noKontrak, focus: .kontrak)
                helper("Inisial kontrak company: \(options?.contract_prefix ?? "KKP").")
                field("Pax", text: $paxText, focus: .pax, keyboard: .numberPad)
                    .onChange(of: paxText) { _, _ in paxEdited = true }

                fileButton(
                    "Upload Kontrak",
                    file: contractPDF,
                    existingName: isEditing && hasExistingContract ? (project?.doc_kontrak_name ?? "Dokumen kontrak.pdf") : nil,
                    required: !isEditing
                ) {
                    pdfTarget = .contract
                    isPickingPDF = true
                }
                helper("PDF, kontrak sudah ditandatangani semua pihak.")
                fileButton(
                    "File Persetujuan Produk",
                    file: agreementPDF,
                    existingName: isEditing && hasExistingAgreement ? "Persetujuan produk.pdf" : nil,
                    required: !isEditing
                ) {
                    pdfTarget = .agreement
                    isPickingPDF = true
                }
                helper("PDF, file persetujuan produk sudah ditandatangani.")

                VStack(alignment: .leading, spacing: 8) {
                    Text("Status Pesanan")
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                    LazyVGrid(columns: [GridItem(.flexible(), spacing: 8), GridItem(.flexible(), spacing: 8)], spacing: 8) {
                        ForEach(options?.statuses ?? [
                            .init(value: "pending", label: "Pending"),
                            .init(value: "processing", label: "Processing"),
                            .init(value: "done", label: "Done"),
                            .init(value: "cancelled", label: "Cancelled"),
                        ]) { item in
                            Button { status = item.value } label: {
                                Text(item.label)
                                    .font(.poppins(.caption, weight: .semibold))
                                    .foregroundStyle(status == item.value ? .white : WofinsTheme.primary)
                                    .frame(maxWidth: .infinity)
                                    .frame(height: 40)
                                    .background(status == item.value ? WofinsTheme.primary : WofinsTheme.background, in: Capsule())
                            }
                            .buttonStyle(.plain)
                        }
                    }
                    helper("Status Done: Finance hanya bisa view, Super Admin bisa edit.")
                }

                field("Keterangan Tambahan", text: $note, focus: .note, axis: true)
            }
        }
    }

    private var productsSection: some View {
        VStack(spacing: 16) {
            formSection("Product dipesan") {
                if options?.products.isEmpty == true {
                    helper("Belum ada paket dengan stok lebih dari 1.")
                }

                ForEach(Array(items.enumerated()), id: \.element.id) { index, item in
                    itemCard(index: index, item: item)
                }

                Button {
                    items.append(DraftItem())
                } label: {
                    Label("Tambah Paket", systemImage: "plus")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 44)
                }
                .buttonStyle(.plain)
                .disabled(availableProducts(except: nil).isEmpty)

                Divider()
                moneyLine("Total Paket Awal", totalPrice)
                moneyLine("Promo", 0)
                moneyLine("Penambahan Harga", totalPenambahan)
                moneyLine("Total Pengurangan dari Produk", totalPengurangan)
                helper("Nilai keuangan dihitung otomatis dari paket yang dipilih.")
            }
        }
    }

    private var paymentsSection: some View {
        VStack(spacing: 16) {
            formSection("Jika Ada Pembayaran") {
                helper("Opsional. Isi jika klien sudah transfer saat closing.")
                ForEach(Array(payments.enumerated()), id: \.element.id) { index, payment in
                    paymentCard(index: index, payment: payment)
                }

                Button {
                    var row = DraftPayment()
                    row.keterangan = "\(payments.count + 1)"
                    if let first = options?.payment_methods.first, options?.payment_methods.count == 1 {
                        row.paymentMethodId = first.id
                    }
                    payments.append(row)
                } label: {
                    Label("Tambah Pembayaran", systemImage: "plus")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 44)
                }
                .buttonStyle(.plain)
                .disabled(options?.payment_methods.isEmpty == true)

                if options?.payment_methods.isEmpty == true {
                    helper("Belum ada rekening/metode pembayaran.")
                }
            }
        }
    }

    private var summarySection: some View {
        VStack(spacing: 16) {
            formSection("Informasi Keuangan") {
                moneyLine("Uang dibayar", paidAmount)
                helper("Pembayaran klien ke rekening perusahaan")
                moneyLine("Grand Total", grandTotal)
                helper("Grand Total = Total Paket + Penambahan - Promo - Pengurangan")
                moneyLine("Pengeluaran", 0)
                helper("Total pembayaran ke vendor. Dicatat setelah proyek tersimpan.")
                moneyLine("Sisa Pembayaran", remaining)
                helper("Sisa yang masih harus dibayar klien")
                moneyLine("Laba Kotor", grandTotal)
                helper("Grand total - Pembayaran ke vendor")
                moneyLine("Uang Diterima", paidAmount)
                helper("Uang yang sudah diterima dari klien")

                readOnlyField(
                    "Closing Date",
                    closingDate.map { Self.dayFormatter.string(from: $0) } ?? "Otomatis dari pembayaran pertama"
                )

                HStack {
                    Text("Lunas / Belum")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    Text(remaining <= 0 && grandTotal > 0 ? "Lunas" : "Belum")
                        .font(.poppins(.caption, weight: .bold))
                        .foregroundStyle(.white)
                        .padding(.horizontal, 10)
                        .frame(height: 28)
                        .background(remaining <= 0 && grandTotal > 0 ? WofinsTheme.primary : WofinsTheme.muted, in: Capsule())
                }
                helper("Otomatis lunas jika sisa pembayaran ≤ 0")
            }

            formSection("Pengeluaran") {
                helper("Catat pengeluaran ke vendor setelah proyek tersimpan, lewat detail proyek. Setiap vendor hanya boleh dipilih sekali per order.")
                readOnlyField("Ringkasan", "Total pengeluaran: 0 item | Total nominal: Rp 0")
            }

            formSection("Riwayat Modifikasi") {
                helper("Riwayat dibuat, diubah, dan editor muncul setelah proyek disimpan.")
            }

            if let quota = options?.quota_message, !quota.isEmpty {
                helper(quota)
            }
        }
    }

    private var footer: some View {
        VStack(spacing: 8) {
            if step != .info {
                Button {
                    if let previous = Step(rawValue: step.rawValue - 1) { step = previous }
                } label: {
                    Text("Kembali")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 48)
                        .background(WofinsTheme.card, in: Capsule())
                        .overlay { Capsule().stroke(WofinsTheme.border, lineWidth: 1) }
                }
                .buttonStyle(.plain)
            }

            Button {
                goNextOrSave()
            } label: {
                HStack(spacing: 8) {
                    if isSaving { ProgressView().tint(.white) }
                    Text(step == .summary ? (isEditing ? "Simpan Perubahan" : "Simpan Proyek") : "Lanjut")
                        .font(.poppins(.subheadline, weight: .bold))
                }
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .frame(height: 48)
                .background(WofinsTheme.primary, in: Capsule())
            }
            .buttonStyle(.plain)
            .disabled(isSaving)
        }
        .padding(.horizontal, 16)
        .padding(.top, 8)
        .padding(.bottom, 8)
        .background(WofinsTheme.background.ignoresSafeArea(edges: .bottom))
    }

    private func itemCard(index: Int, item: DraftItem) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Paket \(index + 1)")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer()
                if items.count > 1 {
                    Button("Hapus") { items.removeAll { $0.id == item.id } }
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.danger)
                }
            }

            menuPicker(
                "Product",
                selection: bindingItem(item.id, \.productId),
                placeholder: "Pilih paket",
                options: availableProducts(except: item.productId).map { ($0.id, $0.title) }
            )

            if let product = options?.products.first(where: { $0.id == item.productId }) {
                helper("Harga: \(MoneyFormat.idr(product.unitPrice)) · Stok: \(product.stock ?? 0) · Pax: \(product.pax ?? 0)")
                stepper("Quantity", value: bindingItem(item.id, \.quantity), min: 1, max: max(1, product.stock ?? 1))
                readOnlyField("Unit Price", MoneyFormat.idr(product.unitPrice))
            }
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
        .onChange(of: item.productId) { _, productId in
            guard !paxEdited, let product = options?.products.first(where: { $0.id == productId }), (product.pax ?? 0) > 0 else { return }
            if filledItems.count <= 1 {
                paxText = String(product.pax ?? paxValue)
            }
        }
    }

    private func paymentCard(index: Int, payment: DraftPayment) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Pembayaran \(index + 1)")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer()
                Button("Hapus") { payments.removeAll { $0.id == payment.id } }
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.danger)
            }

            field("Keterangan", text: bindingPayment(payment.id, \.keterangan), focus: .keterangan(payment.id))
            helper("Contoh: 1, DP, Pelunasan")
            menuPicker(
                "Metode Pembayaran",
                selection: bindingPayment(payment.id, \.paymentMethodId),
                placeholder: "Pilih metode",
                options: (options?.payment_methods ?? []).map { ($0.id, $0.title) }
            )
            field("Nominal", text: bindingPayment(payment.id, \.nominalText), focus: .nominal(payment.id), keyboard: .numberPad, prefix: "Rp")
            menuPicker(
                "Tipe Transaksi",
                selection: bindingPayment(payment.id, \.kategori),
                placeholder: "Pilih tipe",
                options: [("uang_masuk", "Uang Masuk"), ("uang_keluar", "Uang Keluar")]
            )
            DatePicker("Tgl. Bayar", selection: bindingPayment(payment.id, \.date), displayedComponents: .date)
                .environment(\.locale, Locale(identifier: "id_ID"))
                .font(.poppins(.subheadline))
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

            PhotosPicker(selection: bindingPayment(payment.id, \.proofItem), matching: .images) {
                HStack {
                    Image(systemName: "photo")
                    Text(payment.proofName ?? payment.existingProofName ?? "Payment Proof (opsional)")
                        .lineLimit(1)
                    Spacer()
                }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .padding(12)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
            }
            .task(id: payment.proofItem?.itemIdentifier) {
                await loadProof(id: payment.id, item: payment.proofItem)
            }
            helper("Max 1MB. JPG atau PNG.")
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    private var slugValue: String {
        let name = selectedProspect?.title ?? ""
        let slug = name.lowercased()
            .replacingOccurrences(of: " ", with: "-")
            .replacingOccurrences(of: "&", with: "")
        return slug.isEmpty ? "mengikuti-nama-acara" : slug
    }

    private func formSection<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(title)
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
            content()
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(16)
        .projectSurface()
    }

    private func field(
        _ title: String,
        text: Binding<String>,
        focus: Field,
        keyboard: UIKeyboardType = .default,
        prefix: String? = nil,
        axis: Bool = false
    ) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            HStack(alignment: .center, spacing: 10) {
                if let prefix {
                    Text(prefix)
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(width: 42, alignment: .leading)
                        .lineLimit(1)
                }
                Group {
                    if axis {
                        TextField("", text: text, axis: .vertical)
                            .lineLimit(3...6)
                    } else {
                        TextField("", text: text)
                            .lineLimit(1)
                    }
                }
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .keyboardType(keyboard)
                .textInputAutocapitalization(keyboard == .numberPad ? .never : .words)
                .autocorrectionDisabled(keyboard == .numberPad)
                .focused($focused, equals: focus)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            }
            .padding(.horizontal, 14)
            .padding(.vertical, 12)
            .frame(minHeight: 48, alignment: .center)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .stroke(WofinsTheme.border, lineWidth: 1)
            }
        }
    }

    private func readOnlyField(_ title: String, _ value: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Text(value)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding(.horizontal, 14)
                .padding(.vertical, 12)
                .frame(minHeight: 48, alignment: .center)
                .background(WofinsTheme.background.opacity(0.65), in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
        }
    }

    private func helper(_ text: String) -> some View {
        Text(text)
            .font(.poppins(.caption2))
            .foregroundStyle(WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
    }

    private func moneyLine(_ title: String, _ amount: Int) -> some View {
        HStack(alignment: .firstTextBaseline) {
            Text(title)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
    }

    private func menuPicker<T: Hashable>(
        _ title: String,
        selection: Binding<T>,
        placeholder: String,
        options: [(T, String)]
    ) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Menu {
                ForEach(Array(options.enumerated()), id: \.offset) { _, option in
                    Button(option.1) { selection.wrappedValue = option.0 }
                }
            } label: {
                HStack {
                    Text(options.first { isEqual($0.0, selection.wrappedValue) }?.1 ?? placeholder)
                        .font(.poppins(.subheadline))
                        .foregroundStyle(options.contains { isEqual($0.0, selection.wrappedValue) } ? WofinsTheme.ink : WofinsTheme.muted)
                        .lineLimit(1)
                        .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    Image(systemName: "chevron.up.chevron.down")
                        .font(.caption.weight(.semibold))
                        .foregroundStyle(WofinsTheme.muted)
                }
                .padding(.horizontal, 14)
                .frame(minHeight: 48)
                .frame(maxWidth: .infinity)
                .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
            }
        }
    }

    private func stepper(_ title: String, value: Binding<Int>, min: Int, max: Int) -> some View {
        HStack {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Spacer()
            Button {
                value.wrappedValue = Swift.max(min, value.wrappedValue - 1)
            } label: {
                Image(systemName: "minus")
                    .frame(width: 36, height: 36)
                    .background(WofinsTheme.card, in: Circle())
            }
            .buttonStyle(.plain)
            Text("\(value.wrappedValue)")
                .font(.poppins(.subheadline, weight: .bold))
                .frame(minWidth: 28)
            Button {
                value.wrappedValue = Swift.min(max, value.wrappedValue + 1)
            } label: {
                Image(systemName: "plus")
                    .frame(width: 36, height: 36)
                    .background(WofinsTheme.card, in: Circle())
            }
            .buttonStyle(.plain)
        }
    }

    private func fileButton(_ title: String, file: PickedFile?, existingName: String? = nil, required: Bool, action: @escaping () -> Void) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(required ? "\(title) *" : title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Button(action: action) {
                HStack(spacing: 10) {
                    Image(systemName: "doc.fill")
                    Text(file?.name ?? existingName ?? "Pilih file PDF")
                        .lineLimit(1)
                    Spacer()
                }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .padding(12)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
            }
            .buttonStyle(.plain)
        }
    }

    private func goNextOrSave() {
        errorMessage = nil
        dismissKeyboard()
        switch step {
        case .info:
            guard infoReady else {
                errorMessage = missingInfoMessage()
                return
            }
            step = .products
        case .products:
            guard productsReady else {
                errorMessage = "Minimal satu paket harus dipilih."
                return
            }
            step = .payments
        case .payments:
            guard paymentsReady else {
                errorMessage = "Lengkapi keterangan, metode, nominal, dan tanggal setiap pembayaran, atau hapus baris kosong."
                return
            }
            step = .summary
        case .summary:
            Task { await save() }
        }
    }

    private func missingInfoMessage() -> String {
        if prospectId <= 0 { return "Prospek wajib dipilih." }
        if userId <= 0 { return "Account Manager wajib dipilih." }
        if employeeId <= 0 { return "Event Manager wajib dipilih." }
        if trimmed(noKontrak).isEmpty { return "Nomor kontrak wajib diisi." }
        if contractPDF == nil && !isEditing { return "File kontrak PDF wajib diunggah." }
        if agreementPDF == nil && !isEditing { return "File persetujuan produk PDF wajib diunggah." }
        return "Lengkapi informasi proyek."
    }

    private func save() async {
        errorMessage = nil
        guard canCreate else {
            errorMessage = options?.quota_message ?? "Kuota proyek sudah penuh."
            return
        }
        guard infoReady, productsReady, paymentsReady else {
            errorMessage = missingInfoMessage()
            if !productsReady { errorMessage = "Minimal satu paket harus dipilih." }
            if !paymentsReady { errorMessage = "Lengkapi data pembayaran atau hapus baris yang belum selesai." }
            return
        }
        if !isEditing {
            guard contractPDF != nil, agreementPDF != nil else { return }
        }

        isSaving = true
        defer { isSaving = false }

        let payloadItems: [[String: Int]] = filledItems.map {
            ["product_id": $0.product.id, "quantity": $0.quantity]
        }
        let payloadPayments: [[String: Any]] = payments.map { payment in
            var row: [String: Any] = [
                "keterangan": trimmed(payment.keterangan),
                "payment_method_id": payment.paymentMethodId,
                "nominal": Int(payment.nominalText.filter(\.isNumber)) ?? 0,
                "kategori_transaksi": payment.kategori,
                "tgl_bayar": Self.dayAPIFormatter.string(from: payment.date),
            ]
            if let recordId = payment.recordId {
                row["id"] = recordId
            }
            return row
        }

        guard
            let itemsData = try? JSONSerialization.data(withJSONObject: payloadItems),
            let paymentsData = try? JSONSerialization.data(withJSONObject: payloadPayments),
            let itemsJSON = String(data: itemsData, encoding: .utf8),
            let paymentsJSON = String(data: paymentsData, encoding: .utf8)
        else {
            errorMessage = "Gagal menyiapkan data paket."
            return
        }

        let fields = [
            "number": number,
            "prospect_id": String(prospectId),
            "user_id": String(userId),
            "employee_id": String(employeeId),
            "no_kontrak": trimmed(noKontrak),
            "pax": String(paxValue),
            "status": status,
            "note": trimmed(note),
            "items": itemsJSON,
            "payments": paymentsJSON,
        ]

        var files: [APIClient.MultipartFile] = []
        if let contractPDF {
            files.append(.init(field: "doc_kontrak", fileName: contractPDF.name, mimeType: "application/pdf", data: contractPDF.data))
        }
        if let agreementPDF {
            files.append(.init(field: "agreement_product", fileName: agreementPDF.name, mimeType: "application/pdf", data: agreementPDF.data))
        }

        for (index, payment) in payments.enumerated() {
            if let data = payment.proofData {
                files.append(.init(
                    field: "payment_proof_\(index)",
                    fileName: payment.proofName ?? "bukti-\(index + 1).jpg",
                    mimeType: "image/jpeg",
                    data: data
                ))
            }
        }

        do {
            if let project {
                _ = try await appState.api.updateProject(id: project.id, fields: fields, files: files)
            } else {
                _ = try await appState.api.createProject(fields: fields, files: files)
            }
            onSaved()
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func loadOptions() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let loaded = try await appState.api.projectFormOptions(orderId: project?.id)
            options = loaded
            if let project {
                applyProject(project, options: loaded)
            } else {
                number = loaded.number ?? ""
                noKontrak = loaded.default_no_kontrak ?? noKontrak
                if !paxEdited {
                    paxText = String(loaded.default_pax ?? 1000)
                }
                if userId == 0 { userId = loaded.current_user_id ?? loaded.account_managers.first?.id ?? 0 }
                if employeeId == 0 { employeeId = loaded.current_user_id ?? loaded.event_managers.first?.id ?? 0 }
                if loaded.prospects.count == 1 { prospectId = loaded.prospects[0].id }
                if status.isEmpty { status = loaded.statuses.first?.value ?? "pending" }
            }
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func applyProject(_ project: FinanceProjectDetail, options: ProjectFormOptions) {
        number = project.number ?? ""
        prospectId = project.prospect_id ?? project.prospect?.id ?? 0
        userId = project.user_id ?? options.current_user_id ?? options.account_managers.first?.id ?? 0
        employeeId = project.employee_id ?? options.current_user_id ?? options.event_managers.first?.id ?? 0
        noKontrak = project.no_kontrak ?? ""
        if let pax = project.pax, pax > 0 {
            paxText = String(pax)
            paxEdited = true
        }
        status = project.status ?? "pending"
        note = project.note ?? ""
        let mappedItems = (project.products ?? []).compactMap { item -> DraftItem? in
            guard let productId = item.product_id, productId > 0 else { return nil }
            return DraftItem(productId: productId, quantity: max(1, item.quantity ?? 1))
        }
        items = mappedItems.isEmpty ? [DraftItem()] : mappedItems
        payments = (project.payments ?? []).map { payment in
            var row = DraftPayment()
            row.recordId = payment.id
            row.keterangan = payment.keterangan ?? ""
            row.paymentMethodId = payment.payment_method_id ?? 0
            row.nominalText = payment.amount.map(String.init) ?? ""
            row.kategori = payment.kategori_transaksi ?? "uang_masuk"
            if let raw = payment.date, let parsed = Self.dayAPIFormatter.date(from: raw) {
                row.date = parsed
            }
            if payment.has_proof == true {
                row.existingProofName = "Bukti tersimpan"
            }
            return row
        }
    }

    private func availableProducts(except keepId: Int?) -> [ProjectFormProductOption] {
        let taken = Set(items.map(\.productId).filter { $0 > 0 && $0 != (keepId ?? -1) })
        return (options?.products ?? []).filter { !taken.contains($0.id) || $0.id == keepId }
    }

    private func bindingItem<T>(_ id: UUID, _ keyPath: WritableKeyPath<DraftItem, T>) -> Binding<T> {
        Binding(
            get: { items.first { $0.id == id }?[keyPath: keyPath] ?? items[0][keyPath: keyPath] },
            set: { value in
                if let index = items.firstIndex(where: { $0.id == id }) {
                    items[index][keyPath: keyPath] = value
                }
            }
        )
    }

    private func bindingPayment<T>(_ id: UUID, _ keyPath: WritableKeyPath<DraftPayment, T>) -> Binding<T> {
        Binding(
            get: {
                if let row = payments.first(where: { $0.id == id }) {
                    return row[keyPath: keyPath]
                }
                return DraftPayment()[keyPath: keyPath]
            },
            set: { value in
                if let index = payments.firstIndex(where: { $0.id == id }) {
                    payments[index][keyPath: keyPath] = value
                }
            }
        )
    }

    private func loadProof(id: UUID, item: PhotosPickerItem?) async {
        guard let item else { return }
        guard let data = try? await item.loadTransferable(type: Data.self) else { return }
        if data.count > 1280 * 1024 {
            errorMessage = "Bukti bayar maksimal 1MB."
            return
        }
        if let index = payments.firstIndex(where: { $0.id == id }) {
            payments[index].proofData = data
            payments[index].proofName = "bukti-\(index + 1).jpg"
        }
    }

    private func handlePDF(_ result: Result<URL, Error>, assign: Binding<PickedFile?>) {
        switch result {
        case .success(let url):
            let accessed = url.startAccessingSecurityScopedResource()
            defer { if accessed { url.stopAccessingSecurityScopedResource() } }
            guard let data = try? Data(contentsOf: url) else {
                errorMessage = "File PDF tidak dapat dibaca."
                return
            }
            if data.count > 10 * 1024 * 1024 {
                errorMessage = "File PDF maksimal 10MB."
                return
            }
            assign.wrappedValue = PickedFile(name: url.lastPathComponent, data: data)
        case .failure:
            break
        }
    }

    private func dismissKeyboard() {
        focused = nil
        UIApplication.shared.sendAction(#selector(UIResponder.resignFirstResponder), to: nil, from: nil, for: nil)
    }

    private func trimmed(_ value: String) -> String {
        value.trimmingCharacters(in: .whitespacesAndNewlines)
    }

    private func isEqual<T: Hashable>(_ lhs: T, _ rhs: T) -> Bool { lhs == rhs }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "id_ID")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "d MMM yyyy"
        return formatter
    }()

    private static let dayAPIFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}

struct CreateProspectView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    var prospect: FinanceProspectItem? = nil
    var onSaved: () -> Void = {}

    @State private var didPrefill = false

    @State private var nameEvent = ""
    @State private var nameCpp = ""
    @State private var nameCpw = ""
    @State private var phone = ""
    @State private var address = ""
    @State private var venue = ""
    @State private var offerText = ""
    @State private var notes = ""
    @State private var dateLamaran: Date?
    @State private var dateAkad: Date?
    @State private var dateResepsi: Date?
    @State private var timeLamaran: Date?
    @State private var timeAkad: Date?
    @State private var timeResepsi: Date?
    @State private var isSaving = false
    @State private var errorMessage: String?
    @FocusState private var focused: Field?

    private enum Field: Hashable {
        case event, cpp, cpw, phone, address, venue, offer, notes
    }

    private var isEditing: Bool { prospect != nil }

    private var canSave: Bool {
        !normalizedPhone.isEmpty
            && !trimmed(nameCpp).isEmpty
            && !trimmed(nameCpw).isEmpty
            && !trimmed(address).isEmpty
            && !trimmed(venue).isEmpty
            && !resolvedEventName.isEmpty
            && !isSaving
    }

    private var resolvedEventName: String {
        let value = trimmed(nameEvent)
        if !value.isEmpty { return value }
        let cpp = trimmed(nameCpp)
        let cpw = trimmed(nameCpw)
        guard !cpp.isEmpty, !cpw.isEmpty else { return "" }
        return "Pernikahan \(cpp) & \(cpw)"
    }

    private var normalizedPhone: String {
        var digits = phone.filter(\.isNumber)
        if digits.hasPrefix("62") { digits = String(digits.dropFirst(2)) }
        if digits.hasPrefix("0") { digits = String(digits.dropFirst()) }
        return digits
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                VStack(spacing: 16) {
                    if let errorMessage {
                        Text(errorMessage)
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.danger)
                            .fixedSize(horizontal: false, vertical: true)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .padding(14)
                            .background(WofinsTheme.danger.opacity(0.08), in: RoundedRectangle(cornerRadius: 16, style: .continuous))
                    }

                    formSection("Informasi Klien") {
                        field("Calon pengantin pria", text: $nameCpp, focus: .cpp, contentType: .name)
                        field("Calon pengantin wanita", text: $nameCpw, focus: .cpw, contentType: .name)
                        field("Telepon", text: $phone, focus: .phone, keyboard: .numberPad, prefix: "+62")
                        helper("Tanpa 0 di depan, contoh 81234567890")
                        field("Alamat", text: $address, focus: .address, axis: true)
                    }

                    formSection("Informasi Acara") {
                        field("Nama acara", text: $nameEvent, focus: .event)
                        helper(resolvedEventName.isEmpty ? "Contoh: Pernikahan Andi & Sinta" : "Jika kosong: \(resolvedEventName)")
                        field("Lokasi venue", text: $venue, focus: .venue, axis: true)
                        optionalSchedule("Lamaran", date: $dateLamaran, time: $timeLamaran)
                        optionalSchedule("Akad", date: $dateAkad, time: $timeAkad)
                        optionalSchedule("Resepsi", date: $dateResepsi, time: $timeResepsi)
                    }

                    formSection("Keuangan") {
                        field("Total penawaran", text: $offerText, focus: .offer, keyboard: .numberPad, prefix: "Rp")
                        field("Catatan", text: $notes, focus: .notes, axis: true)
                    }
                }
                .padding(.horizontal, 16)
                .padding(.top, 16)
                .padding(.bottom, 28)
                .frame(maxWidth: .infinity)
            }
            .scrollDismissesKeyboard(.immediately)

            Button {
                dismissKeyboard()
                Task { await save() }
            } label: {
                Group {
                    if isSaving {
                        ProgressView().tint(.white)
                    } else {
                        Text(isEditing ? "Simpan Perubahan" : "Simpan Prospek")
                            .font(.poppins(.subheadline, weight: .semibold))
                    }
                }
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .frame(height: 52)
                .background(canSave ? WofinsTheme.primary : WofinsTheme.muted, in: Capsule())
            }
            .disabled(!canSave)
            .padding(.horizontal, 16)
            .padding(.top, 8)
            .padding(.bottom, 16)
            .padding(.bottom, 8)
            .background(WofinsTheme.background.ignoresSafeArea(edges: .bottom))
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsDismissKeyboardOnOutsideTap()
        .toolbar(.hidden, for: .navigationBar)
        .toolbar {
            ToolbarItemGroup(placement: .keyboard) {
                Spacer()
                Button("Selesai") { dismissKeyboard() }
            }
        }
        .onAppear { prefillIfNeeded() }
    }

    private func dismissKeyboard() {
        focused = nil
        UIApplication.shared.sendAction(#selector(UIResponder.resignFirstResponder), to: nil, from: nil, for: nil)
    }

    private var header: some View {
        HStack(spacing: 12) {
            Button { dismiss() } label: {
                Image(systemName: "xmark")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Tutup")
            .fixedSize()

            VStack(alignment: .leading, spacing: 2) {
                Text(isEditing ? "Edit Prospek" : "Tambah Prospek")
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                    .lineLimit(1)
                Text(isEditing ? (prospect?.displayName ?? "Perbarui data calon klien") : "Calon klien baru")
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
        .contentShape(Rectangle())
        .onTapGesture { dismissKeyboard() }
    }

    private func formSection<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(title)
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
            content()
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(16)
        .projectSurface()
    }

    private func field(
        _ title: String,
        text: Binding<String>,
        focus: Field,
        keyboard: UIKeyboardType = .default,
        contentType: UITextContentType? = nil,
        prefix: String? = nil,
        axis: Bool = false
    ) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            HStack(alignment: .center, spacing: 10) {
                if let prefix {
                    Text(prefix)
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(width: 42, alignment: .leading)
                        .lineLimit(1)
                }
                Group {
                    if axis {
                        TextField("", text: text, axis: .vertical)
                            .lineLimit(3...6)
                    } else {
                        TextField("", text: text)
                            .lineLimit(1)
                    }
                }
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .keyboardType(keyboard)
                .textInputAutocapitalization(keyboard == .numberPad ? .never : .words)
                .autocorrectionDisabled(keyboard == .numberPad)
                .textContentType(contentType)
                .focused($focused, equals: focus)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            }
            .padding(.horizontal, 14)
            .padding(.vertical, 12)
            .frame(minHeight: 48, alignment: .center)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .stroke(WofinsTheme.border, lineWidth: 1)
            }
        }
    }

    private func helper(_ text: String) -> some View {
        Text(text)
            .font(.poppins(.caption2))
            .foregroundStyle(WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
    }

    private func optionalSchedule(_ title: String, date: Binding<Date?>, time: Binding<Date?>) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack {
                Text(title)
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer(minLength: 8)
                if date.wrappedValue == nil {
                    Button("Atur") { date.wrappedValue = Date() }
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                } else {
                    Button("Hapus") {
                        date.wrappedValue = nil
                        time.wrappedValue = nil
                    }
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.danger)
                }
            }

            if date.wrappedValue != nil {
                DatePicker(
                    "Tanggal",
                    selection: dateBinding(date),
                    displayedComponents: .date
                )
                .environment(\.locale, Locale(identifier: "id_ID"))
                .font(.poppins(.subheadline))

                HStack {
                    Text("Jam")
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.muted)
                    Spacer()
                    if time.wrappedValue == nil {
                        Button("Tambah jam") { time.wrappedValue = Date() }
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                    } else {
                        DatePicker(
                            "",
                            selection: dateBinding(time),
                            displayedComponents: .hourAndMinute
                        )
                        .labelsHidden()
                        .environment(\.locale, Locale(identifier: "id_ID"))
                        Button {
                            time.wrappedValue = nil
                        } label: {
                            Image(systemName: "xmark.circle.fill")
                                .foregroundStyle(WofinsTheme.muted)
                        }
                        .buttonStyle(.plain)
                    }
                }
            }
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    private func dateBinding(_ source: Binding<Date?>) -> Binding<Date> {
        Binding(
            get: { source.wrappedValue ?? Date() },
            set: { source.wrappedValue = $0 }
        )
    }

    private func trimmed(_ value: String) -> String {
        value.trimmingCharacters(in: .whitespacesAndNewlines)
    }

    private func save() async {
        errorMessage = nil
        let phoneValue = normalizedPhone
        guard phoneValue.count >= 8, phoneValue.count <= 15 else {
            errorMessage = "Nomor telepon 8–15 digit, tanpa 0 di depan."
            return
        }

        isSaving = true
        defer { isSaving = false }

        let offer = Int(offerText.filter(\.isNumber)) ?? 0
        let payload = CreateProspectPayload(
            name_event: resolvedEventName,
            name_cpp: trimmed(nameCpp),
            name_cpw: trimmed(nameCpw),
            phone: phoneValue,
            address: trimmed(address),
            venue: trimmed(venue),
            total_penawaran: offer,
            notes: trimmed(notes).isEmpty ? nil : trimmed(notes),
            date_lamaran: dateLamaran.map(Self.dayFormatter.string(from:)) ?? "",
            time_lamaran: dateLamaran == nil ? "" : (timeLamaran.map(Self.timeFormatter.string(from:)) ?? ""),
            date_akad: dateAkad.map(Self.dayFormatter.string(from:)) ?? "",
            time_akad: dateAkad == nil ? "" : (timeAkad.map(Self.timeFormatter.string(from:)) ?? ""),
            date_resepsi: dateResepsi.map(Self.dayFormatter.string(from:)) ?? "",
            time_resepsi: dateResepsi == nil ? "" : (timeResepsi.map(Self.timeFormatter.string(from:)) ?? "")
        )

        do {
            if let id = prospect?.id {
                _ = try await appState.api.updateProspect(id: id, payload)
            } else {
                _ = try await appState.api.createProspect(payload)
            }
            onSaved()
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }

    private func prefillIfNeeded() {
        guard !didPrefill, let prospect else { return }
        didPrefill = true
        nameEvent = prospect.name_event ?? ""
        nameCpp = prospect.name_cpp ?? ""
        nameCpw = prospect.name_cpw ?? ""
        phone = prospect.phone ?? ""
        address = prospect.address ?? ""
        venue = prospect.venue ?? ""
        if let offer = prospect.total_penawaran, offer > 0 {
            offerText = String(offer)
        }
        let notesValue = prospect.notes?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        notes = notesValue.localizedCaseInsensitiveCompare("Tidak ada catatan") == .orderedSame ? "" : notesValue
        dateLamaran = FinanceProjectItem.parseDate(prospect.date_lamaran)
        dateAkad = FinanceProjectItem.parseDate(prospect.date_akad)
        dateResepsi = FinanceProjectItem.parseDate(prospect.date_resepsi)
        timeLamaran = Self.parseTime(prospect.time_lamaran)
        timeAkad = Self.parseTime(prospect.time_akad)
        timeResepsi = Self.parseTime(prospect.time_resepsi)
    }

    private static func parseTime(_ raw: String?) -> Date? {
        let value = raw?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        guard !value.isEmpty else { return nil }
        for format in ["HH:mm", "HH:mm:ss"] {
            let formatter = DateFormatter()
            formatter.locale = Locale(identifier: "en_US_POSIX")
            formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
            formatter.dateFormat = format
            if let date = formatter.date(from: String(value.prefix(8))) {
                return date
            }
        }
        return nil
    }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()

    private static let timeFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "HH:mm"
        return formatter
    }()
}

struct ProspectDetailView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let prospectId: Int
    var preview: FinanceProspectItem?
    var onChanged: () -> Void = {}

    @State private var detail: FinanceProspectItem?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var actionMessage: String?
    @State private var showEdit = false

    private var prospect: FinanceProspectItem? { detail ?? preview }
    private var title: String { prospect?.displayName ?? "Detail Prospek" }
    private var appearance: (color: Color, label: String) { prospectStatusAppearance(prospect?.order_status) }

    private var whatsappURL: URL? {
        ProjectWhatsApp.url(
            phone: prospect?.phone,
            title: title,
            number: prospect?.order?.number ?? "prospek",
            context: "prospek"
        )
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                VStack(spacing: 16) {
                    if isLoading && prospect == nil {
                        HStack(spacing: 12) {
                            ProgressView().tint(WofinsTheme.primary)
                            Text("Memuat prospek…")
                                .font(.poppins(.subheadline))
                                .foregroundStyle(WofinsTheme.muted)
                        }
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding(18)
                        .projectSurface()
                        .padding(.horizontal, 16)
                    } else if let errorMessage, detail == nil, preview == nil {
                        errorCard(errorMessage)
                    } else {
                        identityCard
                        if let notes = cleanedNotes {
                            notesCard(notes)
                        }
                        if let order = prospect?.order {
                            NavigationLink {
                                ProjectDetailView(projectId: order.id)
                            } label: {
                                projectLinkCard(order)
                            }
                            .buttonStyle(.plain)
                        }
                    }
                }
                .padding(.top, 16)
                .padding(.bottom, 28)
            }
            .refreshable { await load() }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .toolbar(.hidden, for: .navigationBar)
        .task { await load() }
        .fullScreenCover(isPresented: $showEdit) {
            CreateProspectView(prospect: prospect) {
                Task {
                    await load()
                    onChanged()
                }
            }
            .environmentObject(appState)
        }
        .alert("Informasi", isPresented: Binding(get: { actionMessage != nil }, set: { if !$0 { actionMessage = nil } })) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(actionMessage ?? "")
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
            .fixedSize()

            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                    .lineLimit(1)
                    .minimumScaleFactor(0.75)
                Text(appearance.label)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

            Button {
                showEdit = true
            } label: {
                Image(systemName: "pencil")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Edit prospek")
            .disabled(prospect == nil)
            .fixedSize()

            Menu {
                Button {
                    showEdit = true
                } label: {
                    Label("Edit prospek", systemImage: "pencil")
                }
                if let whatsappURL {
                    Link(destination: whatsappURL) {
                        Label("WhatsApp klien", systemImage: "message.fill")
                    }
                }
                if let phone = prospect?.phone, !phone.isEmpty {
                    Button {
                        UIPasteboard.general.string = phone
                        actionMessage = "Nomor \(phone) disalin."
                    } label: {
                        Label("Salin nomor", systemImage: "doc.on.doc")
                    }
                }
            } label: {
                Image(systemName: "ellipsis")
                    .font(.system(size: 17, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Aksi prospek")
            .fixedSize()
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var identityCard: some View {
        VStack(alignment: .leading, spacing: 14) {
            Text(appearance.label)
                .font(.poppins(.caption2, weight: .semibold))
                .foregroundStyle(appearance.color)
                .padding(.horizontal, 10)
                .padding(.vertical, 5)
                .background(appearance.color.opacity(0.12), in: Capsule())

            if let couple = prospect?.coupleLabel {
                labeledRow("Pasangan", couple, icon: "heart.fill")
            }
            if let venue = prospect?.venue, !venue.isEmpty {
                labeledRow("Venue", venue, icon: "mappin.and.ellipse")
            }
            if let value = datetimeLabel(date: prospect?.date_lamaran, time: prospect?.time_lamaran) {
                labeledRow("Lamaran", value, icon: "heart.circle.fill")
            }
            if let value = datetimeLabel(date: prospect?.date_akad, time: prospect?.time_akad) {
                labeledRow("Akad", value, icon: "calendar.badge.clock")
            }
            if let value = datetimeLabel(date: prospect?.date_resepsi, time: prospect?.time_resepsi) {
                labeledRow("Resepsi", value, icon: "calendar")
            }
            if let manager = prospect?.account_manager, !manager.isEmpty {
                labeledRow("Account manager", manager, icon: "person.fill")
            }
            if let phone = prospect?.phone, !phone.isEmpty {
                labeledRow("Telepon", phone, icon: "phone.fill")
            }
            if let address = prospect?.address, !address.isEmpty {
                labeledRow("Alamat", address, icon: "house.fill")
            }
            if let offer = prospect?.total_penawaran, offer != 0 {
                labeledRow("Penawaran", MoneyFormat.idr(offer), icon: "banknote.fill")
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func notesCard(_ notes: String) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Label("Catatan", systemImage: "note.text")
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
            Text(notes)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.ink)
                .fixedSize(horizontal: false, vertical: true)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func projectLinkCard(_ order: FinanceProspectOrderRef) -> some View {
        HStack(spacing: 12) {
            Image(systemName: "heart.fill")
                .font(.system(size: 16, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .frame(width: 42, height: 42)
                .background(WofinsTheme.primary.opacity(0.10), in: Circle())
            VStack(alignment: .leading, spacing: 3) {
                Text("Sudah jadi proyek")
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                Text(order.number ?? order.name ?? "Proyek #\(order.id)")
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                    .lineLimit(2)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            Image(systemName: "chevron.right")
                .font(.system(size: 12, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func errorCard(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Prospek belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(WofinsTheme.danger)
            Text(message)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
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

    private var cleanedNotes: String? {
        let text = prospect?.notes?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        guard !text.isEmpty, text.localizedCaseInsensitiveCompare("Tidak ada catatan") != .orderedSame else {
            return nil
        }
        return text
    }

    private func datetimeLabel(date: String?, time: String?) -> String? {
        let dateText = FinanceProjectItem.formatDisplayDate(date)
        let timeText = (time ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        if let dateText, !timeText.isEmpty { return "\(dateText), \(timeText)" }
        if let dateText { return dateText }
        return timeText.isEmpty ? nil : timeText
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financeProspect(id: prospectId)
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct ProjectDetailView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let projectId: Int
    var preview: FinanceProjectItem?

    @State private var detail: FinanceProjectDetail?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var showInvoicePreview = false
    @State private var showContractPreview = false
    @State private var isPreparingInvoice = false
    @State private var invoiceShareItem: ProjectShareItem?
    @State private var actionMessage: String?
    @State private var showEdit = false

    private var title: String { detail?.displayName ?? preview?.displayName ?? "Detail Proyek" }
    private var number: String { detail?.number ?? preview?.number ?? "Proyek #\(projectId)" }
    private var status: String? { detail?.status ?? preview?.status }
    private var appearance: (color: Color, label: String) { projectStatusAppearance(status) }
    private var prospect: FinanceProspectRef? { detail?.prospect ?? preview?.prospect }
    private var canEditProject: Bool {
        if let canEdit = detail?.can_edit { return canEdit }
        return (detail?.status ?? preview?.status) != "done"
    }

    private var invoiceURL: URL? {
        APIConfig.mediaURL(from: detail?.invoice_url)
            ?? URL(string: APIConfig.baseURL.absoluteString + APIConfig.apiPrefix + "/finance/projects/\(projectId)/invoice")
    }

    private var contractURL: URL? {
        APIConfig.mediaURL(from: detail?.doc_kontrak_url)
    }

    private var invoiceFileName: String {
        detail?.invoice_name ?? "Invoice-\(number).pdf"
    }

    private var whatsappURL: URL? {
        ProjectWhatsApp.url(phone: prospect?.phone, title: title, number: number)
    }

    var body: some View {
        ZStack {
            VStack(spacing: 0) {
                header
                ScrollView(showsIndicators: false) {
                    VStack(spacing: 16) {
                        if isLoading && detail == nil && preview == nil {
                            loadingCard
                        } else if let errorMessage, detail == nil {
                            errorCard(errorMessage)
                        } else {
                            identityCard
                            contractCard
                            financeGrid
                            if let products = detail?.products, !products.isEmpty {
                                productsCard(products)
                            }
                            if detail != nil {
                                paymentsCard
                                expensesCard
                            }
                        }
                    }
                    .padding(.top, 16)
                    .padding(.bottom, 120)
                    .frame(maxWidth: .infinity)
                }
                .frame(maxWidth: .infinity, maxHeight: .infinity)
                .refreshable { await load() }
            }
            .frame(maxWidth: .infinity, maxHeight: .infinity)

            if isPreparingInvoice {
                ZStack {
                    Color.black.opacity(0.28).ignoresSafeArea()
                    VStack(spacing: 12) {
                        ProgressView()
                            .tint(WofinsTheme.primary)
                        Text("Menyiapkan invoice…")
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.ink)
                            .multilineTextAlignment(.center)
                    }
                    .padding(22)
                    .frame(maxWidth: 260)
                    .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
                    .padding(.horizontal, 32)
                }
                .allowsHitTesting(true)
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .toolbar(.hidden, for: .navigationBar)
        .task { await load() }
        .navigationDestination(isPresented: $showInvoicePreview) {
            if let invoiceURL {
                ProjectDocumentView(
                    url: invoiceURL,
                    title: "Invoice",
                    fileName: invoiceFileName,
                    token: appState.api.token
                )
            }
        }
        .navigationDestination(isPresented: $showContractPreview) {
            if let contractURL {
                ProjectDocumentView(
                    url: contractURL,
                    title: "Upload Kontrak",
                    fileName: detail?.doc_kontrak_name ?? "Dokumen kontrak.pdf",
                    token: appState.api.token
                )
            }
        }
        .sheet(item: $invoiceShareItem) { item in
            ProjectShareSheet(items: [item.url])
        }
        .alert("Proyek", isPresented: Binding(
            get: { actionMessage != nil },
            set: { if !$0 { actionMessage = nil } }
        )) {
            Button("OK", role: .cancel) {}
        } message: {
            Text(actionMessage ?? "")
        }
        .fullScreenCover(isPresented: $showEdit) {
            if let detail {
                CreateProjectView(project: detail) {
                    Task { await load() }
                }
                .environmentObject(appState)
            }
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
            .fixedSize()

            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                    .lineLimit(1)
                    .minimumScaleFactor(0.75)
                Text(number)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

            Button {
                if canEditProject {
                    showEdit = true
                } else {
                    actionMessage = detail?.can_edit_reason ?? "Proyek sudah selesai. Hanya Super Admin yang dapat mengedit."
                }
            } label: {
                Image(systemName: "pencil")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Edit proyek")
            .disabled(detail == nil)
            .fixedSize()

            Menu {
                Button {
                    if canEditProject {
                        showEdit = true
                    } else {
                        actionMessage = detail?.can_edit_reason ?? "Proyek sudah selesai. Hanya Super Admin yang dapat mengedit."
                    }
                } label: {
                    Label("Edit Proyek", systemImage: "pencil")
                }
                .disabled(detail == nil)
                Button {
                    Task { await downloadInvoice() }
                } label: {
                    Label("Download Invoice", systemImage: "arrow.down.doc.fill")
                }
                Button {
                    showInvoicePreview = true
                } label: {
                    Label("Lihat Invoice", systemImage: "doc.text.magnifyingglass")
                }
                if contractURL != nil {
                    Button {
                        showContractPreview = true
                    } label: {
                        Label("Lihat Kontrak", systemImage: "doc.richtext.fill")
                    }
                }
                if let whatsappURL {
                    Link(destination: whatsappURL) {
                        Label("WhatsApp klien", systemImage: "message.fill")
                    }
                }
                Button {
                    UIPasteboard.general.string = number
                    actionMessage = "Nomor proyek \(number) disalin."
                } label: {
                    Label("Salin nomor proyek", systemImage: "doc.on.doc")
                }
            } label: {
                Image(systemName: "plus")
                    .font(.system(size: 17, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Aksi proyek")
            .fixedSize()
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var identityCard: some View {
        let paid = detail?.paidValue ?? preview?.paid_amount ?? 0
        let total = detail?.grandTotalValue ?? preview?.grand_total ?? 0
        let progress = total > 0 ? min(max(Double(paid) / Double(total), 0), 1) : 0

        return VStack(alignment: .leading, spacing: 14) {
            Text(appearance.label)
                .font(.poppins(.caption2, weight: .semibold))
                .foregroundStyle(appearance.color)
                .padding(.horizontal, 10)
                .padding(.vertical, 5)
                .background(appearance.color.opacity(0.12), in: Capsule())

            if let couple = prospect?.coupleLabel {
                labeledRow("Pasangan", couple, icon: "heart.fill")
            }
            if let venue = prospect?.venue, !venue.isEmpty {
                labeledRow("Venue", venue, icon: "mappin.and.ellipse")
            }
            if let lamaran = formattedDate(prospect?.date_lamaran) {
                labeledRow("Lamaran", lamaran, icon: "heart.circle.fill")
            }
            if let akad = formattedDate(prospect?.date_akad) {
                labeledRow("Akad", akad, icon: "calendar.badge.clock")
            }
            if let resepsi = formattedDate(prospect?.date_resepsi) {
                labeledRow("Resepsi", resepsi, icon: "calendar")
            }
            if let pax = detail?.pax, pax != 0 {
                labeledRow("Jumlah tamu", "\(pax) pax", icon: "person.3.fill")
            }
            if let manager = detail?.account_manager ?? preview?.account_manager, !manager.isEmpty {
                labeledRow("Account manager", manager, icon: "person.fill")
            }
            if let em = detail?.event_manager, !em.isEmpty {
                labeledRow("Event manager", em, icon: "person.2.fill")
            }
            if let kontrak = detail?.no_kontrak, !kontrak.isEmpty {
                labeledRow("No. kontrak", kontrak, icon: "doc.text.fill")
            }
            if let phone = prospect?.phone, !phone.isEmpty {
                labeledRow("Telepon", phone, icon: "phone.fill")
            }

            if paid != 0, total != 0 {
                VStack(alignment: .leading, spacing: 8) {
                    HStack {
                        Text("Progress bayar")
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                        Spacer()
                        Text("\(Int(progress * 100))%")
                            .font(.poppins(.caption, weight: .bold))
                            .foregroundStyle(appearance.color)
                    }
                    Capsule()
                        .fill(WofinsTheme.border)
                        .frame(height: 8)
                        .overlay(alignment: .leading) {
                            Capsule()
                                .fill(LinearGradient(colors: [WofinsTheme.primary, WofinsTheme.yellow], startPoint: .leading, endPoint: .trailing))
                                .scaleEffect(x: progress, y: 1, anchor: .leading)
                        }
                        .clipShape(Capsule())
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    @ViewBuilder
    private var contractCard: some View {
        if let url = APIConfig.mediaURL(from: detail?.doc_kontrak_url) {
                    NavigationLink {
                        ProjectDocumentView(
                            url: url,
                            title: "Upload Kontrak",
                            fileName: detail?.doc_kontrak_name ?? "Dokumen kontrak.pdf",
                            token: appState.api.token
                        )
            } label: {
                HStack(alignment: .center, spacing: 12) {
                    Image(systemName: "doc.richtext.fill")
                        .font(.system(size: 18, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(width: 40, height: 40)
                        .background(WofinsTheme.primary.opacity(0.10), in: RoundedRectangle(cornerRadius: 10, style: .continuous))
                    VStack(alignment: .leading, spacing: 2) {
                        Text("Upload Kontrak")
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.ink)
                            .lineLimit(1)
                        Text(detail?.doc_kontrak_name ?? "Dokumen kontrak.pdf")
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }
                    .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    Text("Lihat")
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                    Image(systemName: "chevron.right")
                        .font(.system(size: 12, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                        .fixedSize()
                }
                .padding(17)
                .contentShape(Rectangle())
                .projectSurface()
                .padding(.horizontal, 16)
            }
            .buttonStyle(.plain)
        }
    }

    private var financeGrid: some View {
        let tiles = [
            ("Nilai proyek", detail?.grandTotalValue ?? preview?.grand_total, WofinsTheme.primary),
            ("Terbayar", detail?.paidValue ?? preview?.paid_amount, WofinsTheme.success),
            ("Sisa", detail?.remainingValue ?? preview?.remaining, Color(red: 0.76, green: 0.52, blue: 0.00)),
            ("Pengeluaran", detail?.expensesValue ?? preview?.expenses_total, WofinsTheme.danger),
        ].filter { ($0.1 ?? 0) != 0 }

        return Group {
            if !tiles.isEmpty {
                VStack(spacing: 12) {
                    ForEach(Array(stride(from: 0, to: tiles.count, by: 2)), id: \.self) { index in
                        if index + 1 < tiles.count {
                            HStack(spacing: 12) {
                                financeTile(tiles[index].0, tiles[index].1, tiles[index].2)
                                financeTile(tiles[index + 1].0, tiles[index + 1].1, tiles[index + 1].2)
                            }
                        } else {
                            financeTile(tiles[index].0, tiles[index].1, tiles[index].2)
                        }
                    }
                }
                .padding(.horizontal, 16)
            }
        }
    }

    private func financeTile(_ title: String, _ amount: Int?, _ color: Color) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
                .lineLimit(1)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.55)
                .frame(maxWidth: .infinity, alignment: .leading)
            RoundedRectangle(cornerRadius: 2)
                .fill(color)
                .frame(width: 28, height: 3)
        }
        .padding(14)
        .frame(maxWidth: .infinity, minHeight: 86, alignment: .topLeading)
        .projectSurface()
    }

    private func productsCard(_ products: [FinanceProjectProduct]) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            sectionTitle("Paket", "gift.fill")
            ForEach(Array(products.enumerated()), id: \.element.id) { index, item in
                if index > 0 { Divider() }
                if let productId = item.product_id {
                    NavigationLink {
                        ProductDetailView(
                            productId: productId,
                            previewName: item.name,
                            previewPax: item.pax,
                            previewPrice: item.unit_price
                        )
                    } label: {
                        packageRow(item, showsChevron: true)
                    }
                    .buttonStyle(.plain)
                } else {
                    packageRow(item, showsChevron: false)
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func packageRow(_ item: FinanceProjectProduct, showsChevron: Bool) -> some View {
        HStack(alignment: .top, spacing: 10) {
            VStack(alignment: .leading, spacing: 3) {
                Text(item.name ?? "Paket")
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                    .multilineTextAlignment(.leading)
                    .fixedSize(horizontal: false, vertical: true)
                HStack(spacing: 6) {
                    Text("\(item.quantity ?? 0)× \(MoneyFormat.idr(item.unit_price))")
                    if let pax = item.pax {
                        Text("·").foregroundStyle(WofinsTheme.border)
                        Text("\(pax) pax")
                    }
                }
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
                .lineLimit(1)
                .minimumScaleFactor(0.8)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            VStack(alignment: .trailing, spacing: 6) {
                Text(MoneyFormat.idr(item.lineTotal))
                    .font(.poppins(.caption, weight: .bold))
                    .foregroundStyle(WofinsTheme.primary)
                    .lineLimit(1)
                    .minimumScaleFactor(0.7)
                if showsChevron {
                    Image(systemName: "chevron.right")
                        .font(.system(size: 12, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                }
            }
        }
        .contentShape(Rectangle())
    }

    private var paymentsCard: some View {
        let items = detail?.payments ?? []
        return VStack(alignment: .leading, spacing: 12) {
            sectionTitle("Pembayaran", "arrow.down.circle.fill")
            if items.isEmpty && detail != nil {
                emptyLine("Belum ada pembayaran")
            } else {
                ForEach(Array(items.enumerated()), id: \.element.id) { index, item in
                    if index > 0 { Divider() }
                    moneyRow(
                        title: item.keterangan ?? "Pembayaran",
                        subtitle: [formattedDate(item.date), item.payment_method].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " · "),
                        amount: item.amount,
                        positive: true
                    )
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var expensesCard: some View {
        let items = detail?.expenses ?? []
        return VStack(alignment: .leading, spacing: 12) {
            sectionTitle("Pengeluaran", "arrow.up.circle.fill")
            if items.isEmpty && detail != nil {
                emptyLine("Belum ada pengeluaran")
            } else {
                ForEach(Array(items.enumerated()), id: \.element.id) { index, item in
                    if index > 0 { Divider() }
                    moneyRow(
                        title: item.vendor ?? item.note ?? "Pengeluaran",
                        subtitle: [formattedDate(item.date), item.note].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " · "),
                        amount: item.amount,
                        positive: false
                    )
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var loadingCard: some View {
        HStack(spacing: 12) {
            ProgressView().tint(WofinsTheme.primary)
            Text("Memuat detail proyek…")
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(18)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func errorCard(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Detail belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(WofinsTheme.danger)
            Text(message)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            Button("Coba lagi") { Task { await load() } }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .padding(.vertical, 12)
                .background(WofinsTheme.primary, in: Capsule())
        }
        .padding(18)
        .projectSurface()
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
            .frame(maxWidth: .infinity, alignment: .leading)
        }
    }

    private func sectionTitle(_ title: String, _ icon: String) -> some View {
        Label(title, systemImage: icon)
            .font(.poppins(.subheadline, weight: .bold))
            .foregroundStyle(WofinsTheme.ink)
    }

    private func emptyLine(_ text: String) -> some View {
        Text(text)
            .font(.poppins(.caption))
            .foregroundStyle(WofinsTheme.muted)
    }

    private func moneyRow(title: String, subtitle: String, amount: Int?, positive: Bool) -> some View {
        HStack(alignment: .top, spacing: 10) {
            VStack(alignment: .leading, spacing: 3) {
                Text(title)
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                    .fixedSize(horizontal: false, vertical: true)
                if !subtitle.isEmpty {
                    Text(subtitle)
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            Text((positive ? "+ " : "− ") + MoneyFormat.idr(amount))
                .font(.poppins(.caption, weight: .bold))
                .foregroundStyle(positive ? WofinsTheme.success : WofinsTheme.danger)
                .lineLimit(1)
                .minimumScaleFactor(0.65)
        }
    }

    private func formattedDate(_ raw: String?) -> String? {
        FinanceProjectItem.formatDisplayDate(raw)
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

    private func downloadInvoice() async {
        isPreparingInvoice = true
        defer { isPreparingInvoice = false }
        do {
            let data = try await appState.api.financeProjectInvoice(id: projectId)
            let safeName = invoiceFileName
                .replacingOccurrences(of: "/", with: "-")
                .replacingOccurrences(of: ":", with: "-")
            let url = FileManager.default.temporaryDirectory.appendingPathComponent(safeName)
            try data.write(to: url, options: .atomic)
            invoiceShareItem = ProjectShareItem(url: url)
        } catch {
            actionMessage = error.localizedDescription
        }
    }
}

private struct ProjectShareItem: Identifiable {
    let id = UUID()
    let url: URL
}

private struct ProjectShareSheet: UIViewControllerRepresentable {
    let items: [Any]

    func makeUIViewController(context: Context) -> UIActivityViewController {
        UIActivityViewController(activityItems: items, applicationActivities: nil)
    }

    func updateUIViewController(_ uiViewController: UIActivityViewController, context: Context) {}
}

private enum ProjectWhatsApp {
    static func url(phone: String?, title: String, number: String, context: String = "proyek") -> URL? {
        let digits = (phone ?? "").filter(\.isNumber)
        guard !digits.isEmpty else { return nil }

        var normalized = digits
        if normalized.hasPrefix("0") {
            normalized = "62" + String(normalized.dropFirst())
        } else if normalized.hasPrefix("8") {
            normalized = "62" + normalized
        }

        let message = context == "prospek"
            ? "Halo, berikut terkait prospek \(title)."
            : "Halo, berikut terkait proyek \(title) (\(number))."
        var components = URLComponents(string: "https://wa.me/\(normalized)")
        components?.queryItems = [URLQueryItem(name: "text", value: message)]
        return components?.url
    }
}

struct ProjectDocumentView: View {
    @Environment(\.dismiss) private var dismiss

    let url: URL
    let title: String
    let fileName: String
    var token: String?

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

                VStack(alignment: .leading, spacing: 2) {
                    Text(title)
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(.white)
                        .lineLimit(1)
                        .minimumScaleFactor(0.8)
                    Text(fileName)
                        .font(.poppins(.caption))
                        .foregroundStyle(.white.opacity(0.72))
                        .lineLimit(1)
                        .minimumScaleFactor(0.8)
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))

            DocumentWebView(url: url, token: token)
                .background(WofinsTheme.background)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .toolbar(.hidden, for: .navigationBar)
    }
}

private struct DocumentWebView: UIViewRepresentable {
    let url: URL
    var token: String?

    func makeUIView(context: Context) -> WKWebView {
        let webView = WKWebView()
        webView.backgroundColor = .clear
        webView.isOpaque = false
        webView.scrollView.contentInsetAdjustmentBehavior = .never
        var request = URLRequest(url: url)
        request.setValue("application/pdf", forHTTPHeaderField: "Accept")
        if let token, !token.isEmpty {
            request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        }
        webView.load(request)
        return webView
    }

    func updateUIView(_ uiView: WKWebView, context: Context) {}
}

struct ProductDetailView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let productId: Int
    var previewName: String?
    var previewPax: Int?
    var previewPrice: Int?

    @State private var detail: FinanceProductDetail?
    @State private var isLoading = false
    @State private var errorMessage: String?

    private var title: String { detail?.name ?? previewName ?? "Paket" }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                LazyVStack(spacing: 16) {
                    if isLoading && detail == nil {
                        loadingCard
                    } else if let errorMessage, detail == nil {
                        errorCard(errorMessage)
                    } else {
                        summaryCard
                        priceGrid
                        if let vendors = detail?.vendors, !vendors.isEmpty {
                            vendorsCard(vendors)
                        }
                        if let discounts = detail?.discounts?.filter({ ($0.amount ?? 0) != 0 }), !discounts.isEmpty {
                            discountsCard(discounts)
                        }
                    }
                }
                .padding(.top, 16)
                .padding(.bottom, 28)
            }
            .refreshable { await load() }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .toolbar(.hidden, for: .navigationBar)
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
                    .minimumScaleFactor(0.75)
                Text(headerSubtitle)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var headerSubtitle: String {
        if let pax = detail?.pax ?? previewPax {
            return "\(pax) pax"
        }
        return "Detail paket"
    }

    private var summaryCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            if let category = detail?.category, !category.isEmpty {
                labeledRow("Kategori", category, icon: "square.grid.2x2.fill")
            }
            if let pax = detail?.pax ?? previewPax {
                labeledRow("Kapasitas", "\(pax) pax", icon: "person.3.fill")
            }
            if let description = detail?.description, !description.isEmpty {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Deskripsi")
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                    Text(description)
                        .font(.poppins(.subheadline))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var priceGrid: some View {
        let publish = detail?.product_price ?? previewPrice
        let vendor = detail?.vendor_price
        let profit = detail?.profit
        let discount = detail?.pengurangan ?? 0
        let finalPrice = detail?.price ?? previewPrice
        let tiles: [(String, Int?, Color)] = [
            ("Harga publish", publish, WofinsTheme.primary),
            ("Harga vendor", (vendor ?? 0) != 0 ? vendor : nil, WofinsTheme.ink),
            ("Profit", (profit ?? 0) != 0 ? profit : nil, WofinsTheme.success),
            ("Diskon", discount == 0 ? nil : discount, Color(red: 0.76, green: 0.52, blue: 0.00)),
            ("Harga akhir", (finalPrice ?? 0) != 0 && finalPrice != publish ? finalPrice : nil, WofinsTheme.success),
        ].filter { ($0.1 ?? 0) != 0 }

        return Group {
            if !tiles.isEmpty {
                VStack(spacing: 12) {
                    ForEach(Array(stride(from: 0, to: tiles.count, by: 2)), id: \.self) { index in
                        if index + 1 < tiles.count {
                            HStack(spacing: 12) {
                                priceTile(tiles[index].0, tiles[index].1, tiles[index].2)
                                priceTile(tiles[index + 1].0, tiles[index + 1].1, tiles[index + 1].2)
                            }
                        } else {
                            priceTile(tiles[index].0, tiles[index].1, tiles[index].2)
                        }
                    }
                }
                .padding(.horizontal, 16)
            }
        }
    }

    private func priceTile(_ title: String, _ amount: Int?, _ color: Color) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
                .lineLimit(1)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.55)
                .frame(maxWidth: .infinity, alignment: .leading)
            RoundedRectangle(cornerRadius: 2)
                .fill(color)
                .frame(width: 28, height: 3)
        }
        .padding(14)
        .frame(maxWidth: .infinity, minHeight: 86, alignment: .topLeading)
        .projectSurface()
    }

    private func vendorsCard(_ vendors: [FinanceProductVendor]) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Vendor dalam paket", systemImage: "building.2.fill")
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
            ForEach(Array(vendors.enumerated()), id: \.element.id) { index, vendor in
                if index > 0 { Divider() }
                if let vendorId = vendor.vendor_id {
                    NavigationLink {
                        VendorDetailView(vendorId: vendorId, previewName: vendor.name)
                    } label: {
                        vendorRow(vendor, showsChevron: true)
                    }
                    .buttonStyle(.plain)
                } else {
                    vendorRow(vendor, showsChevron: false)
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func vendorRow(_ vendor: FinanceProductVendor, showsChevron: Bool) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack(alignment: .top, spacing: 10) {
                VStack(alignment: .leading, spacing: 3) {
                    Text(vendor.name ?? "Vendor")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .multilineTextAlignment(.leading)
                        .fixedSize(horizontal: false, vertical: true)
                    if !vendorMeta(vendor).isEmpty {
                        Text(vendorMeta(vendor))
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                            .fixedSize(horizontal: false, vertical: true)
                    }
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                if showsChevron {
                    Image(systemName: "chevron.right")
                        .font(.system(size: 12, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                        .padding(.top, 4)
                        .fixedSize()
                }
            }

            if let pic = vendor.pic_name, !pic.isEmpty {
                vendorInfoLine("person.fill", "PIC \(pic)")
            }
            if let phone = vendor.phone, !phone.isEmpty {
                vendorInfoLine("phone.fill", PhoneFormat.display(phone))
            }
            if let address = vendor.address, !address.isEmpty {
                vendorInfoLine("mappin.and.ellipse", address)
            }
            if let description = vendor.description, !description.isEmpty {
                Text(description)
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
            }

            vendorPricePair(publicAmount: vendor.linePublicValue, vendorAmount: vendor.lineVendorValue)
        }
        .contentShape(Rectangle())
    }

    private func vendorInfoLine(_ icon: String, _ text: String) -> some View {
        HStack(alignment: .top, spacing: 8) {
            Image(systemName: icon)
                .font(.system(size: 11, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .frame(width: 14)
                .padding(.top, 1)
            Text(text)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.ink)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                .fixedSize(horizontal: false, vertical: true)
        }
    }

    @ViewBuilder
    private func vendorPricePair(publicAmount: Int, vendorAmount: Int) -> some View {
        if publicAmount != 0 || vendorAmount != 0 {
            HStack(spacing: 12) {
                if publicAmount != 0 {
                    VStack(alignment: .leading, spacing: 2) {
                        Text("Publish")
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                        Text(MoneyFormat.idr(publicAmount))
                            .font(.poppins(.caption, weight: .bold))
                            .foregroundStyle(WofinsTheme.primary)
                            .lineLimit(1)
                            .minimumScaleFactor(0.7)
                    }
                    .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                }
                if vendorAmount != 0 {
                    VStack(alignment: .trailing, spacing: 2) {
                        Text("Vendor")
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                        Text(MoneyFormat.idr(vendorAmount))
                            .font(.poppins(.caption, weight: .bold))
                            .foregroundStyle(WofinsTheme.ink)
                            .lineLimit(1)
                            .minimumScaleFactor(0.7)
                    }
                    .frame(minWidth: 0, maxWidth: .infinity, alignment: .trailing)
                }
            }
        }
    }

    private func discountsCard(_ discounts: [FinanceProductDiscount]) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Diskon paket", systemImage: "tag.fill")
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
            ForEach(Array(discounts.enumerated()), id: \.element.id) { index, item in
                if index > 0 { Divider() }
                HStack(alignment: .top, spacing: 10) {
                    VStack(alignment: .leading, spacing: 3) {
                        Text(item.description ?? "Diskon")
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.ink)
                            .fixedSize(horizontal: false, vertical: true)
                        if let notes = item.notes, !notes.isEmpty {
                            Text(notes)
                                .font(.poppins(.caption2))
                                .foregroundStyle(WofinsTheme.muted)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)
                    Text("− \(MoneyFormat.idr(item.amount))")
                        .font(.poppins(.caption, weight: .bold))
                        .foregroundStyle(WofinsTheme.danger)
                        .lineLimit(1)
                        .minimumScaleFactor(0.7)
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var loadingCard: some View {
        HStack(spacing: 12) {
            ProgressView().tint(WofinsTheme.primary)
            Text("Memuat detail paket…")
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(18)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func errorCard(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Paket belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(WofinsTheme.danger)
            Text(message)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            Button("Coba lagi") { Task { await load() } }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .padding(.vertical, 12)
                .background(WofinsTheme.primary, in: Capsule())
        }
        .padding(18)
        .projectSurface()
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
            .frame(maxWidth: .infinity, alignment: .leading)
        }
    }

    private func vendorMeta(_ vendor: FinanceProductVendor) -> String {
        var parts: [String] = []
        if let qty = vendor.quantity { parts.append("\(qty)×") }
        if let category = vendor.category, !category.isEmpty { parts.append(category) }
        return parts.joined(separator: " · ")
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financeProduct(id: productId)
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct VendorDetailView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    let vendorId: Int
    var previewName: String?

    @State private var detail: FinanceVendorDetail?
    @State private var isLoading = false
    @State private var errorMessage: String?

    private var title: String { detail?.name ?? previewName ?? "Vendor" }

    var body: some View {
        VStack(spacing: 0) {
            header
            ScrollView(showsIndicators: false) {
                LazyVStack(spacing: 16) {
                    if isLoading && detail == nil {
                        loadingCard
                    } else if let errorMessage, detail == nil {
                        errorCard(errorMessage)
                    } else {
                        summaryCard
                        priceGrid
                    }
                }
                .padding(.top, 16)
                .padding(.bottom, 28)
            }
            .refreshable { await load() }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .toolbar(.hidden, for: .navigationBar)
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
                    .minimumScaleFactor(0.75)
                Text(detail?.category ?? "Detail vendor")
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var summaryCard: some View {
        VStack(alignment: .leading, spacing: 12) {
            if let category = detail?.category, !category.isEmpty {
                labeledRow("Kategori", category, icon: "square.grid.2x2.fill")
            }
            if let pic = detail?.pic_name, !pic.isEmpty {
                labeledRow("PIC", pic, icon: "person.fill")
            }
            if let phone = detail?.phone, !phone.isEmpty {
                if let url = PhoneFormat.telURL(phone) {
                    Link(destination: url) {
                        labeledRow("Telepon", PhoneFormat.display(phone), icon: "phone.fill")
                    }
                    .buttonStyle(.plain)
                } else {
                    labeledRow("Telepon", PhoneFormat.display(phone), icon: "phone.fill")
                }
            }
            if let address = detail?.address, !address.isEmpty {
                labeledRow("Alamat", address, icon: "mappin.and.ellipse")
            }
            if let description = detail?.description, !description.isEmpty {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Deskripsi")
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                    Text(description)
                        .font(.poppins(.subheadline))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private var priceGrid: some View {
        let publish = detail?.harga_publish
        let vendor = detail?.harga_vendor
        let profit = detail?.profit_amount
        let tiles: [(String, Int?, Color)] = [
            ("Harga publish", publish, WofinsTheme.primary),
            ("Harga vendor", (vendor ?? 0) != 0 ? vendor : nil, WofinsTheme.ink),
            ("Profit", (profit ?? 0) != 0 ? profit : nil, WofinsTheme.success),
        ].filter { ($0.1 ?? 0) != 0 }

        return Group {
            if !tiles.isEmpty {
                VStack(spacing: 12) {
                    ForEach(Array(stride(from: 0, to: tiles.count, by: 2)), id: \.self) { index in
                        if index + 1 < tiles.count {
                            HStack(spacing: 12) {
                                priceTile(tiles[index].0, tiles[index].1, tiles[index].2)
                                priceTile(tiles[index + 1].0, tiles[index + 1].1, tiles[index + 1].2)
                            }
                        } else {
                            priceTile(tiles[index].0, tiles[index].1, tiles[index].2)
                        }
                    }
                }
                .padding(.horizontal, 16)
            }
        }
    }

    private func priceTile(_ title: String, _ amount: Int?, _ color: Color) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
                .lineLimit(1)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.55)
                .frame(maxWidth: .infinity, alignment: .leading)
            RoundedRectangle(cornerRadius: 2)
                .fill(color)
                .frame(width: 28, height: 3)
        }
        .padding(14)
        .frame(maxWidth: .infinity, minHeight: 86, alignment: .topLeading)
        .projectSurface()
    }

    private var loadingCard: some View {
        HStack(spacing: 12) {
            ProgressView().tint(WofinsTheme.primary)
            Text("Memuat detail vendor…")
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(18)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func errorCard(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Vendor belum dapat dimuat", systemImage: "exclamationmark.triangle.fill")
                .font(.poppins(.headline, weight: .semibold))
                .foregroundStyle(WofinsTheme.danger)
            Text(message)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
            Button("Coba lagi") { Task { await load() } }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .padding(.vertical, 12)
                .background(WofinsTheme.primary, in: Capsule())
        }
        .padding(18)
        .projectSurface()
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
            .frame(maxWidth: .infinity, alignment: .leading)
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financeVendor(id: vendorId)
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

private func prospectStatusAppearance(_ status: String?) -> (color: Color, label: String) {
    switch status {
    case "no_order", nil, "":
        return (Color(red: 0.78, green: 0.55, blue: 0.00), "Hangat")
    default:
        return projectStatusAppearance(status)
    }
}

private func projectStatusAppearance(_ status: String?) -> (color: Color, label: String) {
    switch status {
    case "processing": return (WofinsTheme.success, "Berjalan")
    case "pending": return (Color(red: 0.76, green: 0.52, blue: 0.00), "Akan Datang")
    case "done": return (WofinsTheme.primary, "Selesai")
    case "cancelled": return (WofinsTheme.danger, "Batal")
    default: return (WofinsTheme.muted, status?.capitalized ?? "-")
    }
}

private extension FinanceProspectItem {
    var initials: String {
        let words = displayName
            .replacingOccurrences(of: "Pernikahan", with: "")
            .split(whereSeparator: { $0 == " " || $0 == "&" })
            .prefix(2)
        let result = words.compactMap(\.first).map(String.init).joined()
        return result.isEmpty ? "PS" : result.uppercased()
    }

    var eventDateRows: [(label: String, compact: String)] {
        [
            ("Lamaran", date_lamaran),
            ("Akad", date_akad),
            ("Resepsi", date_resepsi),
        ].compactMap { label, raw in
            guard let text = FinanceProjectItem.formatDisplayCompactDate(raw) else { return nil }
            return (label, text)
        }
    }
}

private extension FinanceProjectItem {
    var initials: String {
        let words = displayName
            .replacingOccurrences(of: "Pernikahan", with: "")
            .split(whereSeparator: { $0 == " " || $0 == "&" })
            .prefix(2)
        let result = words.compactMap(\.first).map(String.init).joined()
        return result.isEmpty ? "PR" : result.uppercased()
    }

    var eventDate: Date? {
        let candidates = [
            prospect?.date_resepsi,
            prospect?.date_akad,
            prospect?.date_lamaran,
            closing_date,
        ]

        for candidate in candidates {
            if let date = Self.parseDate(candidate) {
                return date
            }
        }
        return nil
    }

    var eventDateRows: [(label: String, value: String, compact: String, icon: String)] {
        [
            ("Lamaran", prospect?.date_lamaran, "heart.circle.fill"),
            ("Akad", prospect?.date_akad, "calendar.badge.clock"),
            ("Resepsi", prospect?.date_resepsi, "calendar"),
        ].compactMap { label, raw, icon in
            guard let date = Self.parseDate(raw) else { return nil }
            return (
                label,
                Self.displayDateFormatter.string(from: date),
                Self.compactDateFormatter.string(from: date),
                icon
            )
        }
    }

    static func formatDisplayDate(_ raw: String?) -> String? {
        guard let date = parseDate(raw) else { return nil }
        return displayDateFormatter.string(from: date)
    }

    static func formatDisplayCompactDate(_ raw: String?) -> String? {
        guard let date = parseDate(raw) else { return nil }
        return compactDateFormatter.string(from: date)
    }

    static func parseDate(_ raw: String?) -> Date? {
        let trimmed = raw?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        guard !trimmed.isEmpty, trimmed != "0", trimmed != "0000-00-00" else { return nil }
        return apiDateFormatter.date(from: String(trimmed.prefix(10)))
    }

    private static let apiDateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()

    private static let displayDateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "id_ID")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "d MMMM yyyy"
        return formatter
    }()

    private static let compactDateFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "id_ID")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "d MMM yyyy"
        return formatter
    }()
}

private extension View {
    func projectSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 18, style: .continuous)
                    .stroke(WofinsTheme.border.opacity(0.75), lineWidth: 1)
            }
            .shadow(color: WofinsTheme.primary.opacity(0.055), radius: 12, y: 5)
    }
}
