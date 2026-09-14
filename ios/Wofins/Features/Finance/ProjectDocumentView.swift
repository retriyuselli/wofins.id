import SwiftUI
import UIKit
import WebKit

struct ProjectDocumentView: View {
    @Environment(\.dismiss) private var dismiss

    let url: URL
    let title: String
    let fileName: String
    var token: String?

    var body: some View {
        VStack(spacing: 0) {
            HStack(spacing: 12) {
                Button { dismiss() } label: {
                    Image(systemName: "chevron.left")
                        .font(.system(size: 16, weight: .bold))
                        .foregroundStyle(.white)
                        .frame(width: 40, height: 40)
                        .background(.white.opacity(0.11), in: Circle())
                }
                .accessibilityLabel("Kembali")

                VStack(alignment: .leading, spacing: 2) {
                    Text(title)
                        .font(.poppins(.headline, weight: .bold))
                        .foregroundStyle(.white)
                        .lineLimit(1)
                        .minimumScaleFactor(0.8)
                    Text(fileName)
                        .font(.poppins(.caption))
                        .foregroundStyle(.white.opacity(0.72))
                        .lineLimit(1)
                        .minimumScaleFactor(0.8)
                }
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))

            DocumentWebView(url: url, token: token)
                .background(WofinsTheme.background)
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsHidesNavigationBar()
    }
}

private struct DocumentWebView: UIViewRepresentable {
    let url: URL
    var token: String?

    func makeUIView(context: Context) -> WKWebView {
        let webView = WKWebView()
        webView.backgroundColor = .clear
        webView.isOpaque = false
        webView.scrollView.contentInsetAdjustmentBehavior = .never
        if url.isFileURL {
            webView.loadFileURL(url, allowingReadAccessTo: url.deletingLastPathComponent())
        } else {
            var request = URLRequest(url: url)
            request.timeoutInterval = 180
            request.setValue("application/pdf", forHTTPHeaderField: "Accept")
            if let token, !token.isEmpty, APIConfig.shouldAttachAuthorization(to: url) {
                request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
            }
            webView.load(request)
        }
        return webView
    }

    func updateUIView(_ uiView: WKWebView, context: Context) {}
}
