import SwiftUI

struct ProductFormView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    var recordId: Int? = nil
    var onSaved: () -> Void

    @State private var schema: ProductFormSchema?
    @State private var values: [String: String] = [:]
    @State private var items: [ProductItemDraft] = [ProductItemDraft()]
    @State private var discounts: [ProductDiscountDraft] = []
    @State private var additions: [ProductAdditionDraft] = []
    @State private var isLoading = false
    @State private var isSaving = false
    @State private var errorMessage: String?

    private var vendors: [ProductVendorOption] { schema?.vendor_options ?? [] }

    private var totalPublish: Int {
        items.reduce(0) { $0 + $1.pricePublic }
    }

    private var totalVendorCost: Int {
        items.reduce(0) { $0 + $1.totalVendor }
    }

    private var totalDiscount: Int {
        discounts.reduce(0) { $0 + $1.amountValue }
    }

    private var totalAddPublish: Int {
        additions.reduce(0) { $0 + $1.publishValue }
    }

    private var totalAddVendor: Int {
        additions.reduce(0) { $0 + $1.vendorValue }
    }

    private var finalPrice: Int {
        max(0, totalPublish - totalDiscount + totalAddPublish)
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
                            capacitySection
                            facilitiesSection
                            discountsSection
                            additionsSection
                            pricingSection
                            detailSection
                            statusSection

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
            .navigationTitle(schema?.title ?? (recordId == nil ? "Tambah Paket" : "Edit Paket"))
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Tutup") { dismiss() }
                }
            }
            .wofinsSwipeBack()
            .task { await loadForm() }
        }
    }

    private var basicSection: some View {
        section("Informasi dasar") {
            textField("Nama paket *", key: "name", placeholder: "nama_lokasi_pax")
            selectField("Kategori *", key: "category_id", options: options(for: "category_id"), placeholder: "Pilih")
            selectField("Paket induk", key: "parent_id", options: options(for: "parent_id"), placeholder: "Opsional", allowClear: true)
            helper("Opsional jika paket ini adalah varian (child).")
        }
    }

    private var capacitySection: some View {
        section("Kapasitas") {
            numberField("Resepsi (pax) *", key: "pax", placeholder: "1000", money: false)
            numberField("Akad (pax)", key: "pax_akad", placeholder: "100", money: false)
            numberField("Stok *", key: "stock", placeholder: "10", money: false)
            helper("Biasanya diisi 10.")
        }
    }

    private var facilitiesSection: some View {
        section("Fasilitas dasar (Vendor)") {
            helper("Pilih vendor fasilitas. Harga & total dihitung otomatis.")
            ForEach(Array(items.enumerated()), id: \.element.id) { index, _ in
                vendorItemCard(index: index)
            }
            Button {
                items.append(ProductItemDraft())
            } label: {
                Label("Tambah Vendor", systemImage: "plus.circle.fill")
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                    .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
            }
        }
    }

    private var discountsSection: some View {
        section("Pengurangan harga") {
            ForEach(Array(discounts.enumerated()), id: \.element.id) { index, _ in
                discountCard(index: index)
            }
            Button {
                discounts.append(ProductDiscountDraft())
            } label: {
                Label("Tambah Pengurangan", systemImage: "plus.circle.fill")
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                    .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
            }
        }
    }

    private var additionsSection: some View {
        section("Penambahan harga") {
            ForEach(Array(additions.enumerated()), id: \.element.id) { index, _ in
                additionCard(index: index)
            }
            Button {
                additions.append(ProductAdditionDraft())
            } label: {
                Label("Tambah Penambahan", systemImage: "plus.circle.fill")
                    .font(.poppins(.subheadline, weight: .semibold))
                    .foregroundStyle(WofinsTheme.primary)
                    .frame(maxWidth: .infinity)
                    .frame(height: 44)
                    .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
            }
        }
    }

    private var pricingSection: some View {
        section("Harga (otomatis)") {
            readonlyMoney("Total harga publish", totalPublish)
            readonlyMoney("Total harga vendor", totalVendorCost)
            readonlyMoney("Total pengurangan", totalDiscount)
            readonlyMoney("Penambahan publish", totalAddPublish)
            readonlyMoney("Penambahan vendor", totalAddVendor)
            readonlyMoney("Harga jual / Total paket", finalPrice, emphasize: true)
            helper("publish − pengurangan + penambahan publish")
        }
    }

    private var detailSection: some View {
        section("Detail") {
            textArea("Deskripsi", key: "description")
            textArea("Keterangan free / pengurangan", key: "free_pengurangan")
            helper("Catatan free atau keterangan pengurangan.")
        }
    }

    private var statusSection: some View {
        section("Status") {
            HStack {
                VStack(alignment: .leading, spacing: 2) {
                    Text("Aktif")
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                    Text("Nonaktifkan untuk menyembunyikan paket.")
                        .font(.poppins(.caption2))
                        .foregroundStyle(WofinsTheme.muted)
                }
                Spacer()
                Toggle("", isOn: toggleBinding("is_active"))
                    .labelsHidden()
                    .tint(WofinsTheme.primary)
            }
            .padding(12)
            .moduleSurface()
        }
    }

    @ViewBuilder
    private func vendorItemCard(index: Int) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Vendor \(index + 1)")
                    .font(.poppins(.caption, weight: .bold))
                    .foregroundStyle(WofinsTheme.ink)
                Spacer()
                if items.count > 1 {
                    Button("Hapus") { items.remove(at: index) }
                        .font(.poppins(.caption2, weight: .semibold))
                        .foregroundStyle(WofinsTheme.danger)
                }
            }

            Menu {
                ForEach(vendors) { option in
                    Button(option.label) { applyVendor(option, at: index) }
                }
            } label: {
                HStack {
                    Text(vendorLabel(for: items[index].vendorId) ?? "Pilih vendor")
                        .font(.poppins(.subheadline))
                        .foregroundStyle(items[index].vendorId.isEmpty ? WofinsTheme.muted : WofinsTheme.ink)
                        .lineLimit(1)
                        .minimumScaleFactor(0.85)
                    Spacer()
                    Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
                }
                .padding(.horizontal, 12)
                .frame(minHeight: 44)
                .moduleSurface()
            }

            HStack(spacing: 10) {
                editableMoney("Harga publish", text: bindingItem(index, \.hargaPublishText)) {
                    recalculateItem(at: index)
                }
                editableNumber("Qty", text: bindingItem(index, \.quantityText)) {
                    recalculateItem(at: index)
                }
            }

            readonlyMoney("Harga publish × qty", items[index].pricePublic)
            readonlyMoney("Harga vendor (unit)", items[index].hargaVendorValue)
            readonlyMoney("Total vendor", items[index].totalVendor)

            TextField("Fasilitas / keterangan", text: bindingItem(index, \.description), axis: .vertical)
                .font(.poppins(.subheadline))
                .lineLimit(2...4)
                .padding(12)
                .moduleSurface()
        }
        .padding(12)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    @ViewBuilder
    private func discountCard(index: Int) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Pengurangan \(index + 1)")
                    .font(.poppins(.caption, weight: .bold))
                Spacer()
                Button("Hapus") { discounts.remove(at: index) }
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.danger)
            }
            TextField("Nama / deskripsi", text: bindingDiscount(index, \.description))
                .font(.poppins(.subheadline))
                .padding(.horizontal, 12)
                .frame(height: 44)
                .moduleSurface()
            editableMoney("Nilai", text: bindingDiscount(index, \.amountText))
            TextField("Keterangan", text: bindingDiscount(index, \.notes), axis: .vertical)
                .font(.poppins(.subheadline))
                .lineLimit(2...4)
                .padding(12)
                .moduleSurface()
        }
        .padding(12)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    @ViewBuilder
    private func additionCard(index: Int) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Penambahan \(index + 1)")
                    .font(.poppins(.caption, weight: .bold))
                Spacer()
                Button("Hapus") { additions.remove(at: index) }
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.danger)
            }
            Menu {
                ForEach(vendors) { option in
                    Button(option.label) { applyAdditionVendor(option, at: index) }
                }
            } label: {
                HStack {
                    Text(vendorLabel(for: additions[index].vendorId) ?? "Pilih vendor")
                        .font(.poppins(.subheadline))
                        .foregroundStyle(additions[index].vendorId.isEmpty ? WofinsTheme.muted : WofinsTheme.ink)
                        .lineLimit(1)
                    Spacer()
                    Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
                }
                .padding(.horizontal, 12)
                .frame(minHeight: 44)
                .moduleSurface()
            }
            HStack(spacing: 10) {
                editableMoney("Publish", text: bindingAddition(index, \.hargaPublishText))
                editableMoney("Vendor", text: bindingAddition(index, \.hargaVendorText))
            }
            TextField("Keterangan", text: bindingAddition(index, \.description), axis: .vertical)
                .font(.poppins(.subheadline))
                .lineLimit(2...4)
                .padding(12)
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

    private func textField(_ title: String, key: String, placeholder: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            TextField(placeholder, text: stringBinding(key))
                .font(.poppins(.subheadline))
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

    private func numberField(_ title: String, key: String, placeholder: String, money: Bool) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title).font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            if money {
                TextField(placeholder, text: MoneyFormat.groupedBinding(stringBinding(key)))
                    .font(.poppins(.subheadline))
                    .keyboardType(.numberPad)
                    .padding(.horizontal, 12)
                    .frame(height: 46)
                    .moduleSurface()
            } else {
                TextField(placeholder, text: stringBinding(key))
                    .font(.poppins(.subheadline))
                    .keyboardType(.numberPad)
                    .padding(.horizontal, 12)
                    .frame(height: 46)
                    .moduleSurface()
            }
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

    private func readonlyMoney(_ title: String, _ amount: Int, emphasize: Bool = false) -> some View {
        HStack {
            Text(title)
                .font(.poppins(emphasize ? .subheadline : .caption, weight: emphasize ? .semibold : .regular))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(2)
                .minimumScaleFactor(0.85)
            Spacer()
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(emphasize ? WofinsTheme.primary : WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
        .padding(.horizontal, 12)
        .frame(minHeight: 44)
        .background(WofinsTheme.muted.opacity(0.08), in: RoundedRectangle(cornerRadius: 12))
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

    private func editableNumber(_ title: String, text: Binding<String>, onChange: (() -> Void)? = nil) -> some View {
        VStack(alignment: .leading, spacing: 4) {
            Text(title).font(.poppins(.caption2, weight: .semibold)).foregroundStyle(WofinsTheme.muted)
            TextField("1", text: Binding(
                get: { text.wrappedValue },
                set: {
                    text.wrappedValue = $0
                    onChange?()
                }
            ))
            .font(.poppins(.subheadline))
            .keyboardType(.numberPad)
            .padding(.horizontal, 10)
            .frame(height: 42)
            .moduleSurface()
        }
        .frame(width: 88, alignment: .leading)
    }

    private func options(for name: String) -> [ModuleFormOption] {
        schema?.fields?.first(where: { $0.name == name })?.options ?? []
    }

    private func vendorLabel(for id: String) -> String? {
        guard !id.isEmpty else { return nil }
        return vendors.first(where: { $0.value == id })?.label
    }

    private func applyVendor(_ option: ProductVendorOption, at index: Int) {
        guard items.indices.contains(index) else { return }
        items[index].vendorId = option.value
        items[index].hargaPublishText = String(option.harga_publish ?? 0)
        items[index].hargaVendorText = String(option.harga_vendor ?? 0)
        if items[index].description.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            items[index].description = option.description ?? ""
        }
        recalculateItem(at: index)
    }

    private func applyAdditionVendor(_ option: ProductVendorOption, at index: Int) {
        guard additions.indices.contains(index) else { return }
        additions[index].vendorId = option.value
        additions[index].hargaPublishText = String(option.harga_publish ?? 0)
        additions[index].hargaVendorText = String(option.harga_vendor ?? 0)
        if additions[index].description.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty {
            additions[index].description = option.description ?? ""
        }
    }

    private func recalculateItem(at index: Int) {
        guard items.indices.contains(index) else { return }
        let qty = max(1, Int(items[index].quantityText.filter(\.isNumber)) ?? 1)
        items[index].quantityText = String(qty)
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

    private func bindingItem<T>(_ index: Int, _ keyPath: WritableKeyPath<ProductItemDraft, T>) -> Binding<T> {
        Binding(
            get: { items[index][keyPath: keyPath] },
            set: { items[index][keyPath: keyPath] = $0 }
        )
    }

    private func bindingDiscount<T>(_ index: Int, _ keyPath: WritableKeyPath<ProductDiscountDraft, T>) -> Binding<T> {
        Binding(
            get: { discounts[index][keyPath: keyPath] },
            set: { discounts[index][keyPath: keyPath] = $0 }
        )
    }

    private func bindingAddition<T>(_ index: Int, _ keyPath: WritableKeyPath<ProductAdditionDraft, T>) -> Binding<T> {
        Binding(
            get: { additions[index][keyPath: keyPath] },
            set: { additions[index][keyPath: keyPath] = $0 }
        )
    }

    private func loadForm() async {
        isLoading = true
        defer { isLoading = false }
        do {
            let loaded = try await appState.api.productForm(id: recordId)
            schema = loaded
            var seed = loaded.defaults ?? [:]
            for field in loaded.fields ?? [] where seed[field.name] == nil {
                if field.fieldType == "toggle" { seed[field.name] = "0" }
            }
            values = seed
            if let existingItems = loaded.items, !existingItems.isEmpty {
                items = existingItems.map(ProductItemDraft.init)
            } else {
                items = [ProductItemDraft()]
            }
            discounts = (loaded.discounts ?? []).map(ProductDiscountDraft.init)
            additions = (loaded.additions ?? []).map(ProductAdditionDraft.init)
            errorMessage = nil
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func save() {
        guard !isSaving else { return }
        let name = (values["name"] ?? "").trimmingCharacters(in: .whitespacesAndNewlines)
        let category = values["category_id"] ?? ""
        guard !name.isEmpty, !category.isEmpty else {
            errorMessage = "Nama paket dan kategori wajib diisi."
            return
        }
        guard items.contains(where: { !$0.vendorId.isEmpty }) else {
            errorMessage = "Minimal satu vendor fasilitas harus dipilih."
            return
        }

        isSaving = true
        errorMessage = nil
        let payload = CreateProductPayload(
            name: name,
            category_id: Int(category),
            parent_id: Int(values["parent_id"] ?? ""),
            pax: Int(values["pax"] ?? "") ?? 1000,
            pax_akad: Int(values["pax_akad"] ?? "") ?? 100,
            stock: Int(values["stock"] ?? "") ?? 10,
            description: values["description"],
            free_pengurangan: values["free_pengurangan"],
            is_active: values["is_active"] == "1" || values["is_active"] == "true",
            product_price: totalPublish,
            pengurangan: totalDiscount,
            penambahan_publish: totalAddPublish,
            penambahan_vendor: totalAddVendor,
            price: finalPrice,
            items: items.filter { !$0.vendorId.isEmpty }.map(\.payload),
            discounts: discounts.filter { !$0.description.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty || $0.amountValue > 0 }.map(\.payload),
            additions: additions.filter { !$0.vendorId.isEmpty }.map(\.payload)
        )

        Task {
            defer { isSaving = false }
            do {
                if let recordId {
                    _ = try await appState.api.updateProduct(id: recordId, payload)
                } else {
                    _ = try await appState.api.createProduct(payload)
                }
                onSaved()
                dismiss()
            } catch {
                APILoadFailure.assign(error, to: &errorMessage)
            }
        }
    }
}

private struct ProductItemDraft: Identifiable {
    let id = UUID()
    var vendorId = ""
    var hargaPublishText = "0"
    var hargaVendorText = "0"
    var quantityText = "1"
    var description = ""

    var hargaPublishValue: Int { Int(hargaPublishText.filter(\.isNumber)) ?? 0 }
    var hargaVendorValue: Int { Int(hargaVendorText.filter(\.isNumber)) ?? 0 }
    var quantityValue: Int { max(1, Int(quantityText.filter(\.isNumber)) ?? 1) }
    var pricePublic: Int { hargaPublishValue * quantityValue }
    var totalVendor: Int { hargaVendorValue * quantityValue }

    init() {}

    init(_ item: ProductFormItemRow) {
        vendorId = item.vendor_id ?? ""
        hargaPublishText = String(item.harga_publish ?? 0)
        hargaVendorText = String(item.harga_vendor ?? 0)
        quantityText = String(max(1, item.quantity ?? 1))
        description = item.description ?? ""
    }

    var payload: CreateProductItemPayload {
        CreateProductItemPayload(
            vendor_id: Int(vendorId) ?? 0,
            harga_publish: hargaPublishValue,
            harga_vendor: hargaVendorValue,
            quantity: quantityValue,
            price_public: pricePublic,
            total_price: totalVendor,
            description: description
        )
    }
}

private struct ProductDiscountDraft: Identifiable {
    let id = UUID()
    var description = ""
    var amountText = "0"
    var notes = ""

    var amountValue: Int { Int(amountText.filter(\.isNumber)) ?? 0 }

    init() {}

    init(_ row: ProductFormDiscountRow) {
        description = row.description ?? ""
        amountText = String(row.amount ?? 0)
        notes = row.notes ?? ""
    }

    var payload: CreateProductDiscountPayload {
        CreateProductDiscountPayload(description: description, amount: amountValue, notes: notes)
    }
}

private struct ProductAdditionDraft: Identifiable {
    let id = UUID()
    var vendorId = ""
    var hargaPublishText = "0"
    var hargaVendorText = "0"
    var description = ""

    var publishValue: Int { Int(hargaPublishText.filter(\.isNumber)) ?? 0 }
    var vendorValue: Int { Int(hargaVendorText.filter(\.isNumber)) ?? 0 }

    init() {}

    init(_ row: ProductFormAdditionRow) {
        vendorId = row.vendor_id ?? ""
        hargaPublishText = String(row.harga_publish ?? 0)
        hargaVendorText = String(row.harga_vendor ?? 0)
        description = row.description ?? ""
    }

    var payload: CreateProductAdditionPayload {
        CreateProductAdditionPayload(
            vendor_id: Int(vendorId) ?? 0,
            harga_publish: publishValue,
            harga_vendor: vendorValue,
            description: description
        )
    }
}
