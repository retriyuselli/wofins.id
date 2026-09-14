import SwiftUI
import UIKit

struct ReportsView: View {
    @EnvironmentObject private var appState: AppState
    @State private var mode = "cash"
    @State private var period = FinancePeriodSelection()
    @State private var report: FinanceReportSummary?
    @State private var isLoading = false
    @State private var errorMessage: String?
    @State private var reloadToken = 0
    @State private var notice: String?
    @State private var isDownloadingPdf = false
    @State private var isDownloadingExcel = false
    @State private var pdfShareItem: ReportShareItem?

    private var isExporting: Bool { isDownloadingPdf || isDownloadingExcel }

    private var cashRows: [(String, Int, Bool)] {
        guard let report else { return [] }
        let values = report.by_type ?? [:]
        return values.keys.sorted().map { key in
            (key, values[key] ?? 0, key.localizedCaseInsensitiveContains("masuk"))
        }
    }

    var body: some View {
        NavigationStack {
            VStack(spacing: 0) {
                header

                if appState.allows(.basicFinance) {
                    ScrollView(showsIndicators: false) {
                        LazyVStack(spacing: 16) {
                            periodMenu
                            modePicker

                            if isLoading && report == nil {
                                loadingState
                            } else if let errorMessage {
                                errorState(errorMessage)
                            } else if let report {
                                summaryCard(report)
                                performanceCard(report)
                                breakdownCard(report)
                                ModuleShortcutsView(
                                    keys: ["account_manager_targets"],
                                    title: "Laporan lanjutan"
                                )
                                exportButtons
                            }
                        }
                        .padding(.top, 16)
                        .padding(.bottom, 120)
                    }
                    .refreshable { await load() }
                } else {
                    PlanLockedView(feature: .basicFinance, title: "Laporan")
                    Spacer()
                }
            }
            .background(WofinsTheme.background.ignoresSafeArea())
            .wofinsHidesNavigationBar()
            .task(id: reloadToken) {
                guard appState.allows(.basicFinance) else { return }
                await load()
            }
            .sheet(item: $pdfShareItem) { item in
                ReportShareSheet(items: [item.url])
            }
            .alert("Laporan", isPresented: Binding(get: { notice != nil }, set: { if !$0 { notice = nil } })) {
                Button("OK", role: .cancel) {}
            } message: { Text(notice ?? "") }
            .overlay {
                if isExporting {
                    ZStack {
                        Color.black.opacity(0.28).ignoresSafeArea()
                        VStack(spacing: 12) {
                            ProgressView().tint(WofinsTheme.primary)
                            Text(isDownloadingExcel ? "Menyiapkan Excel…" : "Menyiapkan PDF…")
                                .font(.poppins(.subheadline, weight: .semibold))
                                .foregroundStyle(WofinsTheme.ink)
                                .multilineTextAlignment(.center)
                        }
                        .padding(22)
                        .frame(maxWidth: 260)
                        .background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
                        .padding(.horizontal, 32)
                    }
                    .allowsHitTesting(true)
                }
            }
        }
    }

    private var header: some View {
        HStack(spacing: 12) {
            WofinsCompactMark()
            VStack(alignment: .leading, spacing: 2) { Text("Laporan").font(.poppins(.headline, weight: .bold)).foregroundStyle(.white); Text(appState.currentUser?.companyDisplayName ?? "Performa keuangan").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.72)).lineLimit(1) }
            Spacer()
            Button {
                Task { await downloadPdf() }
            } label: {
                Image(systemName: "arrow.down.to.line")
                    .font(.system(size: 17, weight: .bold))
                    .foregroundStyle(.white)
                    .frame(width: 40, height: 40)
                    .background(.white.opacity(0.11), in: Circle())
            }
            .accessibilityLabel("Unduh PDF")
            .disabled(isExporting || !appState.allows(.basicFinance))
        }
        .padding(.horizontal, 20).padding(.vertical, 12)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(WofinsTheme.primary.ignoresSafeArea(edges: .top))
    }

    private var periodMenu: some View {
        Menu {
            Button("Bulan ini") { setPeriod(.thisMonth) }
            Button("Bulan lalu") { setPeriod(.lastMonth) }
            Button("Tahun ini") { setPeriod(.thisYear) }
        } label: {
            HStack(spacing: 13) {
                Image(systemName: "calendar").font(.system(size: 18, weight: .semibold)).foregroundStyle(WofinsTheme.primary)
                    .frame(width: 40, height: 40).background(WofinsTheme.primary.opacity(0.09), in: RoundedRectangle(cornerRadius: 11))
                Text(period.preset.label).font(.poppins(.subheadline, weight: .bold)).foregroundStyle(WofinsTheme.ink)
                Spacer(); Image(systemName: "chevron.down").foregroundStyle(WofinsTheme.muted)
            }.padding(13).reportSurface()
        }.buttonStyle(.plain).padding(.horizontal, 16)
    }

    private var modePicker: some View {
        HStack(spacing: 4) {
            modeButton("Arus Kas", value: "cash")
            modeButton("Laba Rugi", value: "profit_loss")
        }.padding(4).background(WofinsTheme.card, in: Capsule()).overlay { Capsule().stroke(WofinsTheme.border) }.padding(.horizontal, 16)
    }

    private func modeButton(_ title: String, value: String) -> some View {
        Button {
            mode = value; reloadToken += 1
        } label: {
            Text(title).font(.poppins(.subheadline, weight: .semibold)).foregroundStyle(mode == value ? .white : WofinsTheme.primary)
                .frame(maxWidth: .infinity).frame(height: 40).background(mode == value ? WofinsTheme.primary : Color.clear, in: Capsule())
        }.buttonStyle(.plain)
    }

    private func summaryCard(_ report: FinanceReportSummary) -> some View {
        let net = mode == "cash" ? report.net : report.net_profit
        let incoming = mode == "cash" ? report.total_in : report.total_order_value
        let outgoing = mode == "cash" ? report.total_out : report.total_wedding_expenses
        return VStack(alignment: .leading, spacing: 17) {
            HStack(spacing: 13) {
                Image(systemName: "chart.line.uptrend.xyaxis").font(.system(size: 22, weight: .bold)).foregroundStyle(WofinsTheme.yellow)
                    .frame(width: 50, height: 50).background(.white.opacity(0.1), in: Circle())
                VStack(alignment: .leading, spacing: 3) {
                    Text(mode == "cash" ? "Kas Bersih" : "Laba Kotor").font(.poppins(.caption)).foregroundStyle(.white.opacity(0.75))
                    Text(MoneyFormat.idr(net)).font(.poppins(size: 27, weight: .bold)).foregroundStyle(.white).lineLimit(1).minimumScaleFactor(0.65)
                }
            }
            Divider().overlay(.white.opacity(0.16))
            HStack {
                summaryMetric(mode == "cash" ? "Total Masuk" : "Nilai Order", incoming, WofinsTheme.success)
                Divider().frame(height: 38).overlay(.white.opacity(0.16))
                summaryMetric(mode == "cash" ? "Total Keluar" : "Biaya Wedding", outgoing, WofinsTheme.danger)
            }
        }.padding(19)
            .background(WofinsTheme.primary, in: RoundedRectangle(cornerRadius: 22, style: .continuous))
            .wofinsHeroShadow()
            .padding(.horizontal, 16)
    }

    private func summaryMetric(_ title: String, _ amount: Int?, _ color: Color) -> some View {
        VStack(alignment: .leading, spacing: 3) {
            Text(title).font(.poppins(.caption2)).foregroundStyle(.white.opacity(0.72))
            Text(MoneyFormat.idr(amount)).font(.poppins(.caption, weight: .bold)).foregroundStyle(.white).lineLimit(1).minimumScaleFactor(0.6)
        }.frame(maxWidth: .infinity, alignment: .leading).padding(.horizontal, 6)
    }

    private func performanceCard(_ report: FinanceReportSummary) -> some View {
        let incoming = max(0, mode == "cash" ? report.total_in ?? 0 : report.total_order_value ?? 0)
        let outgoing = max(0, mode == "cash" ? report.total_out ?? 0 : report.total_wedding_expenses ?? 0)
        let maximum = max(incoming, outgoing, 1)
        return VStack(alignment: .leading, spacing: 15) {
            Text("Komposisi Keuangan").font(.poppins(.headline, weight: .bold)).foregroundStyle(WofinsTheme.primary)
            reportBar("Masuk", incoming, maximum, WofinsTheme.primaryLight)
            reportBar("Keluar", outgoing, maximum, WofinsTheme.yellow)
        }.padding(17).reportSurface().padding(.horizontal, 16)
    }

    private func reportBar(_ title: String, _ value: Int, _ maximum: Int, _ color: Color) -> some View {
        VStack(alignment: .leading, spacing: 6) {
            HStack { Text(title); Spacer(); Text(MoneyFormat.idr(value)) }.font(.poppins(.caption, weight: .semibold)).foregroundStyle(WofinsTheme.ink)
            GeometryReader { geo in
                ZStack(alignment: .leading) {
                    Capsule().fill(WofinsTheme.border)
                    Capsule().fill(color).frame(width: geo.size.width * CGFloat(value) / CGFloat(maximum))
                }
            }.frame(height: 9)
        }
    }

    private func breakdownCard(_ report: FinanceReportSummary) -> some View {
        let rows: [(String, Int, Bool)] = mode == "cash" ? cashRows : [
            ("Nilai Order", report.total_order_value ?? 0, true),
            ("Pembayaran", report.total_payments_on_orders ?? 0, true),
            ("Pengeluaran Wedding", report.total_wedding_expenses ?? 0, false),
            ("Biaya Operasional", report.operational_expenses ?? 0, false),
            ("Pendapatan Lainnya", report.other_income ?? 0, true),
        ]
        return VStack(alignment: .leading, spacing: 0) {
            Text("Rincian Laporan").font(.poppins(.headline, weight: .bold)).foregroundStyle(WofinsTheme.primary).padding(.bottom, 10)
            ForEach(Array(rows.enumerated()), id: \.offset) { index, row in
                HStack(spacing: 11) {
                    Image(systemName: row.2 ? "arrow.down.left" : "arrow.up.right").font(.system(size: 13, weight: .bold))
                        .foregroundStyle(row.2 ? WofinsTheme.success : WofinsTheme.danger).frame(width: 34, height: 34)
                        .background((row.2 ? WofinsTheme.success : WofinsTheme.danger).opacity(0.1), in: Circle())
                    Text(row.0).font(.poppins(.caption)).foregroundStyle(WofinsTheme.ink)
                    Spacer(); Text(MoneyFormat.idr(row.1)).font(.poppins(.caption, weight: .bold)).foregroundStyle(row.2 ? WofinsTheme.success : WofinsTheme.danger)
                }.padding(.vertical, 10)
                if index < rows.count - 1 { Divider().padding(.leading, 45) }
            }
        }.padding(17).reportSurface().padding(.horizontal, 16)
    }

    private var exportButtons: some View {
        VStack(spacing: 10) {
            HStack(spacing: 12) {
                Button {
                    Task { await downloadPdf() }
                } label: {
                    Label("Unduh PDF", systemImage: "arrow.down.doc.fill")
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.primary)
                        .lineLimit(1)
                        .minimumScaleFactor(0.8)
                        .frame(maxWidth: .infinity)
                        .frame(height: 48)
                        .background(WofinsTheme.primary.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                        .overlay { RoundedRectangle(cornerRadius: 14).stroke(WofinsTheme.primary.opacity(0.35)) }
                }
                .buttonStyle(.plain)
                .disabled(isExporting)

                Button {
                    Task { await downloadExcel() }
                } label: {
                    Label("Ekspor Excel", systemImage: "tablecells.fill")
                        .font(.poppins(.caption, weight: .semibold))
                        .foregroundStyle(WofinsTheme.success)
                        .lineLimit(1)
                        .minimumScaleFactor(0.8)
                        .frame(maxWidth: .infinity)
                        .frame(height: 48)
                        .background(WofinsTheme.success.opacity(0.08), in: RoundedRectangle(cornerRadius: 14))
                        .overlay { RoundedRectangle(cornerRadius: 14).stroke(WofinsTheme.success.opacity(0.35)) }
                }
                .buttonStyle(.plain)
                .disabled(isExporting)
            }
        }
        .padding(.horizontal, 16)
    }

    private var loadingState: some View {
        HStack { ProgressView(); Text("Memuat laporan…").font(.poppins(.subheadline)).foregroundStyle(WofinsTheme.muted) }
            .frame(maxWidth: .infinity, alignment: .leading).padding(18).reportSurface().padding(.horizontal, 16)
    }

    private func errorState(_ message: String) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            Label("Laporan belum dapat dimuat", systemImage: "exclamationmark.triangle.fill").foregroundStyle(WofinsTheme.danger)
            Text(message).font(.poppins(.caption)).foregroundStyle(WofinsTheme.muted)
            Button("Coba lagi") { reloadToken += 1 }.foregroundStyle(.white).padding(9).background(WofinsTheme.primary, in: Capsule())
        }.frame(maxWidth: .infinity, alignment: .leading).padding(18).reportSurface().padding(.horizontal, 16)
    }

    private func setPeriod(_ preset: FinancePeriodPreset) { period.preset = preset; reloadToken += 1 }
    private func load() async {
        isLoading = true; defer { isLoading = false }
        do { report = try await appState.api.financeReportSummary(from: period.fromString, to: period.toString, mode: mode); errorMessage = nil }
        catch {
            if let message = APILoadFailure.userMessage(for: error) {
                errorMessage = message
            }
        }
    }

    private func downloadPdf() async {
        guard appState.allows(.basicFinance), !isExporting else { return }
        isDownloadingPdf = true
        defer { isDownloadingPdf = false }
        do {
            let data = try await appState.api.financeReportPdf(from: period.fromString, to: period.toString, mode: mode)
            try shareFile(data: data, fileName: reportFileName(ext: "pdf"))
        } catch {
            APILoadFailure.assign(error, to: &notice)
        }
    }

    private func downloadExcel() async {
        guard appState.allows(.basicFinance), !isExporting else { return }
        isDownloadingExcel = true
        defer { isDownloadingExcel = false }
        do {
            let data = try await appState.api.financeReportExcel(from: period.fromString, to: period.toString, mode: mode)
            try shareFile(data: data, fileName: reportFileName(ext: "xlsx"))
        } catch {
            APILoadFailure.assign(error, to: &notice)
        }
    }

    private func reportFileName(ext: String) -> String {
        let kind = mode == "profit_loss" ? "laba-rugi" : "arus-kas"
        return "laporan-\(kind)-\(period.fromString)-\(period.toString).\(ext)"
    }

    private func shareFile(data: Data, fileName: String) throws {
        let url = FileManager.default.temporaryDirectory.appendingPathComponent(fileName)
        try data.write(to: url, options: .atomic)
        pdfShareItem = ReportShareItem(url: url)
    }
}

private struct ReportShareItem: Identifiable {
    let id = UUID()
    let url: URL
}

private struct ReportShareSheet: UIViewControllerRepresentable {
    let items: [Any]

    func makeUIViewController(context: Context) -> UIActivityViewController {
        UIActivityViewController(activityItems: items, applicationActivities: nil)
    }

    func updateUIViewController(_ uiViewController: UIActivityViewController, context: Context) {}
}

private extension View {
    func reportSurface() -> some View {
        background(WofinsTheme.card, in: RoundedRectangle(cornerRadius: 18, style: .continuous))
            .overlay { RoundedRectangle(cornerRadius: 18).stroke(WofinsTheme.border.opacity(0.75)) }
            .wofinsSoftShadow()
    }
}
