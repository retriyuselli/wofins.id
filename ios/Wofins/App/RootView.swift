import SwiftUI

enum WofinsTheme {
    static let accent = Color(red: 0.12, green: 0.42, blue: 0.38)
    static let background = Color(red: 0.96, green: 0.97, blue: 0.96)
    static let card = Color.white
    static let muted = Color(red: 0.40, green: 0.45, blue: 0.44)
    static let danger = Color(red: 0.75, green: 0.22, blue: 0.20)
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
        .task {
            await appState.bootstrap()
        }
    }
}

struct MainTabView: View {
    var body: some View {
        TabView {
            DashboardView()
                .tabItem { Label("Dashboard", systemImage: "chart.line.uptrend.xyaxis") }

            ProjectsView()
                .tabItem { Label("Proyek", systemImage: "folder.fill") }

            TransactionsView()
                .tabItem { Label("Transaksi", systemImage: "arrow.left.arrow.right") }

            ReportsView()
                .tabItem { Label("Laporan", systemImage: "doc.text.fill") }

            AccountView()
                .tabItem { Label("Akun", systemImage: "person.crop.circle") }
        }
        .tint(WofinsTheme.accent)
    }
}
