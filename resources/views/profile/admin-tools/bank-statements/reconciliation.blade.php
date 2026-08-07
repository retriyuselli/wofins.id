@extends('profile.layout')

@section('profile-page-title', 'Bank Statement (Rekonsiliasi)')
@section('profile-page-subtitle', 'Monitoring status dan item rekonsiliasi per statement')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('profile.admin-tools.bank-statements') }}" class="wf-admin-link-chip">Kembali</a>
        <a href="{{ route('profile.admin-tools.bank-statements.failed') }}" class="wf-admin-link-chip">Lihat yang Failed</a>
    </div>

    <div class="wf-profile-card overflow-hidden">
        @include('profile.admin-tools.partials.wf-admin-header', [
            'eyebrow' => 'Bank Statement',
            'title' => 'Rekonsiliasi',
            'subtitle' => 'Monitoring status dan item rekonsiliasi per statement.',
        ])

        <div class="p-6 space-y-5">
            <div class="wf-admin-table-wrap">
                <table class="wf-admin-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Rekening</th>
                            <th>Periode</th>
                            <th>Rekonsiliasi</th>
                            <th class="text-right">Item</th>
                            <th class="text-right">Trx</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)]">
                        @forelse($statements as $st)
                            <tr class="text-xs">
                                <td>
                                    <div class="font-bold text-[var(--wf-navy)]">{{ $st->paymentMethod?->name ?? '-' }}</div>
                                    <div class="text-[11px] text-[var(--wf-muted)]">#{{ $st->id }}</div>
                                </td>
                                <td>
                                    <div>{{ $st->period_start ? $st->period_start->format('d M Y') : '-' }}</div>
                                    <div class="text-[11px] text-[var(--wf-muted)]">{{ $st->period_end ? $st->period_end->format('d M Y') : '-' }}</div>
                                </td>
                                <td>
                                    <span class="wf-admin-badge {{ $st->reconciliation_status === 'failed' ? 'wf-admin-badge--danger' : 'wf-admin-badge--muted' }}">
                                        {{ $st->reconciliation_status ?? '-' }}
                                    </span>
                                    @if($st->reconciliation_file)
                                        <div class="text-[11px] text-[var(--wf-muted)] mt-1">{{ $st->reconciliation_original_filename ?? 'file' }}</div>
                                    @endif
                                </td>
                                <td class="text-right tabular-nums">{{ number_format((int) $st->reconciliation_items_count) }}</td>
                                <td class="text-right tabular-nums">{{ number_format((int) $st->transactions_count) }}</td>
                                <td class="text-right">
                                    <a href="{{ route('profile.admin-tools.bank-statements.show', $st) }}" class="wf-admin-link-chip">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-sm text-[var(--wf-muted)]">Belum ada data rekonsiliasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $statements->links() }}</div>
        </div>
    </div>
</div>
@endsection
