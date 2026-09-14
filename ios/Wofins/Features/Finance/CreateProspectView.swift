import SwiftUI

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
                LazyVStack(spacing: 16) {
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
                        field("Total penawaran", text: MoneyFormat.groupedBinding($offerText), focus: .offer, keyboard: .numberPad, prefix: "Rp")
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
        .wofinsHidesNavigationBar()
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
            APILoadFailure.assign(error, to: &errorMessage)
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
