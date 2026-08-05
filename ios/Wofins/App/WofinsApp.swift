import SwiftUI

@main
struct WofinsApp: App {
    @StateObject private var appState = AppState()

    var body: some Scene {
        WindowGroup {
            RootView()
                .environmentObject(appState)
                .font(.poppins(.body))
                .tint(WofinsTheme.accent)
        }
    }
}
