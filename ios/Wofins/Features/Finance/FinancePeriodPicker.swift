import Foundation
import SwiftUI

enum FinancePeriodPreset: String, CaseIterable, Identifiable {
    case thisMonth
    case lastMonth
    case thisYear
    case custom

    var id: String { rawValue }

    var label: String {
        switch self {
        case .thisMonth: return "Bulan ini"
        case .lastMonth: return "Bulan lalu"
        case .thisYear: return "Tahun ini"
        case .custom: return "Kustom"
        }
    }
}

struct FinancePeriodSelection: Equatable {
    var preset: FinancePeriodPreset = .thisMonth
    var customFrom: Date = Calendar.current.date(from: Calendar.current.dateComponents([.year, .month], from: Date())) ?? Date()
    var customTo: Date = Date()

    var fromString: String {
        Self.iso.string(from: range.from)
    }

    var toString: String {
        Self.iso.string(from: range.to)
    }

    var label: String {
        "\(fromString) → \(toString)"
    }

    var range: (from: Date, to: Date) {
        let cal = Calendar.current
        let today = Date()
        switch preset {
        case .thisMonth:
            let start = cal.date(from: cal.dateComponents([.year, .month], from: today)) ?? today
            let end = cal.date(byAdding: DateComponents(month: 1, day: -1), to: start) ?? today
            return (start, min(end, today))
        case .lastMonth:
            let thisStart = cal.date(from: cal.dateComponents([.year, .month], from: today)) ?? today
            let start = cal.date(byAdding: .month, value: -1, to: thisStart) ?? today
            let end = cal.date(byAdding: DateComponents(day: -1), to: thisStart) ?? today
            return (start, end)
        case .thisYear:
            let start = cal.date(from: DateComponents(year: cal.component(.year, from: today), month: 1, day: 1)) ?? today
            return (start, today)
        case .custom:
            let from = cal.startOfDay(for: min(customFrom, customTo))
            let to = cal.startOfDay(for: max(customFrom, customTo))
            return (from, to)
        }
    }

    private static let iso: DateFormatter = {
        let f = DateFormatter()
        f.calendar = Calendar(identifier: .gregorian)
        f.locale = Locale(identifier: "en_US_POSIX")
        f.timeZone = TimeZone(identifier: "Asia/Jakarta") ?? .current
        f.dateFormat = "yyyy-MM-dd"
        return f
    }()
}

struct FinancePeriodPicker: View {
    @Binding var selection: FinancePeriodSelection
    var onChanged: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 10) {
            ScrollView(.horizontal, showsIndicators: false) {
                HStack(spacing: 8) {
                    ForEach(FinancePeriodPreset.allCases) { preset in
                        Button {
                            selection.preset = preset
                            onChanged()
                        } label: {
                            Text(preset.label)
                                .font(.poppins(.caption, weight: .medium))
                                .padding(.horizontal, 12)
                                .padding(.vertical, 7)
                                .background(selection.preset == preset ? WofinsTheme.accent : WofinsTheme.card)
                                .foregroundStyle(selection.preset == preset ? Color.white : WofinsTheme.muted)
                                .clipShape(Capsule())
                        }
                        .buttonStyle(.plain)
                    }
                }
            }

            Text(selection.label)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)

            if selection.preset == .custom {
                HStack {
                    DatePicker("Dari", selection: $selection.customFrom, displayedComponents: .date)
                        .labelsHidden()
                        .onChange(of: selection.customFrom) { _, _ in onChanged() }
                    Text("–").foregroundStyle(WofinsTheme.muted)
                    DatePicker("Sampai", selection: $selection.customTo, displayedComponents: .date)
                        .labelsHidden()
                        .onChange(of: selection.customTo) { _, _ in onChanged() }
                }
            }
        }
    }
}
