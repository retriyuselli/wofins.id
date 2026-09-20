import LocalAuthentication
import SwiftUI
import UIKit

enum AppRelease {
    static var marketing: String {
        Bundle.main.object(forInfoDictionaryKey: "CFBundleShortVersionString") as? String ?? "1.0.0"
    }

    static var build: String {
        Bundle.main.object(forInfoDictionaryKey: "CFBundleVersion") as? String ?? "1"
    }

    static var label: String { "Versi \(marketing)" }
}

enum AccountDateFormat {
    static func display(_ raw: String?) -> String {
        guard let raw, !raw.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty else { return "—" }
        let fractional = ISO8601DateFormatter()
        fractional.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
        let plain = ISO8601DateFormatter()
        plain.formatOptions = [.withInternetDateTime]
        let day = DateFormatter()
        day.calendar = Calendar(identifier: .gregorian)
        day.locale = Locale(identifier: "en_US_POSIX")
        day.timeZone = TimeZone(identifier: "Asia/Jakarta")
        day.dateFormat = "yyyy-MM-dd"
        let date = fractional.date(from: raw) ?? plain.date(from: raw) ?? day.date(from: String(raw.prefix(10)))
        guard let date else { return raw }
        let out = DateFormatter()
        out.locale = Locale(identifier: "id_ID")
        out.timeZone = TimeZone(identifier: "Asia/Jakarta")
        out.dateFormat = raw.count > 10 ? "d MMM yyyy, HH.mm" : "d MMM yyyy"
        return out.string(from: date)
    }
}

struct CompanyInfoView: View {
    @EnvironmentObject private var appState: AppState

    private var company: UserCompany? { appState.currentUser?.company }

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 16) {
                if let company {
                    headerCard(company)
                    infoCard("Identitas") {
                        infoRow("Nama", company.name)
                        infoRow("Inisial", company.inisial)
                        infoRow("Status", company.is_active == false ? "Tidak aktif" : "Aktif")
                        infoRow("Tahun berdiri", company.established_year.map(String.init))
                    }
                    infoCard("Kontak") {
                        infoRow("Email", company.email)
                        infoRow("Telepon", company.phone)
                        infoRow("Alamat", company.address)
                        infoRow("Kota", company.locationLine)
                        websiteRow(company.website)
                    }
                    if let owner = company.owner_name, !owner.isEmpty {
                        infoCard("Penanggung jawab") {
                            infoRow("Nama", owner)
                            infoRow("Jabatan", company.jabatan_owner)
                        }
                    }
                    if let description = company.description?.trimmingCharacters(in: .whitespacesAndNewlines), !description.isEmpty {
                        infoCard("Deskripsi") {
                            Text(description)
                                .font(.poppins(.subheadline))
                                .foregroundStyle(WofinsTheme.ink)
                                .fixedSize(horizontal: false, vertical: true)
                                .frame(maxWidth: .infinity, alignment: .leading)
                        }
                    }
                } else {
                    emptyCard("Akun ini belum terhubung ke company. Data perusahaan tidak ditampilkan lintas tenant.")
                }
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle("Informasi Perusahaan")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .refreshable { await appState.refreshMe() }
    }

    private func headerCard(_ company: UserCompany) -> some View {
        HStack(alignment: .center, spacing: 14) {
            companyLogo(company)
            VStack(alignment: .leading, spacing: 4) {
                Text(company.name ?? "Perusahaan")
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(WofinsTheme.ink)
                    .fixedSize(horizontal: false, vertical: true)
                Text(company.subscription_label ?? "Paket belum diatur")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
            }
            .frame(maxWidth: .infinity, alignment: .leading)
            .minWidthZero()
        }
        .padding(16)
        .accountPageSurface()
    }

    private func companyLogo(_ company: UserCompany) -> some View {
        Group {
            if let url = APIConfig.mediaURL(from: company.logo_url) {
                AsyncImage(url: url) { phase in
                    if case .success(let image) = phase {
                        image.resizable().scaledToFill()
                    } else {
                        logoFallback(company)
                    }
                }
            } else {
                logoFallback(company)
            }
        }
        .frame(width: 56, height: 56)
        .clipShape(RoundedRectangle(cornerRadius: 14, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .stroke(WofinsTheme.border.opacity(0.8))
        }
    }

    private func logoFallback(_ company: UserCompany) -> some View {
        Text(String((company.inisial ?? company.name ?? "WO").prefix(2)).uppercased())
            .font(.poppins(.headline, weight: .bold))
            .foregroundStyle(.white)
            .frame(maxWidth: .infinity, maxHeight: .infinity)
            .background(WofinsTheme.primary)
    }

    @ViewBuilder
    private func websiteRow(_ raw: String?) -> some View {
        let trimmed = raw?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        if trimmed.isEmpty {
            infoRow("Situs web", nil)
        } else if let url = URL(string: trimmed.hasPrefix("http") ? trimmed : "https://\(trimmed)") {
            Link(destination: url) {
                infoRow("Situs web", trimmed, showsChevron: true)
            }
            .buttonStyle(.plain)
        } else {
            infoRow("Situs web", trimmed)
        }
    }
}

struct SubscriptionPlanView: View {
    @EnvironmentObject private var appState: AppState

    private var user: UserProfile? { appState.currentUser }
    private var company: UserCompany? { user?.company }
    private var entitlements: PlanEntitlements? { user?.entitlements }

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 16) {
                if company == nil {
                    emptyCard("Akun ini belum terhubung ke company. Paket tidak ditampilkan lintas tenant.")
                } else {
                    VStack(alignment: .leading, spacing: 8) {
                        Text("Paket Aktif")
                            .font(.poppins(.caption))
                            .foregroundStyle(.white.opacity(0.72))
                        Text(entitlements?.plan_label ?? company?.subscription_label ?? "Paket belum diatur")
                            .font(.poppins(.title3, weight: .bold))
                            .foregroundStyle(.white)
                            .fixedSize(horizontal: false, vertical: true)
                        Text(company?.name ?? user?.companyDisplayName ?? "Perusahaan")
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(user?.hasExpiredSubscription == true || user?.is_expired == true ? WofinsTheme.yellow : Color.green.opacity(0.9))
                            .fixedSize(horizontal: false, vertical: true)
                    }
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .padding(18)
                    .background(
                        LinearGradient(colors: [WofinsTheme.primary, WofinsTheme.primaryLight], startPoint: .leading, endPoint: .trailing),
                        in: RoundedRectangle(cornerRadius: 20, style: .continuous)
                    )

                    infoCard("Masa berlaku") {
                        infoRow("Berlaku hingga", user?.subscription_expires_label ?? AccountDateFormat.display(company?.subscription_expires_at ?? user?.expire_date))
                        infoRow("Status paket", user?.hasExpiredSubscription == true ? "Masa aktif berakhir" : "Aktif")
                        infoRow("Status akun", user?.is_expired == true ? "Masa aktif berakhir" : "Aktif")
                        if let seats = entitlements?.seat_limit {
                            infoRow("Kuota pengguna", "\(seats) orang")
                        }
                    }

                    infoCard("Fitur paket") {
                        ForEach(PlanFeature.allCases, id: \.rawValue) { feature in
                            featureRow(feature.screenTitle, allowed: user?.allows(feature) == true)
                        }
                    }

                    if user?.canManageSubscription == true {
                        SubscriptionPaywallView(
                            title: "Kelola paket",
                            subtitle: "Upgrade atau perpanjang lewat In-App Purchase. Langganan yang dibeli di web tetap berlaku.",
                            showsLogout: false
                        )
                    }
                }
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle("Paket Aktif")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .refreshable { await appState.refreshMe() }
    }
}

struct AppSettingsView: View {
    @State private var showPrivacy = false

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 16) {
                infoCard("Keamanan") {
                    NavigationLink { FaceIDSettingsView() } label: {
                        settingsRow("Face ID", "faceid")
                    }
                    Divider()
                    NavigationLink { ConnectedDevicesView() } label: {
                        settingsRow("Perangkat Terhubung", "laptopcomputer.and.iphone")
                    }
                    Divider()
                    NavigationLink { ChangePasswordView() } label: {
                        settingsRow("Ubah Password", "lock.fill")
                    }
                }
                infoCard("Aplikasi") {
                    Button { showPrivacy = true } label: {
                        settingsRow("Kebijakan Privasi", "hand.raised.fill")
                    }
                    Divider()
                    NavigationLink { AboutWofinsView() } label: {
                        settingsRow("Tentang WOFINS", "info.circle.fill")
                    }
                    Divider()
                    infoRow("Versi", AppRelease.label)
                }
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle("Pengaturan")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .sheet(isPresented: $showPrivacy) {
            PrivacyStatementView()
        }
    }
}

struct FaceIDSettingsView: View {
    private let keychain = KeychainStore()
    @State private var credentialsSaved = false
    @State private var confirmDisable = false

    private var biometryName: String {
        let context = LAContext()
        _ = context.canEvaluatePolicy(.deviceOwnerAuthenticationWithBiometrics, error: nil)
        switch context.biometryType {
        case .faceID: return "Face ID"
        case .touchID: return "Touch ID"
        default: return "Biometrik"
        }
    }

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 16) {
                infoCard("Status di perangkat ini") {
                    infoRow("Sensor", keychain.canUseBiometrics ? "\(biometryName) tersedia" : "Tidak tersedia")
                    infoRow("Login cepat", credentialsSaved ? "Aktif" : "Belum diaktifkan")
                }

                infoCard("Cara memakai") {
                    Text("Centang Ingat saya saat masuk. Berikutnya, tombol \(biometryName) di halaman login membuka sesi tanpa mengetik password.")
                        .font(.poppins(.subheadline))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                }

                if credentialsSaved {
                    Button { confirmDisable = true } label: {
                        Text("Matikan login \(biometryName)")
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.danger)
                            .frame(maxWidth: .infinity)
                            .padding(.vertical, 12)
                            .background(WofinsTheme.danger.opacity(0.08), in: Capsule())
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle(biometryName)
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .onAppear { credentialsSaved = keychain.hasAnySavedCredentials }
        .confirmationDialog("Matikan login \(biometryName)?", isPresented: $confirmDisable, titleVisibility: .visible) {
            Button("Matikan", role: .destructive) {
                keychain.clearCredentials()
                credentialsSaved = false
            }
            Button("Batal", role: .cancel) {}
        } message: {
            Text("Anda tetap bisa masuk dengan email dan password.")
        }
    }
}

struct ConnectedDevicesView: View {
    @EnvironmentObject private var appState: AppState
    @State private var devices: [AuthSessionDevice] = []
    @State private var isLoading = false
    @State private var errorMessage: String?

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 12) {
                if isLoading && devices.isEmpty {
                    ProgressView("Memuat perangkat…")
                        .frame(maxWidth: .infinity)
                        .padding(24)
                        .accountPageSurface()
                } else if let errorMessage, devices.isEmpty {
                    emptyCard(errorMessage)
                } else if devices.isEmpty {
                    emptyCard("Belum ada sesi perangkat yang tercatat.")
                } else {
                    if let errorMessage {
                        Text(errorMessage)
                            .font(.poppins(.caption))
                            .foregroundStyle(WofinsTheme.danger)
                            .fixedSize(horizontal: false, vertical: true)
                    }
                    ForEach(devices) { device in
                        VStack(alignment: .leading, spacing: 8) {
                            HStack(alignment: .firstTextBaseline, spacing: 8) {
                                Text(device.displayName)
                                    .font(.poppins(.subheadline, weight: .semibold))
                                    .foregroundStyle(WofinsTheme.ink)
                                    .fixedSize(horizontal: false, vertical: true)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .minWidthZero()
                                if device.isCurrent {
                                    Text("Perangkat ini")
                                        .font(.poppins(.caption2, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.success)
                                        .padding(.horizontal, 8)
                                        .padding(.vertical, 4)
                                        .background(WofinsTheme.success.opacity(0.14), in: Capsule())
                                        .fixedSize()
                                }
                            }
                            Text("Terakhir dipakai: \(AccountDateFormat.display(device.last_used_at ?? device.created_at))")
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.muted)
                                .fixedSize(horizontal: false, vertical: true)
                        }
                        .padding(16)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .accountPageSurface()
                    }
                }
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle("Perangkat Terhubung")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .task { await load() }
        .refreshable { await load() }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            devices = try await appState.api.authDevices()
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }
}

struct HelpCenterView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.openURL) private var openURL
    @State private var showContact = false

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 16) {
                infoCard("Pertanyaan umum") {
                    faq("Bagaimana masuk dengan Face ID?", "Di halaman masuk, centang Ingat saya lalu masuk sekali. Berikutnya gunakan tombol Face ID.")
                    Divider()
                    faq("Lupa password?", "Atur ulang lewat tautan di bawah. Halaman web memakai host yang sama dengan API aplikasi.")
                    Divider()
                    faq("Fitur bertanda Pro atau Business?", "Fitur mengikuti akses yang sudah ditetapkan untuk perusahaan Anda.")
                }

                VStack(spacing: 10) {
                    webButton("Atur ulang password", path: "/forgot-password")
                    Button { showContact = true } label: {
                        helpActionLabel("Bantuan teknis")
                    }
                    .buttonStyle(.plain)
                }
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle("Pusat Bantuan")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .sheet(isPresented: $showContact) {
            ContactUsSheet()
                .environmentObject(appState)
        }
    }

    private func helpActionLabel(_ title: String) -> some View {
        Text(title)
            .font(.poppins(.subheadline, weight: .semibold))
            .foregroundStyle(WofinsTheme.primary)
            .frame(maxWidth: .infinity)
            .padding(.vertical, 12)
            .background(WofinsTheme.primary.opacity(0.08), in: Capsule())
    }

    private func faq(_ title: String, _ body: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
                .fixedSize(horizontal: false, vertical: true)
            Text(body)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(.vertical, 4)
    }

    private func webButton(_ title: String, path: String) -> some View {
        Button {
            if let url = APIConfig.websiteURL(path) {
                openURL(url)
            }
        } label: {
            Text(title)
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .frame(maxWidth: .infinity)
                .padding(.vertical, 12)
                .background(WofinsTheme.primary.opacity(0.08), in: Capsule())
        }
        .buttonStyle(.plain)
    }
}

enum SupportContact {
    static let whatsAppDigits = "6281373183794"
    static let whatsAppDisplay = "+62 813-7318-3794"
    static let email = "support@wofins.id"
    static let location = "Palembang, Indonesia"

    static func whatsAppURL(message: String) -> URL? {
        var components = URLComponents()
        components.scheme = "whatsapp"
        components.host = "send"
        components.queryItems = [
            URLQueryItem(name: "phone", value: whatsAppDigits),
            URLQueryItem(name: "text", value: message),
        ]
        return components.url
    }

    static func mailURL(subject: String, body: String) -> URL? {
        var components = URLComponents()
        components.scheme = "mailto"
        components.path = email
        components.queryItems = [
            URLQueryItem(name: "subject", value: subject),
            URLQueryItem(name: "body", value: body),
        ]
        return components.url
    }
}

private struct AccountBottomSheet<Content: View>: View {
    let title: String
    let content: Content
    @Environment(\.dismiss) private var dismiss

    init(title: String, @ViewBuilder content: () -> Content) {
        self.title = title
        self.content = content()
    }

    var body: some View {
        NavigationStack {
            ScrollView(showsIndicators: false) {
                content
                    .padding(.horizontal, 16)
                    .padding(.top, 8)
                    .padding(.bottom, 28)
                    .safeAreaPadding(.bottom)
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle(title)
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Tutup") { dismiss() }
                }
            }
            .wofinsSwipeBack()
        }
        .presentationDetents([.medium, .large])
        .presentationDragIndicator(.visible)
        .presentationContentInteraction(.scrolls)
        .presentationCornerRadius(22)
    }
}

struct ContactUsSheet: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.openURL) private var openURL
    @State private var notice: String?

    private var defaultMessage: String {
        let name = appState.currentUser?.name ?? "pengguna WOFINS"
        let company = appState.currentUser?.company?.name ?? appState.currentUser?.companyDisplayName ?? "company"
        return "Halo, saya \(name) dari \(company). Saya membutuhkan bantuan penggunaan WOFINS."
    }

    var body: some View {
        AccountBottomSheet(title: "Bantuan teknis") {
            VStack(alignment: .leading, spacing: 14) {
                Text("Jika Anda mengalami kendala teknis saat memakai WOFINS, kirim detail masalah ke email dukungan.")
                    .font(.poppins(.subheadline))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)

                if let notice {
                    Text(notice)
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.success)
                        .fixedSize(horizontal: false, vertical: true)
                }

                contactRow(title: "Email dukungan", value: SupportContact.email, icon: "envelope.fill") {
                    copy(SupportContact.email)
                }

                Button {
                    sendEmail()
                } label: {
                    Label("Kirim email dukungan", systemImage: "envelope.fill")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(.white)
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 12)
                        .background(WofinsTheme.primary, in: Capsule())
                }
                .buttonStyle(.plain)
            }
        }
    }

    private func contactRow(title: String, value: String, icon: String, copyAction: @escaping () -> Void) -> some View {
        Button(action: copyAction) {
            HStack(spacing: 12) {
                Image(systemName: icon)
                    .font(.system(size: 14, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .frame(width: 36, height: 36)
                    .background(WofinsTheme.primary.opacity(0.09), in: Circle())
                    .fixedSize()
                VStack(alignment: .leading, spacing: 2) {
                    Text(title)
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.muted)
                    Text(value)
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                }
                .frame(maxWidth: .infinity, alignment: .leading)
                .frame(minWidth: 0)
                Text("Salin")
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .fixedSize()
            }
            .padding(14)
            .accountPageSurface()
        }
        .buttonStyle(.plain)
    }

    private func copy(_ value: String) {
        UIPasteboard.general.string = value
        notice = "Disalin: \(value)"
    }

    private func sendEmail() {
        if let url = SupportContact.mailURL(subject: "Bantuan teknis WOFINS", body: defaultMessage) {
            openURL(url)
        }
    }
}

struct AboutWofinsView: View {
    @State private var showPrivacy = false

    var body: some View {
        ScrollView(showsIndicators: false) {
            LazyVStack(alignment: .leading, spacing: 16) {
                VStack(spacing: 10) {
                    Image("LaunchLogo")
                        .resizable()
                        .scaledToFit()
                        .frame(width: 72, height: 72)
                    Text("WOFINS")
                        .font(.poppins(.title3, weight: .bold))
                        .foregroundStyle(.white)
                    Text("Wedding Organizer Financial System")
                        .font(.poppins(.caption))
                        .foregroundStyle(.white.opacity(0.8))
                        .multilineTextAlignment(.center)
                        .fixedSize(horizontal: false, vertical: true)
                    Text(AppRelease.label)
                        .font(.poppins(.caption2, weight: .semibold))
                        .foregroundStyle(WofinsTheme.yellow)
                }
                .frame(maxWidth: .infinity)
                .padding(20)
                .background(
                    LinearGradient(colors: [WofinsTheme.primary, WofinsTheme.primaryDark], startPoint: .top, endPoint: .bottom),
                    in: RoundedRectangle(cornerRadius: 20, style: .continuous)
                )

                infoCard("Tentang") {
                    Text("WOFINS membantu wedding organizer mengelola proyek, keuangan, dan operasional dalam satu aplikasi.")
                        .font(.poppins(.subheadline))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)
                }

                Button { showPrivacy = true } label: {
                    Text("Kebijakan privasi")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .padding(.vertical, 12)
                        .background(WofinsTheme.primary.opacity(0.08), in: Capsule())
                }
                .buttonStyle(.plain)
            }
            .padding(.horizontal, 16)
            .padding(.top, 16)
            .padding(.bottom, 28)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .navigationTitle("Tentang WOFINS")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar(.visible, for: .navigationBar)
        .wofinsSwipeBack()
        .sheet(isPresented: $showPrivacy) {
            PrivacyStatementView()
        }
    }
}

private func infoCard<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
    VStack(alignment: .leading, spacing: 10) {
        Text(title)
            .font(.poppins(.caption, weight: .semibold))
            .foregroundStyle(WofinsTheme.muted)
        content()
    }
    .padding(16)
    .frame(maxWidth: .infinity, alignment: .leading)
    .accountPageSurface()
}

private func emptyCard(_ message: String) -> some View {
    Text(message)
        .font(.poppins(.subheadline))
        .foregroundStyle(WofinsTheme.muted)
        .fixedSize(horizontal: false, vertical: true)
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(16)
        .accountPageSurface()
}

private func infoRow(_ title: String, _ value: String?, showsChevron: Bool = false) -> some View {
    HStack(alignment: .top, spacing: 12) {
        Text(title)
            .font(.poppins(.caption))
            .foregroundStyle(WofinsTheme.muted)
            .frame(width: 96, alignment: .leading)
        Text((value?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false ? value : "—") ?? "—")
            .font(.poppins(.subheadline, weight: .semibold))
            .foregroundStyle(WofinsTheme.ink)
            .fixedSize(horizontal: false, vertical: true)
            .frame(maxWidth: .infinity, alignment: .leading)
            .minWidthZero()
        if showsChevron {
            Image(systemName: "arrow.up.right")
                .font(.system(size: 11, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
                .padding(.top, 3)
        }
    }
}

private func featureRow(_ title: String, allowed: Bool) -> some View {
    HStack(spacing: 10) {
        Image(systemName: allowed ? "checkmark.circle.fill" : "minus.circle")
            .foregroundStyle(allowed ? WofinsTheme.success : WofinsTheme.muted)
        Text(title)
            .font(.poppins(.subheadline))
            .foregroundStyle(allowed ? WofinsTheme.ink : WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
            .frame(maxWidth: .infinity, alignment: .leading)
            .minWidthZero()
    }
    .padding(.vertical, 3)
}

private func settingsRow(_ title: String, _ icon: String) -> some View {
    HStack(spacing: 12) {
        Image(systemName: icon)
            .font(.system(size: 14, weight: .semibold))
            .foregroundStyle(WofinsTheme.primary)
            .frame(width: 35, height: 35)
            .background(WofinsTheme.primary.opacity(0.09), in: Circle())
        Text(title)
            .font(.poppins(.subheadline))
            .foregroundStyle(WofinsTheme.ink)
            .frame(maxWidth: .infinity, alignment: .leading)
            .minWidthZero()
        Image(systemName: "chevron.right")
            .font(.system(size: 12, weight: .semibold))
            .foregroundStyle(WofinsTheme.muted)
    }
    .padding(.vertical, 6)
    .contentShape(Rectangle())
}

private extension View {
    func minWidthZero() -> some View {
        frame(minWidth: 0)
    }

    func accountPageSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 18)
                    .stroke(WofinsTheme.border.opacity(0.75))
                    .allowsHitTesting(false)
            }
            .wofinsSoftShadow()
    }
}
