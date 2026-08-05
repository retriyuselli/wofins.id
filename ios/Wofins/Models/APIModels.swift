import Foundation

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
    let status: String?
    let avatar_url: String?
    let roles: [String]?
    let expire_date: String?
    let is_expired: Bool?
    let is_expiring_soon: Bool?
    let days_until_expiration: Int?

    var roleLabel: String {
        roles?.joined(separator: ", ").capitalized ?? "Karyawan"
    }
}

struct UpdateProfilePayload: Encodable {
    var name: String?
    var email: String?
    var phone_number: String?
    var address: String?
    var date_of_birth: String?
    var gender: String?
    var emergency_contact: String?
}

struct LeaveTypeRef: Decodable, Equatable {
    let id: Int?
    let name: String?
    let keterangan: String?
    let max_days_per_year: Int?
}

struct NamedRef: Decodable, Equatable {
    let id: Int?
    let name: String?
}

struct LeaveRequestItem: Decodable, Identifiable, Equatable {
    let id: Int
    let leave_type: LeaveTypeRef?
    let start_date: String?
    let end_date: String?
    let total_days: Int?
    let reason: String?
    let emergency_contact: String?
    let status: String?
    let documents: [LeaveDocument]?
    let replacement_employee: NamedRef?
    let approver: NamedRef?
    let approval_notes: String?
    let created_at: String?
    let updated_at: String?

    var statusLabel: String {
        switch status {
        case "approved": return "Disetujui"
        case "pending": return "Menunggu"
        case "rejected": return "Ditolak"
        default: return status?.capitalized ?? "-"
        }
    }
}

struct LeaveDocument: Decodable, Equatable {
    let path: String?
    let url: String?
}

struct LeaveTypeItem: Decodable, Identifiable, Hashable {
    let id: Int
    let name: String
    let keterangan: String?
    let max_days_per_year: Int?
}

struct CreateLeavePayload: Encodable {
    let leave_type_id: Int
    let start_date: String
    let end_date: String
    let reason: String
    let emergency_contact: String?
}

struct CompensationData: Decodable {
    let period: String?
    let current_year: Int?
    let payroll: PayrollItem?
    let leave_stats: LeaveStats?
    let leave_by_type: [String: Int]?
    let annual_leave_allowance: Int?
    let used_leave: Int?
    let display_used_leave: Int?
    let remaining_leave: Int?
    let prev_year: Int?
    let prev_used_leave: Int?
    let prev_usage_percentage: Int?
    let carry_over: Int?
    let effective_allowance_year: Int?
}

struct LeaveStats: Decodable {
    let approved: Int?
    let pending: Int?
    let rejected: Int?
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

struct LeaveBalancesData: Decodable {
    let year: Int?
    let annual_leave_allowance: Int?
    let balances: [LeaveBalanceItem]
}

struct LeaveBalanceItem: Decodable, Identifiable {
    let id: Int
    let year: FlexibleStringInt?
    let leave_type: LeaveTypeRef?
    let allocated_days: Int?
    let carried_over_days: Int?
    let used_days: Int?
    let remaining_days: Int?
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

struct ScheduleData: Decodable {
    let current_date: String?
    let days_until_next_leave: Int?
    let next_leave: LeaveRequestItem?
    let upcoming_leaves: [LeaveRequestItem]
    let recent_leaves: [LeaveRequestItem]
    let status_translations: [String: String]?
    let leave_type_translations: [String: String]?
}

// MARK: - Absensi

struct AbsensiPengaturan: Decodable, Equatable {
    let jam_masuk: String?
    let jam_pulang: String?
    let wajib_foto: Bool?
    let wajib_lokasi: Bool?
    let tolak_jika_di_luar_radius: Bool?
    let ukuran_foto_maks_kb: Int?
}

struct AbsensiLogItem: Decodable, Identifiable, Equatable {
    let id: Int
    let jenis: String?
    let waktu: String?
    let lintang: Double?
    let bujur: Double?
    let jarak_ke_kantor_meter: Int?
    let dalam_radius: Bool?
    let foto_url: String?
    let valid: Bool?
}

struct AbsensiItem: Decodable, Identifiable, Equatable {
    let id: Int
    let tanggal: String?
    let status: String?
    let jam_masuk: String?
    let jam_pulang: String?
    let menit_kerja: Int?
    let menit_terlambat: Int?
    let menit_pulang_cepat: Int?
    let sumber: String?
    let catatan: String?
    let sudah_masuk: Bool?
    let sudah_pulang: Bool?
    let logs: [AbsensiLogItem]?
}

struct AbsensiHariIniData: Decodable {
    let tanggal: String?
    let pengaturan: AbsensiPengaturan?
    let absensi: AbsensiItem?
    let bisa_masuk: Bool?
    let bisa_pulang: Bool?
}

struct LokasiAbsensiItem: Decodable, Identifiable, Equatable {
    let id: Int
    let nama: String?
    let lintang: Double
    let bujur: Double
    let radius_meter: Int
    let alamat: String?
    let urutan: Int?
}

struct CekLokasiData: Decodable {
    let dalam_radius: Bool
    let jarak_meter: Int?
    let lokasi: LokasiAbsensiItem?
}

struct AbsensiRingkasanData: Decodable {
    let periode: String?
    let total_hari: Int?
    let hadir: Int?
    let terlambat: Int?
    let alfa: Int?
    let cuti: Int?
    let total_menit_terlambat: Int?
    let total_menit_kerja: Int?
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
    let date_lamaran: String?
    let date_akad: String?
    let date_resepsi: String?
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
    let totals: FinanceProjectTotals?
    let payments: [FinancePaymentItem]?
    let expenses: [FinanceExpenseItem]?

    var displayName: String {
        name ?? prospect?.name_event ?? number ?? "Proyek #\(id)"
    }
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
