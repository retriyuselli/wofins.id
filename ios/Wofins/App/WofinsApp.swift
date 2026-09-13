import SwiftUI

enum SplashTiming {
    /// Durasi minimum overlay. Reduce Motion hampir langsung; selain itu cukup untuk logo tanpa menahan 3 detik.
    static func minimumHoldNanoseconds(reduceMotion: Bool) -> UInt64 {
        reduceMotion ? 120_000_000 : 800_000_000
    }
}

@main
struct WofinsApp: App {
    @StateObject private var appState = AppState()

    var body: some Scene {
        WindowGroup {
            AppBootstrap()
                .environmentObject(appState)
        }
    }
}

private struct AppBootstrap: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.accessibilityReduceMotion) private var reduceMotion
    @State private var showsAnimatedSplash = true

    var body: some View {
        ZStack {
            RootView()

            if showsAnimatedSplash {
                AnimatedSplashView(reduceMotion: reduceMotion)
                    .transition(reduceMotion ? .identity : .opacity)
                    .zIndex(10)
            }
        }
        .font(.poppins(.body))
        .tint(WofinsTheme.accent)
        .preferredColorScheme(.light)
        .task {
            await dismissSplashWhenReady()
        }
        .onOpenURL { url in
            _ = GoogleSignInService.shared.handle(url: url)
        }
    }

    private func dismissSplashWhenReady() async {
        let hold = SplashTiming.minimumHoldNanoseconds(reduceMotion: reduceMotion)
        try? await Task.sleep(nanoseconds: hold)
        while appState.isBootstrapping {
            try? await Task.sleep(nanoseconds: 50_000_000)
        }
        if reduceMotion {
            showsAnimatedSplash = false
        } else {
            withAnimation(.easeInOut(duration: 0.35)) {
                showsAnimatedSplash = false
            }
        }
    }
}

private struct AnimatedSplashView: View {
    let reduceMotion: Bool

    @State private var logoScale: CGFloat = 0.82
    @State private var logoOpacity = 0.0
    @State private var glowScale: CGFloat = 0.6
    @State private var glowOpacity = 0.0
    @State private var textOffset: CGFloat = 14
    @State private var textOpacity = 0.0

    var body: some View {
        ZStack {
            WofinsTheme.primary.ignoresSafeArea()

            Circle()
                .fill(WofinsTheme.primaryLight.opacity(0.28))
                .frame(width: 245, height: 245)
                .scaleEffect(glowScale)
                .opacity(glowOpacity)

            VStack(spacing: 18) {
                Image("LaunchLogo")
                    .resizable()
                    .scaledToFit()
                    .frame(width: 160, height: 160)
                    .scaleEffect(logoScale)
                    .opacity(logoOpacity)
                    .shadow(color: WofinsTheme.yellow.opacity(0.25), radius: 20)

                VStack(spacing: 4) {
                    Text("WOFINS")
                        .font(.poppins(size: 30, weight: .bold))
                        .tracking(2.2)
                        .foregroundStyle(.white)
                    Text("Wedding Organizer Financial System")
                        .font(.poppins(.caption))
                        .foregroundStyle(.white.opacity(0.72))
                }
                .offset(y: textOffset)
                .opacity(textOpacity)
            }
        }
        .onAppear {
            if reduceMotion {
                logoScale = 1
                logoOpacity = 1
                glowScale = 1
                glowOpacity = 1
                textOffset = 0
                textOpacity = 1
                return
            }
            withAnimation(.easeInOut(duration: 0.7)) {
                logoScale = 1
                logoOpacity = 1
            }
            withAnimation(.easeInOut(duration: 0.85)) {
                glowScale = 1
                glowOpacity = 1
            }
            withAnimation(.easeInOut(duration: 0.45).delay(0.2)) {
                textOffset = 0
                textOpacity = 1
            }
        }
        .accessibilityElement(children: .combine)
        .accessibilityLabel("WOFINS, Wedding Organizer Financial System")
    }
}
