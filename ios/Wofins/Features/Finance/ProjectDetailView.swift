import SwiftUI
import UIKit

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
                    LazyVStack(spacing: 16) {
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
                .scrollDismissesKeyboard(.immediately)
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
        .wofinsHidesNavigationBar()
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
                    GeometryReader { geo in
                        ZStack(alignment: .leading) {
                            Capsule()
                                .fill(WofinsTheme.border)
                            Capsule()
                                .fill(WofinsTheme.primary)
                                .frame(width: max(4, geo.size.width * progress))
                        }
                    }
                    .frame(height: 8)
                    .clipShape(Capsule())
                    .accessibilityLabel("Progress bayar \(Int(progress * 100)) persen")
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
        let grandTotal = detail?.grandTotalValue ?? preview?.grand_total ?? 0
        let expenses = detail?.expensesValue ?? preview?.expenses_total ?? 0
        let profit = ProjectProfitDisplay.amount(
            api: detail?.grossProfitValue ?? preview?.gross_profit,
            grandTotal: grandTotal,
            expenses: expenses
        )
        let profitColor = profit < 0 ? WofinsTheme.danger : WofinsTheme.success
        var tiles = [
            ("Nilai proyek", detail?.grandTotalValue ?? preview?.grand_total, WofinsTheme.primary),
            ("Terbayar", detail?.paidValue ?? preview?.paid_amount, WofinsTheme.success),
            ("Sisa", detail?.remainingValue ?? preview?.remaining, Color(red: 0.76, green: 0.52, blue: 0.00)),
            ("Pengeluaran", detail?.expensesValue ?? preview?.expenses_total, WofinsTheme.danger),
        ].filter { $0.0 == "Sisa" ? ($0.1 ?? 0) != 0 : ($0.1 ?? 0) != 0 }

        if detail != nil || preview != nil {
            tiles.append((ProjectProfitDisplay.title(for: profit), profit, profitColor))
        }

        return Group {
            if !tiles.isEmpty {
                VStack(alignment: .leading, spacing: 8) {
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
                    Text(ProjectProfitDisplay.caption)
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                        .fixedSize(horizontal: false, vertical: true)
                        .frame(maxWidth: .infinity, alignment: .leading)
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
        .frame(minWidth: 0, maxWidth: .infinity, minHeight: 86, alignment: .topLeading)
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
                            previewName: DisplayText.titleCase(item.name ?? "Paket"),
                            previewPax: item.pax,
                            previewPrice: item.unit_price
                        )
                    } label: {
                        packageRow(item)
                    }
                    .buttonStyle(.plain)
                } else {
                    packageRow(item)
                }
            }
        }
        .padding(17)
        .projectSurface()
        .padding(.horizontal, 16)
    }

    private func packageRow(_ item: FinanceProjectProduct) -> some View {
        HStack(alignment: .top, spacing: 10) {
            VStack(alignment: .leading, spacing: 3) {
                Text(DisplayText.titleCase(item.name ?? "Paket"))
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
            Text(MoneyFormat.idr(item.lineTotal))
                .font(.poppins(.caption, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
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
                        title: DisplayText.titleCase(item.keterangan ?? "Pembayaran"),
                        subtitle: [
                            formattedDate(item.date),
                            item.payment_method.map(DisplayText.titleCase)
                        ].compactMap { $0 }.filter { !$0.isEmpty }.joined(separator: " · "),
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
                        title: DisplayText.titleCase(item.vendor ?? item.note ?? "Pengeluaran"),
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
            APILoadFailure.assign(error, to: &errorMessage)
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
            APILoadFailure.assign(error, to: &actionMessage)
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

enum ProjectWhatsApp {
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
