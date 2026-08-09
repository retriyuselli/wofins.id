{{-- Partial tab — tema WOFINS via .pm-wf parent --}}
<link rel="stylesheet" href="{{ asset('assets/payment/paymentmethod.css') }}?v={{ @filemtime(public_path('assets/payment/paymentmethod.css')) }}">

<div class="pm-panel">
    <div class="pm-panel__header">
        <div>
            <h2 class="pm-panel__title">Detail Uang Masuk</h2>
            <p class="pm-panel__sub">Rekening: {{ $record->name }}</p>
        </div>
        <div>
            <p class="pm-panel__total is-in">
                Rp {{ number_format($totalUangMasuk, 0, ',', '.') }}
            </p>
            <p class="pm-panel__meta">
                {{ $pendapatanLain->count() + $dataPembayaran->count() }} transaksi masuk
            </p>
            <p class="pm-panel__note">
                Sejak
                {{ $record->opening_balance_date ? \Carbon\Carbon::parse($record->opening_balance_date)->format('d M Y') : 'pembukaan rekening' }}
            </p>
        </div>
    </div>

    <div class="billing-info text-sm sm:text-base mt-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 w-full">
            <div>
                <h3 class="pm-section-title">Detail Rekening</h3>
                <p class="pm-muted">Nama Rekening: <span class="pm-ink font-medium">{{ $record->name }}</span></p>
                <p class="pm-muted">Saldo Awal: <span class="pm-ink font-medium">Rp {{ number_format($record->opening_balance, 0, ',', '.') }}</span></p>
                <p class="pm-muted">Total Transaksi Masuk: <span class="pm-ink font-medium">Rp {{ number_format($totalUangMasuk, 0, ',', '.') }}</span></p>
                <p class="pm-muted">Jumlah Transaksi: <span class="pm-ink font-medium">{{ $pendapatanLain->count() + $dataPembayaran->count() }}</span></p>
            </div>
            <div>
                <h3 class="pm-section-title">Distribusi Pemasukan</h3>
                <p class="pm-muted">Pendapatan Lain: <span class="pm-ink font-medium">{{ $pendapatanLain->count() }} transaksi</span></p>
                <p class="pm-muted">Pembayaran Customer: <span class="pm-ink font-medium">{{ $dataPembayaran->count() }} transaksi</span></p>
                <p class="pm-panel__note" style="text-align:left">
                    * Total di atas adalah transaksi masuk aktual, tidak termasuk saldo awal.
                </p>
            </div>
        </div>
    </div>

    <div class="mt-8 mb-10 overflow-x-auto">
        <table class="detail-tagihan-table w-full text-sm sm:text-base">
            <thead>
                <tr>
                    <th colspan="2">Ringkasan Pemasukan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="px-4 py-2">Total Pendapatan Lain</td>
                    <td class="text-right px-4 py-2">Rp {{ number_format($pendapatanLain->sum('nominal'), 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="px-4 py-2">Total Pembayaran Customer</td>
                    <td class="text-right px-4 py-2">Rp {{ number_format($dataPembayaran->sum('nominal'), 0, ',', '.') }}</td>
                </tr>
                <tr class="total">
                    <td class="font-semibold px-4 py-3">TOTAL UANG MASUK</td>
                    <td class="text-right font-semibold px-4 py-3">
                        Rp {{ number_format($totalUangMasuk, 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-8 pt-4 mb-8">
        <h3 class="section-header mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <div class="section-header-content">
                <span class="section-header-title">Detail Pendapatan Lain</span>
                <p class="section-description">Rincian pendapatan di luar pembayaran customer.</p>
            </div>
        </h3>
        <div class="overflow-x-auto mt-4">
            <table class="item-pengurangan-table w-full text-sm sm:text-base">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Keterangan</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendapatanLain as $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                {{ $item->tgl_bayar ? \Carbon\Carbon::parse($item->tgl_bayar)->format('d M Y') : 'Tanggal tidak tersedia' }}
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $item->keterangan ?? 'Pendapatan Lain' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-green-600 text-right font-medium">
                                +Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center pm-muted">Belum ada transaksi pendapatan lain</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($pendapatanLain->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm font-medium">Total Pendapatan Lain</td>
                            <td class="px-4 py-3 text-sm font-bold text-green-600 text-right">
                                +Rp {{ number_format($pendapatanLain->sum('nominal'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-8 pt-4 mb-4">
        <h3 class="section-header mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="section-header-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div class="section-header-content">
                <span class="section-header-title">Detail Pembayaran Customer</span>
                <p class="section-description">Rincian pembayaran yang diterima dari customer.</p>
            </div>
        </h3>
        <div class="overflow-x-auto mt-4">
            <table class="item-pengurangan-table w-full text-sm sm:text-base">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left">Tanggal</th>
                        <th class="px-4 py-3 text-left">Customer / Event</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dataPembayaran as $item)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                {{ $item->tgl_bayar ? \Carbon\Carbon::parse($item->tgl_bayar)->format('d M Y') : 'Tanggal tidak tersedia' }}
                            </td>
                            <td class="px-4 py-3 text-sm">{{ $item->order->prospect->name_event ?? 'Pembayaran Customer' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-blue-600 text-right font-medium">
                                +Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center pm-muted">Belum ada transaksi pembayaran customer</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($dataPembayaran->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-sm font-medium">Total Pembayaran Customer</td>
                            <td class="px-4 py-3 text-sm font-bold text-blue-600 text-right">
                                +Rp {{ number_format($dataPembayaran->sum('nominal'), 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
