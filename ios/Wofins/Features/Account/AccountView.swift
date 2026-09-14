import SwiftUI
import PhotosUI

struct EditProfileView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    @State private var name = ""
    @State private var email = ""
    @State private var phone = ""
    @State private var address = ""
    @State private var emergency = ""
    @State private var gender = ""
    @State private var department = ""
    @State private var birthDate: Date?
    @State private var photoItem: PhotosPickerItem?
    @State private var avatarImage: UIImage?
    @State private var isSaving = false
    @State private var isUploadingPhoto = false
    @State private var errorMessage: String?
    @State private var successMessage: String?

    private var user: UserProfile? { appState.currentUser }
    private var canEditEmail: Bool { user?.isSuperAdmin == true }

    var body: some View {
        Form {
            Section {
                HStack(spacing: 14) {
                    avatarPreview
                        .frame(width: 56, height: 56)
                        .clipShape(Circle())

                    PhotosPicker(selection: $photoItem, matching: .images) {
                        Text(isUploadingPhoto ? "Mengunggah…" : "Ubah foto")
                            .font(.poppins(.subheadline))
                    }
                    .disabled(isUploadingPhoto)
                }
            }

            Section {
                TextField("Nama", text: $name)
                    .textContentType(.name)
                TextField("Email", text: $email)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.emailAddress)
                    .textContentType(.emailAddress)
                    .disabled(!canEditEmail)
                    .foregroundStyle(canEditEmail ? .primary : .secondary)
                TextField("Telepon", text: $phone)
                    .keyboardType(.phonePad)
                    .textContentType(.telephoneNumber)
                DatePicker(
                    "Tanggal lahir",
                    selection: Binding(
                        get: { birthDate ?? Calendar.current.date(byAdding: .year, value: -25, to: Date()) ?? Date() },
                        set: { birthDate = $0 }
                    ),
                    in: ...Calendar.current.date(byAdding: .year, value: -17, to: Date())!,
                    displayedComponents: .date
                )
                .environment(\.locale, Locale(identifier: "id_ID"))
                Picker("Jenis kelamin", selection: $gender) {
                    Text("Belum dipilih").tag("")
                    Text("Laki-laki").tag("male")
                    Text("Perempuan").tag("female")
                }
                Picker("Departemen", selection: $department) {
                    Text("Belum dipilih").tag("")
                    Text("Bisnis").tag("bisnis")
                    Text("Operasional").tag("operasional")
                }
                TextField("Alamat", text: $address, axis: .vertical)
                    .lineLimit(2...4)
                TextField("Kontak darurat", text: $emergency, axis: .vertical)
                    .lineLimit(2...4)
            }

            Section("Kepegawaian") {
                infoRow("ID karyawan", user?.employee_id)
                infoRow("Tanggal masuk", Self.displayDay(user?.hire_date))
                infoRow("Tanggal keluar", Self.displayDay(user?.last_working_date))
                infoRow("Status", user?.statusLabel)
                infoRow("Masa aktif", Self.displayDay(user?.expire_date))
                infoRow("Peran", user?.roleLabel)
            }

            Section("Perusahaan") {
                infoRow("Nama", user?.company?.name)
                infoRow("Inisial", user?.company?.inisial)
                infoRow("Paket", user?.company?.subscription_label)
            }

            if let errorMessage {
                Section {
                    Text(errorMessage).foregroundStyle(WofinsTheme.danger)
                }
            }
            if let successMessage {
                Section {
                    Text(successMessage).foregroundStyle(WofinsTheme.success)
                }
            }

            Section {
                Button {
                    Task { await save() }
                } label: {
                    if isSaving {
                        ProgressView()
                    } else {
                        Text("Simpan")
                    }
                }
                .disabled(isSaving || name.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty)
            }
        }
        .navigationTitle("Edit Profil")
        .navigationBarTitleDisplayMode(.inline)
        .wofinsSwipeBack()
        .scrollDismissesKeyboard(.immediately)
        .wofinsKeyboardDoneButton()
        .onAppear { loadUser() }
        .onChange(of: photoItem) { _, item in
            Task { await uploadSelectedPhoto(item) }
        }
    }

    @ViewBuilder
    private var avatarPreview: some View {
        if let avatarImage {
            Image(uiImage: avatarImage).resizable().scaledToFill()
        } else if let url = APIConfig.mediaURL(from: user?.avatar_url) {
            AsyncImage(url: url) { phase in
                if case .success(let image) = phase {
                    image.resizable().scaledToFill()
                } else {
                    Image(systemName: "person.crop.circle.fill")
                        .resizable()
                        .foregroundStyle(WofinsTheme.muted)
                }
            }
        } else {
            Image(systemName: "person.crop.circle.fill")
                .resizable()
                .foregroundStyle(WofinsTheme.muted)
        }
    }

    private func loadUser() {
        guard let user else { return }
        name = user.name
        email = user.email
        phone = user.phone_number ?? ""
        address = user.address ?? ""
        emergency = user.emergency_contact ?? ""
        gender = user.gender ?? ""
        department = user.department ?? ""
        birthDate = Self.parseDay(user.date_of_birth)
    }

    private func save() async {
        isSaving = true
        defer { isSaving = false }
        do {
            var payload = UpdateProfilePayload(
                name: name.trimmingCharacters(in: .whitespacesAndNewlines),
                phone_number: phone.trimmedNilIfEmpty,
                address: address.trimmedNilIfEmpty,
                date_of_birth: birthDate.map(Self.isoDay.string(from:)),
                gender: gender.isEmpty ? nil : gender,
                department: department.isEmpty ? nil : department,
                emergency_contact: emergency.trimmedNilIfEmpty
            )
            if canEditEmail {
                payload.email = email.trimmingCharacters(in: .whitespacesAndNewlines)
            }
            let updated = try await appState.api.updateProfile(payload)
            appState.currentUser = updated
            successMessage = "Profil disimpan."
            errorMessage = nil
            try? await Task.sleep(nanoseconds: 600_000_000)
            dismiss()
        } catch {
            if let message = APILoadFailure.userMessage(for: error) {
                errorMessage = message
                successMessage = nil
            }
        }
    }

    private func uploadSelectedPhoto(_ item: PhotosPickerItem?) async {
        guard let item else { return }
        isUploadingPhoto = true
        defer { isUploadingPhoto = false }
        do {
            guard let data = try await item.loadTransferable(type: Data.self),
                  let image = UIImage(data: data),
                  let jpeg = image.jpegData(compressionQuality: 0.82)
            else {
                errorMessage = "Foto tidak dapat dibaca."
                return
            }
            avatarImage = image
            let updated = try await appState.api.updateAvatar(imageData: jpeg)
            appState.currentUser = updated
            successMessage = "Foto profil diperbarui."
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private static func parseDay(_ value: String?) -> Date? {
        guard let value, !value.isEmpty else { return nil }
        return isoDay.date(from: String(value.prefix(10)))
    }

    private func infoRow(_ title: String, _ value: String?) -> some View {
        LabeledContent(title, value: (value?.isEmpty == false ? value : "—") ?? "—")
    }

    private static func displayDay(_ value: String?) -> String {
        guard let date = parseDay(value) else { return "—" }
        return display.string(from: date)
    }

    private static let isoDay: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()

    private static let display: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "id_ID")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "d MMM yyyy"
        return formatter
    }()
}

private extension String {
    var trimmedNilIfEmpty: String? {
        let trimmed = trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.isEmpty ? nil : trimmed
    }
}

struct ChangePasswordView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    @State private var current = ""
    @State private var password = ""
    @State private var confirmation = ""
    @State private var isSaving = false
    @State private var errorMessage: String?

    var body: some View {
        Form {
            Section {
                SecureField("Password saat ini", text: $current)
                SecureField("Password baru", text: $password)
                SecureField("Konfirmasi password baru", text: $confirmation)
            }
            if let errorMessage {
                Text(errorMessage).foregroundStyle(WofinsTheme.danger)
            }
            Section {
                Button {
                    Task { await save() }
                } label: {
                    if isSaving { ProgressView() } else { Text("Ubah Password") }
                }
                .disabled(isSaving || current.isEmpty || password.count < 8 || password != confirmation)
            }
        }
        .navigationTitle("Ganti Password")
        .wofinsSwipeBack()
        .scrollDismissesKeyboard(.immediately)
        .wofinsKeyboardDoneButton()
    }

    private func save() async {
        isSaving = true
        defer { isSaving = false }
        do {
            try await appState.api.updatePassword(
                current: current,
                password: password,
                confirmation: confirmation
            )
            dismiss()
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}
