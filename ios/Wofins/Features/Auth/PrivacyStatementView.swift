import SwiftUI

enum WofinsLegalURL {
    static let privacyPolicy = URL(string: "https://wofins.id/kebijakan-privasi")!
    /// Custom Terms of Use / EULA for auto-renewable subscriptions (Guideline 3.1.2(c)).
    static let termsOfUse = URL(string: "https://wofins.id/syarat-ketentuan")!
}

struct PrivacyStatementView: View {
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 12) {
                    Link(destination: WofinsLegalURL.privacyPolicy) {
                        HStack(spacing: 12) {
                            Image(systemName: "hand.raised.fill")
                                .font(.system(size: 18, weight: .semibold))
                                .foregroundStyle(WofinsTheme.primary)
                                .frame(width: 38, height: 38)
                                .background(WofinsTheme.primary.opacity(0.1), in: Circle())

                            VStack(alignment: .leading, spacing: 2) {
                                Text("Buka kebijakan privasi lengkap")
                                    .font(.poppins(.subheadline, weight: .semibold))
                                    .foregroundStyle(WofinsTheme.ink)
                                Text("wofins.id/kebijakan-privasi")
                                    .font(.poppins(.caption2))
                                    .foregroundStyle(WofinsTheme.muted)
                                    .lineLimit(1)
                                    .minimumScaleFactor(0.8)
                            }
                            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

                            Image(systemName: "arrow.up.right")
                                .font(.system(size: 13, weight: .semibold))
                                .foregroundStyle(WofinsTheme.primary)
                        }
                        .padding(14)
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 16, style: .continuous))
                        .overlay {
                            RoundedRectangle(cornerRadius: 16, style: .continuous)
                                .stroke(WofinsTheme.border, lineWidth: 1)
                                .allowsHitTesting(false)
                        }
                    }
                    .buttonStyle(.plain)

                    Text("Kami menghargai privasi Anda. Berikut ringkasan bagaimana kami mengelola data Anda:")
                        .font(.poppins(.subheadline))
                        .foregroundStyle(WofinsTheme.ink)
                        .fixedSize(horizontal: false, vertical: true)

                    bullet("Kami mengumpulkan data hanya untuk keperluan layanan.")
                    bullet("Data Anda tidak akan dijual ke pihak ketiga.")
                    bullet("Kami memakai HTTPS dan enkripsi di server untuk melindungi data sensitif.")
                    bullet("Anda berhak meminta penghapusan data Anda kapan saja.")
                    bullet("Cookie di situs web dipakai untuk meningkatkan pengalaman pengguna.")
                }
                .padding(20)
                .frame(maxWidth: .infinity, alignment: .leading)
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle("Kebijakan privasi")
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
    }

    private func bullet(_ text: String) -> some View {
        HStack(alignment: .top, spacing: 10) {
            Text("•")
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
            Text(text)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)
        }
    }
}
