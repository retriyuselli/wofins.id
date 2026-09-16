import SwiftUI
import UIKit

enum WofinsTheme {
    private static var isHastana: Bool { AppColorTheme.selected == .hastana }

    // Nilai tema WOFINS dipertahankan persis seperti tampilan sebelumnya.
    static var primary: Color {
        isHastana ? Color(red: 0.70, green: 0.07, blue: 0.11) : Color(red: 0.00, green: 0.27, blue: 0.50)
    }
    static var primaryDark: Color {
        isHastana ? Color(red: 0.07, green: 0.07, blue: 0.08) : Color(red: 0.00, green: 0.18, blue: 0.34)
    }
    static var primaryLight: Color {
        isHastana ? Color(red: 0.82, green: 0.12, blue: 0.17) : Color(red: 0.08, green: 0.39, blue: 0.66)
    }
    static var yellow: Color {
        isHastana ? Color(red: 0.98, green: 0.64, blue: 0.66) : Color(red: 1.00, green: 0.73, blue: 0.00)
    }
    static var yellowSoft: Color {
        isHastana ? .white : Color(red: 1.00, green: 0.83, blue: 0.48)
    }
    static var accent: Color { primary }
    static var background: Color {
        isHastana ? Color(red: 0.97, green: 0.97, blue: 0.975) : Color(red: 0.96, green: 0.975, blue: 0.99)
    }
    static var card: Color { .white }
    static var ink: Color {
        isHastana ? Color(red: 0.07, green: 0.07, blue: 0.08) : Color(red: 0.07, green: 0.16, blue: 0.25)
    }
    static var muted: Color {
        isHastana ? Color(red: 0.34, green: 0.34, blue: 0.38) : Color(red: 0.40, green: 0.48, blue: 0.59)
    }
    static var border: Color {
        isHastana ? Color(red: 0.83, green: 0.83, blue: 0.85) : Color(red: 0.86, green: 0.90, blue: 0.95)
    }
    static var success: Color {
        isHastana ? Color(red: 0.06, green: 0.43, blue: 0.27) : Color(red: 0.08, green: 0.57, blue: 0.39)
    }
    static var danger: Color {
        isHastana ? Color(red: 0.70, green: 0.07, blue: 0.11) : Color(red: 0.86, green: 0.25, blue: 0.23)
    }
}

struct RootView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        Group {
            if appState.isBootstrapping {
                ProgressView("Memuat…")
                    .frame(maxWidth: .infinity, maxHeight: .infinity)
                    .background(WofinsTheme.background)
            } else if appState.isAuthenticated {
                MainTabView()
            } else {
                LoginView()
            }
        }
        .animation(.easeInOut(duration: 0.2), value: appState.isAuthenticated)
        .wofinsDismissKeyboardOnOutsideTap()
        .task {
            await appState.bootstrap()
        }
    }
}

struct MainTabView: View {
    @AppStorage(AppColorTheme.storageKey) private var colorTheme = AppColorTheme.hastana.rawValue
    @State private var selectedTab = 0

    var body: some View {
        TabView(selection: $selectedTab) {
            DashboardView()
                .tabItem { Label("Dashboard", systemImage: "house.fill") }
                .tag(0)

            ProjectsView()
                .tabItem { Label("Proyek", systemImage: "heart.fill") }
                .tag(1)

            TransactionsView()
                .tabItem { Label("Transaksi", systemImage: "banknote.fill") }
                .tag(2)

            ReportsView()
                .tabItem { Label("Laporan", systemImage: "chart.pie.fill") }
                .tag(3)

            AccountView()
                .tabItem { Label("Akun", systemImage: "person.crop.circle.fill") }
                .tag(4)
        }
        .id(colorTheme)
        .tint(WofinsTheme.accent)
        .toolbarBackground(.white, for: .tabBar)
        .toolbarBackground(.visible, for: .tabBar)
        .preferredColorScheme(.light)
    }
}

struct PlanLockedView: View {
    @EnvironmentObject private var appState: AppState

    let feature: PlanFeature
    var title: String?

    var body: some View {
        VStack(spacing: 14) {
            Image(systemName: "lock.fill")
                .font(.system(size: 28, weight: .semibold))
                .foregroundStyle(WofinsTheme.yellow)
                .frame(width: 58, height: 58)
                .background(WofinsTheme.yellow.opacity(0.16), in: Circle())

            Text(title ?? feature.screenTitle)
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .multilineTextAlignment(.center)

            Text(feature.upgradeMessage(planLabel: appState.currentUser?.entitlements?.plan_label ?? appState.currentUser?.company?.subscription_label))
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .multilineTextAlignment(.center)
                .fixedSize(horizontal: false, vertical: true)

            if let label = appState.currentUser?.entitlements?.plan_label ?? appState.currentUser?.company?.subscription_label {
                Text(label)
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .padding(.horizontal, 10)
                    .padding(.vertical, 5)
                    .background(WofinsTheme.primary.opacity(0.10), in: Capsule())
            }
        }
        .padding(22)
        .frame(maxWidth: .infinity)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
        .overlay { RoundedRectangle(cornerRadius: 18).stroke(WofinsTheme.border.opacity(0.75)) }
        .padding(.horizontal, 16)
        .padding(.top, 24)
    }
}

struct WofinsBrandMark: View {
    static let size: CGFloat = 52

    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 13, style: .continuous)
                .fill(.white.opacity(0.10))
                .overlay {
                    RoundedRectangle(cornerRadius: 13, style: .continuous)
                        .stroke(WofinsTheme.yellow.opacity(0.9), lineWidth: 1.5)
                }
            Text("W")
                .font(.poppins(size: 23, weight: .bold))
                .foregroundStyle(WofinsTheme.yellowSoft)
        }
        .frame(width: Self.size, height: Self.size)
    }
}

struct WofinsCompactMark: View {
    @EnvironmentObject private var appState: AppState
    static let size: CGFloat = 42

    var body: some View {
        Group {
            if let url = APIConfig.mediaURL(from: appState.currentUser?.avatar_url) {
                AsyncImage(url: url) { phase in
                    switch phase {
                    case .success(let image):
                        image.resizable().scaledToFill()
                    default:
                        brandFallback
                    }
                }
            } else {
                brandFallback
            }
        }
        .frame(width: Self.size, height: Self.size)
        .clipShape(Circle())
        .overlay { Circle().stroke(WofinsTheme.yellow, lineWidth: 1.5) }
        .accessibilityLabel(appState.currentUser?.name ?? "WOFINS")
    }

    private var brandFallback: some View {
        Text("W")
            .font(.poppins(.headline, weight: .bold))
            .foregroundStyle(WofinsTheme.yellowSoft)
            .frame(width: Self.size, height: Self.size)
            .background(.white.opacity(0.1), in: Circle())
    }
}

struct WofinsPageHeader<Accessory: View>: View {
    let brandCaption: String
    let title: String
    let subtitle: String
    let accessory: Accessory

    init(
        brandCaption: String,
        title: String,
        subtitle: String,
        @ViewBuilder accessory: () -> Accessory
    ) {
        self.brandCaption = brandCaption
        self.title = title
        self.subtitle = subtitle
        self.accessory = accessory()
    }

    var body: some View {
        ZStack(alignment: .bottomLeading) {
            Circle()
                .fill(.white.opacity(0.055))
                .frame(width: 260, height: 260)
                .offset(x: -92, y: 105)
                .allowsHitTesting(false)

            VStack(alignment: .leading, spacing: 10) {
                HStack(alignment: .center, spacing: 12) {
                    WofinsBrandMark()

                    VStack(alignment: .leading, spacing: 1) {
                        Text("WOFINS")
                            .font(.poppins(size: 22, weight: .bold))
                            .tracking(1.2)
                            .foregroundStyle(.white)
                        Text(brandCaption)
                            .font(.poppins(.caption))
                            .foregroundStyle(.white.opacity(0.72))
                            .lineLimit(1)
                            .minimumScaleFactor(0.8)
                    }

                    Spacer(minLength: 8)

                    accessory
                        .frame(width: 40, height: 40)
                }
                .frame(height: WofinsBrandMark.size)

                VStack(alignment: .leading, spacing: 3) {
                    Text(title)
                        .font(.poppins(size: 22, weight: .bold))
                        .foregroundStyle(.white)
                        .fixedSize(horizontal: false, vertical: true)
                    Text(subtitle)
                        .font(.poppins(.subheadline))
                        .foregroundStyle(.white.opacity(0.76))
                        .fixedSize(horizontal: false, vertical: true)
                }

                Capsule()
                    .fill(WofinsTheme.yellow)
                    .frame(width: 58, height: 4)
            }
            .padding(.horizontal, 20)
            .padding(.top, 6)
            .padding(.bottom, 16)
            .safeAreaPadding(.top)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(
            UnevenRoundedRectangle(bottomLeadingRadius: 28, bottomTrailingRadius: 28)
                .fill(
                    LinearGradient(
                        colors: [WofinsTheme.primary, WofinsTheme.primaryDark],
                        startPoint: .topLeading,
                        endPoint: .bottomTrailing
                    )
                )
        )
    }
}

struct WofinsYellowIconButton: View {
    let systemName: String
    let accessibilityLabel: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            Image(systemName: systemName)
                .font(.system(size: 16, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
                .frame(width: 40, height: 40)
                .background(WofinsTheme.yellow, in: Circle())
        }
        .accessibilityLabel(accessibilityLabel)
    }
}

extension View {
    func wofinsPinnedScreen() -> some View {
        background(WofinsTheme.background.ignoresSafeArea())
            .wofinsHidesNavigationBar()
            .navigationBarTitleDisplayMode(.inline)
            .ignoresSafeArea(edges: .top)
    }

    func wofinsDismissKeyboardOnOutsideTap() -> some View {
        background {
            WofinsKeyboardDismissProbe()
                .frame(width: 1, height: 1)
                .accessibilityHidden(true)
        }
    }

    func wofinsKeyboardDoneButton() -> some View {
        toolbar {
            ToolbarItemGroup(placement: .keyboard) {
                Spacer()
                Button("Selesai") {
                    UIApplication.shared.sendAction(#selector(UIResponder.resignFirstResponder), to: nil, from: nil, for: nil)
                }
            }
        }
    }

    func projectSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 18, style: .continuous)
                    .stroke(WofinsTheme.border.opacity(0.75), lineWidth: 1)
            }
            .wofinsSoftShadow()
    }

    /// Soft shadow for cards inside scrolling lists — cheap enough for 60fps.
    func wofinsSoftShadow() -> some View {
        shadow(color: Color.black.opacity(0.04), radius: 3, y: 1)
    }

    /// Slightly stronger shadow for one-off hero cards (balance / cash summary).
    func wofinsHeroShadow() -> some View {
        shadow(color: Color.black.opacity(0.08), radius: 6, y: 3)
    }
}

private struct WofinsKeyboardDismissProbe: UIViewControllerRepresentable {
    func makeUIViewController(context: Context) -> WofinsKeyboardDismissController {
        WofinsKeyboardDismissController()
    }

    func updateUIViewController(_ uiViewController: WofinsKeyboardDismissController, context: Context) {}
}

private final class WofinsKeyboardDismissController: UIViewController, UIGestureRecognizerDelegate {
    private weak var installedWindow: UIWindow?
    private var recognizer: UITapGestureRecognizer?

    override func viewDidLoad() {
        super.viewDidLoad()
        view.backgroundColor = .clear
        view.isUserInteractionEnabled = false
    }

    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(animated)
        install()
    }

    override func viewWillDisappear(_ animated: Bool) {
        super.viewWillDisappear(animated)
        uninstall()
    }

    private func install() {
        guard recognizer == nil, let window = view.window else { return }
        if window.gestureRecognizers?.contains(where: { $0.name == "wofins.dismissKeyboard" }) == true {
            return
        }
        let tap = UITapGestureRecognizer(target: self, action: #selector(dismissKeyboard))
        tap.cancelsTouchesInView = false
        tap.delegate = self
        tap.name = "wofins.dismissKeyboard"
        window.addGestureRecognizer(tap)
        recognizer = tap
        installedWindow = window
    }

    private func uninstall() {
        if let recognizer {
            installedWindow?.removeGestureRecognizer(recognizer)
        }
        recognizer = nil
        installedWindow = nil
    }

    @objc private func dismissKeyboard() {
        installedWindow?.endEditing(true)
    }

    func gestureRecognizer(_ gestureRecognizer: UIGestureRecognizer, shouldReceive touch: UITouch) -> Bool {
        var view = touch.view
        while let current = view {
            if current is UITextField || current is UITextView {
                return false
            }
            view = current.superview
        }
        return true
    }

    func gestureRecognizer(
        _ gestureRecognizer: UIGestureRecognizer,
        shouldRecognizeSimultaneouslyWith otherGestureRecognizer: UIGestureRecognizer
    ) -> Bool {
        true
    }
}
