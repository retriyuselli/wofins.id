import SwiftUI

@main
struct WofinsApp: App {
    @StateObject private var appState = AppState()
    @State private var showsAnimatedSplash = true

    var body: some Scene {
        WindowGroup {
            ZStack {
                RootView()
                    .environmentObject(appState)

                if showsAnimatedSplash {
                    AnimatedSplashView()
                        .transition(.opacity)
                        .zIndex(10)
                }
            }
            .font(.poppins(.body))
            .tint(WofinsTheme.accent)
            .task {
                guard showsAnimatedSplash else { return }
                try? await Task.sleep(for: .seconds(3.2))
                withAnimation(.easeInOut(duration: 0.7)) {
                    showsAnimatedSplash = false
                }
            }
            .onOpenURL { url in
                _ = GoogleSignInService.shared.handle(url: url)
            }
        }
    }
}

private struct AnimatedSplashView: View {
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
            withAnimation(.easeInOut(duration: 1.45)) {
                logoScale = 1
                logoOpacity = 1
            }
            withAnimation(.easeInOut(duration: 1.8)) {
                glowScale = 1
                glowOpacity = 1
            }
            withAnimation(.easeInOut(duration: 0.9).delay(0.65)) {
                textOffset = 0
                textOpacity = 1
            }
        }
        .accessibilityElement(children: .combine)
        .accessibilityLabel("WOFINS, Wedding Organizer Financial System")
    }
}
