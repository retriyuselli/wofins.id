import SwiftUI

struct ForgotPasswordView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    @State private var email = ""
    @State private var isSubmitting = false
    @State private var errorMessage: String?
    @State private var successMessage: String?
    @FocusState private var emailFocused: Bool

    private var canSubmit: Bool {
        !isSubmitting
            && successMessage == nil
            && email.trimmingCharacters(in: .whitespacesAndNewlines).contains("@")
    }

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 16) {
                    Text("Masukkan email akun Anda. Kami akan mengirim tautan untuk mengatur ulang password.")
                        .font(.poppins(.subheadline))
                        .foregroundStyle(WofinsTheme.muted)
                        .fixedSize(horizontal: false, vertical: true)

                    VStack(alignment: .leading, spacing: 8) {
                        Text("Email")
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(WofinsTheme.ink)

                        TextField("nama@perusahaan.com", text: $email)
                            .font(.poppins(.body))
                            .foregroundStyle(WofinsTheme.ink)
                            .keyboardType(.emailAddress)
                            .textContentType(.emailAddress)
                            .textInputAutocapitalization(.never)
                            .autocorrectionDisabled()
                            .focused($emailFocused)
                            .disabled(isSubmitting || successMessage != nil)
                            .padding(.horizontal, 14)
                            .padding(.vertical, 14)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 12, style: .continuous))
                            .overlay {
                                RoundedRectangle(cornerRadius: 12, style: .continuous)
                                    .stroke(WofinsTheme.border, lineWidth: 1)
                                    .allowsHitTesting(false)
                            }
                    }

                    if let successMessage {
                        Text(successMessage)
                            .font(.poppins(.subheadline))
                            .foregroundStyle(WofinsTheme.primary)
                            .fixedSize(horizontal: false, vertical: true)
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .padding(12)
                            .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12, style: .continuous))
                    }

                    if let errorMessage {
                        Text(errorMessage)
                            .font(.poppins(.caption))
                            .foregroundStyle(Color.red.opacity(0.9))
                            .fixedSize(horizontal: false, vertical: true)
                    }

                    Button {
                        Task { await submit() }
                    } label: {
                        ZStack {
                            RoundedRectangle(cornerRadius: 14, style: .continuous)
                                .fill(WofinsTheme.primary)

                            if isSubmitting {
                                ProgressView()
                                    .tint(.white)
                            } else {
                                Text(successMessage == nil ? "Kirim tautan" : "Terkirim")
                                    .font(.poppins(.subheadline, weight: .bold))
                                    .foregroundStyle(.white)
                            }
                        }
                        .frame(maxWidth: .infinity)
                        .frame(height: 50)
                        .opacity(canSubmit || isSubmitting ? 1 : 0.7)
                    }
                    .buttonStyle(.plain)
                    .disabled(!canSubmit)

                    if successMessage != nil {
                        Button("Tutup") { dismiss() }
                            .font(.poppins(.subheadline, weight: .semibold))
                            .foregroundStyle(WofinsTheme.primary)
                            .frame(maxWidth: .infinity)
                            .padding(.top, 4)
                    }
                }
                .padding(20)
                .frame(maxWidth: .infinity, alignment: .leading)
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle("Lupa password")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Tutup") { dismiss() }
                }
            }
            .wofinsSwipeBack()
            .onAppear {
                if email.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty,
                   let saved = UserDefaults.standard.string(forKey: "wofins.savedEmail"),
                   !saved.isEmpty {
                    email = saved
                }
                DispatchQueue.main.asyncAfter(deadline: .now() + 0.35) {
                    emailFocused = true
                }
            }
        }
        .presentationDetents([.medium, .large])
        .presentationDragIndicator(.visible)
    }

    private func submit() async {
        let trimmed = email.trimmingCharacters(in: .whitespacesAndNewlines)
        guard !trimmed.isEmpty else { return }

        emailFocused = false
        isSubmitting = true
        errorMessage = nil
        defer { isSubmitting = false }

        do {
            let message = try await appState.api.forgotPassword(email: trimmed)
            successMessage = message ?? "Link reset password telah dikirim ke email Anda."
        } catch {
            errorMessage = APILoadFailure.userMessage(for: error)
                ?? error.localizedDescription
        }
    }
}
