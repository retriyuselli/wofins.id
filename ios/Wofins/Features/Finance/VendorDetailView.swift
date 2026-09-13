import SwiftUI

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
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

func prospectStatusAppearance(_ status: String?) -> (color: Color, label: String) {
    switch status {
    case "no_order", nil, "":
        return (Color(red: 0.78, green: 0.55, blue: 0.00), "Hangat")
    default:
        return projectStatusAppearance(status)
    }
}

func projectStatusAppearance(_ status: String?) -> (color: Color, label: String) {
    switch status {
    case "processing": return (WofinsTheme.success, "Berjalan")
    case "pending": return (Color(red: 0.76, green: 0.52, blue: 0.00), "Akan Datang")
    case "done": return (WofinsTheme.primary, "Selesai")
    case "cancelled": return (WofinsTheme.danger, "Batal")
    default: return (WofinsTheme.muted, status?.capitalized ?? "-")
    }
}

extension FinanceProspectItem {
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

extension FinanceProjectItem {
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
