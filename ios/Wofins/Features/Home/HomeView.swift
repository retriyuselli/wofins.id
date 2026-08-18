import SwiftUI

struct HomeView: View {
    @EnvironmentObject private var appState: AppState

    var body: some View {
        NavigationStack {
            ScrollView {
                VStack(alignment: .leading, spacing: 20) {
                    if let user = appState.currentUser {
                        VStack(alignment: .leading, spacing: 6) {
                            Text("Halo, \(user.name)")
                                .font(.poppins(.title2, weight: .bold))
                            Text(user.roleLabel)
                                .font(.poppins(.subheadline))
                                .foregroundStyle(WofinsTheme.muted)
                            if let emp = user.employee_id {
                                Text(emp)
                                    .font(.poppins(.caption))
                                    .foregroundStyle(WofinsTheme.muted)
                            }
                        }
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .padding()
                        .background(WofinsTheme.card)
                        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
                        .shadow(color: .black.opacity(0.04), radius: 8, y: 2)
                    }

                    Text("Selamat datang di WOFINS.")
                        .foregroundStyle(WofinsTheme.muted)
                        .padding()
                        .frame(maxWidth: .infinity, alignment: .leading)
                        .background(WofinsTheme.card)
                        .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
                }
                .padding()
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle("Home")
        }
    }
}
