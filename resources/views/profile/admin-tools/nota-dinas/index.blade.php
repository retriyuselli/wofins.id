@extends('profile.layout')

@section('profile-page-title', 'Nota Dinas')
@section('profile-page-subtitle', 'Monitoring Nota Dinas dan status pembayarannya (khusus super admin)')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'Keuangan',
        'title' => 'Nota Dinas',
        'subtitle' => 'Monitoring nota dinas dan status pembayarannya.',
    ])

    <div class="p-6 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Total Nota Dinas</div>
                <div class="wf-admin-stat__value">{{ number_format($notaDinasCount) }}</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Total Detail</div>
                <div class="wf-admin-stat__value">{{ number_format($detailsCount) }}</div>
                <div class="mt-1 text-xs font-medium text-[var(--wf-muted)]">{{ number_format($detailsPaidCount) }} sudah dibayar</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Total Jumlah Transfer</div>
                <div class="wf-admin-stat__value">Rp {{ number_format((float) $detailsSum, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($statusSummary as $s => $c)
                <span class="wf-admin-badge wf-admin-badge--muted">
                    {{ $s ?? '-' }}: {{ number_format((int) $c) }}
                </span>
            @endforeach
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari no_nd / hal / catatan" class="wf-admin-input">
            <select name="status" class="wf-admin-select">
                <option value="">Semua Status</option>
                @foreach(['draft','diajukan','disetujui','dibayar','ditolak'] as $opt)
                    <option value="{{ $opt }}" @selected($status === $opt)>{{ ucfirst($opt) }}</option>
                @endforeach
            </select>
            <input type="month" name="month" value="{{ $month }}" class="wf-admin-input">
            <button type="submit" class="wf-btn-gold inline-flex items-center justify-center px-4 py-2.5 text-sm">Terapkan</button>
        </form>

        <div class="wf-admin-table-wrap">
            <table class="wf-admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>No ND</th>
                        <th>Hal</th>
                        <th>Status</th>
                        <th>Detail</th>
                        <th>Total Detail</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($notaDinas as $nd)
                        <tr>
                            <td class="text-xs text-[var(--wf-muted)]">{{ optional($nd->tanggal)->format('d-m-Y') ?? '-' }}</td>
                            <td class="text-xs font-semibold text-[var(--wf-navy)]">{{ $nd->no_nd }}</td>
                            <td class="text-xs text-[var(--wf-ink)]">{{ $nd->hal }}</td>
                            <td><span class="wf-admin-badge">{{ $nd->status ?? '-' }}</span></td>
                            <td class="text-xs text-[var(--wf-ink)]">{{ number_format((int) ($nd->details_paid_count ?? 0)) }}</td>
                            <td>
                                <div class="text-xs font-semibold text-[var(--wf-ink)]">{{ number_format((int) ($nd->details_count ?? 0)) }} item</div>
                                <div class="text-xs text-[var(--wf-muted)] tabular-nums">{{ number_format((float) ($nd->details_sum ?? 0), 0, ',', '.') }}</div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('profile.admin-tools.nota-dinas.show', $nd) }}" class="wf-admin-link-chip">Detail</a>
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

        <div>{{ $notaDinas->links() }}</div>
    </div>
</div>
@endsection
