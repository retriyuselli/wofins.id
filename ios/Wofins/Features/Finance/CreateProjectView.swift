import PhotosUI
import SwiftUI
import UniformTypeIdentifiers
import UIKit

struct CreateProjectView: View {
    @EnvironmentObject private var appState: AppState
    @Environment(\.dismiss) private var dismiss

    var project: FinanceProjectDetail? = nil
    var onSaved: () -> Void = {}

    @State private var options: ProjectFormOptions?
    @State private var isLoading = true
    @State private var isSaving = false
    @State private var errorMessage: String?
    @State private var step: Step = .info

    @State private var number = ""
    @State private var prospectId = 0
    @State private var userId = 0
    @State private var employeeId = 0
    @State private var noKontrak = ""
    @State private var paxText = "1000"
    @State private var status = "pending"
    @State private var note = ""
    @State private var items: [DraftItem] = [DraftItem()]
    @State private var payments: [DraftPayment] = []
    @State private var contractPDF: PickedFile?
    @State private var agreementPDF: PickedFile?
    @State private var pdfTarget: PDFTarget = .contract
    @State private var isPickingPDF = false
    @State private var paxEdited = false
    @FocusState private var focused: Field?

    private enum PDFTarget {
        case contract, agreement
    }

    private enum Field: Hashable {
        case kontrak, pax, note, quantity(UUID), keterangan(UUID), nominal(UUID)
    }

    private enum Step: Int, CaseIterable, Identifiable {
        case info, products, payments, summary

        var id: Int { rawValue }

        var title: String {
            switch self {
            case .info: return "Info"
            case .products: return "Paket"
            case .payments: return "Bayar"
            case .summary: return "Ringkas"
            }
        }

        var fullTitle: String {
            switch self {
            case .info: return "Informasi Proyek"
            case .products: return "Paket Dipesan"
            case .payments: return "Data Pembayaran"
            case .summary: return "Informasi Keuangan"
            }
        }
    }

    private struct DraftItem: Identifiable {
        let id = UUID()
        var productId = 0
        var quantity = 1
    }

    private struct DraftPayment: Identifiable {
        let id = UUID()
        var recordId: Int? = nil
        var keterangan = ""
        var paymentMethodId = 0
        var nominalText = ""
        var kategori = "uang_masuk"
        var date = Date()
        var proofItem: PhotosPickerItem?
        var proofName: String?
        var proofData: Data?
        var existingProofName: String?
    }

    private struct PickedFile {
        let name: String
        let data: Data
    }

    private enum PDFLoadError: LocalizedError {
        case unreadable
        case tooLarge

        var errorDescription: String? {
            switch self {
            case .unreadable: return "File PDF tidak dapat dibaca."
            case .tooLarge: return "File PDF maksimal 10MB."
            }
        }
    }

    private var selectedProspect: ProjectFormProspectOption? {
        options?.prospects.first { $0.id == prospectId }
    }

    private var paxValue: Int { max(1, Int(paxText.filter(\.isNumber)) ?? 1) }

    private var filledItems: [(product: ProjectFormProductOption, quantity: Int)] {
        items.compactMap { item in
            guard let product = options?.products.first(where: { $0.id == item.productId }) else { return nil }
            return (product, max(1, item.quantity))
        }
    }

    private var totalPrice: Int {
        filledItems.reduce(0) { $0 + $1.product.unitPrice * $1.quantity }
    }

    private var totalPengurangan: Int {
        filledItems.reduce(0) { $0 + ($1.product.pengurangan ?? 0) * $1.quantity }
    }

    private var totalPenambahan: Int {
        filledItems.reduce(0) { $0 + ($1.product.penambahan_publish ?? 0) * $1.quantity }
    }

    private var grandTotal: Int { totalPrice + totalPenambahan - totalPengurangan }

    private var paidAmount: Int {
        payments.reduce(0) { sum, payment in
            guard payment.kategori != "uang_keluar" else { return sum }
            return sum + (Int(payment.nominalText.filter(\.isNumber)) ?? 0)
        }
    }

    private var remaining: Int { grandTotal - paidAmount }

    private var closingDate: Date? {
        payments.map(\.date).min()
    }

    private var isEditing: Bool { project != nil }

    private var hasExistingContract: Bool { project?.has_doc_kontrak == true || project?.doc_kontrak_url != nil }
    private var hasExistingAgreement: Bool { project?.has_agreement_product == true }

    private var canCreate: Bool { isEditing || (options?.can_create ?? true) }

    private var infoReady: Bool {
        prospectId > 0
            && userId > 0
            && employeeId > 0
            && !trimmed(noKontrak).isEmpty
            && paxValue >= 1
            && (contractPDF != nil || isEditing)
            && (agreementPDF != nil || isEditing)
            && !status.isEmpty
    }

    private var productsReady: Bool { !filledItems.isEmpty }

    private var paymentsReady: Bool {
        payments.allSatisfy { payment in
            !trimmed(payment.keterangan).isEmpty
                && payment.paymentMethodId > 0
                && (Int(payment.nominalText.filter(\.isNumber)) ?? 0) > 0
        }
    }

    var body: some View {
        VStack(spacing: 0) {
            header
            stepBar
            if isLoading {
                Spacer()
                ProgressView("Memuat form…")
                    .font(.poppins(.subheadline))
                    .tint(WofinsTheme.primary)
                Spacer()
            } else {
                ScrollView(showsIndicators: false) {
                    LazyVStack(spacing: 16) {
                        if let errorMessage {
                            Text(errorMessage)
                                .font(.poppins(.caption))
                                .foregroundStyle(WofinsTheme.danger)
                                .fixedSize(horizontal: false, vertical: true)
                                .frame(maxWidth: .infinity, alignment: .leading)
                                .padding(14)
                                .background(WofinsTheme.danger.opacity(0.08), in: RoundedRectangle(cornerRadius: 16, style: .continuous))
                        }
                        switch step {
                        case .info: infoSection
                        case .products: productsSection
                        case .payments: paymentsSection
                        case .summary: summarySection
                        }
                    }
                    .padding(.horizontal, 16)
                    .padding(.top, 16)
                    .padding(.bottom, 8)
                }
                .wofinsFormScrollBehavior()
                footer
            }
        }
        .background(WofinsTheme.background.ignoresSafeArea())
        .wofinsKeyboardDoneButton()
        .wofinsHidesNavigationBar()
        .task { await loadOptions() }
        .fileImporter(isPresented: $isPickingPDF, allowedContentTypes: [.pdf]) { result in
            switch pdfTarget {
            case .contract: handlePDF(result, assign: $contractPDF)
            case .agreement: handlePDF(result, assign: $agreementPDF)
            }
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            Button { dismiss() } label: {
                Image(systemName: "xmark")
                    .font(.system(size: 16, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Tutup")
            .fixedSize()

            VStack(alignment: .leading, spacing: 2) {
                Text(isEditing ? "Edit Proyek" : "Tambah Proyek")
                    .font(.poppins(.headline, weight: .bold))
                    .foregroundStyle(.white)
                    .lineLimit(1)
                Text(step.fullTitle)
                    .font(.poppins(.caption))
                    .foregroundStyle(.white.opacity(0.72))
                    .lineLimit(1)
            }
            .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
        }
        .padding(.horizontal, 16)
        .padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
        .contentShape(Rectangle())
        .onTapGesture { dismissKeyboard() }
    }

    private var stepBar: some View {
        ScrollView(.horizontal, showsIndicators: false) {
            HStack(spacing: 8) {
                ForEach(Step.allCases) { item in
                    Button { step = item } label: {
                        Text(item.title)
                            .font(.poppins(.caption, weight: .semibold))
                            .foregroundStyle(step == item ? .white : WofinsTheme.primary)
                            .padding(.horizontal, 14)
                            .frame(height: 34)
                            .background(step == item ? WofinsTheme.primary : WofinsTheme.card, in: Capsule())
                    }
                    .buttonStyle(.plain)
                    .fixedSize()
                }
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 10)
        }
        .background(WofinsTheme.background)
    }

    private var infoSection: some View {
        VStack(spacing: 16) {
            formSection("Informasi Proyek") {
                readOnlyField("Nomor Proyek", number.isEmpty ? "Otomatis" : number)
                helper("Otomatis dari inisial WO perusahaan (\(options?.number_prefix ?? "-")).")

                if isEditing {
                    readOnlyField("Prospek", selectedProspect?.title ?? project?.prospect?.name_event ?? "Prospek terkunci")
                    helper("Prospek tidak dapat diganti setelah proyek dibuat.")
                } else {
                    menuPicker(
                        "Prospek",
                        selection: $prospectId,
                        placeholder: "Pilih prospek Hangat",
                        options: (options?.prospects ?? []).map { ($0.id, $0.title) }
                    )
                    helper("Hanya prospek yang belum punya proyek. Prospek mengisi nama acara dan slug.")

                    if options?.prospects.isEmpty == true {
                        helper("Belum ada prospek Hangat. Tutup form ini, buat prospek di tab Prospek, lalu kembali.")
                    }
                }

                readOnlyField("Nama Acara", selectedProspect?.title ?? "Mengikuti prospek")
                menuPicker(
                    "Account Manager",
                    selection: $userId,
                    placeholder: "Pilih Account Manager",
                    options: (options?.account_managers ?? []).map { ($0.id, $0.title) }
                )
                helper(options?.single_seat == true
                       ? "Paket 1 seat: pilih akun Anda sendiri sebagai penanggung jawab proyek."
                       : "Pilih Account Manager dari tim Anda.")

                readOnlyField("Slug", slugValue)
                menuPicker(
                    "Event Manager",
                    selection: $employeeId,
                    placeholder: "Pilih Event Manager",
                    options: (options?.event_managers ?? []).map { ($0.id, $0.title) }
                )
                helper(options?.single_seat == true
                       ? "Paket 1 seat: pilih akun Anda sendiri sebagai Event Manager."
                       : "Pilih Event Manager dari tim Anda.")

                field("No. Kontrak", text: $noKontrak, focus: .kontrak)
                helper("Inisial kontrak company: \(options?.contract_prefix ?? "KKP").")
                field("Pax", text: $paxText, focus: .pax, keyboard: .numberPad)
                    .onChange(of: paxText) { _, _ in paxEdited = true }

                fileButton(
                    "Upload Kontrak",
                    file: contractPDF,
                    existingName: isEditing && hasExistingContract ? (project?.doc_kontrak_name ?? "Dokumen kontrak.pdf") : nil,
                    required: !isEditing
                ) {
                    pdfTarget = .contract
                    isPickingPDF = true
                }
                helper("PDF, kontrak sudah ditandatangani semua pihak.")
                fileButton(
                    "File Persetujuan Produk",
                    file: agreementPDF,
                    existingName: isEditing && hasExistingAgreement ? "Persetujuan produk.pdf" : nil,
                    required: !isEditing
                ) {
                    pdfTarget = .agreement
                    isPickingPDF = true
                }
                helper("PDF, file persetujuan produk sudah ditandatangani.")

                VStack(alignment: .leading, spacing: 8) {
                    Text("Status Pesanan")
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.muted)
                    LazyVGrid(columns: [GridItem(.flexible(), spacing: 8), GridItem(.flexible(), spacing: 8)], spacing: 8) {
                        ForEach(options?.statuses ?? [
                            .init(value: "pending", label: "Pending"),
                            .init(value: "processing", label: "Processing"),
                            .init(value: "done", label: "Done"),
                            .init(value: "cancelled", label: "Cancelled"),
                        ]) { item in
                            Button { status = item.value } label: {
                                Text(item.label)
                                    .font(.poppins(.caption, weight: .semibold))
                                    .foregroundStyle(status == item.value ? .white : WofinsTheme.primary)
                                    .frame(maxWidth: .infinity)
                                    .frame(height: 40)
                                    .background(status == item.value ? WofinsTheme.primary : WofinsTheme.background, in: Capsule())
                            }
                            .buttonStyle(.plain)
                        }
                    }
                    helper("Status Done: Finance hanya bisa view, Super Admin bisa edit.")
                }

                field("Keterangan Tambahan", text: $note, focus: .note, axis: true)
            }
        }
    }

    private var productsSection: some View {
        VStack(spacing: 16) {
            formSection("Product dipesan") {
                if options?.products.isEmpty == true {
                    helper("Belum ada paket dengan stok lebih dari 1.")
                }

                ForEach(Array(items.enumerated()), id: \.element.id) { index, item in
                    itemCard(index: index, item: item)
                }

                Button {
                    items.append(DraftItem())
                } label: {
                    Label("Tambah Paket", systemImage: "plus")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 44)
                }
                .buttonStyle(.plain)
                .disabled(availableProducts(except: nil).isEmpty)

                Divider()
                moneyLine("Total Paket Awal", totalPrice)
                moneyLine("Promo", 0)
                moneyLine("Penambahan Harga", totalPenambahan)
                moneyLine("Total Pengurangan dari Produk", totalPengurangan)
                helper("Nilai keuangan dihitung otomatis dari paket yang dipilih.")
            }
        }
    }

    private var paymentsSection: some View {
        VStack(spacing: 16) {
            formSection("Jika Ada Pembayaran") {
                helper("Opsional. Isi jika klien sudah transfer saat closing.")
                ForEach(Array(payments.enumerated()), id: \.element.id) { index, payment in
                    paymentCard(index: index, payment: payment)
                }

                Button {
                    var row = DraftPayment()
                    row.keterangan = "\(payments.count + 1)"
                    if let first = options?.payment_methods.first, options?.payment_methods.count == 1 {
                        row.paymentMethodId = first.id
                    }
                    payments.append(row)
                } label: {
                    Label("Tambah Pembayaran", systemImage: "plus")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 44)
                }
                .buttonStyle(.plain)
                .disabled(options?.payment_methods.isEmpty == true)

                if options?.payment_methods.isEmpty == true {
                    helper("Belum ada rekening/metode pembayaran.")
                }
            }
        }
    }

    private var summarySection: some View {
        VStack(spacing: 16) {
            formSection("Informasi Keuangan") {
                moneyLine("Uang dibayar", paidAmount)
                helper("Pembayaran klien ke rekening perusahaan")
                moneyLine("Grand Total", grandTotal)
                helper("Grand Total = Total Paket + Penambahan - Promo - Pengurangan")
                moneyLine("Pengeluaran", 0)
                helper("Total pembayaran ke vendor. Dicatat setelah proyek tersimpan.")
                moneyLine("Sisa Pembayaran", remaining)
                helper("Sisa yang masih harus dibayar klien")
                moneyLine("Laba Kotor", grandTotal)
                helper("Grand total - Pembayaran ke vendor")
                moneyLine("Uang Diterima", paidAmount)
                helper("Uang yang sudah diterima dari klien")

                readOnlyField(
                    "Closing Date",
                    closingDate.map { Self.dayFormatter.string(from: $0) } ?? "Otomatis dari pembayaran pertama"
                )

                HStack {
                    Text("Lunas / Belum")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.ink)
                        .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    Text(remaining <= 0 && grandTotal > 0 ? "Lunas" : "Belum")
                        .font(.poppins(.caption, weight: .bold))
                        .foregroundStyle(.white)
                        .padding(.horizontal, 10)
                        .frame(height: 28)
                        .background(remaining <= 0 && grandTotal > 0 ? WofinsTheme.primary : WofinsTheme.muted, in: Capsule())
                }
                helper("Otomatis lunas jika sisa pembayaran ≤ 0")
            }

            formSection("Pengeluaran") {
                helper("Catat pengeluaran ke vendor setelah proyek tersimpan, lewat detail proyek. Setiap vendor hanya boleh dipilih sekali per order.")
                readOnlyField("Ringkasan", "Total pengeluaran: 0 item | Total nominal: Rp 0")
            }

            formSection("Riwayat Modifikasi") {
                helper("Riwayat dibuat, diubah, dan editor muncul setelah proyek disimpan.")
            }

            if let quota = options?.quota_message, !quota.isEmpty {
                helper(quota)
            }
        }
    }

    private var footer: some View {
        VStack(spacing: 8) {
            if step != .info {
                Button {
                    if let previous = Step(rawValue: step.rawValue - 1) { step = previous }
                } label: {
                    Text("Kembali")
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(maxWidth: .infinity)
                        .frame(height: 48)
                        .background(WofinsTheme.card, in: Capsule())
                        .overlay { Capsule().stroke(WofinsTheme.border, lineWidth: 1) }
                }
                .buttonStyle(.plain)
            }

            Button {
                goNextOrSave()
            } label: {
                HStack(spacing: 8) {
                    if isSaving { ProgressView().tint(.white) }
                    Text(step == .summary ? (isEditing ? "Simpan Perubahan" : "Simpan Proyek") : "Lanjut")
                        .font(.poppins(.subheadline, weight: .bold))
                }
                .foregroundStyle(.white)
                .frame(maxWidth: .infinity)
                .frame(height: 48)
                .background(WofinsTheme.primary, in: Capsule())
            }
            .buttonStyle(.plain)
            .disabled(isSaving)
        }
        .padding(.horizontal, 16)
        .padding(.top, 8)
        .padding(.bottom, 8)
        .background(WofinsTheme.background.ignoresSafeArea(edges: .bottom))
    }

    private func itemCard(index: Int, item: DraftItem) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Paket \(index + 1)")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer()
                if items.count > 1 {
                    Button("Hapus") { items.removeAll { $0.id == item.id } }
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.danger)
                }
            }

            menuPicker(
                "Product",
                selection: bindingItem(item.id, \.productId),
                placeholder: "Pilih paket",
                options: availableProducts(except: item.productId).map { ($0.id, $0.title) }
            )

            if let product = options?.products.first(where: { $0.id == item.productId }) {
                helper("Harga: \(MoneyFormat.idr(product.unitPrice)) · Stok: \(product.stock ?? 0) · Pax: \(product.pax ?? 0)")
                stepper("Quantity", value: bindingItem(item.id, \.quantity), min: 1, max: max(1, product.stock ?? 1))
                readOnlyField("Unit Price", MoneyFormat.idr(product.unitPrice))
            }
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
        .onChange(of: item.productId) { _, productId in
            guard !paxEdited, let product = options?.products.first(where: { $0.id == productId }), (product.pax ?? 0) > 0 else { return }
            if filledItems.count <= 1 {
                paxText = String(product.pax ?? paxValue)
            }
        }
    }

    private func paymentCard(index: Int, payment: DraftPayment) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text("Pembayaran \(index + 1)")
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer()
                Button("Hapus") { payments.removeAll { $0.id == payment.id } }
                    .font(.poppins(.caption, weight: .semibold))
                    .foregroundStyle(WofinsTheme.danger)
            }

            field("Keterangan", text: bindingPayment(payment.id, \.keterangan), focus: .keterangan(payment.id))
            helper("Contoh: 1, DP, Pelunasan")
            menuPicker(
                "Metode Pembayaran",
                selection: bindingPayment(payment.id, \.paymentMethodId),
                placeholder: "Pilih metode",
                options: (options?.payment_methods ?? []).map { ($0.id, $0.title) }
            )
            field("Nominal", text: MoneyFormat.groupedBinding(bindingPayment(payment.id, \.nominalText)), focus: .nominal(payment.id), keyboard: .numberPad, prefix: "Rp")
            menuPicker(
                "Tipe Transaksi",
                selection: bindingPayment(payment.id, \.kategori),
                placeholder: "Pilih tipe",
                options: [("uang_masuk", "Uang Masuk"), ("uang_keluar", "Uang Keluar")]
            )
            DatePicker("Tgl. Bayar", selection: bindingPayment(payment.id, \.date), displayedComponents: .date)
                .environment(\.locale, Locale(identifier: "id_ID"))
                .font(.poppins(.subheadline))
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)

            PhotosPicker(selection: bindingPayment(payment.id, \.proofItem), matching: .images) {
                HStack {
                    Image(systemName: "photo")
                    Text(payment.proofName ?? payment.existingProofName ?? "Payment Proof (opsional)")
                        .lineLimit(1)
                    Spacer()
                }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .padding(12)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
            }
            .task(id: payment.proofItem?.itemIdentifier) {
                await loadProof(id: payment.id, item: payment.proofItem)
            }
            helper("Max 1MB. JPG atau PNG.")
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
    }

    private var slugValue: String {
        let name = selectedProspect?.title ?? ""
        let slug = name.lowercased()
            .replacingOccurrences(of: " ", with: "-")
            .replacingOccurrences(of: "&", with: "")
        return slug.isEmpty ? "mengikuti-nama-acara" : slug
    }

    private func formSection<Content: View>(_ title: String, @ViewBuilder content: () -> Content) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Text(title)
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
            content()
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(16)
        .projectSurface()
    }

    private func field(
        _ title: String,
        text: Binding<String>,
        focus: Field,
        keyboard: UIKeyboardType = .default,
        prefix: String? = nil,
        axis: Bool = false
    ) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            HStack(alignment: .center, spacing: 10) {
                if let prefix {
                    Text(prefix)
                        .font(.poppins(.subheadline, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .frame(width: 42, alignment: .leading)
                        .lineLimit(1)
                }
                Group {
                    if axis {
                        TextField("", text: text, axis: .vertical)
                            .lineLimit(3...)
                            .scrollDisabled(true)
                    } else {
                        TextField("", text: text)
                            .lineLimit(1)
                    }
                }
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .keyboardType(keyboard)
                .textInputAutocapitalization(keyboard == .numberPad ? .never : .words)
                .autocorrectionDisabled(keyboard == .numberPad)
                .focused($focused, equals: focus)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            }
            .padding(.horizontal, 14)
            .padding(.vertical, 12)
            .frame(minHeight: 48, alignment: .center)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .stroke(WofinsTheme.border, lineWidth: 1)
            }
        }
    }

    private func readOnlyField(_ title: String, _ value: String) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Text(value)
                .font(.poppins(.subheadline))
                .foregroundStyle(WofinsTheme.ink)
                .frame(maxWidth: .infinity, alignment: .leading)
                .padding(.horizontal, 14)
                .padding(.vertical, 12)
                .frame(minHeight: 48, alignment: .center)
                .background(WofinsTheme.background.opacity(0.65), in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
        }
    }

    private func helper(_ text: String) -> some View {
        Text(text)
            .font(.poppins(.caption2))
            .foregroundStyle(WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
    }

    private func moneyLine(_ title: String, _ amount: Int) -> some View {
        HStack(alignment: .firstTextBaseline) {
            Text(title)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(WofinsTheme.ink)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
    }

    private func menuPicker<T: Hashable>(
        _ title: String,
        selection: Binding<T>,
        placeholder: String,
        options: [(T, String)]
    ) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Menu {
                ForEach(Array(options.enumerated()), id: \.offset) { _, option in
                    Button(option.1) { selection.wrappedValue = option.0 }
                }
            } label: {
                HStack {
                    Text(options.first { isEqual($0.0, selection.wrappedValue) }?.1 ?? placeholder)
                        .font(.poppins(.subheadline))
                        .foregroundStyle(options.contains { isEqual($0.0, selection.wrappedValue) } ? WofinsTheme.ink : WofinsTheme.muted)
                        .lineLimit(1)
                        .frame(minWidth: 0, maxWidth: .infinity, alignment: .leading)
                    Image(systemName: "chevron.up.chevron.down")
                        .font(.caption.weight(.semibold))
                        .foregroundStyle(WofinsTheme.muted)
                }
                .padding(.horizontal, 14)
                .frame(minHeight: 48)
                .frame(maxWidth: .infinity)
                .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
            }
        }
    }

    private func stepper(_ title: String, value: Binding<Int>, min: Int, max: Int) -> some View {
        HStack {
            Text(title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Spacer()
            Button {
                value.wrappedValue = Swift.max(min, value.wrappedValue - 1)
            } label: {
                Image(systemName: "minus")
                    .frame(width: 36, height: 36)
                    .background(WofinsTheme.card, in: Circle())
            }
            .buttonStyle(.plain)
            Text("\(value.wrappedValue)")
                .font(.poppins(.subheadline, weight: .bold))
                .frame(minWidth: 28)
            Button {
                value.wrappedValue = Swift.min(max, value.wrappedValue + 1)
            } label: {
                Image(systemName: "plus")
                    .frame(width: 36, height: 36)
                    .background(WofinsTheme.card, in: Circle())
            }
            .buttonStyle(.plain)
        }
    }

    private func fileButton(_ title: String, file: PickedFile?, existingName: String? = nil, required: Bool, action: @escaping () -> Void) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(required ? "\(title) *" : title)
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(WofinsTheme.muted)
            Button(action: action) {
                HStack(spacing: 10) {
                    Image(systemName: "doc.fill")
                    Text(file?.name ?? existingName ?? "Pilih file PDF")
                        .lineLimit(1)
                    Spacer()
                }
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.primary)
                .padding(12)
                .frame(maxWidth: .infinity, alignment: .leading)
                .background(WofinsTheme.background, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                .overlay {
                    RoundedRectangle(cornerRadius: 14, style: .continuous)
                        .stroke(WofinsTheme.border, lineWidth: 1)
                }
            }
            .buttonStyle(.plain)
        }
    }

    private func goNextOrSave() {
        errorMessage = nil
        dismissKeyboard()
        switch step {
        case .info:
            guard infoReady else {
                errorMessage = missingInfoMessage()
                return
            }
            step = .products
        case .products:
            guard productsReady else {
                errorMessage = "Minimal satu paket harus dipilih."
                return
            }
            step = .payments
        case .payments:
            guard paymentsReady else {
                errorMessage = "Lengkapi keterangan, metode, nominal, dan tanggal setiap pembayaran, atau hapus baris kosong."
                return
            }
            step = .summary
        case .summary:
            Task { await save() }
        }
    }

    private func missingInfoMessage() -> String {
        if prospectId <= 0 { return "Prospek wajib dipilih." }
        if userId <= 0 { return "Account Manager wajib dipilih." }
        if employeeId <= 0 { return "Event Manager wajib dipilih." }
        if trimmed(noKontrak).isEmpty { return "Nomor kontrak wajib diisi." }
        if contractPDF == nil && !isEditing { return "File kontrak PDF wajib diunggah." }
        if agreementPDF == nil && !isEditing { return "File persetujuan produk PDF wajib diunggah." }
        return "Lengkapi informasi proyek."
    }

    private func save() async {
        errorMessage = nil
        guard canCreate else {
            errorMessage = options?.quota_message ?? "Kuota proyek sudah penuh."
            return
        }
        guard infoReady, productsReady, paymentsReady else {
            errorMessage = missingInfoMessage()
            if !productsReady { errorMessage = "Minimal satu paket harus dipilih." }
            if !paymentsReady { errorMessage = "Lengkapi data pembayaran atau hapus baris yang belum selesai." }
            return
        }
        if !isEditing {
            guard contractPDF != nil, agreementPDF != nil else { return }
        }

        isSaving = true
        defer { isSaving = false }

        let payloadItems: [[String: Int]] = filledItems.map {
            ["product_id": $0.product.id, "quantity": $0.quantity]
        }
        let payloadPayments: [[String: Any]] = payments.map { payment in
            var row: [String: Any] = [
                "keterangan": trimmed(payment.keterangan),
                "payment_method_id": payment.paymentMethodId,
                "nominal": Int(payment.nominalText.filter(\.isNumber)) ?? 0,
                "kategori_transaksi": payment.kategori,
                "tgl_bayar": Self.dayAPIFormatter.string(from: payment.date),
            ]
            if let recordId = payment.recordId {
                row["id"] = recordId
            }
            return row
        }

        guard
            let itemsData = try? JSONSerialization.data(withJSONObject: payloadItems),
            let paymentsData = try? JSONSerialization.data(withJSONObject: payloadPayments),
            let itemsJSON = String(data: itemsData, encoding: .utf8),
            let paymentsJSON = String(data: paymentsData, encoding: .utf8)
        else {
            errorMessage = "Gagal menyiapkan data paket."
            return
        }

        let fields = [
            "number": number,
            "prospect_id": String(prospectId),
            "user_id": String(userId),
            "employee_id": String(employeeId),
            "no_kontrak": trimmed(noKontrak),
            "pax": String(paxValue),
            "status": status,
            "note": trimmed(note),
            "items": itemsJSON,
            "payments": paymentsJSON,
        ]

        var files: [APIClient.MultipartFile] = []
        if let contractPDF {
            files.append(.init(field: "doc_kontrak", fileName: contractPDF.name, mimeType: "application/pdf", data: contractPDF.data))
        }
        if let agreementPDF {
            files.append(.init(field: "agreement_product", fileName: agreementPDF.name, mimeType: "application/pdf", data: agreementPDF.data))
        }

        for (index, payment) in payments.enumerated() {
            if let data = payment.proofData {
                files.append(.init(
                    field: "payment_proof_\(index)",
                    fileName: payment.proofName ?? "bukti-\(index + 1).jpg",
                    mimeType: "image/jpeg",
                    data: data
                ))
            }
        }

        do {
            if let project {
                _ = try await appState.api.updateProject(id: project.id, fields: fields, files: files)
            } else {
                _ = try await appState.api.createProject(fields: fields, files: files)
            }
            onSaved()
            dismiss()
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func loadOptions() async {
        isLoading = true
        errorMessage = nil
        defer { isLoading = false }
        do {
            let loaded = try await appState.api.projectFormOptions(orderId: project?.id)
            options = loaded
            if let project {
                applyProject(project, options: loaded)
            } else {
                number = loaded.number ?? ""
                noKontrak = loaded.default_no_kontrak ?? noKontrak
                if !paxEdited {
                    paxText = String(loaded.default_pax ?? 1000)
                }
                if userId == 0 { userId = loaded.current_user_id ?? loaded.account_managers.first?.id ?? 0 }
                if employeeId == 0 { employeeId = loaded.current_user_id ?? loaded.event_managers.first?.id ?? 0 }
                if loaded.prospects.count == 1 { prospectId = loaded.prospects[0].id }
                if status.isEmpty { status = loaded.statuses.first?.value ?? "pending" }
            }
        } catch {
            APILoadFailure.assign(error, to: &errorMessage)
        }
    }

    private func applyProject(_ project: FinanceProjectDetail, options: ProjectFormOptions) {
        number = project.number ?? ""
        prospectId = project.prospect_id ?? project.prospect?.id ?? 0
        userId = project.user_id ?? options.current_user_id ?? options.account_managers.first?.id ?? 0
        employeeId = project.employee_id ?? options.current_user_id ?? options.event_managers.first?.id ?? 0
        noKontrak = project.no_kontrak ?? ""
        if let pax = project.pax, pax > 0 {
            paxText = String(pax)
            paxEdited = true
        }
        status = project.status ?? "pending"
        note = project.note ?? ""
        let mappedItems = (project.products ?? []).compactMap { item -> DraftItem? in
            guard let productId = item.product_id, productId > 0 else { return nil }
            return DraftItem(productId: productId, quantity: max(1, item.quantity ?? 1))
        }
        items = mappedItems.isEmpty ? [DraftItem()] : mappedItems
        payments = (project.payments ?? []).map { payment in
            var row = DraftPayment()
            row.recordId = payment.id
            row.keterangan = payment.keterangan ?? ""
            row.paymentMethodId = payment.payment_method_id ?? 0
            row.nominalText = payment.amount.map(String.init) ?? ""
            row.kategori = payment.kategori_transaksi ?? "uang_masuk"
            if let raw = payment.date, let parsed = Self.dayAPIFormatter.date(from: raw) {
                row.date = parsed
            }
            if payment.has_proof == true {
                row.existingProofName = "Bukti tersimpan"
            }
            return row
        }
    }

    private func availableProducts(except keepId: Int?) -> [ProjectFormProductOption] {
        let taken = Set(items.map(\.productId).filter { $0 > 0 && $0 != (keepId ?? -1) })
        return (options?.products ?? []).filter { !taken.contains($0.id) || $0.id == keepId }
    }

    private func bindingItem<T>(_ id: UUID, _ keyPath: WritableKeyPath<DraftItem, T>) -> Binding<T> {
        Binding(
            get: { items.first { $0.id == id }?[keyPath: keyPath] ?? items[0][keyPath: keyPath] },
            set: { value in
                if let index = items.firstIndex(where: { $0.id == id }) {
                    items[index][keyPath: keyPath] = value
                }
            }
        )
    }

    private func bindingPayment<T>(_ id: UUID, _ keyPath: WritableKeyPath<DraftPayment, T>) -> Binding<T> {
        Binding(
            get: {
                if let row = payments.first(where: { $0.id == id }) {
                    return row[keyPath: keyPath]
                }
                return DraftPayment()[keyPath: keyPath]
            },
            set: { value in
                if let index = payments.firstIndex(where: { $0.id == id }) {
                    payments[index][keyPath: keyPath] = value
                }
            }
        )
    }

    private func loadProof(id: UUID, item: PhotosPickerItem?) async {
        guard let item else { return }
        guard let data = try? await item.loadTransferable(type: Data.self) else { return }
        if data.count > 1280 * 1024 {
            errorMessage = "Bukti bayar maksimal 1MB."
            return
        }
        if let index = payments.firstIndex(where: { $0.id == id }) {
            payments[index].proofData = data
            payments[index].proofName = "bukti-\(index + 1).jpg"
        }
    }

    private func handlePDF(_ result: Result<URL, Error>, assign: Binding<PickedFile?>) {
        switch result {
        case .success(let url):
            Task {
                do {
                    assign.wrappedValue = try await Self.loadPDF(from: url)
                } catch {
                    errorMessage = (error as? LocalizedError)?.errorDescription
                        ?? "File PDF tidak dapat dibaca."
                }
            }
        case .failure:
            break
        }
    }

    private static func loadPDF(from url: URL) async throws -> PickedFile {
        try await Task.detached(priority: .userInitiated) {
            let accessed = url.startAccessingSecurityScopedResource()
            defer { if accessed { url.stopAccessingSecurityScopedResource() } }

            let values = try? url.resourceValues(forKeys: [.fileSizeKey])
            if let size = values?.fileSize, size > 10 * 1024 * 1024 {
                throw PDFLoadError.tooLarge
            }
            guard let data = try? Data(contentsOf: url, options: .mappedIfSafe) else {
                throw PDFLoadError.unreadable
            }
            guard data.count <= 10 * 1024 * 1024 else {
                throw PDFLoadError.tooLarge
            }
            return PickedFile(name: url.lastPathComponent, data: data)
        }.value
    }

    private func dismissKeyboard() {
        focused = nil
        UIApplication.shared.sendAction(#selector(UIResponder.resignFirstResponder), to: nil, from: nil, for: nil)
    }

    private func trimmed(_ value: String) -> String {
        value.trimmingCharacters(in: .whitespacesAndNewlines)
    }

    private func isEqual<T: Hashable>(_ lhs: T, _ rhs: T) -> Bool { lhs == rhs }

    private static let dayFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "id_ID")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "d MMM yyyy"
        return formatter
    }()

    private static let dayAPIFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta")
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()
}
