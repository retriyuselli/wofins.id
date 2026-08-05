import SwiftUI

struct HomeView: View {
    @EnvironmentObject private var appState: AppState
    @State private var schedule: ScheduleData?
    @State private var isLoading = false
    @State private var errorMessage: String?

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

                    Group {
                        if isLoading && schedule == nil {
                            ProgressView("Memuat jadwal…")
                                .frame(maxWidth: .infinity)
                                .padding()
                        } else if let errorMessage {
                            Text(errorMessage)
                                .foregroundStyle(WofinsTheme.danger)
                        } else if let next = schedule?.next_leave {
                            VStack(alignment: .leading, spacing: 8) {
                                Text("Cuti berikutnya")
                                    .font(.poppins(.headline))
                                Text(next.leave_type?.name ?? "Cuti")
                                    .font(.poppins(.title3, weight: .semibold))
                                Text("\(next.start_date ?? "-") → \(next.end_date ?? "-")")
                                    .foregroundStyle(WofinsTheme.muted)
                                if let days = schedule?.days_until_next_leave {
                                    Text(days == 0 ? "Hari ini" : "\(days) hari lagi")
                                        .font(.poppins(.subheadline, weight: .medium))
                                        .foregroundStyle(WofinsTheme.accent)
                                }
                                Text(next.statusLabel)
                                    .font(.poppins(.caption, weight: .semibold))
                                    .padding(.horizontal, 10)
                                    .padding(.vertical, 4)
                                    .background(WofinsTheme.accent.opacity(0.12))
                                    .clipShape(Capsule())
                            }
                            .frame(maxWidth: .infinity, alignment: .leading)
                            .padding()
                            .background(WofinsTheme.card)
                            .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
                        } else {
                            Text("Belum ada cuti terjadwal.")
                                .foregroundStyle(WofinsTheme.muted)
                                .padding()
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .background(WofinsTheme.card)
                                .clipShape(RoundedRectangle(cornerRadius: 16, style: .continuous))
                        }
                    }

                    VStack(alignment: .leading, spacing: 12) {
                        Text("Aksi cepat")
                            .font(.poppins(.headline))
                        NavigationLink {
                            LeaveCreateView()
                        } label: {
                            Label("Ajukan cuti", systemImage: "plus.circle.fill")
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .padding()
                                .background(WofinsTheme.accent.opacity(0.12))
                                .foregroundStyle(WofinsTheme.accent)
                                .clipShape(RoundedRectangle(cornerRadius: 12, style: .continuous))
                        }
                    }
                }
                .padding()
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle("Home")
            .refreshable { await load() }
            .task { await load() }
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            schedule = try await appState.api.schedule()
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
