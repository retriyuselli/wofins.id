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
    @State private var googleInfoMessage: String?
    @State private var faceNote = ""
    @State private var showPrivacy = false
    @State private var showForgotPassword = false
    @State private var selectedHost = APIConfig.selectedHost
    @State private var showInternalHostPicker = LoginHostPolicy.isInternalUnlocked
    @State private var logoUnlockTaps = 0
    @State private var internalAccessCode = ""
    @State private var revealedInternalHosts = LoginHostPolicy.revealedInternalHosts
    @State private var hostCodeNotice: String?
    @State private var isVerifyingHostCode = false
    @FocusState private var focusedField: Field?

    private enum Field {
        case email, password, hostCode
    }

    private var navy: Color { WofinsTheme.primary }
    private var navyDeep: Color { WofinsTheme.primaryDark }
    private var gold: Color { WofinsTheme.yellow }
    private var muted: Color { WofinsTheme.muted }
    private var line: Color { WofinsTheme.border }
    private var canvas: Color { WofinsTheme.background }
    private let keychain = KeychainStore()

    private var canSubmitLogin: Bool {
        !isLoading
            && !email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && !password.isEmpty
    }

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
                    LazyVStack(spacing: 20) {
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
                .wofinsFormScrollBehavior()
                .onTapGesture { dismissKeyboard() }
            }
        }
        .sheet(isPresented: $showPrivacy) {
            PrivacyStatementView()
        }
        .sheet(isPresented: $showForgotPassword) {
            ForgotPasswordView()
                .environmentObject(appState)
        }
        .alert(
            "Akun belum terdaftar",
            isPresented: Binding(
                get: { googleInfoMessage != nil },
                set: { if !$0 { googleInfoMessage = nil } }
            )
        ) {
            Button("Mengerti", role: .cancel) { googleInfoMessage = nil }
        } message: {
            Text(googleInfoMessage ?? "")
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
            showInternalHostPicker = LoginHostPolicy.isInternalUnlocked
            selectedHost = APIConfig.selectedHost
            let publicHost = LoginHostPolicy.resolvedHost(
                unlocked: showInternalHostPicker,
                current: selectedHost
            )
            if publicHost != selectedHost {
                applyHost(publicHost)
            }
            if showInternalHostPicker {
                var revealed = LoginHostPolicy.revealedInternalHosts
                if selectedHost.isInternalHost {
                    revealed.insert(selectedHost)
                    LoginHostPolicy.revealedInternalHosts = revealed
                }
                revealedInternalHosts = revealed
            } else {
                revealedInternalHosts = []
            }
            if let saved = UserDefaults.standard.string(forKey: "wofins.savedEmail"), !saved.isEmpty {
                email = saved
            }
        }
    }

    private func dismissKeyboard() {
        focusedField = nil
    }

    private func openForgotPassword() {
        dismissKeyboard()
        showForgotPassword = true
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
                        .simultaneousGesture(TapGesture().onEnded(handleLogoUnlockTap))
                        .accessibilityLabel("WOFINS")
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

                    // Accent bar mengikuti tema yang sedang aktif.
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
        WofinsBrandMark()
    }

    // MARK: - Form panel

    private var formPanel: some View {
        VStack(alignment: .leading, spacing: 0) {
            Text("Masuk ke akun")
                .font(.poppins(size: 22, weight: .bold))
                .foregroundStyle(navy)

            Text(LoginHostPolicy.accountHint(for: selectedHost, unlocked: showInternalHostPicker))
                .font(.poppins(size: 13))
                .foregroundStyle(muted)
                .padding(.top, 6)
                .fixedSize(horizontal: false, vertical: true)

            if showInternalHostPicker {
                hostPicker
                    .padding(.top, 16)
            }

            if let errorMessage {
                Text(errorMessage)
                    .font(.poppins(.footnote))
                    .foregroundStyle(WofinsTheme.danger)
                    .padding(12)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .background(WofinsTheme.danger.opacity(0.09))
                    .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                    .padding(.top, 16)
            }

            labeledField(title: "Email") {
                HStack(spacing: 12) {
                    Image(systemName: "envelope.fill")
                        .font(.poppins(size: 14))
                        .foregroundStyle(navy.opacity(0.55))
                        .frame(width: 20)
                    ZStack(alignment: .leading) {
                        // Prompt SwiftUI ikut .tint app (bisa biru); overlay agar tetap abu-abu.
                        if email.isEmpty {
                            Text("nama@email.com")
                                .font(.poppins(size: 15))
                                .foregroundStyle(muted)
                                .allowsHitTesting(false)
                        }
                        TextField("", text: $email)
                            .font(.poppins(size: 15))
                            .foregroundStyle(navyDeep)
                            .textContentType(.username)
                            .keyboardType(.emailAddress)
                            .textInputAutocapitalization(.never)
                            .autocorrectionDisabled()
                            .focused($focusedField, equals: .email)
                    }
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

                Button(action: openForgotPassword) {
                    Text("Lupa password?")
                        .font(.poppins(size: 13, weight: .semibold))
                        .foregroundStyle(navy)
                }
                .buttonStyle(.plain)
                .disabled(isLoading)
            }
            .padding(.top, 18)

            Button {
                guard canSubmitLogin else { return }
                Task { await submit() }
            } label: {
                ZStack {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .fill(LinearGradient(colors: [navy, navyDeep], startPoint: .leading, endPoint: .trailing))

                    if isLoading {
                        ProgressView()
                            .tint(Color.white)
                    } else {
                        Text("Masuk")
                            .font(.poppins(size: 16, weight: .bold))
                            .foregroundStyle(Color.white)
                    }
                }
                .frame(maxWidth: .infinity)
                .frame(height: 52)
                .overlay(alignment: .bottom) {
                    Capsule()
                        .fill(gold)
                        .frame(height: 3)
                        .padding(.horizontal, 18)
                        .offset(y: 1)
                }
                .shadow(color: navy.opacity(canSubmitLogin ? 0.28 : 0.14), radius: 14, y: 8)
                .opacity(canSubmitLogin || isLoading ? 1 : 0.88)
            }
            .buttonStyle(.plain)
            .padding(.top, 22)
            // Hindari `.disabled` — iOS mengaburkan teks putih jadi hampir tak terbaca.

            HStack(spacing: 12) {
                Rectangle().fill(line).frame(height: 1)
                Text("atau lanjut dengan")
                    .font(.poppins(size: 12))
                    .foregroundStyle(muted)
                    .fixedSize()
                Rectangle().fill(line).frame(height: 1)
            }
            .padding(.top, 22)

            HStack(spacing: 8) {
                Button {
                    Task { await loginWithGoogle() }
                } label: {
                    VStack(spacing: 4) {
                        googleMark
                        Text("Google")
                            .font(.poppins(size: 12, weight: .semibold))
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }
                    .frame(maxWidth: .infinity)
                    .frame(height: 56)
                    .foregroundStyle(navy)
                    .background(WofinsTheme.card)
                    .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                    .overlay(
                        RoundedRectangle(cornerRadius: 14, style: .continuous)
                            .stroke(navy.opacity(0.18), lineWidth: 1.2)
                    )
                }
                .buttonStyle(.plain)
                .disabled(isLoading)
                .opacity(isLoading ? 0.65 : 1)
                .accessibilityLabel("Masuk dengan Google")

                Button {
                    Task { await startAppleSignIn() }
                } label: {
                    VStack(spacing: 4) {
                        Image(systemName: "apple.logo")
                            .font(.system(size: 18, weight: .medium))
                        Text("Apple")
                            .font(.poppins(size: 12, weight: .semibold))
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }
                    .frame(maxWidth: .infinity)
                    .frame(height: 56)
                    .foregroundStyle(navy)
                    .background(WofinsTheme.card)
                    .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                    .overlay(
                        RoundedRectangle(cornerRadius: 14, style: .continuous)
                            .stroke(navy.opacity(0.18), lineWidth: 1.2)
                    )
                }
                .buttonStyle(.plain)
                .disabled(isLoading)
                .opacity(isLoading ? 0.65 : 1)
                .accessibilityLabel("Masuk dengan Apple")

                Button {
                    Task { await loginWithFaceID() }
                } label: {
                    VStack(spacing: 4) {
                        Image(systemName: "faceid")
                            .font(.system(size: 18, weight: .medium))
                        Text("Face ID")
                            .font(.poppins(size: 12, weight: .semibold))
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }
                    .frame(maxWidth: .infinity)
                    .frame(height: 56)
                    .foregroundStyle(navy)
                    .background(WofinsTheme.card)
                    .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
                    .overlay(
                        RoundedRectangle(cornerRadius: 14, style: .continuous)
                            .stroke(navy.opacity(0.18), lineWidth: 1.2)
                    )
                }
                .buttonStyle(.plain)
                .disabled(isLoading)
                .opacity(isLoading ? 0.65 : 1)
                .accessibilityLabel("Masuk dengan Face ID")
            }
            .frame(maxWidth: .infinity)
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
        .background(WofinsTheme.card)
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

    private func handleLogoUnlockTap() {
        logoUnlockTaps += 1
        guard logoUnlockTaps >= LoginHostPolicy.unlockTapCount else { return }
        logoUnlockTaps = 0
        let unlocked = !LoginHostPolicy.isInternalUnlocked
        LoginHostPolicy.isInternalUnlocked = unlocked
        showInternalHostPicker = unlocked
        hostCodeNotice = nil
        internalAccessCode = ""
        if unlocked {
            if selectedHost.isInternalHost {
                var revealed = LoginHostPolicy.revealedInternalHosts
                revealed.insert(selectedHost)
                LoginHostPolicy.revealedInternalHosts = revealed
                revealedInternalHosts = revealed
            } else {
                revealedInternalHosts = LoginHostPolicy.revealedInternalHosts
            }
        } else {
            LoginHostPolicy.clearRevealedHosts()
            revealedInternalHosts = []
            applyHost(.wofins)
        }
        UINotificationFeedbackGenerator().notificationOccurred(unlocked ? .success : .warning)
    }

    private var visibleHostOptions: [APIHostOption] {
        LoginHostPolicy.visibleHostOptions(revealed: revealedInternalHosts)
    }

    private var hostPicker: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Akses internal")
                .font(.poppins(size: 12, weight: .semibold))
                .foregroundStyle(navy)

            HStack(spacing: 8) {
                ZStack(alignment: .leading) {
                    if internalAccessCode.isEmpty {
                        Text("Item Purchase Code")
                            .font(.poppins(size: 13))
                            .foregroundStyle(muted)
                            .allowsHitTesting(false)
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }
                    TextField("", text: $internalAccessCode)
                        .font(.poppins(size: 13))
                        .foregroundStyle(navyDeep)
                        .textInputAutocapitalization(.never)
                        .autocorrectionDisabled()
                        .keyboardType(.asciiCapable)
                        .textContentType(.none)
                        .submitLabel(.go)
                        .focused($focusedField, equals: .hostCode)
                        .onSubmit { Task { await submitInternalAccessCode() } }
                }
                .padding(.horizontal, 12)
                .frame(maxWidth: .infinity, minHeight: 44, maxHeight: 44)
                .background(canvas)
                .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                .overlay(
                    RoundedRectangle(cornerRadius: 12, style: .continuous)
                        .stroke(line, lineWidth: 1)
                )

                Button {
                    Task { await submitInternalAccessCode() }
                } label: {
                    Group {
                        if isVerifyingHostCode {
                            ProgressView()
                                .tint(.white)
                        } else {
                            Text("Buka")
                                .font(.poppins(size: 13, weight: .semibold))
                                .lineLimit(1)
                        }
                    }
                    .frame(height: 44)
                    .padding(.horizontal, 14)
                    .foregroundStyle(Color.white)
                    .background(navy)
                    .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                }
                .buttonStyle(.plain)
                .disabled(
                    isLoading
                        || isVerifyingHostCode
                        || internalAccessCode.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
                )
                .accessibilityLabel("Buka host dengan purchase code")
            }
            .frame(maxWidth: .infinity)

            if let hostCodeNotice {
                Text(hostCodeNotice)
                    .font(.poppins(size: 11))
                    .foregroundStyle(hostCodeNoticeHasError ? WofinsTheme.danger : muted)
                    .fixedSize(horizontal: false, vertical: true)
            }

            LazyVGrid(
                columns: [
                    GridItem(.flexible(), spacing: 8),
                    GridItem(.flexible(), spacing: 8),
                ],
                spacing: 8
            ) {
                ForEach(visibleHostOptions) { option in
                    Button {
                        applyHost(option)
                    } label: {
                        Text(option.title)
                            .font(.poppins(size: 13, weight: .semibold))
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                            .frame(maxWidth: .infinity)
                            .frame(height: 44)
                            .foregroundStyle(selectedHost == option ? Color.white : navy)
                            .background(selectedHost == option ? navy : canvas)
                            .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                            .overlay(
                                RoundedRectangle(cornerRadius: 12, style: .continuous)
                                    .stroke(selectedHost == option ? navy : line, lineWidth: 1)
                            )
                    }
                    .buttonStyle(.plain)
                    .disabled(isLoading)
                    .accessibilityLabel(option.title)
                    .accessibilityAddTraits(selectedHost == option ? .isSelected : [])
                }
            }
            .frame(maxWidth: .infinity)

            Text(selectedHost.subtitle)
                .font(.poppins(size: 11))
                .foregroundStyle(muted)
                .frame(maxWidth: .infinity, alignment: .leading)
                .lineLimit(1)
                .minimumScaleFactor(0.8)
        }
    }

    private var hostCodeNoticeHasError: Bool {
        guard let hostCodeNotice else { return false }
        let lower = hostCodeNotice.lowercased()
        return lower.contains("tidak")
            || lower.contains("gagal")
            || lower.contains("habis")
            || lower.contains("dicabut")
            || lower.contains("invalid")
    }

    private func submitInternalAccessCode() async {
        dismissKeyboard()
        let code = internalAccessCode.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !code.isEmpty else { return }
        guard ItemPurchaseCodeClient.isPurchaseCodeFormat(code) else {
            hostCodeNotice = "Purchase code harus UUID dari maknafinance.id."
            UINotificationFeedbackGenerator().notificationOccurred(.error)
            return
        }

        isVerifyingHostCode = true
        hostCodeNotice = nil
        defer { isVerifyingHostCode = false }

        do {
            let result = try await ItemPurchaseCodeClient.verify(code: code)
            guard result.valid else {
                hostCodeNotice = result.message.isEmpty ? "Purchase code tidak valid." : result.message
                UINotificationFeedbackGenerator().notificationOccurred(.error)
                return
            }
            guard let domain = result.domain,
                  let host = APIHostOption.fromPurchaseDomain(domain, companyName: result.company_name) else {
                hostCodeNotice = "Purchase code valid, tetapi domain belum terisi di maknafinance.id."
                UINotificationFeedbackGenerator().notificationOccurred(.error)
                return
            }
            let revealed = LoginHostPolicy.reveal(host) ?? host
            revealedInternalHosts = LoginHostPolicy.revealedInternalHosts
            internalAccessCode = ""
            hostCodeNotice = "Host \(revealed.title) terbuka (\(revealed.host))."
            applyHost(revealed)
            UINotificationFeedbackGenerator().notificationOccurred(.success)
        } catch {
            hostCodeNotice = APILoadFailure.userMessage(for: error)
                ?? (error as? LocalizedError)?.errorDescription
                ?? "Gagal verifikasi purchase code."
            UINotificationFeedbackGenerator().notificationOccurred(.error)
        }
    }

    private func applyHost(_ option: APIHostOption) {
        guard selectedHost != option else { return }
        dismissKeyboard()
        errorMessage = nil
        password = ""
        appState.selectAPIHost(option)
        selectedHost = option
    }

    private var googleMark: some View {
        Text("G")
            .font(.poppins(size: 18, weight: .bold))
            .foregroundStyle(
                LinearGradient(
                    colors: [
                        Color(red: 0.26, green: 0.52, blue: 0.96),
                        Color(red: 0.20, green: 0.66, blue: 0.33),
                        Color(red: 0.98, green: 0.74, blue: 0.02),
                        Color(red: 0.92, green: 0.26, blue: 0.21),
                    ],
                    startPoint: .topLeading,
                    endPoint: .bottomTrailing
                )
            )
            .frame(width: 22, height: 22)
    }

    private var securityBadge: some View {
        VStack(spacing: 8) {
            HStack(alignment: .top, spacing: 8) {
                Image(systemName: "checkmark.shield.fill")
                    .foregroundStyle(navy.opacity(0.55))
                Text("Koneksi memakai HTTPS. Sesi dilindungi token di perangkat ini — bukan enkripsi end-to-end.")
                    .font(.poppins(size: 11))
                    .foregroundStyle(muted)
                    .fixedSize(horizontal: false, vertical: true)
            }
            Button("Kebijakan privasi") { showPrivacy = true }
                .font(.poppins(size: 12, weight: .semibold))
                .foregroundStyle(navy)
        }
        .padding(.horizontal, 12)
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
            errorMessage = APIConfig.connectionErrorMessage
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func loginWithGoogle() async {
        dismissKeyboard()
        errorMessage = nil
        googleInfoMessage = nil
        faceNote = ""
        isLoading = true
        defer { isLoading = false }
        do {
            try await appState.loginWithGoogle()
            keychain.clearCredentials()
            if rememberMe, let savedEmail = appState.currentUser?.email {
                UserDefaults.standard.set(savedEmail, forKey: "wofins.savedEmail")
            }
        } catch let error as GoogleSignInError where error == .cancelled {
            faceNote = error.localizedDescription
        } catch let error as URLError where error.code == .cannotConnectToHost || error.code == .timedOut || error.code == .networkConnectionLost {
            errorMessage = APIConfig.connectionErrorMessage
        } catch {
            let message = APILoadFailure.userMessage(for: error) ?? error.localizedDescription
            if isGoogleAccountNotRegistered(message) {
                googleInfoMessage = !selectedHost.isInternalHost
                    ? "Akun Google belum tersedia di WOFINS. Gunakan akun yang sudah diundang ke perusahaan Anda."
                    : message
            } else {
                errorMessage = message
            }
        }
    }

    private func isGoogleAccountNotRegistered(_ message: String) -> Bool {
        let text = message.lowercased()
        return text.contains("belum tersedia")
            || text.contains("belum terdaftar")
            || text.contains("sudah diundang")
    }

    private func startAppleSignIn() async {
        dismissKeyboard()
        errorMessage = nil
        googleInfoMessage = nil
        faceNote = ""
        isLoading = true
        defer { isLoading = false }

        do {
            let credential = try await AppleSignInService.shared.signIn()
            try await appState.loginWithApple(
                identityToken: credential.identityToken,
                fullName: credential.fullName
            )
            keychain.clearCredentials()
            if rememberMe, let savedEmail = appState.currentUser?.email {
                UserDefaults.standard.set(savedEmail, forKey: "wofins.savedEmail")
            }
        } catch let error as AppleSignInError where error == .cancelled {
            // User cancelled — no error banner.
        } catch let error as URLError where error.code == .cannotConnectToHost || error.code == .timedOut || error.code == .networkConnectionLost {
            errorMessage = APIConfig.connectionErrorMessage
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func loginWithFaceID() async {
        dismissKeyboard()
        errorMessage = nil
        faceNote = ""

        guard keychain.hasAnySavedCredentials else {
            faceNote = "Login sekali dulu, lalu Face ID bisa dipakai."
            return
        }

        let context = LAContext()
        var authError: NSError?
        guard context.canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: &authError) else {
            faceNote = "Face ID tidak tersedia di perangkat ini."
            return
        }
        context.localizedReason = "Masuk ke WOFINS dengan Face ID"

        if let creds = keychain.readProtectedCredentials(context: context) {
            email = creds.email
            password = creds.password
            rememberMe = true
            await submit()
            return
        }

        guard let legacy = keychain.readLegacyCredentials() else {
            faceNote = "Login sekali dulu, lalu Face ID bisa dipakai."
            return
        }

        do {
            let ok = try await context.evaluatePolicy(
                .deviceOwnerAuthenticationWithBiometrics,
                localizedReason: "Masuk ke WOFINS dengan Face ID"
            )
            guard ok else { return }
            email = legacy.email
            password = legacy.password
            rememberMe = true
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
