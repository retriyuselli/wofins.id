import SwiftUI

struct AccountView: View {
    @EnvironmentObject private var appState: AppState
    @State private var confirmLogout = false
    @State private var showPrivacy = false

    private var user: UserProfile? { appState.currentUser }
    private var initials: String {
        let chars = (user?.name ?? "WOFINS").split(separator: " ").prefix(2).compactMap(\.first)
        return chars.map(String.init).joined().uppercased()
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                header

                ScrollView(showsIndicators: false) {
                    LazyVStack(spacing: 16) {
                        profileCard
                        subscriptionCard
                        accountSection
                        modulesSection
                        securitySection
                        helpSection
                        logoutButton
                        Text(AppRelease.label).font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    }
                    .padding(.top, 16)
                    .padding(.bottom, 28)
                }
                .refreshable { await appState.refreshMe() }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .wofinsHidesNavigationBar()
            .confirmationDialog("Keluar dari akun?", isPresented: $confirmLogout, titleVisibility: .visible) {
                Button("Keluar", role: .destructive) { Task { await appState.logout() } }
                Button("Batal", role: .cancel) {}
            }
            .sheet(isPresented: $showPrivacy) {
                PrivacyStatementView()
            }
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) { Text(user?.name ?? "Pengguna WOFINS").font(.poppins(.headline, weight: .bold)).foregroundStyle(.white).lineLimit(1); Text(user?.companyDisplayName ?? user?.roleLabel ?? "Akun").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.72)).lineLimit(1) }
            Spacer(minLength: 8)
            NavigationLink {
                AppSettingsView()
            } label: {
                Image(systemName: "gearshape.fill")
                    .font(.system(size: 17, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .buttonStyle(.plain)
            .fixedSize()
        }
        .padding(.horizontal, 20).padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var profileCard: some View {
        NavigationLink { EditProfileView() } label: {
            HStack(spacing: 15) {
                UserAvatarView(urlString: user?.avatar_url, initials: initials, size: 68, ringWidth: 4)
                VStack(alignment: .leading, spacing: 4) {
                    Text(user?.name ?? "Pengguna WOFINS").font(.poppins(.headline, weight: .bold)).foregroundStyle(WofinsTheme.ink)
                    Text(user?.companyDisplayName ?? (user?.roleLabel ?? "Pengguna")).font(.poppins(.caption2, weight: .semibold)).foregroundStyle(WofinsTheme.primary)
                        .padding(.horizontal, 9).padding(.vertical, 4).background(WofinsTheme.yellow.opacity(0.22), in: Capsule())
                    Label(user?.email ?? "-", systemImage: "envelope.fill").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted).lineLimit(1)
                }
                Spacer(); Image(systemName: "chevron.right").foregroundStyle(WofinsTheme.muted)
            }.padding(17).accountSurface()
        }.buttonStyle(.plain).padding(.horizontal, 16)
    }

    private var subscriptionCard: some View {
        NavigationLink {
            SubscriptionPlanView()
        } label: {
            HStack(spacing: 14) {
                Image(systemName: "crown.fill").font(.system(size: 22, weight: .bold)).foregroundStyle(WofinsTheme.yellow)
                    .frame(width: 50, height: 50).background(.white.opacity(0.1), in: Circle())
                VStack(alignment: .leading, spacing: 4) {
                    Text("Paket Aktif").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.72))
                    Text(user?.company?.subscription_label ?? "Belum terhubung company").font(.poppins(.headline, weight: .bold)).foregroundStyle(.white).lineLimit(1).minimumScaleFactor(0.8)
                    Text(user?.company?.name ?? (user?.is_expired == true ? "Masa aktif akun berakhir" : "Akun tanpa company"))
                        .font(.poppins(.caption2, weight: .semibold)).foregroundStyle(user?.company == nil || user?.is_expired == true ? WofinsTheme.yellow : Color.green.opacity(0.9)).lineLimit(1)
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                Spacer(); Image(systemName: "chevron.right").foregroundStyle(.white.opacity(0.75))
            }.padding(18)
                .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 20, style: .continuous))
                .wofinsHeroShadow()
        }.buttonStyle(.plain).padding(.horizontal, 16)
    }

    private var accountSection: some View {
        settingsGroup("Akun & Perusahaan") {
            NavigationLink { EditProfileView() } label: { settingRow("Edit Profil", "person.fill") }
            Divider().padding(.leading, 47)
            NavigationLink { CompanyInfoView() } label: { settingRow("Informasi Perusahaan", "building.2.fill") }
            Divider().padding(.leading, 47)
            NavigationLink { ModulesHubView() } label: { settingRow("Semua Modul", "square.grid.2x2.fill") }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "team", title: "Tim", feature: "role_management", allowed: user?.canManageTeam == true, badge: "Business", icon: "person.3.fill")) } label: {
                settingRow("Tim & Hak Akses", "person.3.fill", badge: user?.canManageTeam == true ? nil : "Business")
            }
        }
    }

    private var securitySection: some View {
        settingsGroup("Keamanan") {
            NavigationLink { ChangePasswordView() } label: { settingRow("Ubah Password", "lock.fill") }
            Divider().padding(.leading, 47)
            NavigationLink { FaceIDSettingsView() } label: { settingRow("Face ID", "faceid", badge: "Tersedia") }
            Divider().padding(.leading, 47)
            NavigationLink { ConnectedDevicesView() } label: { settingRow("Perangkat Terhubung", "laptopcomputer.and.iphone") }
            Divider().padding(.leading, 47)
            NavigationLink { CompensationView() } label: {
                settingRow("Kompensasi", "banknote.fill", badge: appState.allows(.payroll) ? nil : "Pro")
            }
        }
    }

    private var modulesSection: some View {
        settingsGroup("Modul Perusahaan") {
            NavigationLink { ModuleListView(item: .placeholder(key: "nota_dinas", title: "Nota Dinas", feature: "nota_dinas", allowed: appState.allows(.notaDinas), icon: "doc.text.fill")) } label: {
                settingRow("Nota Dinas", "doc.text.fill")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "simulasi", title: "Simulasi", feature: "simulasi", allowed: appState.allows(.simulasi), badge: "Pro", icon: "doc.badge.plus")) } label: {
                settingRow("Simulasi", "doc.badge.plus", badge: appState.allows(.simulasi) ? nil : "Pro")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "fixed_assets", title: "Aset Tetap", feature: "fixed_assets", allowed: appState.allows(.fixedAssets), badge: "Pro", icon: "building.2.fill")) } label: {
                settingRow("Aset Tetap", "building.2.fill", badge: appState.allows(.fixedAssets) ? nil : "Pro")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "bank_statements", title: "Rekonsiliasi", feature: "reconciliation", allowed: appState.allows(.reconciliation), badge: "Pro", icon: "arrow.left.arrow.right")) } label: {
                settingRow("Rekonsiliasi", "arrow.left.arrow.right", badge: appState.allows(.reconciliation) ? nil : "Pro")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "documents", title: "Dokumen", feature: "documents", allowed: appState.allows(.documents), badge: "Business", icon: "folder.fill")) } label: {
                settingRow("Dokumen & SOP", "folder.fill", badge: appState.allows(.documents) ? nil : "Business")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "data_pribadis", title: "Crew Freelance", feature: "crew_freelance", allowed: appState.allows(.crewFreelance), badge: "Business", icon: "person.crop.rectangle.fill")) } label: {
                settingRow("Crew Freelance", "person.crop.rectangle.fill", badge: appState.allows(.crewFreelance) ? nil : "Business")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "payrolls", title: "Payroll", feature: "payroll", allowed: appState.allows(.payroll), badge: "Pro", icon: "banknote.fill")) } label: {
                settingRow("Payroll Tim", "person.crop.rectangle.stack.fill", badge: appState.allows(.payroll) ? nil : "Pro")
            }
            Divider().padding(.leading, 47)
            NavigationLink { ModuleListView(item: .placeholder(key: "employees", title: "Karyawan", feature: "payroll", allowed: appState.allows(.payroll), badge: "Pro", icon: "person.2.fill")) } label: {
                settingRow("Karyawan", "person.2.fill", badge: appState.allows(.payroll) ? nil : "Pro")
            }
        }
    }

    private var helpSection: some View {
        settingsGroup("Bantuan") {
            NavigationLink { HelpCenterView() } label: { settingRow("Pusat Bantuan", "questionmark.circle.fill") }
            Divider().padding(.leading, 47)
            Button { showPrivacy = true } label: { settingRow("Kebijakan Privasi", "hand.raised.fill") }
            Divider().padding(.leading, 47)
            NavigationLink { AboutWofinsView() } label: { settingRow("Tentang WOFINS", "info.circle.fill") }
        }
    }

    private func settingsGroup<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: 0) {
            Text(title).font(.poppins(.headline, weight: .bold)).foregroundStyle(WofinsTheme.primary).padding(.bottom, 8)
            content()
        }.padding(16).accountSurface().padding(.horizontal, 16)
    }

    private func settingRow(_ title: String, _ icon: String, badge: String? = nil) -> some View {
        HStack(spacing: 12) {
            Image(systemName: icon).font(.system(size: 14, weight: .semibold)).foregroundStyle(WofinsTheme.primary)
                .frame(width: 35, height: 35).background(WofinsTheme.primary.opacity(0.09), in: Circle())
            Text(title)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.85)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            if let badge {
                Text(badge)
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(badge == "Tersedia" ? WofinsTheme.success : WofinsTheme.primary)
                    .padding(.horizontal, 9)
                    .padding(.vertical, 5)
                    .background((badge == "Tersedia" ? WofinsTheme.success : WofinsTheme.yellow).opacity(0.18), in: Capsule())
                    .fixedSize()
            }
            Image(systemName: "chevron.right").font(.system(size: 12, weight: .semibold)).foregroundStyle(WofinsTheme.muted)
        }.padding(.vertical, 8).contentShape(Rectangle())
    }

    private var logoutButton: some View {
        Button { confirmLogout = true } label: {
            Label("Keluar dari Akun", systemImage: "rectangle.portrait.and.arrow.right")
                .font(.poppins(.subheadline, weight: .semibold)).foregroundStyle(WofinsTheme.danger)
                .frame(maxWidth: .infinity).frame(height: 50).background(WofinsTheme.danger.opacity(0.05), in: RoundedRectangle(cornerRadius: 15))
                .overlay { RoundedRectangle(cornerRadius: 15).stroke(WofinsTheme.danger.opacity(0.7)) }
        }.buttonStyle(.plain).padding(.horizontal, 16)
    }
}

private struct UserAvatarView: View {
    let urlString: String?
    let initials: String
    let size: CGFloat
    var ringWidth: CGFloat = 4

    var body: some View {
        Group {
            if let url = APIConfig.mediaURL(from: urlString) {
                AsyncImage(url: url) { phase in
                    switch phase {
                    case .success(let image):
                        image.resizable().scaledToFill()
                    default:
                        fallback
                    }
                }
            } else {
                fallback
            }
        }
        .frame(width: size, height: size)
        .clipShape(Circle())
        .overlay { Circle().stroke(WofinsTheme.yellow, lineWidth: ringWidth) }
    }

    private var fallback: some View {
        Text(initials)
            .font(.poppins(.title2, weight: .bold))
            .foregroundStyle(.white)
            .frame(width: size, height: size)
            .background(
                LinearGradient(colors: [WofinsTheme.primaryLight, WofinsTheme.primary], startPoint: .top, endPoint: .bottom),
                in: Circle()
            )
    }
}

private extension View {
    func accountSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay { RoundedRectangle(cornerRadius: 18).stroke(WofinsTheme.border.opacity(0.75)) }
            .wofinsSoftShadow()
    }
}
