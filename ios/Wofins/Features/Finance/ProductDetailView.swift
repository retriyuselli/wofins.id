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
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}
