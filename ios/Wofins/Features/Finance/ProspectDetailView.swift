import SwiftUI

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
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}
