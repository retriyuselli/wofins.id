import SwiftUI
import UIKit

struct VendorFormView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    var recordId: Int? = nil
    var onSaved: () -> Void

    @State private var schema: VendorFormSchema?
    @State private var values: [String: String] = [:]
    @State private var priceHistories: [VendorPriceHistoryDraft] = []
    @State private var isLoading = false
    @State private var isSaving = false
    @State private var errorMessage: String?

    private var isMaster: Bool {
        values["is_master"] == "1" || values["is_master"] == "true"
    }

    private var statusValue: String { values["status"] ?? "product" }

    private var showsParent: Bool { statusValue == "product" }

    private var publishValue: Int { Int((values["harga_publish"] ?? "0").filter(\.isNumber)) ?? 0 }
    private var vendorCostValue: Int { Int((values["harga_vendor"] ?? "0").filter(\.isNumber)) ?? 0 }
    private var profitAmount: Int { publishValue - vendorCostValue }
    private var profitMarginPercent: Double {
        guard publishValue > 0 else { return 0 }
        return (Double(profitAmount) / Double(publishValue)) * 100
    }

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
                        LazyVStack(alignment: .leading, spacing: 16) {
                            if let errorMessage {
                                Text(errorMessage)
                                    .font(.poppins(.caption))
                                    .foregroundStyle(WofinsTheme.danger)
                                    .padding(12)
                                    .frame(maxWidth: .infinity, alignment: .leading)
                                    .background(WofinsTheme.danger.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
                            }

                            basicSection
                            financialSection
                            bankSection
                            if isMaster {
                                priceHistorySection
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
                            .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 14))
                            .disabled(isSaving)
                        }
                        .padding(16)
                        .padding(.bottom, 28)
                    }
                    .scrollDismissesKeyboard(.immediately)
                    .wofinsKeyboardDoneButton()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .navigationTitle(schema?.title ?? (recordId == nil ? "Tambah Vendor" : "Edit Vendor"))
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Tutup") { dismiss() }
                }
            }
            .wofinsSwipeBack()
            .task { await loadForm() }
            .onChange(of: values["harga_publish"]) { _, _ in syncProfitDisplay() }
            .onChange(of: values["harga_vendor"]) { _, _ in syncProfitDisplay() }
            .onChange(of: values["status"]) { _, newValue in
                if newValue != "product" {
                    values["parent_id"] = ""
                }
            }
            .onChange(of: values["is_master"]) { _, newValue in
                if newValue != "1" && newValue != "true" {
                    priceHistories = []
                } else if priceHistories.isEmpty {
                    priceHistories = [VendorPriceHistoryDraft()]
                }
            }
        }
    }

    private var basicSection: some View {
        section("Informasi dasar") {
            textField("Nama vendor *", key: "name", placeholder: "Nama vendor")
            textField("Telepon *", key: "phone", placeholder: "812XXXXXXXX", keyboard: .phonePad)
            helper("Nomor tanpa angka 0 di depan (+62).")
            textField("Alamat", key: "address", placeholder: "Alamat")
            textField("Nama PIC", key: "pic_name", placeholder: "Nama PIC")
            selectField("Status *", key: "status", options: options(for: "status"), placeholder: "Pilih")
            if showsParent {
                selectField("Vendor induk", key: "parent_id", options: options(for: "parent_id"), placeholder: "Opsional", allowClear: true)
                helper("Kosongkan jika ini vendor induk.")
            }
            selectField("Kategori *", key: "category_id", options: options(for: "category_id"), placeholder: "Pilih")
            toggleRow("Master", key: "is_master", helper: "Tandai sebagai data master (bisa punya periode harga).")
            toggleRow("Published", key: "is_published", helper: nil)
            textArea("Deskripsi", key: "description")
        }
    }

    private var financialSection: some View {
        section("Keuangan") {
            moneyField("Harga publish", key: "harga_publish")
            moneyField("Harga vendor", key: "harga_vendor")
            readonlyMoney("Profit", profitAmount)
            readonlyText("Profit margin", String(format: "%.2f %%", profitMarginPercent))
            helper("Profit & margin dihitung otomatis.")
            numberField("Stok", key: "stock", placeholder: "10")
        }
    }

    private var bankSection: some View {
        section("Rekening") {
            textField("Nama bank", key: "bank_name", placeholder: "Mandiri")
            textField("Nomor rekening", key: "bank_account", placeholder: "Nomor rekening", keyboard: .numberPad)
            textField("Nama pemilik rekening", key: "account_holder", placeholder: "Atas nama")
            helper("Upload kontrak kerjasama tetap lewat admin web.")
        }
    }

    private var priceHistorySection: some View {
        section("Periode harga (Master)") {
            helper("Isi periode harga aktif untuk vendor master.")
            ForEach(Array(priceHistories.enumerated()), id: \.element.id) { index, _ in
                priceHistoryCard(index: index)
            }
            Button {
                priceHistories.append(VendorPriceHistoryDraft())
            } label: {
                Label("Tambah periode harga", systemImage: "plus.circle.fill")
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                    .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
            }
        }
    }

    @ViewBuilder
    private func priceHistoryCard(index: Int) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Periode \(index + 1)")
                    .font(.poppins(.caption, weight: .bold))
                Spacer()
                if priceHistories.count > 1 {
                    Button("Hapus") { priceHistories.remove(at: index) }
                        .font(.poppins(.caption2, weight: .semibold))
                        .foregroundStyle(WofinsTheme.danger)
                }
            }
            dateField("Tgl mulai", date: bindingHistory(index, \.from))
            dateField("Tgl akhir", date: bindingHistory(index, \.to))
            HStack(spacing: 10) {
                editableMoney("Publish", text: bindingHistory(index, \.hargaPublishText)) {
                    recalculateHistory(at: index)
                }
                editableMoney("Vendor", text: bindingHistory(index, \.hargaVendorText)) {
                    recalculateHistory(at: index)
                }
            }
            readonlyMoney("Profit", priceHistories[index].profitAmount)
            readonlyText("Margin", String(format: "%.2f %%", priceHistories[index].profitMarginPercent))
            TextField("Deskripsi", text: bindingHistory(index, \.description))
                .font(.poppins(.subheadline))
                .padding(.horizontal, 12)
                .frame(height: 44)
                .moduleSurface()
        }
        .padding(12)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    private func section<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(title)
                .font(.poppins(.caption, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
            content()
        }
    }

    private func helper(_ text: String) -> some View {
        Text(text)
            .font(.poppins(.caption2))
            .foregroundStyle(WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
    }

    private func textField(_ title: String, key: String, placeholder: String, keyboard: UIKeyboardType = .default) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            TextField(placeholder, text: stringBinding(key))
                .font(.poppins(.subheadline))
                .keyboardType(keyboard)
                .padding(.horizontal, 12)
                .frame(height: 46)
                .moduleSurface()
        }
    }

    private func textArea(_ title: String, key: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            TextField(title, text: stringBinding(key), axis: .vertical)
                .font(.poppins(.subheadline))
                .lineLimit(3...6)
                .padding(12)
                .moduleSurface()
        }
    }

    private func numberField(_ title: String, key: String, placeholder: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            TextField(placeholder, text: stringBinding(key))
                .font(.poppins(.subheadline))
                .keyboardType(.numberPad)
                .padding(.horizontal, 12)
                .frame(height: 46)
                .moduleSurface()
        }
    }

    private func moneyField(_ title: String, key: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            TextField("0", text: MoneyFormat.groupedBinding(stringBinding(key)))
                .font(.poppins(.subheadline))
                .keyboardType(.numberPad)
                .padding(.horizontal, 12)
                .frame(height: 46)
                .moduleSurface()
        }
    }

    private func selectField(
        _ title: String,
        key: String,
        options: [ModuleFormOption],
        placeholder: String,
        allowClear: Bool = false
    ) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            Menu {
                if allowClear {
                    Button("Kosongkan") { values[key] = "" }
                }
                ForEach(options) { option in
                    Button(option.label) { values[key] = option.value }
                }
            } label: {
                HStack {
                    Text(options.first(where: { $0.value == values[key] })?.label ?? placeholder)
                        .font(.poppins(.subheadline))
                        .foregroundStyle((values[key] ?? "").isEmpty ? WofinsTheme.muted : WofinsTheme.ink)
                        .lineLimit(1)
                        .minimumScaleFactor(0.85)
                    Spacer()
                    Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
                }
                .padding(.horizontal, 12)
                .frame(minHeight: 46)
                .moduleSurface()
            }
        }
    }

    private func toggleRow(_ title: String, key: String, helper: String?) -> some View {
        HStack(alignment: .center, spacing: 12) {
            VStack(alignment: .leading, spacing: 2) {
                Text(title)
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.ink)
                if let helper, !helper.isEmpty {
                    Text(helper)
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                        .fixedSize(horizontal: false, vertical: true)
                }
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            Toggle("", isOn: toggleBinding(key))
                .labelsHidden()
                .tint(WofinsTheme.primary)
        }
        .padding(12)
        .moduleSurface()
    }

    private func readonlyMoney(_ title: String, _ amount: Int) -> some View {
        HStack {
            Text(title).font(.poppins(.caption)).foregroundStyle(WofinsTheme.ink)
            Spacer()
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
        }
        .padding(.horizontal, 12)
        .frame(minHeight: 44)
        .background(WofinsTheme.muted.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
    }

    private func readonlyText(_ title: String, _ value: String) -> some View {
        HStack {
            Text(title).font(.poppins(.caption)).foregroundStyle(WofinsTheme.ink)
            Spacer()
            Text(value)
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
        }
        .padding(.horizontal, 12)
        .frame(minHeight: 44)
        .background(WofinsTheme.muted.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
    }

    private func dateField(_ title: String, date: Binding<Date>) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption2, weight: .semibold)).foregroundStyle(WofinsTheme.muted)
            DatePicker("", selection: date, displayedComponents: .date)
                .labelsHidden()
                .datePickerStyle(.compact)
                .frame(maxWidth: .infinity, alignment: .leading)
        }
    }

    private func editableMoney(_ title: String, text: Binding<String>, onChange: (() -> Void)? = nil) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title).font(.poppins(.caption2, weight: .semibold)).foregroundStyle(WofinsTheme.muted)
            TextField("0", text: MoneyFormat.groupedBinding(Binding(
                get: { text.wrappedValue },
                set: {
                    text.wrappedValue = $0
                    onChange?()
                }
            )))
            .font(.poppins(.subheadline))
            .keyboardType(.numberPad)
            .padding(.horizontal, 10)
            .frame(height: 42)
            .moduleSurface()
        }
        .frame(maxWidth: .infinity, alignment: .leading)
    }

    private func options(for name: String) -> [ModuleFormOption] {
        schema?.fields?.first(where: { $0.name == name })?.options ?? []
    }

    private func stringBinding(_ key: String) -> Binding<String> {
        Binding(get: { values[key] ?? "" }, set: { values[key] = $0 })
    }

    private func toggleBinding(_ key: String) -> Binding<Bool> {
        Binding(
            get: { values[key] == "1" || values[key] == "true" },
            set: { values[key] = $0 ? "1" : "0" }
        )
    }

    private func bindingHistory<T>(_ index: Int, _ keyPath: WritableKeyPath<VendorPriceHistoryDraft, T>) -> Binding<T> {
        Binding(
            get: { priceHistories[index][keyPath: keyPath] },
            set: { priceHistories[index][keyPath: keyPath] = $0 }
        )
    }

    private func syncProfitDisplay() {
        values["profit_amount"] = String(profitAmount)
        values["profit_margin"] = String(format: "%.2f", profitMarginPercent)
    }

    private func recalculateHistory(at index: Int) {
        guard priceHistories.indices.contains(index) else { return }
        _ = priceHistories[index].profitAmount
    }

    private func loadForm() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let loaded = try await appState.api.vendorForm(id: recordId)
            schema = loaded
            var seed = loaded.defaults ?? [:]
            for field in loaded.fields ?? [] where seed[field.name] == nil {
                if field.fieldType == "toggle" { seed[field.name] = "0" }
            }
            values = seed
            syncProfitDisplay()
            let histories = (loaded.price_histories ?? []).map(VendorPriceHistoryDraft.init)
            if (seed["is_master"] == "1" || seed["is_master"] == "true") {
                priceHistories = histories.isEmpty ? [VendorPriceHistoryDraft()] : histories
            } else {
                priceHistories = histories
            }
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func save() {
        guard !isSaving else { return }
        let name = (values["name"] ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let phone = (values["phone"] ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let category = values["category_id"] ?? ""
        let status = values["status"] ?? ""
        guard !name.isEmpty, !phone.isEmpty, !category.isEmpty, !status.isEmpty else {
            errorMessage = "Nama, telepon, kategori, dan status wajib diisi."
            return
        }

        isSaving = true
        errorMessage = nil
        let payload = CreateVendorPayload(
            name: name,
            phone: phone,
            address: values["address"],
            pic_name: values["pic_name"],
            status: status,
            parent_id: showsParent ? Int(values["parent_id"] ?? "") : nil,
            category_id: Int(category),
            is_master: isMaster,
            is_published: values["is_published"] == "1" || values["is_published"] == "true",
            description: values["description"],
            harga_publish: publishValue,
            harga_vendor: vendorCostValue,
            profit_amount: profitAmount,
            profit_margin: Int(round(profitMarginPercent * 100)),
            stock: Int(values["stock"] ?? "") ?? 10,
            bank_name: values["bank_name"],
            bank_account: values["bank_account"],
            account_holder: values["account_holder"],
            price_histories: isMaster
                ? priceHistories.filter { !$0.fromText.isEmpty && !$0.toText.isEmpty }.map(\.payload)
                : []
        )

        Task {
            defer { isSaving = false }
            do {
                if let recordId {
                    _ = try await appState.api.updateVendor(id: recordId, payload)
                } else {
                    _ = try await appState.api.createVendor(payload)
                }
                onSaved()
                dismiss()
            } catch {
                APILoadFailure.assign(error, to: &errorMessage)
            }
        }
    }
}

private struct VendorPriceHistoryDraft: Identifiable {
    let id = UUID()
    var from: Date = Date()
    var to: Date = Calendar.current.date(byAdding: .month, value: 1, to: Date()) ?? Date()
    var hargaPublishText = "0"
    var hargaVendorText = "0"
    var description = ""

    var fromText: String { Self.formatter.string(from: from) }
    var toText: String { Self.formatter.string(from: to) }
    var publishValue: Int { Int(hargaPublishText.filter(\.isNumber)) ?? 0 }
    var vendorValue: Int { Int(hargaVendorText.filter(\.isNumber)) ?? 0 }
    var profitAmount: Int { publishValue - vendorValue }
    var profitMarginPercent: Double {
        guard publishValue > 0 else { return 0 }
        return (Double(profitAmount) / Double(publishValue)) * 100
    }

    private static let formatter: DateFormatter = {
        let f = DateFormatter()
        f.calendar = Calendar(identifier: .gregorian)
        f.locale = Locale(identifier: "en_US_POSIX")
        f.dateFormat = "yyyy-MM-dd"
        return f
    }()

    init() {}

    init(_ row: VendorPriceHistoryRow) {
        if let from = row.effective_from, let date = Self.formatter.date(from: from) {
            self.from = date
        }
        if let to = row.effective_to, let date = Self.formatter.date(from: to) {
            self.to = date
        }
        hargaPublishText = String(row.harga_publish ?? 0)
        hargaVendorText = String(row.harga_vendor ?? 0)
        description = row.description ?? ""
    }

    var payload: CreateVendorPriceHistoryPayload {
        CreateVendorPriceHistoryPayload(
            effective_from: fromText,
            effective_to: toText,
            harga_publish: publishValue,
            harga_vendor: vendorValue,
            description: description
        )
    }
}
