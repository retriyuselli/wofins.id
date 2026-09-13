import Foundation
import SwiftUI

struct MessageResponse: Decodable {
    let message: String?
}

struct ValidationErrorResponse: Decodable {
    let message: String?
    let errors: [String: [String]]?
}

struct DataEnvelope<T: Decodable>: Decodable {
    let data: T
}

struct MessageDataEnvelope<T: Decodable>: Decodable {
    let message: String?
    let data: T
}

struct PagedEnvelope<T: Decodable>: Decodable {
    let data: [T]
    let meta: PageMeta?
}

struct PageMeta: Decodable {
    let current_page: Int?
    let last_page: Int?
    let per_page: Int?
    let total: Int?

    var canLoadMore: Bool { ListPaging.canLoadMore(current: current_page, last: last_page) }
}

enum ListPaging {
    static func canLoadMore(current: Int?, last: Int?) -> Bool {
        (current ?? 1) < max(last ?? 1, 1)
    }
}

struct LoginResponse: Decodable {
    let message: String?
    let token: String
    let token_type: String?
    let user: UserProfile
}

struct UserProfile: Decodable, Identifiable, Equatable {
    let id: Int
    let employee_id: String?
    let name: String
    let email: String
    let phone_number: String?
    let address: String?
    let date_of_birth: String?
    let gender: String?
    let department: String?
    let hire_date: String?
    let emergency_contact: String?
    let notes: String?
    let status: String?
    let avatar_url: String?
    let roles: [String]?
    let expire_date: String?
    let last_working_date: String?
    let is_expired: Bool?
    let is_expiring_soon: Bool?
    let days_until_expiration: Int?

    let company: UserCompany?
    let entitlements: PlanEntitlements?

    var roleLabel: String {
        roles?.joined(separator: ", ").capitalized ?? "Karyawan"
    }

    var companyDisplayName: String {
        let name = company?.name?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        return name.isEmpty ? "WOFINS" : name
    }

    var isSuperAdmin: Bool {
        roles?.contains("super_admin") == true
    }

    var genderLabel: String {
        switch gender {
        case "male": return "Laki-laki"
        case "female": return "Perempuan"
        default: return "—"
        }
    }

    var departmentLabel: String {
        switch department {
        case "bisnis": return "Bisnis"
        case "operasional": return "Operasional"
        default: return department?.capitalized ?? "—"
        }
    }

    var statusLabel: String {
        switch status {
        case "active": return "Aktif"
        case "inactive": return "Tidak aktif"
        case "terminated": return "Berhenti"
        default: return status?.capitalized ?? "—"
        }
    }

    func allows(_ feature: PlanFeature) -> Bool {
        if isSuperAdmin { return true }
        if let features = entitlements?.features, !features.isEmpty {
            return features.contains(feature.rawValue)
        }
        return PlanFeature.starterDefaults.contains(feature)
    }

    var canManageTeam: Bool {
        if isSuperAdmin { return true }
        if allows(.roleManagement) { return true }
        if let limit = entitlements?.seat_limit { return limit > 1 }
        return false
    }
}

enum PlanFeature: String, CaseIterable {
    case projects
    case basicFinance = "basic_finance"
    case notaDinas = "nota_dinas"
    case simulasi
    case fixedAssets = "fixed_assets"
    case reconciliation
    case payroll
    case documents
    case crewFreelance = "crew_freelance"
    case advancedReports = "advanced_reports"
    case roleManagement = "role_management"
    case multiApproval = "multi_approval"

    static let starterDefaults: Set<PlanFeature> = [.projects, .basicFinance, .notaDinas]

    var screenTitle: String {
        switch self {
        case .projects: return "Proyek"
        case .basicFinance: return "Keuangan"
        case .payroll: return "Kompensasi"
        case .simulasi: return "Simulasi"
        case .reconciliation: return "Rekonsiliasi"
        case .advancedReports: return "Laporan Lanjutan"
        case .documents: return "Dokumen & SOP"
        case .roleManagement: return "Tim & Hak Akses"
        case .fixedAssets: return "Aset Tetap"
        case .crewFreelance: return "Crew Freelance"
        case .notaDinas: return "Nota Dinas"
        case .multiApproval: return "Multi Approval"
        }
    }

    static func title(for raw: String) -> String {
        PlanFeature(rawValue: raw)?.screenTitle
            ?? raw.replacingOccurrences(of: "_", with: " ").capitalized
    }

    func upgradeMessage(planLabel: String?) -> String {
        let plan = planLabel ?? "paket saat ini"
        switch self {
        case .documents, .advancedReports, .crewFreelance:
            return "Fitur ini tidak termasuk \(plan). Upgrade ke Business untuk membuka akses."
        case .roleManagement:
            return "Fitur ini tidak termasuk \(plan). Upgrade ke Enterprise untuk membuka akses."
        case .projects, .basicFinance, .notaDinas:
            return "Fitur ini tidak termasuk \(plan)."
        default:
            return "Fitur ini tidak termasuk \(plan). Upgrade ke Professional atau Business untuk membuka akses."
        }
    }
}

struct PlanEntitlements: Decodable, Equatable {
    let plan: String?
    let plan_label: String?
    let features: [String]?
    let seat_limit: Int?
}

struct UserCompany: Decodable, Equatable {
    let id: Int
    let name: String?
    let inisial: String?
    let logo_url: String?
    let email: String?
    let phone: String?
    let address: String?
    let city: String?
    let province: String?
    let website: String?
    let description: String?
    let owner_name: String?
    let jabatan_owner: String?
    let established_year: Int?
    let is_active: Bool?
    let subscription_plan: String?
    let subscription_label: String?
    let subscription_expires_at: String?

    var locationLine: String? {
        let parts = [city, province]
            .compactMap { $0?.trimmingCharacters(in: .whitespacesAndNewlines) }
            .filter { !$0.isEmpty }
        return parts.isEmpty ? nil : parts.joined(separator: ", ")
    }
}

struct AuthSessionDevice: Decodable, Identifiable, Equatable {
    let id: Int
    let name: String?
    let last_used_at: String?
    let created_at: String?
    let is_current: Bool?

    var isCurrent: Bool { is_current == true }

    var displayName: String {
        switch name {
        case "ios-wofins": return "iPhone (email)"
        case "ios-wofins-google": return "iPhone (Google)"
        case "ios-app": return "Aplikasi iOS"
        case let value? where !value.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty:
            return value
        default:
            return "Perangkat"
        }
    }
}

struct UpdateProfilePayload: Encodable {
    var name: String?
    var email: String?
    var phone_number: String?
    var address: String?
    var date_of_birth: String?
    var gender: String?
    var department: String?
    var emergency_contact: String?

    enum CodingKeys: String, CodingKey {
        case name
        case email
        case phone_number
        case address
        case date_of_birth
        case gender
        case department
        case emergency_contact
    }

    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: CodingKeys.self)
        try container.encodeIfPresent(name, forKey: .name)
        try container.encodeIfPresent(email, forKey: .email)
        try container.encode(phone_number, forKey: .phone_number)
        try container.encode(address, forKey: .address)
        try container.encode(date_of_birth, forKey: .date_of_birth)
        try container.encode(gender, forKey: .gender)
        try container.encode(department, forKey: .department)
        try container.encode(emergency_contact, forKey: .emergency_contact)
    }
}

struct NamedRef: Decodable, Equatable {
    let id: Int?
    let name: String?
}

struct CompensationData: Decodable {
    let period: String?
    let current_year: Int?
    let payroll: PayrollItem?
}

struct PayrollItem: Decodable {
    let id: Int?
    let period_month: Int?
    let period_year: Int?
    let period_name: String?
    let monthly_salary: Int?
    let annual_salary: Int?
    let bonus: Int?
    let total_compensation: Int?
    let formatted: PayrollFormatted?
    let updated_at: String?
}

struct PayrollFormatted: Decodable {
    let monthly_salary: String?
    let annual_salary: String?
    let bonus: String?
    let total_compensation: String?
}

/// API sometimes returns year as Int or String.
enum FlexibleStringInt: Decodable, Equatable {
    case int(Int)
    case string(String)

    init(from decoder: Decoder) throws {
        let container = try decoder.singleValueContainer()
        if let value = try? container.decode(Int.self) {
            self = .int(value)
        } else if let value = try? container.decode(String.self) {
            self = .string(value)
        } else {
            self = .string("")
        }
    }

    var display: String {
        switch self {
        case .int(let v): return String(v)
        case .string(let v): return v
        }
    }
}

// MARK: - Finance

struct FinancePeriod: Decodable, Equatable {
    let from: String?
    let to: String?
}

struct FinanceInflow: Decodable {
    let wedding_payments: Int?
    let other_income: Int?
    let total: Int?
}

struct FinanceOutflow: Decodable {
    let wedding_expenses: Int?
    let operational: Int?
    let other_expenses: Int?
    let total: Int?
}

struct FinanceComparison: Decodable {
    let period: FinancePeriod?
    let previous_inflow: Int?
    let previous_outflow: Int?
    let previous_net_cash: Int?
}

struct FinanceDashboardData: Decodable {
    let period: FinancePeriod?
    let inflow: FinanceInflow?
    let outflow: FinanceOutflow?
    let net_cash: Int?
    let comparison: FinanceComparison?
}

struct FinanceProspectRef: Decodable, Equatable {
    let id: Int?
    let name_event: String?
    let name_cpp: String?
    let name_cpw: String?
    let venue: String?
    let phone: String?
    let address: String?
    let date_lamaran: String?
    let date_akad: String?
    let date_resepsi: String?

    var coupleLabel: String? {
        let cpp = name_cpp?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        let cpw = name_cpw?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        if !cpp.isEmpty && !cpw.isEmpty { return "\(cpp) & \(cpw)" }
        if !cpp.isEmpty { return cpp }
        if !cpw.isEmpty { return cpw }
        return nil
    }
}

struct FinanceProspectOrderRef: Decodable, Identifiable, Equatable {
    let id: Int
    let name: String?
    let number: String?
    let status: String?
}

struct FinanceProspectItem: Decodable, Identifiable, Equatable {
    let id: Int
    let name_event: String?
    let name_cpp: String?
    let name_cpw: String?
    let venue: String?
    let phone: String?
    let address: String?
    let date_lamaran: String?
    let time_lamaran: String?
    let date_akad: String?
    let time_akad: String?
    let date_resepsi: String?
    let time_resepsi: String?
    let total_penawaran: Int?
    let notes: String?
    let account_manager: String?
    let order_status: String?
    let order: FinanceProspectOrderRef?

    var displayName: String {
        name_event ?? order?.name ?? "Prospek #\(id)"
    }

    var coupleLabel: String? {
        let cpp = name_cpp?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        let cpw = name_cpw?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
        if !cpp.isEmpty && !cpw.isEmpty { return "\(cpp) & \(cpw)" }
        if !cpp.isEmpty { return cpp }
        if !cpw.isEmpty { return cpw }
        return nil
    }
}

struct FinanceProspectMeta: Decodable {
    let current_page: Int?
    let last_page: Int?
    let per_page: Int?
    let total: Int?
    let all_count: Int?
    let warm_count: Int?
    let with_order_count: Int?
}

struct FinanceProspectsResponse: Decodable {
    let data: [FinanceProspectItem]
    let meta: FinanceProspectMeta?
}

struct CreateProspectPayload: Encodable {
    let name_event: String
    let name_cpp: String
    let name_cpw: String
    let phone: String
    let address: String
    let venue: String
    let total_penawaran: Int
    let notes: String?
    let date_lamaran: String?
    let time_lamaran: String?
    let date_akad: String?
    let time_akad: String?
    let date_resepsi: String?
    let time_resepsi: String?
}

struct ProjectFormOptions: Decodable {
    let number: String?
    let number_prefix: String?
    let contract_prefix: String?
    let default_no_kontrak: String?
    let default_pax: Int?
    let current_user_id: Int?
    let single_seat: Bool?
    let can_create: Bool?
    let quota_message: String?
    let statuses: [ProjectFormStatusOption]
    let prospects: [ProjectFormProspectOption]
    let products: [ProjectFormProductOption]
    let payment_methods: [ProjectFormPaymentMethodOption]
    let account_managers: [ProjectFormUserOption]
    let event_managers: [ProjectFormUserOption]
}

struct ProjectFormStatusOption: Decodable, Identifiable, Hashable {
    var id: String { value }
    let value: String
    let label: String

    init(value: String, label: String) {
        self.value = value
        self.label = label
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        value = try container.decode(String.self, forKey: .value)
        label = try container.decode(String.self, forKey: .label)
    }

    private enum CodingKeys: String, CodingKey {
        case value, label
    }
}

struct ProjectFormProspectOption: Decodable, Identifiable, Hashable {
    let id: Int
    let name_event: String?
    let name_cpp: String?
    let name_cpw: String?
    let venue: String?

    var title: String { name_event ?? "Prospek #\(id)" }
}

struct ProjectFormProductOption: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String?
    let product_price: Int?
    let pengurangan: Int?
    let penambahan_publish: Int?
    let stock: Int?
    let pax: Int?

    var title: String { name ?? "Paket #\(id)" }
    var unitPrice: Int { product_price ?? 0 }
}

struct ProjectFormPaymentMethodOption: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String?
    let label: String?

    var title: String { label ?? name ?? "Metode #\(id)" }
}

struct ProjectFormUserOption: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String?

    var title: String { name ?? "User #\(id)" }
}

struct FinanceProjectProduct: Decodable, Identifiable {
    let id: Int
    let product_id: Int?
    let name: String?
    let quantity: Int?
    let unit_price: Int?
    let pax: Int?

    var lineTotal: Int { (quantity ?? 0) * (unit_price ?? 0) }
}

struct FinanceProductVendor: Decodable, Identifiable {
    let id: Int
    let vendor_id: Int?
    let name: String?
    let pic_name: String?
    let phone: String?
    let address: String?
    let category: String?
    let quantity: Int?
    let harga_publish: Int?
    let harga_vendor: Int?
    let line_public: Int?
    let line_vendor: Int?
    let line_total: Int?
    let description: String?

    var linePublicValue: Int {
        if let line_public { return line_public }
        if let line_total { return line_total }
        return (harga_publish ?? 0) * max(1, quantity ?? 1)
    }

    var lineVendorValue: Int {
        if let line_vendor, line_vendor != 0 { return line_vendor }
        let unit = harga_vendor ?? 0
        if unit != 0 { return unit * max(1, quantity ?? 1) }
        return 0
    }
}

struct FinanceProductDiscount: Decodable, Identifiable {
    let id: Int
    let description: String?
    let amount: Int?
    let notes: String?
}

struct FinanceProductDetail: Decodable, Identifiable {
    let id: Int
    let name: String?
    let slug: String?
    let pax: Int?
    let category: String?
    let description: String?
    let product_price: Int?
    let vendor_price: Int?
    let pengurangan: Int?
    let price: Int?
    let profit: Int?
    let is_active: Bool?
    let is_approved: Bool?
    let vendors: [FinanceProductVendor]?
    let discounts: [FinanceProductDiscount]?
}

struct FinanceVendorDetail: Decodable, Identifiable {
    let id: Int
    let name: String?
    let pic_name: String?
    let phone: String?
    let address: String?
    let category: String?
    let description: String?
    let harga_publish: Int?
    let harga_vendor: Int?
    let profit_amount: Int?
}

struct FinanceProjectItem: Decodable, Identifiable, Equatable {
    let id: Int
    let slug: String?
    let name: String?
    let number: String?
    let status: String?
    let closing_date: String?
    let account_manager: String?
    let prospect: FinanceProspectRef?
    let grand_total: Int?
    let paid_amount: Int?
    let remaining: Int?
    let expenses_total: Int?
    let net_cash_flow: Int?
    let gross_profit: Int?

    var displayName: String {
        name ?? prospect?.name_event ?? number ?? "Proyek #\(id)"
    }

    var statusLabel: String {
        switch status {
        case "pending": return "Pending"
        case "processing": return "Berjalan"
        case "done": return "Selesai"
        case "cancelled": return "Batal"
        default: return status?.capitalized ?? "-"
        }
    }
}

struct FinanceProjectMeta: Decodable {
    let current_page: Int?
    let last_page: Int?
    let per_page: Int?
    let total: Int?
    let total_grand_total: Int?
    let total_payments: Int?
    let total_expenses: Int?
    let total_net_cash_flow: Int?
}

struct FinanceProjectsResponse: Decodable {
    let data: [FinanceProjectItem]
    let meta: FinanceProjectMeta?
}

struct FinanceProjectTotals: Decodable {
    let grand_total: Int?
    let paid: Int?
    let remaining: Int?
    let expenses: Int?
    let net_cash: Int?
    let gross_profit: Int?
}

struct FinancePaymentItem: Decodable, Identifiable {
    let id: Int
    let date: String?
    let amount: Int?
    let keterangan: String?
    let payment_method: String?
    let payment_method_id: Int?
    let kategori_transaksi: String?
    let has_proof: Bool?
}

struct FinanceExpenseItem: Decodable, Identifiable {
    let id: Int
    let date: String?
    let amount: Int?
    let note: String?
    let vendor: String?
    let payment_stage: String?
}

struct FinanceProjectDetail: Decodable, Identifiable {
    let id: Int
    let slug: String?
    let name: String?
    let number: String?
    let status: String?
    let closing_date: String?
    let account_manager: String?
    let prospect: FinanceProspectRef?
    let grand_total: Int?
    let paid_amount: Int?
    let remaining: Int?
    let expenses_total: Int?
    let net_cash_flow: Int?
    let gross_profit: Int?
    let pax: Int?
    let no_kontrak: String?
    let user_id: Int?
    let employee_id: Int?
    let prospect_id: Int?
    let note: String?
    let has_doc_kontrak: Bool?
    let has_agreement_product: Bool?
    let can_edit: Bool?
    let can_edit_reason: String?
    let doc_kontrak_url: String?
    let doc_kontrak_name: String?
    let invoice_url: String?
    let invoice_name: String?
    let event_manager: String?
    let totals: FinanceProjectTotals?
    let products: [FinanceProjectProduct]?
    let payments: [FinancePaymentItem]?
    let expenses: [FinanceExpenseItem]?

    var displayName: String {
        name ?? prospect?.name_event ?? number ?? "Proyek #\(id)"
    }

    var statusLabel: String {
        switch status {
        case "pending": return "Akan Datang"
        case "processing": return "Berjalan"
        case "done": return "Selesai"
        case "cancelled": return "Batal"
        default: return status?.capitalized ?? "-"
        }
    }

    var grandTotalValue: Int { totals?.grand_total ?? grand_total ?? 0 }
    var paidValue: Int { totals?.paid ?? paid_amount ?? 0 }
    var remainingValue: Int { totals?.remaining ?? remaining ?? 0 }
    var expensesValue: Int { totals?.expenses ?? expenses_total ?? 0 }
    var netCashValue: Int { totals?.net_cash ?? net_cash_flow ?? 0 }
}

struct FinanceTransactionItem: Decodable, Identifiable {
    var id: String { "\(source_table ?? "x")-\(source_id ?? 0)-\(date ?? "")" }
    let date: String?
    let type: String?
    let direction: String?
    let amount: Int?
    let description: String?
    let order_id: Int?
    let prospect_name: String?
    let vendor_name: String?
    let payment_method: String?
    let running_balance: Int?
    let source_table: String?
    let source_id: Int?
    let proof_url: String?

    var typeLabel: String {
        switch type {
        case "wedding_payment": return "Masuk Wedding"
        case "other_income": return "Masuk Lain"
        case "wedding_expense": return "Keluar Wedding"
        case "operational_expense": return "Keluar Ops"
        case "other_expense": return "Keluar Lain"
        default: return type ?? "-"
        }
    }

    var isInflow: Bool { direction == "in" }
}

struct FinanceTransactionsResponse: Decodable {
    let data: [FinanceTransactionItem]
    let meta: FinanceTxnMeta?
}

struct FinanceTxnMeta: Decodable {
    let total_in: Int?
    let total_out: Int?
    let net: Int?
    let count: Int?
}

struct FinanceReportSummary: Decodable {
    let mode: String?
    let period: FinancePeriod?
    let by_type: [String: Int]?
    let total_in: Int?
    let total_out: Int?
    let net: Int?
    let orders_count: Int?
    let total_order_value: Int?
    let total_payments_on_orders: Int?
    let total_wedding_expenses: Int?
    let net_profit: Int?
    let operational_expenses: Int?
    let other_expenses: Int?
    let other_income: Int?
}

enum MoneyFormat {
    static func idr(_ value: Int?) -> String {
        let number = value ?? 0
        let formatter = NumberFormatter()
        formatter.numberStyle = .currency
        formatter.currencyCode = "IDR"
        formatter.currencySymbol = "Rp"
        formatter.maximumFractionDigits = 0
        formatter.locale = Locale(identifier: "id_ID")
        return formatter.string(from: NSNumber(value: number)) ?? "Rp\(number)"
    }

    /// `14790000` → `14.790.000` (pemisah ribuan Indonesia).
    static func grouped(_ value: Int) -> String {
        let formatter = NumberFormatter()
        formatter.numberStyle = .decimal
        formatter.locale = Locale(identifier: "id_ID")
        formatter.maximumFractionDigits = 0
        formatter.usesGroupingSeparator = true
        return formatter.string(from: NSNumber(value: value)) ?? "\(value)"
    }

    static func digits(in raw: String) -> String {
        raw.filter(\.isNumber)
    }

    static func groupedInput(_ raw: String) -> String {
        let digits = digits(in: raw)
        guard !digits.isEmpty else { return "" }
        guard let value = Int(digits) else { return digits }
        return grouped(value)
    }

    static func groupedBinding(_ source: Binding<String>) -> Binding<String> {
        Binding(
            get: { groupedInput(source.wrappedValue) },
            set: { source.wrappedValue = digits(in: $0) }
        )
    }

    static func dateRange(_ from: String, _ to: String) -> String {
        let parsedFrom = parseISO(from)
        let parsedTo = parseISO(to)
        guard let parsedFrom, let parsedTo else {
            return "\(from) – \(to)"
        }
        if parsedFrom == parsedTo {
            return display.string(from: parsedFrom)
        }
        return "\(display.string(from: parsedFrom)) – \(display.string(from: parsedTo))"
    }

    private static func parseISO(_ value: String) -> Date? {
        iso.date(from: value)
    }

    private static let iso: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = Calendar(identifier: .gregorian)
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta") ?? .current
        formatter.dateFormat = "yyyy-MM-dd"
        return formatter
    }()

    private static let display: DateFormatter = {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "id_ID")
        formatter.timeZone = TimeZone(identifier: "Asia/Jakarta") ?? .current
        formatter.dateFormat = "d MMM yyyy"
        return formatter
    }()
}

enum PhoneFormat {
    static func display(_ raw: String) -> String {
        let digits = raw.filter(\.isNumber)
        if digits.hasPrefix("62"), digits.count > 2 {
            return "0" + digits.dropFirst(2)
        }
        if digits.hasPrefix("8"), !digits.hasPrefix("08") {
            return "0" + digits
        }
        return raw
    }

    static func telURL(_ raw: String) -> URL? {
        let digits = raw.filter(\.isNumber)
        guard !digits.isEmpty else { return nil }
        if digits.hasPrefix("0") {
            return URL(string: "tel:+62\(digits.dropFirst())")
        }
        if digits.hasPrefix("62") {
            return URL(string: "tel:+\(digits)")
        }
        return URL(string: "tel:+62\(digits)")
    }
}

// MARK: - Piutang

struct FinancePiutangItem: Decodable, Identifiable, Equatable {
    let id: Int
    let nomor: String?
    let nama_debitur: String?
    let jenis: String?
    let status: String?
    let status_label: String?
    let prioritas: String?
    let jumlah_pokok: Int?
    let total_piutang: Int?
    let sudah_dibayar: Int?
    let sisa_piutang: Int?
    let tanggal_piutang: String?
    let tanggal_jatuh_tempo: String?
    let tanggal_lunas: String?
    let is_overdue: Bool?

    var displayName: String {
        nama_debitur ?? nomor ?? "Piutang #\(id)"
    }
}

struct FinancePiutangMeta: Decodable {
    let current_page: Int?
    let last_page: Int?
    let per_page: Int?
    let total: Int?
    let open_count: Int?
    let open_sisa: Int?
    let open_total: Int?
    let open_paid: Int?
}

struct FinancePiutangsResponse: Decodable {
    let data: [FinancePiutangItem]
    let meta: FinancePiutangMeta?
}

struct FinancePiutangPayment: Decodable, Identifiable {
    let id: Int
    let nomor: String?
    let date: String?
    let amount: Int?
    let bunga: Int?
    let denda: Int?
    let total: Int?
    let payment_method: String?
    let catatan: String?
}

struct FinancePiutangDetail: Decodable, Identifiable {
    let id: Int
    let nomor: String?
    let nama_debitur: String?
    let jenis: String?
    let status: String?
    let status_label: String?
    let prioritas: String?
    let jumlah_pokok: Int?
    let total_piutang: Int?
    let sudah_dibayar: Int?
    let sisa_piutang: Int?
    let tanggal_piutang: String?
    let tanggal_jatuh_tempo: String?
    let tanggal_lunas: String?
    let is_overdue: Bool?
    let catatan: String?
    let keterangan: String?
    let kontak_debitur: String?
    let dibuat_oleh: String?
    let payments: [FinancePiutangPayment]?

    var displayName: String {
        nama_debitur ?? nomor ?? "Piutang #\(id)"
    }
}

struct MobileModuleCatalogItem: Decodable, Identifiable, Hashable {
    let key: String
    let title: String
    let subtitle: String?
    let icon: String?
    let group: String?
    let group_label: String?
    let feature: String?
    let allowed: Bool?
    let can_create: Bool?
    let plan_badge: String?
    let count: Int?

    var id: String { key }

    var isAllowed: Bool { allowed ?? false }
    var canCreate: Bool { can_create ?? false }
    var iconName: String { icon ?? "square.grid.2x2.fill" }
    var groupTitle: String { group_label ?? group ?? "Modul" }

    var planFeature: PlanFeature? {
        guard let feature else { return nil }
        return PlanFeature(rawValue: feature)
    }

    static func placeholder(
        key: String,
        title: String,
        feature: String,
        allowed: Bool,
        badge: String? = nil,
        icon: String = "square.grid.2x2.fill"
    ) -> MobileModuleCatalogItem {
        MobileModuleCatalogItem(
            key: key,
            title: title,
            subtitle: nil,
            icon: icon,
            group: nil,
            group_label: nil,
            feature: feature,
            allowed: allowed,
            can_create: allowed,
            plan_badge: allowed ? nil : badge,
            count: nil
        )
    }
}

struct ModuleListResponse: Decodable {
    let data: [ModuleRecord]
    let meta: ModuleListMeta?
}

struct ModuleListMeta: Decodable {
    let current_page: Int?
    let last_page: Int?
    let per_page: Int?
    let total: Int?
    let title: String?
    let can_create: Bool?
}

struct ModuleRecord: Decodable, Identifiable {
    let id: Int
    let title: String?
    let subtitle: String?
    let amount: Int?
    let status: String?
    let date: String?
    let fields: [ModuleFieldRow]?
    let children: [ModuleRecord]?
    let values: [String: String]?
    let payment_simulation: [SimulasiPaymentTerm]?

    var displayTitle: String { title?.isEmpty == false ? title! : "#\(id)" }
}

struct ModuleFieldRow: Decodable, Identifiable, Hashable {
    let label: String
    let value: String?

    var id: String { label }

    var displayText: String {
        let plain = HTMLText.plain(value)
        return plain.isEmpty ? "—" : plain
    }
}

struct ModuleFormSchema: Decodable {
    let title: String?
    let can_create: Bool?
    let fields: [ModuleFormField]?
    let defaults: [String: String]?
    let months: [ModuleFormOption]?
}

struct ModuleFormField: Decodable, Identifiable, Hashable {
    let name: String
    let label: String
    let type: String?
    let required: Bool?
    let placeholder: String?
    let helper: String?
    let readonly: Bool?
    let section: String?
    let options: [ModuleFormOption]?

    var id: String { name }
    var isRequired: Bool { required ?? false }
    var fieldType: String { type ?? "text" }

    /// Number fields that represent money should show thousand separators.
    var usesThousandSeparator: Bool {
        let key = "\(name) \(label)".lowercased()
        let skip = ["pax", "tahun", "bulan", "year", "month", "qty", "quantity", "stok", "stock", "persentase", "percent"]
        if skip.contains(where: { key.contains($0) }) { return false }
        let money = ["nominal", "amount", "harga", "price", "saldo", "gaji", "tunjangan", "pengurangan", "bonus", "jumlah", "target", "pencapaian", "dp", "payment", "transfer"]
        return money.contains(where: { key.contains($0) })
    }
}

struct ModuleFormOption: Decodable, Identifiable, Hashable {
    let value: String
    let label: String
    let total_price: Int?
    let penambahan: Int?
    let pengurangan: Int?

    var id: String { value }
}

struct CreateSimulasiPayload: Encodable {
    let product_id: Int
    let prospect_id: Int
    let user_id: Int?
    let contract_number: String?
    let name_ttd: String?
    let title_ttd: String?
    let notes: String?
    let payment_dp_amount: Int
    let payment_simulation: [SimulasiPaymentTermPayload]
}

struct SimulasiPaymentTermPayload: Encodable {
    let persen: String?
    let nominal: Int
    let bulan: String?
    let tahun: Int
}

struct SimulasiPaymentTerm: Decodable {
    let persen: String?
    let nominal: Int?
    let bulan: String?
    let tahun: Int?

    enum CodingKeys: String, CodingKey {
        case persen, nominal, bulan, tahun
    }

    init(from decoder: Decoder) throws {
        let container = try decoder.container(keyedBy: CodingKeys.self)
        if let text = try? container.decode(String.self, forKey: .persen) {
            persen = text
        } else if let number = try? container.decode(Double.self, forKey: .persen) {
            persen = String(number)
        } else {
            persen = nil
        }
        if let number = try? container.decode(Int.self, forKey: .nominal) {
            nominal = number
        } else if let text = try? container.decode(String.self, forKey: .nominal) {
            nominal = Int(text.filter(\.isNumber))
        } else {
            nominal = nil
        }
        bulan = try container.decodeIfPresent(String.self, forKey: .bulan)
        if let number = try? container.decode(Int.self, forKey: .tahun) {
            tahun = number
        } else if let text = try? container.decode(String.self, forKey: .tahun) {
            tahun = Int(text.filter(\.isNumber))
        } else {
            tahun = nil
        }
    }
}

struct JSONDictionary: Encodable {
    let values: [String: String]

    func encode(to encoder: Encoder) throws {
        var container = encoder.container(keyedBy: RawKey.self)
        for (key, value) in values {
            try container.encode(value, forKey: RawKey(stringValue: key))
        }
    }

    private struct RawKey: CodingKey {
        var stringValue: String
        init(stringValue: String) { self.stringValue = stringValue }
        var intValue: Int? { nil }
        init?(intValue: Int) { return nil }
    }
}

enum HTMLText {
    static func plain(_ raw: String?) -> String {
        guard var text = raw, !text.isEmpty else { return "" }
        guard text.contains("<") else { return text.trimmingCharacters(in: .whitespacesAndNewlines) }

        text = text.replacingOccurrences(of: #"</p>|</div>|<br\s*/?>"# , with: "\n", options: .regularExpression)
        text = text.replacingOccurrences(of: #"<[^>]+>"#, with: "", options: .regularExpression)
        text = text
            .replacingOccurrences(of: "&nbsp;", with: " ")
            .replacingOccurrences(of: "&amp;", with: "&")
            .replacingOccurrences(of: "&lt;", with: "<")
            .replacingOccurrences(of: "&gt;", with: ">")
            .replacingOccurrences(of: "&quot;", with: "\"")
            .replacingOccurrences(of: "&#39;", with: "'")
        text = text.replacingOccurrences(of: #"[ \t]+"# , with: " ", options: .regularExpression)
        return text.trimmingCharacters(in: .whitespacesAndNewlines)
    }
}
