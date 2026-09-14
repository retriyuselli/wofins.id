import SwiftUI

struct BankReconciliationComparisonView: View {
    let comparison: BankReconciliationComparison

    private var stats: BankReconciliationStatistics { comparison.statistics }

    var body: some View {
        VStack(alignment: .leading, spacing: 14) {
            Text("Rekonsiliasi Perbandingan")
                .font(.poppins(.headline, weight: .bold))
                .foregroundStyle(WofinsTheme.primary)
                .fixedSize(horizontal: false, vertical: true)

            Text("Perbandingan transaksi aplikasi dengan mutasi bank — sama seperti di admin web.")
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
                .fixedSize(horizontal: false, vertical: true)

            statsGrid

            section(
                title: "Cocok (\(stats.matched_count))",
                tone: WofinsTheme.success,
                isEmpty: comparison.matched.isEmpty,
                empty: "Belum ada pasangan yang cocok."
            ) {
                ForEach(Array(comparison.matched.enumerated()), id: \.offset) { _, row in
                    matchedCard(row)
                }
                if comparison.truncated?.matched == true {
                    truncatedNote
                }
            }

            section(
                title: "Hanya di aplikasi (\(stats.unmatched_app_count))",
                tone: Color(red: 0.72, green: 0.45, blue: 0.08),
                isEmpty: comparison.unmatched_app.isEmpty,
                empty: "Tidak ada transaksi aplikasi yang belum cocok."
            ) {
                ForEach(Array(comparison.unmatched_app.enumerated()), id: \.offset) { index, item in
                    sideCard(item, badge: "App", index: index)
                }
                if comparison.truncated?.unmatched_app == true {
                    truncatedNote
                }
            }

            section(
                title: "Hanya di bank (\(stats.unmatched_bank_count))",
                tone: WofinsTheme.danger,
                isEmpty: comparison.unmatched_bank.isEmpty,
                empty: "Tidak ada mutasi bank yang belum cocok."
            ) {
                ForEach(Array(comparison.unmatched_bank.enumerated()), id: \.offset) { index, item in
                    sideCard(item, badge: "Bank", index: index)
                }
                if comparison.truncated?.unmatched_bank == true {
                    truncatedNote
                }
            }
        }
    }

    private var statsGrid: some View {
        VStack(spacing: 10) {
            HStack(spacing: 10) {
                statTile("Cocok", "\(stats.matched_count)", WofinsTheme.success)
                statTile("App saja", "\(stats.unmatched_app_count)", Color(red: 0.72, green: 0.45, blue: 0.08))
                statTile("Bank saja", "\(stats.unmatched_bank_count)", WofinsTheme.danger)
            }
            HStack(spacing: 10) {
                statTile("Match", String(format: "%.0f%%", stats.match_percentage), WofinsTheme.primary)
                statTile("Trx app", "\(stats.total_app_transactions)", WofinsTheme.muted)
                statTile("Item bank", "\(stats.total_bank_items)", WofinsTheme.muted)
            }
            VStack(alignment: .leading, spacing: 6) {
                moneyRow("Debit app", stats.total_app_debit, isDebit: true)
                moneyRow("Kredit app", stats.total_app_credit, isDebit: false)
                moneyRow("Debit bank", stats.total_bank_debit, isDebit: true)
                moneyRow("Kredit bank", stats.total_bank_credit, isDebit: false)
            }
            .padding(12)
            .frame(maxWidth: .infinity, alignment: .leading)
            .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
            .overlay {
                RoundedRectangle(cornerRadius: 14, style: .continuous)
                    .stroke(WofinsTheme.border.opacity(0.8))
            }
        }
    }

    private func section<Content: View>(
        title: String,
        tone: Color,
        isEmpty: Bool,
        empty: String,
        @ViewBuilder content: () -> Content
    ) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            Text(title)
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(tone)
                .fixedSize(horizontal: false, vertical: true)

            if isEmpty {
                Text(empty)
                    .font(.poppins(.caption))
                    .foregroundStyle(WofinsTheme.muted)
                    .fixedSize(horizontal: false, vertical: true)
                    .padding(12)
                    .frame(maxWidth: .infinity, alignment: .leading)
                    .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
                    .overlay {
                        RoundedRectangle(cornerRadius: 14, style: .continuous)
                            .stroke(WofinsTheme.border.opacity(0.8))
                    }
            } else {
                content()
            }
        }
    }

    private var truncatedNote: some View {
        Text("Menampilkan 50 baris pertama. Lihat admin web untuk daftar lengkap.")
            .font(.poppins(.caption2))
            .foregroundStyle(WofinsTheme.muted)
            .fixedSize(horizontal: false, vertical: true)
            .padding(.top, 2)
    }

    private func matchedCard(_ row: BankReconciliationMatch) -> some View {
        VStack(alignment: .leading, spacing: 10) {
            HStack {
                Text(row.app?.dateDisplay ?? row.bank?.dateDisplay ?? "—")
                    .font(.poppins(.caption2, weight: .semibold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer(minLength: 8)
                Text("\(row.confidence)%")
                    .font(.poppins(.caption2, weight: .bold))
                    .foregroundStyle(WofinsTheme.success)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 3)
                    .background(WofinsTheme.success.opacity(0.12), in: Capsule())
            }

            if let app = row.app {
                pairLine(label: "App", item: app)
            }
            if let bank = row.bank {
                pairLine(label: "Bank", item: bank)
            }
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .stroke(WofinsTheme.success.opacity(0.35))
        }
    }

    private func sideCard(_ item: BankReconciliationSideItem, badge: String, index: Int) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text(badge)
                    .font(.poppins(.caption2, weight: .bold))
                    .foregroundStyle(WofinsTheme.primary)
                    .padding(.horizontal, 8)
                    .padding(.vertical, 3)
                    .background(WofinsTheme.primary.opacity(0.08), in: Capsule())
                Text(item.dateDisplay)
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer(minLength: 8)
                Text(item.signedAmountLabel)
                    .font(.poppins(.caption, weight: .bold))
                    .foregroundStyle(item.is_debit ? WofinsTheme.danger : WofinsTheme.success)
                    .lineLimit(1)
                    .minimumScaleFactor(0.7)
            }
            Text(item.description)
                .font(.poppins(.subheadline, weight: .semibold))
                .foregroundStyle(WofinsTheme.ink)
                .fixedSize(horizontal: false, vertical: true)
            if let source = item.source, !source.isEmpty {
                Text(source)
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
            }
        }
        .padding(12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 14, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 14, style: .continuous)
                .stroke(WofinsTheme.border.opacity(0.8))
        }
        .id("\(badge)-\(item.id)-\(index)")
    }

    private func pairLine(label: String, item: BankReconciliationSideItem) -> some View {
        VStack(alignment: .leading, spacing: 2) {
            HStack {
                Text(label)
                    .font(.poppins(.caption2, weight: .bold))
                    .foregroundStyle(WofinsTheme.muted)
                Spacer(minLength: 8)
                Text(item.signedAmountLabel)
                    .font(.poppins(.caption, weight: .bold))
                    .foregroundStyle(item.is_debit ? WofinsTheme.danger : WofinsTheme.success)
                    .lineLimit(1)
                    .minimumScaleFactor(0.7)
            }
            Text(item.description)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.ink)
                .fixedSize(horizontal: false, vertical: true)
            if let source = item.source, !source.isEmpty {
                Text(source)
                    .font(.poppins(.caption2))
                    .foregroundStyle(WofinsTheme.muted)
            }
        }
    }

    private func statTile(_ title: String, _ value: String, _ color: Color) -> some View {
        VStack(spacing: 4) {
            Text(value)
                .font(.poppins(.subheadline, weight: .bold))
                .foregroundStyle(color)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
            Text(title)
                .font(.poppins(.caption2))
                .foregroundStyle(WofinsTheme.muted)
                .lineLimit(1)
                .minimumScaleFactor(0.8)
        }
        .frame(maxWidth: .infinity)
        .padding(.vertical, 10)
        .padding(.horizontal, 6)
        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 12, style: .continuous))
        .overlay {
            RoundedRectangle(cornerRadius: 12, style: .continuous)
                .stroke(WofinsTheme.border.opacity(0.75))
        }
    }

    private func moneyRow(_ label: String, _ amount: Int, isDebit: Bool) -> some View {
        HStack {
            Text(label)
                .font(.poppins(.caption))
                .foregroundStyle(WofinsTheme.muted)
            Spacer(minLength: 8)
            Text(MoneyFormat.idr(amount))
                .font(.poppins(.caption, weight: .semibold))
                .foregroundStyle(isDebit ? WofinsTheme.danger : WofinsTheme.success)
                .lineLimit(1)
                .minimumScaleFactor(0.7)
        }
    }
}
