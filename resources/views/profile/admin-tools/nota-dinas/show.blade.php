@extends('profile.layout')

@section('profile-page-title', 'Detail Nota Dinas')
@section('profile-page-subtitle', $notaDinas->no_nd)

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'Nota Dinas',
        'title' => $notaDinas->no_nd,
        'subtitle' => 'Tanggal: '.(optional($notaDinas->tanggal)->format('d-m-Y') ?? '-').' · Status: '.($notaDinas->status ?? '-').' · Hal: '.($notaDinas->hal ?? '-'),
    ])

    <div class="p-6 space-y-5">
        <div class="flex justify-end">
            <a href="{{ route('profile.admin-tools.nota-dinas') }}" class="wf-admin-link-chip">Kembali</a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Pengirim</div>
                <div class="wf-admin-stat__value text-sm">{{ $notaDinas->pengirim?->name ?? '-' }}</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Penerima</div>
                <div class="wf-admin-stat__value text-sm">{{ $notaDinas->penerima?->name ?? '-' }}</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Detail Dibayar</div>
                <div class="wf-admin-stat__value text-sm">{{ number_format($detailsPaidCount) }} / {{ number_format($detailsCount) }}</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Total Transfer</div>
                <div class="wf-admin-stat__value text-sm">Rp {{ number_format((float) $detailsSum, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($paymentStageSummary as $stage => $c)
                <span class="wf-admin-badge wf-admin-badge--muted">
                    {{ $stage !== null && $stage !== '' ? $stage : '-' }}: {{ number_format((int) $c) }}
                </span>
            @endforeach
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari keperluan / vendor / invoice" class="wf-admin-input md:col-span-2">
            <select name="status_invoice" class="wf-admin-select">
                <option value="">Semua Status Invoice</option>
                @foreach(['belum_dibayar','menunggu','sudah_dibayar'] as $opt)
                    <option value="{{ $opt }}" @selected($statusInvoice === $opt)>{{ str_replace('_', ' ', ucfirst($opt)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="wf-btn-gold inline-flex items-center justify-center px-4 py-2.5 text-sm">Terapkan</button>
        </form>

        <div class="wf-admin-table-wrap">
            <table class="wf-admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Keperluan</th>
                        <th>Vendor</th>
                        <th>Event</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Status Invoice</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($details as $d)
                        <tr>
                            <td class="text-xs text-[var(--wf-ink)]">{{ ucfirst((string) $d->keperluan) }}</td>
                            <td class="text-xs text-[var(--wf-ink)]">
                                @php
                                    $accountHolder = $d->account_holder ?: ($d->nama_rekening ?: ($d->vendor?->account_holder ?: null));
                                @endphp
                                <div class="font-semibold">{{ $d->vendor?->name ? \Illuminate\Support\Str::title((string) $d->vendor->name) : '-' }}</div>
                                @if($d->vendor?->name && filled($accountHolder))
                                    <div class="mt-1 inline-flex px-2 py-0.5 rounded-md text-[11px] bg-[var(--wf-cream)] text-[var(--wf-navy)]">
                                        {{ \Illuminate\Support\Str::title((string) $accountHolder) }}
                                    </div>
                                @endif
                            </td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ $d->order?->name ? ucfirst((string) $d->order->name) : ucfirst((string) ($d->event ?? '-')) }}</td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ $d->jenis_pengeluaran ?? '-' }}</td>
                            <td class="text-xs font-semibold tabular-nums text-[var(--wf-navy)]">{{ number_format((float) $d->jumlah_transfer, 0, ',', '.') }}</td>
                            <td>
                                <span class="wf-admin-badge">{{ $d->status_invoice ?? '-' }}</span>
                                @php
                                    $stage = (string) ($d->payment_stage ?? '');
                                @endphp
                                @if($stage !== '')
                                    <div class="mt-1"><span class="wf-admin-badge wf-admin-badge--warn">{{ $stage }}</span></div>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($d->invoice_file)
                                    <button type="button"
                                        data-invoice-url="{{ route('profile.admin-tools.nota-dinas-details.invoice.view', $d) }}"
                                        data-invoice-ext="{{ strtolower(pathinfo((string) $d->invoice_file, PATHINFO_EXTENSION)) }}"
                                        class="js-invoice-view wf-btn-navy inline-flex items-center px-3 py-1.5 text-xs">
                                        View
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-[var(--wf-muted)]">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $details->links() }}</div>
    </div>
</div>

<div id="invoice-preview-modal" class="z-50" style="display:none; position:fixed; inset:0;">
    <div class="w-full h-full p-4 flex items-start justify-center bg-[rgba(7,21,38,0.55)] backdrop-blur-[2px]">
        <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl border border-[var(--wf-line)] overflow-hidden">
            <div class="px-5 py-4 flex items-center justify-between gap-3 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60">
                <div class="text-sm font-bold text-[var(--wf-navy)]">Preview Invoice</div>
                <button type="button" id="invoice-preview-close" class="wf-btn-navy px-3 py-1.5 text-sm">Tutup</button>
            </div>
            <div class="bg-white">
                <iframe id="invoice-preview-frame" src="" class="w-full h-[75vh] bg-white"></iframe>
            </div>
        </div>
    </div>
</div>

<div id="invoice-not-found-modal" class="z-50" style="display:none; position:fixed; inset:0;">
    <div class="w-full h-full p-4 flex items-start justify-center bg-[rgba(7,21,38,0.55)] backdrop-blur-[2px]">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-xl border border-[var(--wf-line)] overflow-hidden">
            <div class="p-5">
                <div class="text-sm font-bold text-[var(--wf-navy)]">Invoice Tidak Ditemukan</div>
                <div id="invoice-not-found-message" class="mt-2 text-sm text-[var(--wf-muted)]">File invoice tidak ditemukan atau tidak dapat diakses.</div>
            </div>
            <div class="px-5 py-4 bg-[var(--wf-cream)]/60 flex items-center justify-end gap-2">
                <button type="button" id="invoice-not-found-close" class="wf-btn-navy px-4 py-2 text-sm">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const previewModal = document.getElementById('invoice-preview-modal');
        const previewFrame = document.getElementById('invoice-preview-frame');
        const previewCloseBtn = document.getElementById('invoice-preview-close');
        const notFoundModal = document.getElementById('invoice-not-found-modal');
        const messageEl = document.getElementById('invoice-not-found-message');
        const closeBtn = document.getElementById('invoice-not-found-close');

        function openNotFoundModal(message) {
            if (messageEl) messageEl.textContent = message || 'File invoice tidak ditemukan atau tidak dapat diakses.';
            if (notFoundModal) notFoundModal.style.display = 'block';
        }

        function closeNotFoundModal() {
            if (notFoundModal) notFoundModal.style.display = 'none';
        }

        function openPreview(url) {
            previewFrame.src = url;
            if (previewModal) previewModal.style.display = 'block';
        }

        function closePreview() {
            if (previewModal) previewModal.style.display = 'none';
            previewFrame.src = '';
        }

        if (previewCloseBtn) previewCloseBtn.addEventListener('click', closePreview);
        if (closeBtn) closeBtn.addEventListener('click', closeNotFoundModal);

        if (previewModal) {
            previewModal.addEventListener('click', function (e) {
                if (e.target === previewModal || e.target === previewModal.firstElementChild) closePreview();
            });
        }

        if (notFoundModal) {
            notFoundModal.addEventListener('click', function (e) {
                if (e.target === notFoundModal || e.target === notFoundModal.firstElementChild) closeNotFoundModal();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePreview();
                closeNotFoundModal();
            }
        });

        document.querySelectorAll('.js-invoice-view').forEach(function (btn) {
            btn.addEventListener('click', async function () {
                const url = btn.getAttribute('data-invoice-url');
                const ext = (btn.getAttribute('data-invoice-ext') || '').toLowerCase();
                if (!url) {
                    openNotFoundModal('File invoice tidak ditemukan atau tidak dapat diakses.');
                    return;
                }

                const allowed = ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'webp'];
                if (ext !== '' && !allowed.includes(ext)) {
                    openNotFoundModal('Preview invoice belum didukung untuk tipe file ini.');
                    return;
                }

                try {
                    const res = await fetch(url, { method: 'HEAD', redirect: 'manual', credentials: 'same-origin' });
                    if (res && res.ok) {
                        openPreview(url);
                        return;
                    }
                } catch (e) {}

                openNotFoundModal('File invoice tidak ditemukan atau tidak dapat diakses.');
            });
        });
    })();
</script>
@endsection
