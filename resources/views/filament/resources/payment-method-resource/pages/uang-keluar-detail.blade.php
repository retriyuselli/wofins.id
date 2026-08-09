{{-- Partial tab — tema WOFINS via .pm-wf parent --}}
<link rel="stylesheet" href="{{ asset('assets/payment/paymentmethod.css') }}?v={{ @filemtime(public_path('assets/payment/paymentmethod.css')) }}">

<div class="pm-panel">
    <div class="pm-panel__header">
        <div>
            <h2 class="pm-panel__title">Detail Uang Keluar</h2>
            <p class="pm-panel__sub">Rekening: {{ $record->name }}</p>
        </div>
        <div>
            <p class="pm-panel__total is-out">
                Rp {{ number_format($totalUangKeluar, 0, ',', '.') }}
            </p>
            <p class="pm-panel__meta">
                {{ $expenses->count() + $expenseOps->count() + $pengeluaranLain->count() }} transaksi
            </p>
        </div>
    </div>

    <div class="billing-info text-sm sm:text-base mt-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <div>
                <h3 class="pm-section-title">Detail Rekening</h3>
                <p class="pm-muted">Nama Rekening: <span class="pm-ink font-medium">{{ $record->name }}</span></p>
                <p class="pm-muted">Total Pengeluaran: <span class="pm-ink font-medium">Rp {{ number_format($totalUangKeluar, 0, ',', '.') }}</span></p>
                <p class="pm-muted">Jumlah Transaksi: <span class="pm-ink font-medium">{{ $expenses->count() + $expenseOps->count() + $pengeluaranLain->count() }}</span></p>
            </div>
            <div>
                <h3 class="pm-section-title">Distribusi Pengeluaran</h3>
                <p class="pm-muted">Wedding: <span class="pm-ink font-medium">{{ $expenses->count() }} transaksi</span></p>
                <p class="pm-muted">Operasional: <span class="pm-ink font-medium">{{ $expenseOps->count() }} transaksi</span></p>
                <p class="pm-muted">Lainnya: <span class="pm-ink font-medium">{{ $pengeluaranLain->count() }} transaksi</span></p>
            </div>
        </div>
    </div>

    <div class="mt-8 mb-10 overflow-x-auto">
        <table class="detail-tagihan-table w-full text-sm sm:text-base">
            <thead>
                <tr>
                    <th colspan="2">Ringkasan Pengeluaran</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-2">Total Wedding Expense</td>
                    <td class="text-right px-4 py-2">Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Total Operational Expense</td>
                    <td class="text-right px-4 py-2">Rp {{ number_format($expenseOps->sum('amount'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Total Pengeluaran Lain</td>
                    <td class="text-right px-4 py-2">Rp {{ number_format($pengeluaranLain->sum('amount'), 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td class="font-semibold px-4 py-3">TOTAL UANG KELUAR</td>
                    <td class="text-right font-semibold px-4 py-3">
                        Rp {{ number_format($totalUangKeluar, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

        <!-- Detail Wedding Expense -->
        <div class="mt-12 pt-8 mb-12">
            <h3 class="section-header mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Detail Wedding Expense</span>
                    <p class="section-description">Rincian pengeluaran untuk acara pernikahan.</p>
                </div>
            </h3>
            <div class="overflow-x-auto mt-6">
                <table class="item-pengurangan-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No. ND</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Project</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Vendor</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Keterangan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($expenses as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->date_expense ? \Carbon\Carbon::parse($item->date_expense)->format('d M Y') : 'Tanggal tidak tersedia' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-900">
                                    {{ $item->no_nd ?? 'Tidak ada nomor ND' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ ucwords(strtolower($item->order->prospect->name_event ?? 'Tidak ada project')) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ ucwords(strtolower($item->vendor->name ?? 'Tidak ada vendor')) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ ucwords(strtolower($item->note ?? 'Wedding Expense')) }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-red-600 text-right font-medium">
                                    -Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                    🏢 Belum ada transaksi wedding expense
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($expenses->count() > 0)
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-sm font-medium text-gray-900">
                                    Total Wedding Expense
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-red-600 text-right">
                                    -Rp {{ number_format($expenses->sum('amount'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Detail Operational Expense -->
        <div class="mt-12 pt-8 mb-12">
            <h3 class="section-header mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Detail Operational Expense</span>
                    <p class="section-description">Rincian pengeluaran operasional harian.</p>
                </div>
            </h3>
            <div class="overflow-x-auto mt-6">
                <table class="item-pengurangan-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No. ND</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nama</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Keterangan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($expenseOps as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->date_expense ? \Carbon\Carbon::parse($item->date_expense)->format('d M Y') : 'Tanggal tidak tersedia' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-900">
                                    {{ $item->no_nd ?? 'Tidak ada nomor ND' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $item->name ?? 'Operational Expense' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $item->note ?? 'Operational Expense' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-orange-600 text-right font-medium">
                                    -Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    🏢 Belum ada transaksi operational expense
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($expenseOps->count() > 0)
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-sm font-medium text-gray-900">
                                    Total Operational Expense
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-orange-600 text-right">
                                    -Rp {{ number_format($expenseOps->sum('amount'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <!-- Detail Pengeluaran Lain -->
        <div class="mt-12 pt-8 mb-12">
            <h3 class="section-header mb-6">
                <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <div class="section-header-content">
                    <span class="section-header-title">Detail Pengeluaran Lain</span>
                    <p class="section-description">Rincian pengeluaran lain-lain.</p>
                </div>
            </h3>
            <div class="overflow-x-auto mt-6">
                <table class="item-pengurangan-table w-full text-sm sm:text-base">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tanggal</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                No. ND</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Keterangan</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($pengeluaranLain as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->date_expense ? \Carbon\Carbon::parse($item->date_expense)->format('d M Y') : 'Tanggal tidak tersedia' }}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-900">
                                    {{ $item->no_nd ?? 'Tidak ada nomor ND' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $item->note ?? 'Pengeluaran Lain' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-purple-600 text-right font-medium">
                                    -Rp {{ number_format($item->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    🏢 Belum ada transaksi pengeluaran lain
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($pengeluaranLain->count() > 0)
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900">
                                    Total Pengeluaran Lain
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-purple-600 text-right">
                                    -Rp {{ number_format($pengeluaranLain->sum('amount'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

