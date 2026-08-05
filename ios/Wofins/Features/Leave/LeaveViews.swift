import SwiftUI

struct LeaveListView: View {
    @EnvironmentObject private var appState: AppState
    @State private var items: [LeaveRequestItem] = []
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var statusFilter: String = "all"

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && items.isEmpty {
                    ProgressView("Memuat cuti…")
                } else if let errorMessage, items.isEmpty {
                    ContentUnavailableView("Gagal memuat", systemImage: "exclamationmark.triangle", description: Text(errorMessage))
                } else if items.isEmpty {
                    ContentUnavailableView("Belum ada cuti", systemImage: "calendar.badge.exclamationmark", description: Text("Ajukan cuti pertama Anda."))
                } else {
                    List(items) { item in
                        NavigationLink {
                            LeaveDetailView(leaveId: item.id)
                        } label: {
                            VStack(alignment: .leading, spacing: 4) {
                                Text(item.leave_type?.name ?? "Cuti")
                                    .font(.poppins(.headline))
                                Text("\(item.start_date ?? "-") → \(item.end_date ?? "-") · \(item.total_days ?? 0) hari")
                                    .font(.poppins(.subheadline))
                                    .foregroundStyle(WofinsTheme.muted)
                                Text(item.statusLabel)
                                    .font(.poppins(.caption, weight: .semibold))
                                    .foregroundStyle(statusColor(item.status))
                            }
                            .padding(.vertical, 4)
                        }
                    }
                    .listStyle(.plain)
                }
            }
            .background(WofinsTheme.background)
            .navigationTitle("Cuti")
            .toolbar {
                ToolbarItem(placement: .topBarTrailing) {
                    NavigationLink {
                        LeaveCreateView()
                    } label: {
                        Image(systemName: "plus")
                    }
                }
                ToolbarItem(placement: .topBarLeading) {
                    Menu {
                        Button("Semua") { statusFilter = "all"; Task { await load() } }
                        Button("Menunggu") { statusFilter = "pending"; Task { await load() } }
                        Button("Disetujui") { statusFilter = "approved"; Task { await load() } }
                        Button("Ditolak") { statusFilter = "rejected"; Task { await load() } }
                    } label: {
                        Label("Filter", systemImage: "line.3.horizontal.decrease.circle")
                    }
                }
            }
            .refreshable { await load() }
            .task { await load() }
        }
    }

    private func statusColor(_ status: String?) -> Color {
        switch status {
        case "approved": return WofinsTheme.accent
        case "rejected": return WofinsTheme.danger
        default: return .orange
        }
    }

    private func load() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let status = statusFilter == "all" ? nil : statusFilter
            items = try await appState.api.leaveRequests(status: status)
            errorMessage = nil
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}

struct LeaveDetailView: View {
    @EnvironmentObject private var appState: AppState
    let leaveId: Int
    @State private var item: LeaveRequestItem?
    @State private var errorMessage: String?

    var body: some View {
        Group {
            if let item {
                List {
                    Section("Jenis") {
                        Text(item.leave_type?.name ?? "-")
                        if let ket = item.leave_type?.keterangan {
                            Text(ket).foregroundStyle(WofinsTheme.muted)
                        }
                    }
                    Section("Periode") {
                        LabeledContent("Mulai", value: item.start_date ?? "-")
                        LabeledContent("Selesai", value: item.end_date ?? "-")
                        LabeledContent("Total", value: "\(item.total_days ?? 0) hari")
                        LabeledContent("Status", value: item.statusLabel)
                    }
                    Section("Alasan") {
                        Text(item.reason ?? "-")
                    }
                    if let notes = item.approval_notes, !notes.isEmpty {
                        Section("Catatan approval") {
                            Text(notes)
                        }
                    }
                }
            } else if let errorMessage {
                ContentUnavailableView("Error", systemImage: "xmark.octagon", description: Text(errorMessage))
            } else {
                ProgressView()
            }
        }
        .navigationTitle("Detail Cuti")
        .task {
            do {
                item = try await appState.api.leaveRequest(id: leaveId)
            } catch {
                errorMessage = error.localizedDescription
            }
        }
    }
}

struct LeaveCreateView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    @State private var types: [LeaveTypeItem] = []
    @State private var selectedType: LeaveTypeItem?
    @State private var startDate = Date().addingTimeInterval(86400)
    @State private var endDate = Date().addingTimeInterval(86400 * 2)
    @State private var reason = ""
    @State private var emergencyContact = ""
    @State private var isSubmitting = false
    @State private var errorMessage: String?

    private let dateFormatter: DateFormatter = {
        let f = DateFormatter()
        f.calendar = Calendar(identifier: .gregorian)
        f.locale = Locale(identifier: "en_US_POSIX")
        f.dateFormat = "yyyy-MM-dd"
        return f
    }()

    var body: some View {
        Form {
            Section("Jenis cuti") {
                Picker("Tipe", selection: $selectedType) {
                    Text("Pilih…").tag(Optional<LeaveTypeItem>.none)
                    ForEach(types) { type in
                        Text(type.name).tag(Optional(type))
                    }
                }
            }

            Section("Tanggal") {
                DatePicker("Mulai", selection: $startDate, in: Date()..., displayedComponents: .date)
                DatePicker("Selesai", selection: $endDate, in: startDate..., displayedComponents: .date)
            }

            Section("Detail") {
                TextField("Alasan (min. 10 karakter)", text: $reason, axis: .vertical)
                    .lineLimit(3...6)
                TextField("Kontak darurat (opsional)", text: $emergencyContact)
            }

            if let errorMessage {
                Section {
                    Text(errorMessage).foregroundStyle(WofinsTheme.danger)
                }
            }

            Section {
                Button {
                    Task { await submit() }
                } label: {
                    if isSubmitting {
                        ProgressView()
                    } else {
                        Text("Ajukan Cuti")
                            .frame(maxWidth: .infinity)
                            .font(.poppins(.body, weight: .semibold))
                    }
                }
                .disabled(isSubmitting || selectedType == nil || reason.count < 10)
            }
        }
        .navigationTitle("Ajukan Cuti")
        .task {
            do {
                types = try await appState.api.leaveTypes()
                selectedType = types.first
            } catch {
                errorMessage = error.localizedDescription
            }
        }
    }

    private func submit() async {
        guard let selectedType else { return }
        isSubmitting = true
        defer { isSubmitting = false }
        do {
            let payload = CreateLeavePayload(
                leave_type_id: selectedType.id,
                start_date: dateFormatter.string(from: startDate),
                end_date: dateFormatter.string(from: endDate),
                reason: reason,
                emergency_contact: emergencyContact.isEmpty ? nil : emergencyContact
            )
            _ = try await appState.api.createLeaveRequest(payload)
            dismiss()
        } catch {
            errorMessage = error.localizedDescription
        }
    }
}
