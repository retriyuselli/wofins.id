import SwiftUI

struct PrivacyStatementView: View {
    @Environment(\.dismiss) private var dismiss

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 12) {
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
