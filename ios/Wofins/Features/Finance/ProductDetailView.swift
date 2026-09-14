import SwiftUI

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
                        if let detail {
                            ProductBreakdownView(detail: detail, padded: true)
                        }
                    }
                }
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
                labeledRow("Resepsi", "\(pax) pax", icon: "person.3.fill")
            }
            if let akad = detail?.pax_akad {
                labeledRow("Akad", "\(akad) pax", icon: "person.2.fill")
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

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            detail = try await appState.api.financeProduct(id: productId)
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

struct ProductBreakdownView: View {
    let detail: FinanceProductDetail
    var padded = false

    @State private var showImage = false

    private var facilities: [FinanceProductVendor] { detail.vendors ?? [] }
    private var additions: [FinanceProductVendor] { detail.additions ?? [] }
    private var discounts: [FinanceProductDiscount] {
        (detail.discounts ?? []).filter { ($0.amount ?? 0) != 0 || !HTMLText.plain($0.notes).isEmpty }
    }

    var body: some View {
        VStack(spacing: 16) {
            if let url = APIConfig.mediaURL(from: detail.image_url) {
                imageCard(url)
            }
            if let pricing = detail.pricing {
                pricingCard(pricing)
            }
            lineSection(title: "Fasilitas Dasar", icon: "cube.fill", items: facilities, emptyText: "Belum ada fasilitas dasar pada paket ini.")
            lineSection(title: "Penambahan Harga", icon: "plus.circle.fill", items: additions, emptyText: "Belum ada penambahan harga pada paket ini.")
            discountSection
        }
        .padding(.horizontal, padded ? 16 : 0)
        .fullScreenCover(isPresented: $showImage) {
            imagePreview
        }
    }

    private func imageCard(_ url: URL) -> some View {
        Button { showImage = true } label: {
            VStack(alignment: .leading, spacing: 10) {
                Text("Referensi Gambar")
                    .font(.poppins(.subheadline, weight: .bold))
                    .foregroundStyle(WofinsTheme.ink)
                AsyncImage(url: url) { phase in
                    switch phase {
                    case .success(let image):
                        image
                            .resizable()
                            .scaledToFill()
                    case .failure:
                        imagePlaceholder("Gambar tidak dapat dimuat")
                    default:
                        imagePlaceholder(nil)
                    }
                }
                .frame(maxWidth: .infinity)
                .frame(height: 180)
                .clipped()
                .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
            }
            .padding(16)
            .frame(maxWidth: .infinity, alignment: .leading)
            .moduleSurface()
        }
        .buttonStyle(.plain)
        .accessibilityLabel("Lihat referensi gambar paket")
    }

    private var imagePreview: some View {
        ZStack(alignment: .topTrailing) {
            Color.black.ignoresSafeArea()
            if let url = APIConfig.mediaURL(from: detail.image_url) {
                AsyncImage(url: url) { phase in
                    switch phase {
                    case .success(let image):
                        image
                            .resizable()
                            .scaledToFit()
                            .frame(maxWidth: .infinity, maxHeight: .infinity)
                    default:
                        ProgressView().tint(.white)
                    }
                }
                .padding(.top, 48)
            }
            Button { showImage = false } label: {
                Image(systemName: "xmark")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.18), in: Circle())
            }
            .padding(.top, 12)
            .padding(.trailing, 16)
            .accessibilityLabel("Tutup")
        }
    }

    private func imagePlaceholder(_ text: String?) -> some View {
        ZStack {
            WofinsTheme.primary.opacity(0.08)
            if let text {
                Text(text)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
            } else {
                ProgressView().tint(WofinsTheme.primary)
            }
        }
    }

    private func pricingCard(_ pricing: FinanceProductPricing) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text("Kalkulasi Harga")
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
            HStack(spacing: 6) {
                Text("Keterangan")
                    .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                Text("Vendor")
                    .frame(width: 78, alignment: .trailing)
                Text("Publish")
                    .frame(width: 78, alignment: .trailing)
            }
            .font(.poppins(.caption2, weight: .semibold))
            .foregroundStyle(WofinsTheme.muted)

            pricingRow("Harga Awal", pricing.harga_awal_publish, pricing.harga_awal_vendor)
            pricingRow("Penambahan", pricing.penambahan_publish, pricing.penambahan_vendor, tone: .plus)
            pricingRow("Subtotal", pricing.subtotal_publish, pricing.subtotal_vendor, emphasize: true)
            pricingRow("Pengurangan", pricing.pengurangan, pricing.pengurangan, tone: .minus)
            pricingRow("Total Paket", pricing.total_publish, pricing.total_vendor, emphasize: true)
            pricingRow("Profit / Loss", nil, pricing.profit, tone: .profit, emphasize: true)
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .moduleSurface()
    }

    private enum PricingTone { case plus, minus, profit }

    private func pricingRow(_ label: String, _ publish: Int?, _ vendor: Int?, tone: PricingTone? = nil, emphasize: Bool = false) -> some View {
        HStack(alignment: .top, spacing: 6) {
            Text(label)
                .font(.poppins(.caption, weight: emphasize ? .bold : .regular))
                .foregroundStyle(WofinsTheme.ink)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                .fixedSize(horizontal: false, vertical: true)
            pricingAmount(vendor, tone: tone == .profit ? profitTone(vendor) : tone)
            pricingAmount(publish, tone: tone)
        }
    }

    private func profitTone(_ amount: Int?) -> PricingTone {
        (amount ?? 0) < 0 ? .minus : .plus
    }

    private func pricingAmount(_ amount: Int?, tone: PricingTone?) -> some View {
        Group {
            if let amount {
                Text(signedMoney(amount, tone: tone))
                    .font(.poppins(.caption2, weight: .bold))
                    .foregroundStyle(amountColor(amount, tone: tone))
                    .lineLimit(1)
                    .minimumScaleFactor(0.55)
            } else {
                Text("—")
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
            }
        }
        .frame(width: 78, alignment: .trailing)
    }

    private func signedMoney(_ amount: Int, tone: PricingTone?) -> String {
        let value = MoneyFormat.idr(abs(amount))
        switch tone {
        case .plus where amount != 0: return "+ \(value)"
        case .minus where amount != 0: return "− \(value)"
        default: return value
        }
    }

    private func amountColor(_ amount: Int, tone: PricingTone?) -> Color {
        switch tone {
        case .plus: return WofinsTheme.success
        case .minus: return WofinsTheme.danger
        case .profit: return amount < 0 ? WofinsTheme.danger : WofinsTheme.success
        case nil: return WofinsTheme.ink
        }
    }

    private func lineSection(title: String, icon: String, items: [FinanceProductVendor], emptyText: String?) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Label(title, systemImage: icon)
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
            if items.isEmpty, let emptyText {
                Text(emptyText)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
            } else {
                ForEach(Array(items.enumerated()), id: \.offset) { index, item in
                    if index > 0 { Divider() }
                    if let vendorId = item.vendor_id {
                        NavigationLink {
                            VendorDetailView(vendorId: vendorId, previewName: item.name)
                        } label: {
                            vendorLine(item, showsChevron: true)
                        }
                        .buttonStyle(.plain)
                    } else {
                        vendorLine(item, showsChevron: false)
                    }
                }
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .moduleSurface()
    }

    private func vendorLine(_ item: FinanceProductVendor, showsChevron: Bool) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            HStack(alignment: .top, spacing: 10) {
                VStack(alignment: .leading, spacing: 3) {
                    Text(DisplayText.titleCase(item.name ?? "Vendor"))
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .multilineTextAlignment(.leading)
                        .fixedSize(horizontal: false, vertical: true)
                    if !vendorMeta(item).isEmpty {
                        Text(vendorMeta(item))
                            .font(.poppins(.caption2))
                            .foregroundStyle(WofinsTheme.muted)
                    }
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                if showsChevron {
                    Image(systemName: "chevron.right")
                        .font(.system(size: 12, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                        .padding(.top, 4)
                }
            }
            if let description = item.description, !description.isEmpty {
                HTMLListView(text: description)
                    .padding(.top, 2)
            }
            pricePair(publicAmount: item.linePublicValue, vendorAmount: item.lineVendorValue)
        }
        .contentShape(Rectangle())
    }

    private var discountSection: some View {
        VStack(alignment: .leading, spacing: 10) {
            Label("Pengurangan Harga", systemImage: "minus.circle.fill")
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
            if discounts.isEmpty && (detail.free_pengurangan ?? "").isEmpty {
                Text("Belum ada pengurangan harga pada paket ini.")
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
            }
            if let free = detail.free_pengurangan, !free.isEmpty {
                VStack(alignment: .leading, spacing: 3) {
                    Text("Free")
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                    Text(free)
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
            ForEach(Array(discounts.enumerated()), id: \.offset) { index, item in
                if index > 0 { Divider() }
                HStack(alignment: .top, spacing: 10) {
                    VStack(alignment: .leading, spacing: 3) {
                        Text(DisplayText.titleCase(item.description ?? "Pengurangan"))
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
                    .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    Text("− \(MoneyFormat.idr(item.amount))")
                        .font(.poppins(.caption, weight: .bold))
                        .foregroundStyle(WofinsTheme.danger)
                        .lineLimit(1)
                        .minimumScaleFactor(0.7)
                }
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .moduleSurface()
    }

    private func pricePair(publicAmount: Int, vendorAmount: Int) -> some View {
        Group {
            if publicAmount != 0 || vendorAmount != 0 {
                HStack(spacing: 12) {
                    if vendorAmount != 0 {
                        VStack(alignment: .leading, spacing: 2) {
                            Text("Vendor")
                                .font(.poppins(.caption2))
                                .foregroundStyle(WofinsTheme.muted)
                            Text(MoneyFormat.idr(vendorAmount))
                                .font(.poppins(.caption, weight: .bold))
                                .foregroundStyle(WofinsTheme.ink)
                                .lineLimit(1)
                                .minimumScaleFactor(0.7)
                        }
                        .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    }
                    if publicAmount != 0 {
                        VStack(alignment: .trailing, spacing: 2) {
                            Text("Publish")
                                .font(.poppins(.caption2))
                                .foregroundStyle(WofinsTheme.muted)
                            Text(MoneyFormat.idr(publicAmount))
                                .font(.poppins(.caption, weight: .bold))
                                .foregroundStyle(WofinsTheme.primary)
                                .lineLimit(1)
                                .minimumScaleFactor(0.7)
                        }
                        .frame(minWidth: 0, maxWidth: .infinity, alignment: .trailing)
                    }
                }
            }
        }
    }

    private func vendorMeta(_ vendor: FinanceProductVendor) -> String {
        var parts: [String] = []
        if let qty = vendor.quantity, qty > 1 { parts.append("\(qty)×") }
        if let category = vendor.category, !category.isEmpty { parts.append(DisplayText.titleCase(category)) }
        return parts.joined(separator: " · ")
    }
}

struct HTMLListView: View {
    let text: String
    var numbered: Bool? = nil
    var font: Font = .poppins(.caption2)
    var color: Color = WofinsTheme.muted

    private var items: [String] { HTMLText.listItems(text) }
    private var useNumbers: Bool { numbered ?? HTMLText.isOrderedList(text) }

    var body: some View {
        if items.count <= 1 {
            Text(items.first ?? text)
                .font(font)
                .foregroundStyle(color)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                .fixedSize(horizontal: false, vertical: true)
        } else {
            VStack(alignment: .leading, spacing: 5) {
                ForEach(Array(items.enumerated()), id: \.offset) { index, item in
                    HStack(alignment: .top, spacing: 8) {
                        Text(useNumbers ? "\(index + 1)." : "•")
                            .font(font)
                            .fontWeight(.semibold)
                            .foregroundStyle(color)
                            .frame(width: useNumbers ? 24 : 12, alignment: .trailing)
                            .padding(.top, 1)
                        Text(item)
                            .font(font)
                            .foregroundStyle(color)
                            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                            .fixedSize(horizontal: false, vertical: true)
                    }
                }
            }
            .frame(maxWidth: .infinity, alignment: .leading)
        }
    }
}
