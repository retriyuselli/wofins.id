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
    @State private var ringScale: CGFloat = 0.78
    @State private var ringOpacity = 0.0
    @State private var accentOpacity = 0.0
    @State private var textOffset: CGFloat = 14
    @State private var textOpacity = 0.0

    var body: some View {
        GeometryReader { geo in
            let size = geo.size
            ZStack {
                WofinsTheme.primary.ignoresSafeArea()
                splashShapes(in: size)

                Circle()
                    .fill(WofinsTheme.primaryLight.opacity(0.28))
                    .frame(width: 245, height: 245)
                    .scaleEffect(glowScale)
                    .opacity(glowOpacity)

                VStack(spacing: 18) {
                    splashLogo

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

                    Image("SplashPartners")
                        .resizable()
                        .scaledToFit()
                        .frame(width: min(310, size.width - 40), height: 66)
                        .offset(y: textOffset)
                        .opacity(textOpacity)
                        .accessibilityLabel("Makna Kreatif Indonesia dan Hastana Indonesia")
                }
            }
            .frame(width: size.width, height: size.height)
        }
        .ignoresSafeArea()
        .onAppear(perform: playIntro)
        .accessibilityElement(children: .combine)
        .accessibilityLabel("WOFINS, Wedding Organizer Financial System, Makna Kreatif Indonesia dan Hastana Indonesia")
    }

    @ViewBuilder
    private var splashLogo: some View {
        if AppColorTheme.selected == .hastana {
            Image("LaunchLogo")
                .renderingMode(.template)
                .resizable()
                .scaledToFit()
                .foregroundStyle(.white)
                .frame(width: 160, height: 160)
                .scaleEffect(logoScale)
                .opacity(logoOpacity)
                .shadow(color: Color.black.opacity(0.22), radius: 16)
        } else {
            Image("LaunchLogo")
                .resizable()
                .scaledToFit()
                .frame(width: 160, height: 160)
                .scaleEffect(logoScale)
                .opacity(logoOpacity)
                .shadow(color: WofinsTheme.yellow.opacity(0.22), radius: 16)
        }
    }

    private func splashShapes(in size: CGSize) -> some View {
        let w = size.width
        let h = size.height
        return ZStack {
            Circle()
                .fill(WofinsTheme.primaryLight.opacity(0.34 * accentOpacity))
                .frame(width: 260, height: 260)
                .offset(x: -w * 0.42, y: -h * 0.38)
            Circle()
                .fill(WofinsTheme.yellow.opacity(0.07 * accentOpacity))
                .frame(width: 210, height: 210)
                .offset(x: w * 0.44, y: h * 0.36)

            Circle()
                .stroke(Color.white.opacity(0.10), lineWidth: 1)
                .frame(width: 292, height: 292)
                .scaleEffect(ringScale)
                .opacity(ringOpacity)
            Circle()
                .stroke(WofinsTheme.yellow.opacity(0.26), lineWidth: 1.5)
                .frame(width: 214, height: 214)
                .scaleEffect(ringScale)
                .opacity(ringOpacity)
            Circle()
                .trim(from: 0.08, to: 0.32)
                .stroke(WofinsTheme.yellow.opacity(0.55), style: StrokeStyle(lineWidth: 2.5, lineCap: .round))
                .frame(width: 248, height: 248)
                .rotationEffect(.degrees(-18))
                .scaleEffect(ringScale)
                .opacity(ringOpacity)

            splashMark(size: 9, x: -w * 0.28, y: -h * 0.18, diamond: true)
            splashMark(size: 6, x: w * 0.30, y: -h * 0.16, diamond: false)
            splashMark(size: 7, x: w * 0.26, y: h * 0.14, diamond: true)
            splashMark(size: 5, x: -w * 0.24, y: h * 0.18, diamond: false)
        }
        .allowsHitTesting(false)
    }

    private func splashMark(size: CGFloat, x: CGFloat, y: CGFloat, diamond: Bool) -> some View {
        RoundedRectangle(cornerRadius: diamond ? 2 : size / 2)
            .fill(diamond ? WofinsTheme.yellow.opacity(0.55) : Color.white.opacity(0.38))
            .frame(width: size, height: size)
            .rotationEffect(.degrees(diamond ? 45 : 0))
            .offset(x: x, y: y)
            .opacity(accentOpacity)
    }

    private func playIntro() {
        if reduceMotion {
            logoScale = 1
            logoOpacity = 1
            glowScale = 1
            glowOpacity = 1
            ringScale = 1
            ringOpacity = 1
            accentOpacity = 1
            textOffset = 0
            textOpacity = 1
            return
        }
        withAnimation(.easeOut(duration: 0.55)) {
            accentOpacity = 1
        }
        withAnimation(.easeOut(duration: 0.7)) {
            ringScale = 1
            ringOpacity = 1
            logoScale = 1
            logoOpacity = 1
        }
        withAnimation(.easeInOut(duration: 0.8)) {
            glowScale = 1
            glowOpacity = 1
        }
        withAnimation(.easeOut(duration: 0.4).delay(0.18)) {
            textOffset = 0
            textOpacity = 1
        }
    }
}
