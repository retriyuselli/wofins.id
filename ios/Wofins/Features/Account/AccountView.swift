import SwiftUI

struct AccountView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        NavigationStack {
            List {
                if let user = appState.currentUser {
                    Section {
                        VStack(alignment: .leading, spacing: 6) {
                            Text(user.name).font(.poppins(.title3, weight: .bold))
                            Text(user.email).foregroundStyle(WofinsTheme.muted)
                            if let phone = user.phone_number, !phone.isEmpty {
                                Text(phone).font(.poppins(.subheadline))
                            }
                        }
                        .padding(.vertical, 4)
                    }

                    Section("Akun") {
                        NavigationLink("Edit profil") {
                            EditProfileView()
                        }
                        NavigationLink("Ganti password") {
                            ChangePasswordView()
                        }
                    }

                    Section("SDM") {
                        NavigationLink {
                            AttendanceView()
                        } label: {
                            Label("Absensi", systemImage: "checkmark.circle.fill")
                        }
                        NavigationLink {
                            LeaveListView()
                        } label: {
                            Label("Cuti", systemImage: "calendar")
                        }
                        NavigationLink {
                            CompensationView()
                        } label: {
                            Label("Kompensasi", systemImage: "banknote.fill")
                        }
                    }
                }

                Section {
                    Button(role: .destructive) {
                        Task { await appState.logout() }
                    } label: {
                        Label("Logout", systemImage: "rectangle.portrait.and.arrow.right")
                    }
                }
            }
            .navigationTitle("Akun")
        }
    }
}

struct EditProfileView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    @State private var name = ""
    @State private var email = ""
    @State private var phone = ""
    @State private var address = ""
    @State private var emergency = ""
    @State private var isSaving = false
    @State private var errorMessage: String?
    @State private var successMessage: String?

    var body: some View {
        Form {
            Section {
                TextField("Nama", text: $name)
                TextField("Email", text: $email)
                    .textInputAutocapitalization(.never)
                    .keyboardType(.emailAddress)
                TextField("Telepon", text: $phone)
                    .keyboardType(.phonePad)
                TextField("Alamat", text: $address, axis: .vertical)
                TextField("Kontak darurat", text: $emergency)
            }

            if let errorMessage {
                Text(errorMessage).foregroundStyle(WofinsTheme.danger)
            }
            if let successMessage {
                Text(successMessage).foregroundStyle(WofinsTheme.accent)
            }

            Section {
                Button {
                    Task { await save() }
                } label: {
                    if isSaving { ProgressView() } else { Text("Simpan") }
                }
                .disabled(isSaving || name.isEmpty || email.isEmpty)
            }
        }
        .navigationTitle("Edit Profil")
        .onAppear {
            guard let user = appState.currentUser else { return }
            name = user.name
            email = user.email
            phone = user.phone_number ?? ""
            address = user.address ?? ""
            emergency = user.emergency_contact ?? ""
        }
    }

    private func save() async {
        isSaving = true
        defer { isSaving = false }
        do {
            let payload = UpdateProfilePayload(
                name: name,
                email: email,
                phone_number: phone.isEmpty ? nil : phone,
                address: address.isEmpty ? nil : address,
                emergency_contact: emergency.isEmpty ? nil : emergency
            )
            let updated = try await appState.api.updateProfile(payload)
            appState.currentUser = updated
            successMessage = "Profil disimpan."
            errorMessage = nil
            try? await Task.sleep(nanoseconds: 600_000_000)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
            successMessage = nil
        }
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
            errorMessage = error.localizedDescription
        }
    }
}
