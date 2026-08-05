import LocalAuthentication
import SwiftUI

struct LoginView: View {
    @EnvironmentObject private var appState: AppState
    @State private var email = ""
    @State private var password = ""
    @State private var rememberMe = true
    @State private var showPassword = false
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var faceNote = ""
    @FocusState private var focusedField: Field?

    private enum Field {
        case email, password
    }

    /// Bank Mandiri palette
    private let navy = Color(red: 0.0, green: 0.239, blue: 0.475) // #003D79
    private let navyDeep = Color(red: 0.0, green: 0.169, blue: 0.337) // #002B56
    private let gold = Color(red: 1.0, green: 0.725, blue: 0.0) // #FFB900
    private let muted = Color(red: 0.42, green: 0.486, blue: 0.576) // #6B7C93
    private let line = Color(red: 0.882, green: 0.906, blue: 0.937) // #E1E7EF
    private let canvas = Color(red: 0.969, green: 0.976, blue: 0.988) // #F7F9FC
    private let keychain = KeychainStore()

    var body: some View {
        ZStack {
            canvas
                .ignoresSafeArea()
                .contentShape(Rectangle())
                .onTapGesture { dismissKeyboard() }

            VStack(spacing: 0) {
                topBanner
                    .contentShape(Rectangle())
                    .onTapGesture { dismissKeyboard() }

                ScrollView(showsIndicators: false) {
                    VStack(spacing: 20) {
                        formPanel
                            .padding(.horizontal, 20)
                            .padding(.top, 22)

                        securityBadge
                            .padding(.bottom, 28)
                            .contentShape(Rectangle())
                            .onTapGesture { dismissKeyboard() }
                    }
                    .frame(maxWidth: .infinity)
                }
                .scrollDismissesKeyboard(.interactively)
                .onTapGesture { dismissKeyboard() }
            }
        }
        .toolbar {
            ToolbarItemGroup(placement: .keyboard) {
                Spacer()
                Button("Selesai") { dismissKeyboard() }
                    .font(.poppins(.body, weight: .semibold))
                    .foregroundStyle(navy)
            }
        }
        .onAppear {
            if let saved = UserDefaults.standard.string(forKey: "wofins.savedEmail"), !saved.isEmpty {
                email = saved
            }
        }
    }

    private func dismissKeyboard() {
        focusedField = nil
    }

    // MARK: - Top banner (new design language)

    private var topBanner: some View {
        ZStack(alignment: .bottom) {
            LinearGradient(
                colors: [navy, navyDeep],
                startPoint: .topLeading,
                endPoint: .bottomTrailing
            )
            .ignoresSafeArea(edges: .top)

            // Soft gold glow accents
            Circle()
                .fill(gold.opacity(0.18))
                .frame(width: 180, height: 180)
                .blur(radius: 40)
                .offset(x: 140, y: -70)

            Circle()
                .fill(Color.white.opacity(0.06))
                .frame(width: 220, height: 220)
                .offset(x: -130, y: 40)

            VStack(spacing: 14) {
                HStack(spacing: 12) {
                    logoBadge
                    VStack(alignment: .leading, spacing: 2) {
                        Text("WOFINS")
                            .font(.poppins(size: 28, weight: .heavy))
                            .foregroundStyle(.white)
                            .tracking(1.2)
                        Text("Wedding Organizer Financial System")
                            .font(.poppins(size: 11, weight: .medium))
                            .foregroundStyle(Color.white.opacity(0.78))
                    }
                    Spacer(minLength: 0)
                }

                Text("Kelola keuangan wedding organizer lebih mudah, akurat, dan terintegrasi.")
                    .font(.poppins(size: 13))
                    .foregroundStyle(Color.white.opacity(0.82))
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding(.top, 4)

                // Gold accent bar
                RoundedRectangle(cornerRadius: 2, style: .continuous)
                    .fill(gold)
                    .frame(width: 48, height: 4)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding(.top, 2)
            }
            .padding(.horizontal, 24)
            .padding(.top, 18)
            .padding(.bottom, 28)
        }
        .frame(maxWidth: .infinity)
        .frame(height: 210)
    }

    private var logoBadge: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .fill(Color.white.opacity(0.12))
                .frame(width: 52, height: 52)
                .overlay(
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(gold.opacity(0.55), lineWidth: 1.5)
                )

            Text("W")
                .font(.poppins(size: 26, weight: .heavy))
                .foregroundStyle(
                    LinearGradient(colors: [.white, gold], startPoint: .topLeading, endPoint: .bottomTrailing)
                )
        }
    }

    // MARK: - Form panel

    private var formPanel: some View {
        VStack(alignment: .leading, spacing: 0) {
            Text("Masuk ke akun")
                .font(.poppins(size: 22, weight: .bold))
                .foregroundStyle(navy)

            Text("Gunakan email dan password Anda")
                .font(.poppins(size: 13))
                .foregroundStyle(muted)
                .padding(.top, 6)

            if let errorMessage {
                Text(errorMessage)
                    .font(.poppins(.footnote))
                    .foregroundStyle(WofinsTheme.danger)
                    .padding(12)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .background(Color(red: 1, green: 0.94, blue: 0.94))
                    .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                    .padding(.top, 16)
            }

            labeledField(title: "Email atau Username") {
                HStack(spacing: 12) {
                    Image(systemName: "envelope.fill")
                        .font(.poppins(size: 14))
                        .foregroundStyle(navy.opacity(0.55))
                        .frame(width: 20)
                    TextField("nama@email.com", text: $email)
                        .textContentType(.username)
                        .keyboardType(.emailAddress)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .focused($focusedField, equals: .email)
                }
            }
            .padding(.top, 22)

            labeledField(title: "Password") {
                HStack(spacing: 12) {
                    Image(systemName: "lock.fill")
                        .font(.poppins(size: 14))
                        .foregroundStyle(navy.opacity(0.55))
                        .frame(width: 20)
                    Group {
                        if showPassword {
                            TextField("••••••••", text: $password)
                        } else {
                            SecureField("••••••••", text: $password)
                        }
                    }
                    .textContentType(.password)
                    .focused($focusedField, equals: .password)

                    Button {
                        showPassword.toggle()
                    } label: {
                        Image(systemName: showPassword ? "eye.slash.fill" : "eye.fill")
                            .font(.poppins(size: 14))
                            .foregroundStyle(muted)
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(.top, 16)

            HStack {
                Toggle(isOn: $rememberMe) {
                    Text("Ingat saya")
                        .font(.poppins(size: 13, weight: .medium))
                        .foregroundStyle(navyDeep)
                }
                .toggleStyle(MandiriCheckboxStyle(navy: navy, gold: gold))

                Spacer()

                Text("Lupa password?")
                    .font(.poppins(size: 13, weight: .semibold))
                    .foregroundStyle(navy)
            }
            .padding(.top, 18)

            Button {
                Task { await submit() }
            } label: {
                ZStack {
                    if isLoading {
                        ProgressView().tint(.white)
                    } else {
                        Text("Masuk")
                            .font(.poppins(size: 16, weight: .bold))
                    }
                }
                .frame(maxWidth: .infinity)
                .frame(height: 52)
                .foregroundStyle(.white)
                .background(
                    LinearGradient(colors: [navy, navyDeep], startPoint: .leading, endPoint: .trailing)
                )
                .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay(alignment: .bottom) {
                    Capsule()
                        .fill(gold)
                        .frame(height: 3)
                        .padding(.horizontal, 18)
                        .offset(y: 1)
                }
                .shadow(color: navy.opacity(0.28), radius: 14, y: 8)
            }
            .buttonStyle(.plain)
            .padding(.top, 22)
            .disabled(isLoading || email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || password.isEmpty)
            .opacity(isLoading || email.isEmpty || password.isEmpty ? 0.65 : 1)

            HStack(spacing: 12) {
                Rectangle().fill(line).frame(height: 1)
                Text("atau lanjut dengan")
                    .font(.poppins(size: 12))
                    .foregroundStyle(muted)
                    .fixedSize()
                Rectangle().fill(line).frame(height: 1)
            }
            .padding(.top, 22)

            Button {
                Task { await loginWithFaceID() }
            } label: {
                HStack(spacing: 10) {
                    Image(systemName: "faceid")
                        .font(.poppins(size: 20, weight: .medium))
                    Text("Face ID")
                        .font(.poppins(size: 15, weight: .semibold))
                }
                .frame(maxWidth: .infinity)
                .frame(height: 50)
                .foregroundStyle(navy)
                .background(canvas)
                .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay(
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(navy.opacity(0.18), lineWidth: 1.2)
                )
            }
            .buttonStyle(.plain)
            .padding(.top, 14)

            if !faceNote.isEmpty {
                Text(faceNote)
                    .font(.poppins(.caption))
                    .foregroundStyle(muted)
                    .frame(maxWidth: .infinity)
                    .padding(.top, 10)
            }
        }
        .padding(22)
        .background(Color.white)
        .clipShape(RoundedRectangle(cornerRadius: 22, style: .continuous))
        .shadow(color: navy.opacity(0.08), radius: 24, y: 10)
        .overlay(alignment: .topLeading) {
            Capsule()
                .fill(gold)
                .frame(width: 36, height: 4)
                .padding(.top, 10)
                .padding(.leading, 22)
        }
    }

    private var securityBadge: some View {
        HStack(spacing: 8) {
            Image(systemName: "checkmark.shield.fill")
                .foregroundStyle(navy.opacity(0.55))
            Text("Aplikasi aman dengan enkripsi end-to-end")
                .font(.poppins(size: 11))
                .foregroundStyle(muted)
        }
    }

    private func labeledField<Content: View>(title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(title)
                .font(.poppins(size: 12, weight: .semibold))
                .foregroundStyle(navy)
            content()
                .padding(.horizontal, 14)
                .frame(height: 50)
                .background(canvas)
                .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                .overlay(
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .stroke(line, lineWidth: 1)
                )
        }
    }

    // MARK: - Actions

    private func submit() async {
        dismissKeyboard()
        errorMessage = nil
        faceNote = ""
        isLoading = true
        defer { isLoading = false }
        let trimmed = email.trimmingCharacters(in: .whitespacesAndNewlines)
        do {
            try await appState.login(email: trimmed, password: password)
            if rememberMe {
                UserDefaults.standard.set(trimmed, forKey: "wofins.savedEmail")
                keychain.saveCredentials(email: trimmed, password: password)
            } else {
                UserDefaults.standard.removeObject(forKey: "wofins.savedEmail")
                keychain.clearCredentials()
            }
        } catch let error as URLError where error.code == .cannotConnectToHost || error.code == .timedOut || error.code == .networkConnectionLost {
            errorMessage = "Tidak terhubung ke \(APIConfig.baseURL.absoluteString). Pastikan API Mac nyala & satu Wi‑Fi."
        } catch {
            let text = error.localizedDescription
            if text.localizedCaseInsensitiveContains("could not connect")
                || text.localizedCaseInsensitiveContains("failed to connect") {
                errorMessage = "Tidak terhubung ke \(APIConfig.baseURL.absoluteString). Pastikan API Mac nyala & satu Wi‑Fi."
            } else {
                errorMessage = text
            }
        }
    }

    private func loginWithFaceID() async {
        dismissKeyboard()
        errorMessage = nil
        faceNote = ""

        guard let creds = keychain.readCredentials() else {
            faceNote = "Login sekali dulu, lalu Face ID bisa dipakai."
            return
        }

        let context = LAContext()
        var authError: NSError?
        guard context.canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: &authError) else {
            faceNote = "Face ID tidak tersedia di perangkat ini."
            return
        }

        do {
            let ok = try await context.evaluatePolicy(
                .deviceOwnerAuthenticationWithBiometrics,
                localizedReason: "Masuk ke WOFINS dengan Face ID"
            )
            guard ok else { return }
            email = creds.email
            password = creds.password
            await submit()
        } catch {
            faceNote = "Autentikasi Face ID dibatalkan."
        }
    }
}

private struct MandiriCheckboxStyle: ToggleStyle {
    let navy: Color
    let gold: Color

    func makeBody(configuration: Configuration) -> some View {
        Button {
            configuration.isOn.toggle()
        } label: {
            HStack(spacing: 8) {
                ZStack {
                    RoundedRectangle(cornerRadius: 5, style: .continuous)
                        .fill(configuration.isOn ? navy : Color.clear)
                        .frame(width: 20, height: 20)
                        .overlay(
                            RoundedRectangle(cornerRadius: 5, style: .continuous)
                                .stroke(configuration.isOn ? navy : Color.gray.opacity(0.4), lineWidth: 1.4)
                        )
                    if configuration.isOn {
                        Image(systemName: "checkmark")
                            .font(.poppins(size: 11, weight: .bold))
                            .foregroundStyle(gold)
                    }
                }
                configuration.label
            }
        }
        .buttonStyle(.plain)
    }
}
