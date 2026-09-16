import SwiftUI
import UIKit

enum WofinsSwipeBackPolicy {
    static func canPop(controllerCount: Int) -> Bool {
        controllerCount > 1
    }

    static func shouldFinishDismiss(translation: CGFloat, velocity: CGFloat) -> Bool {
        translation > 88 || velocity > 720
    }
}

extension View {
    func wofinsSwipeBack() -> some View {
        modifier(WofinsSwipeBackModifier())
    }

    func wofinsHidesNavigationBar() -> some View {
        toolbar(.hidden, for: .navigationBar)
            .toolbarBackground(.hidden, for: .navigationBar)
            .wofinsSwipeBack()
    }
}

private struct WofinsSwipeBackModifier: ViewModifier {
    @Environment(\.dismiss) private var dismiss

    func body(content: Content) -> some View {
        content.background {
            WofinsSwipeBackInstaller(onEdgeDismiss: { dismiss() })
                .frame(width: 0, height: 0)
                .accessibilityHidden(true)
        }
    }
}

private struct WofinsSwipeBackInstaller: UIViewControllerRepresentable {
    var onEdgeDismiss: () -> Void

    func makeUIViewController(context: Context) -> WofinsSwipeBackController {
        let controller = WofinsSwipeBackController()
        controller.onEdgeDismiss = onEdgeDismiss
        return controller
    }

    func updateUIViewController(_ uiViewController: WofinsSwipeBackController, context: Context) {
        uiViewController.onEdgeDismiss = onEdgeDismiss
    }
}

final class WofinsSwipeBackController: UIViewController, UIGestureRecognizerDelegate {
    var onEdgeDismiss: (() -> Void)?
    private var dismissPan: UIScreenEdgePanGestureRecognizer?
    private weak var panHost: UIView?

    override func viewDidLoad() {
        super.viewDidLoad()
        view.backgroundColor = .clear
        view.isUserInteractionEnabled = false
    }

    override func viewDidAppear(_ animated: Bool) {
        super.viewDidAppear(animated)
        configure()
    }

    override func viewDidLayoutSubviews() {
        super.viewDidLayoutSubviews()
        configure()
    }

    override func viewWillDisappear(_ animated: Bool) {
        super.viewWillDisappear(animated)
        panHost?.transform = .identity
        panHost?.alpha = 1
    }

    override func viewDidDisappear(_ animated: Bool) {
        super.viewDidDisappear(animated)
        removeDismissPan()
    }

    private func configure() {
        guard let nav = navigationController else {
            installDismissPanIfNeeded()
            return
        }

        let pop = nav.interactivePopGestureRecognizer
        nav.wofinsEnableInteractivePop()
        pop?.isEnabled = WofinsSwipeBackPolicy.canPop(controllerCount: nav.viewControllers.count)

        if WofinsSwipeBackPolicy.canPop(controllerCount: nav.viewControllers.count) {
            removeDismissPan()
        } else {
            installDismissPanIfNeeded()
        }
    }

    private var isPresentedScreen: Bool {
        presentingViewController != nil
            || parent?.presentingViewController != nil
            || navigationController?.presentingViewController != nil
    }

    private func installDismissPanIfNeeded() {
        guard isPresentedScreen, dismissPan == nil else { return }
        guard let host = navigationController?.view ?? parent?.view ?? view.window else { return }

        let pan = UIScreenEdgePanGestureRecognizer(target: self, action: #selector(handleDismissPan))
        pan.edges = .left
        pan.delegate = self
        pan.name = "wofins.edgeDismiss"
        host.addGestureRecognizer(pan)
        dismissPan = pan
        panHost = host
    }

    private func removeDismissPan() {
        if let dismissPan {
            panHost?.removeGestureRecognizer(dismissPan)
        }
        panHost?.transform = .identity
        panHost?.alpha = 1
        dismissPan = nil
        panHost = nil
    }

    @objc private func handleDismissPan(_ gesture: UIScreenEdgePanGestureRecognizer) {
        guard let host = panHost else { return }
        let translation = gesture.translation(in: host).x
        let width = max(host.bounds.width, 1)

        switch gesture.state {
        case .changed:
            let x = max(0, translation)
            host.transform = CGAffineTransform(translationX: x, y: 0)
            host.alpha = 1 - min(0.18, x / width * 0.18)
        case .ended:
            let velocity = gesture.velocity(in: host).x
            if WofinsSwipeBackPolicy.shouldFinishDismiss(translation: translation, velocity: velocity) {
                UIView.animate(withDuration: 0.22, delay: 0, options: [.curveEaseOut]) {
                    host.transform = CGAffineTransform(translationX: width, y: 0)
                    host.alpha = 0.86
                } completion: { _ in
                    host.transform = .identity
                    host.alpha = 1
                    self.onEdgeDismiss?()
                }
            } else {
                reset(host)
            }
        case .cancelled, .failed:
            reset(host)
        default:
            break
        }
    }

    private func reset(_ host: UIView) {
        UIView.animate(withDuration: 0.2, delay: 0, options: [.curveEaseOut, .beginFromCurrentState]) {
            host.transform = .identity
            host.alpha = 1
        }
    }

    func gestureRecognizerShouldBegin(_ gestureRecognizer: UIGestureRecognizer) -> Bool {
        guard gestureRecognizer == dismissPan,
              isPresentedScreen,
              let pan = gestureRecognizer as? UIScreenEdgePanGestureRecognizer else {
            return false
        }
        let velocity = pan.velocity(in: panHost)
        return velocity.x > 0 && abs(velocity.x) > abs(velocity.y)
    }

}

private final class WofinsPopGestureBridge: NSObject, UIGestureRecognizerDelegate {
    weak var navigationController: UINavigationController?

    func gestureRecognizerShouldBegin(_ gestureRecognizer: UIGestureRecognizer) -> Bool {
        WofinsSwipeBackPolicy.canPop(controllerCount: navigationController?.viewControllers.count ?? 0)
    }

}

private enum WofinsPopGestureStorage {
    static var bridge: UInt8 = 0
}

private extension UINavigationController {
    func wofinsEnableInteractivePop() {
        let pop = interactivePopGestureRecognizer
        if let bridge = objc_getAssociatedObject(self, &WofinsPopGestureStorage.bridge) as? WofinsPopGestureBridge {
            bridge.navigationController = self
            pop?.delegate = bridge
            return
        }

        let bridge = WofinsPopGestureBridge()
        bridge.navigationController = self
        objc_setAssociatedObject(self, &WofinsPopGestureStorage.bridge, bridge, .OBJC_ASSOCIATION_RETAIN_NONATOMIC)
        pop?.delegate = bridge
    }
}
