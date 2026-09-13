import SwiftUI

struct SimulasiFormView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss
    var recordId: Int? = nil
    var onSaved: () -> Void

    private var isEditing: Bool { recordId != nil }

    @State private var schema: ModuleFormSchema?
    @State private var productId = ""
    @State private var userId = ""
    @State private var prospectId = ""
    @State private var contractNumber = ""
    @State private var nameTtd = ""
    @State private var titleTtd = ""
    @State private var notes = ""
    @State private var dpText = "0"
    @State private var terms: [SimulasiTermDraft] = []
    @State private var isLoading = false
    @State private var isSaving = false
    @State private var errorMessage: String?

    private var products: [ModuleFormOption] { field("product_id")?.options ?? [] }
    private var users: [ModuleFormOption] { field("user_id")?.options ?? [] }
    private var prospects: [ModuleFormOption] { field("prospect_id")?.options ?? [] }
    private var months: [ModuleFormOption] {
        if let months = schema?.months, !months.isEmpty { return months }
        return [
            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember",
        ].map { ModuleFormOption(value: $0, label: $0, total_price: nil, penambahan: nil, pengurangan: nil) }
    }

    private var selectedProduct: ModuleFormOption? {
        products.first(where: { $0.value == productId })
    }

    private var totalPrice: Int { selectedProduct?.total_price ?? 0 }
    private var penambahan: Int { selectedProduct?.penambahan ?? 0 }
    private var pengurangan: Int { selectedProduct?.pengurangan ?? 0 }
    private var grandTotal: Int { max(0, totalPrice + penambahan - pengurangan) }
    private var dpAmount: Int { Int(dpText.filter(\.isNumber)) ?? 0 }
    private var termsTotal: Int { terms.reduce(0) { $0 + $1.nominal } }
    private var paymentTotal: Int { dpAmount + termsTotal }
    private var paymentMismatch: Int { grandTotal - paymentTotal }

    var body: some View {
        NavigationStack {
            Group {
                if isLoading && schema == nil {
                    ProgressView("Menyiapkan form…")
                        .frame(maxWidth: .infinity, maxHeight: .infinity)
                } else if let errorMessage, schema == nil {
                    Text(errorMessage)
                        .font(.poppins(.caption))
                        .foregroundStyle(WofinsTheme.danger)
                        .padding()
                } else {
                    ScrollView(showsIndicators: false) {
                        VStack(alignment: .leading, spacing: 16) {
                            if let errorMessage {
                                Text(errorMessage)
                                    .font(.poppins(.caption))
                                    .foregroundStyle(WofinsTheme.danger)
                                    .padding(12)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .background(WofinsTheme.danger.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
                            }

                            section("Paket & harga") {
                                selectField("Paket dasar *", value: $productId, options: products, placeholder: "Pilih paket")
                                helper("Harga otomatis mengikuti paket yang dipilih.")
                                selectField("Account Manager *", value: $userId, options: users, placeholder: "Pilih AM")
                                moneyRow("Harga paket", totalPrice)
                                moneyRow("Penambahan", penambahan)
                                moneyRow("Pengurangan", pengurangan)
                                moneyRow("Grand total", grandTotal, emphasize: true)
                            }

                            section("Detail simulasi") {
                                selectField("Prospek *", value: $prospectId, options: prospects, placeholder: "Pilih prospek")
                                helper("Hanya prospek yang belum punya proyek.")
                                labeledField("Nomor kontrak / surat") {
                                    TextField("Kosongkan untuk otomatis", text: $contractNumber)
                                        .font(.poppins(.subheadline))
                                        .textInputAutocapitalization(.characters)
                                }
                                labeledField("Nama TTD") {
                                    TextField("Nama penandatangan", text: $nameTtd)
                                        .font(.poppins(.subheadline))
                                }
                                labeledField("Jabatan TTD") {
                                    TextField("Contoh: Owner", text: $titleTtd)
                                        .font(.poppins(.subheadline))
                                }
                                labeledField("Catatan") {
                                    TextField("Catatan simulasi", text: $notes, axis: .vertical)
                                        .font(.poppins(.subheadline))
                                        .lineLimit(3...6)
                                }
                            }

                            section("Pola pembayaran") {
                                labeledField("Down Payment (DP)") {
                                    HStack(spacing: 6) {
                                        Text("Rp")
                                            .font(.poppins(.subheadline, weight: .semibold))
                                            .foregroundStyle(WofinsTheme.muted)
                                        TextField("0", text: MoneyFormat.groupedBinding($dpText))
                                            .font(.poppins(.subheadline))
                                            .keyboardType(.numberPad)
                                            .lineLimit(1)
                                            .minimumScaleFactor(0.7)
                                    }
                                }
                                ForEach($terms) { $term in
                                    termCard(term: $term)
                                }
                                Button {
                                    terms.append(SimulasiTermDraft(tahun: Calendar.current.component(.year, from: Date())))
                                } label: {
                                    Label("Tambah pembayaran", systemImage: "plus.circle.fill")
                                        .font(.poppins(.subheadline, weight: .semibold))
                                        .foregroundStyle(WofinsTheme.primary)
                                        .frame(maxWidth: .infinity)
                                        .frame(height: 44)
                                        .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
                                }
                                .buttonStyle(.plain)
                                moneyRow("Total pembayaran (DP + termin)", paymentTotal, emphasize: true)
                                if abs(paymentMismatch) > 1000 {
                                    Text("Total pembayaran belum sama dengan grand total. Selisih \(MoneyFormat.idr(paymentMismatch)).")
                                        .font(.poppins(.caption2))
                                        .foregroundStyle(WofinsTheme.danger)
                                        .fixedSize(horizontal: false, vertical: true)
                                }
                            }

                            Button(action: save) {
                                if isSaving {
                                    ProgressView().tint(.white)
                                } else {
                                    Text("Simpan")
                                        .font(.poppins(.subheadline, weight: .semibold))
                                }
                            }
                            .foregroundStyle(.white)
                            .frame(maxWidth: .infinity)
                            .frame(height: 48)
                            .background(canSave ? WofinsTheme.primary : WofinsTheme.muted, in: RoundedRectangle(cornerRadius: 14))
                            .disabled(isSaving || !canSave)
                        }
                        .padding(16)
                        .padding(.bottom, 28)
                    }
                    .scrollDismissesKeyboard(.immediately)
                    .wofinsKeyboardDoneButton()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle(isEditing ? "Edit Simulasi" : "Tambah Simulasi")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Tutup") { dismiss() }
                }
            }
            .task { await loadForm() }
        }
    }

    private var canSave: Bool {
        !productId.isEmpty && !prospectId.isEmpty && !userId.isEmpty
    }

    private func field(_ name: String) -> ModuleFormField? {
        schema?.fields?.first(where: { $0.name == name })
    }

    private func section(_ title: String, @ViewBuilder content: () -> some View) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(title)
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
            content()
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .moduleSurface()
    }

    private func helper(_ text: String) -> some View {
        Text(text)
            .font(.poppins(.caption2))
            .foregroundStyle(WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
    }

    private func labeledField(_ title: String, @ViewBuilder content: () -> some View) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
            content()
                .padding(.horizontal, 12)
                .padding(.vertical, 10)
                .frame(maxWidth: .infinity, minHeight: 46, alignment: .leading)
                .background(Color.white.opacity(0.001))
                .overlay {
                    RoundedRectangle(cornerRadius: 12).stroke(WofinsTheme.muted.opacity(0.18))
                }
        }
    }

    private func selectField(_ title: String, value: Binding<String>, options: [ModuleFormOption], placeholder: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
            Menu {
                ForEach(options) { option in
                    Button(option.label) { value.wrappedValue = option.value }
                }
            } label: {
                HStack {
                    Text(options.first(where: { $0.value == value.wrappedValue })?.label ?? placeholder)
                        .font(.poppins(.subheadline))
                        .foregroundStyle(value.wrappedValue.isEmpty ? WofinsTheme.muted : WofinsTheme.ink)
                        .lineLimit(2)
                        .minimumScaleFactor(0.85)
                        .multilineTextAlignment(.leading)
                    Spacer(minLength: 8)
                    Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
                }
                .padding(.horizontal, 12)
                .frame(maxWidth: .infinity, minHeight: 46, alignment: .leading)
                .overlay {
                    RoundedRectangle(cornerRadius: 12).stroke(WofinsTheme.muted.opacity(0.18))
                }
            }
        }
    }

    private func moneyRow(_ title: String, _ amount: Int, emphasize: Bool = false) -> some View {
        HStack(alignment: .firstTextBaseline) {
            Text(title)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(emphasize ? .subheadline : .caption, weight: .bold))
                .foregroundStyle(emphasize ? WofinsTheme.primary : WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.75)
        }
    }

    private func termCard(term: Binding<SimulasiTermDraft>) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Termin")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                Spacer()
                Button(role: .destructive) {
                    terms.removeAll { $0.id == term.wrappedValue.id }
                } label: {
                    Image(systemName: "trash")
                        .font(.system(size: 14, weight: .semibold))
                }
                .accessibilityLabel("Hapus termin")
            }
            HStack(spacing: 8) {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Persen").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    TextField("0", text: Binding(
                        get: { term.wrappedValue.persenText },
                        set: { newValue in
                            var next = term.wrappedValue
                            next.persenText = newValue.filter { $0.isNumber || $0 == "." || $0 == "," }
                            let remaining = max(0, grandTotal - dpAmount)
                            let persen = Double(next.persenText.replacingOccurrences(of: ",", with: ".")) ?? 0
                            next.nominalText = String(Int((Double(remaining) * persen / 100).rounded()))
                            term.wrappedValue = next
                        }
                    ))
                    .font(.poppins(.subheadline))
                    .keyboardType(.decimalPad)
                    .padding(.horizontal, 10)
                    .frame(height: 42)
                    .overlay { RoundedRectangle(cornerRadius: 10).stroke(WofinsTheme.muted.opacity(0.18)) }
                }
                .frame(maxWidth: .infinity)
                VStack(alignment: .leading, spacing: 4) {
                    Text("Nominal").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    TextField("0", text: Binding(
                        get: { MoneyFormat.groupedInput(term.wrappedValue.nominalText) },
                        set: { newValue in
                            var next = term.wrappedValue
                            next.nominalText = MoneyFormat.digits(in: newValue)
                            let remaining = max(0, grandTotal - dpAmount)
                            if remaining > 0 {
                                let persen = (Double(next.nominal) / Double(remaining)) * 100
                                next.persenText = String(format: "%.2f", persen)
                            }
                            term.wrappedValue = next
                        }
                    ))
                    .font(.poppins(.subheadline))
                    .keyboardType(.numberPad)
                    .lineLimit(1)
                    .minimumScaleFactor(0.7)
                    .padding(.horizontal, 10)
                    .frame(height: 42)
                    .overlay { RoundedRectangle(cornerRadius: 10).stroke(WofinsTheme.muted.opacity(0.18)) }
                }
                .frame(maxWidth: .infinity)
            }
            HStack(spacing: 8) {
                VStack(alignment: .leading, spacing: 4) {
                    Text("Bulan").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    Menu {
                        ForEach(months) { month in
                            Button(month.label) {
                                var next = term.wrappedValue
                                next.bulan = month.value
                                term.wrappedValue = next
                            }
                        }
                    } label: {
                        HStack {
                            Text(term.wrappedValue.bulan.isEmpty ? "Pilih" : term.wrappedValue.bulan)
                                .font(.poppins(.caption))
                                .foregroundStyle(term.wrappedValue.bulan.isEmpty ? WofinsTheme.muted : WofinsTheme.ink)
                                .lineLimit(1)
                            Spacer()
                            Image(systemName: "chevron.down").font(.caption).foregroundStyle(WofinsTheme.muted)
                        }
                        .padding(.horizontal, 10)
                        .frame(height: 42)
                        .overlay { RoundedRectangle(cornerRadius: 10).stroke(WofinsTheme.muted.opacity(0.18)) }
                    }
                }
                .frame(maxWidth: .infinity)
                VStack(alignment: .leading, spacing: 4) {
                    Text("Tahun").font(.poppins(.caption2)).foregroundStyle(WofinsTheme.muted)
                    TextField("2026", text: Binding(
                        get: { term.wrappedValue.tahunText },
                        set: { newValue in
                            var next = term.wrappedValue
                            next.tahunText = newValue.filter(\.isNumber)
                            term.wrappedValue = next
                        }
                    ))
                    .font(.poppins(.subheadline))
                    .keyboardType(.numberPad)
                    .padding(.horizontal, 10)
                    .frame(height: 42)
                    .overlay { RoundedRectangle(cornerRadius: 10).stroke(WofinsTheme.muted.opacity(0.18)) }
                }
                .frame(width: 90)
            }
        }
        .padding(12)
        .overlay {
            RoundedRectangle(cornerRadius: 12).stroke(WofinsTheme.muted.opacity(0.18))
        }
    }

    private func loadForm() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let loaded = try await appState.api.moduleForm(key: "simulasi", id: recordId)
            schema = loaded
            if let recordId {
                let detail = try await appState.api.moduleDetail(key: "simulasi", id: recordId)
                apply(detail)
            } else if userId.isEmpty {
                userId = loaded.defaults?["user_id"]
                    ?? (appState.currentUser.map { String($0.id) } ?? "")
            }
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func apply(_ detail: ModuleRecord) {
        let values = detail.values ?? [:]
        productId = values["product_id"] ?? productId
        userId = values["user_id"] ?? userId
        prospectId = values["prospect_id"] ?? prospectId
        contractNumber = values["contract_number"] ?? ""
        nameTtd = values["name_ttd"] ?? ""
        titleTtd = values["title_ttd"] ?? ""
        notes = values["notes"] ?? ""
        dpText = values["payment_dp_amount"] ?? "0"
        terms = (detail.payment_simulation ?? []).map { term in
            SimulasiTermDraft(
                persen: term.persen,
                nominal: term.nominal ?? 0,
                bulan: term.bulan,
                tahun: term.tahun
            )
        }
    }

    private func save() {
        guard canSave, !isSaving else { return }
        if abs(paymentMismatch) > 1000 {
            errorMessage = "Total pembayaran (DP + termin) tidak sama dengan grand total. Selisih \(MoneyFormat.idr(paymentMismatch))."
            return
        }
        isSaving = true
        errorMessage = nil
        Task {
            defer { isSaving = false }
            do {
                let payload = CreateSimulasiPayload(
                    product_id: Int(productId) ?? 0,
                    prospect_id: Int(prospectId) ?? 0,
                    user_id: Int(userId),
                    contract_number: trimmed(contractNumber),
                    name_ttd: trimmed(nameTtd),
                    title_ttd: trimmed(titleTtd),
                    notes: trimmed(notes),
                    payment_dp_amount: dpAmount,
                    payment_simulation: terms.map {
                        SimulasiPaymentTermPayload(
                            persen: $0.persenText.isEmpty ? nil : $0.persenText,
                            nominal: $0.nominal,
                            bulan: $0.bulan.isEmpty ? nil : $0.bulan,
                            tahun: Int($0.tahunText) ?? Calendar.current.component(.year, from: Date())
                        )
                    }
                )
                if let recordId {
                    _ = try await appState.api.updateSimulasi(id: recordId, payload)
                } else {
                    _ = try await appState.api.createSimulasi(payload)
                }
                onSaved()
                dismiss()
            } catch {
                APILoadFailure.assign(error, to: &errorMessage)
            }
        }
    }

    private func trimmed(_ value: String) -> String? {
        let text = value.trimmingCharacters(in: .whitespacesAndNewlines)
        return text.isEmpty ? nil : text
    }
}

private struct SimulasiTermDraft: Identifiable {
    let id = UUID()
    var persenText = "100"
    var nominalText = "0"
    var bulan = ""
    var tahunText: String

    var nominal: Int { Int(MoneyFormat.digits(in: nominalText)) ?? 0 }

    init(tahun: Int) {
        self.tahunText = String(tahun)
    }

    init(persen: String?, nominal: Int, bulan: String?, tahun: Int?) {
        self.persenText = persen?.isEmpty == false ? persen! : "100"
        self.nominalText = String(nominal)
        self.bulan = bulan ?? ""
        self.tahunText = String(tahun ?? Calendar.current.component(.year, from: Date()))
    }
}
