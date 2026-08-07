@extends('profile.layout')

@section('profile-page-title', 'Bank Statement')
@section('profile-page-subtitle', 'Monitoring rekening koran dan status rekonsiliasi (read-only)')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="space-y-6">
    <div class="wf-profile-card overflow-hidden">
        @include('profile.admin-tools.partials.wf-admin-header', [
            'eyebrow' => 'Keuangan',
            'title' => 'Bank Statement',
            'subtitle' => 'Monitoring rekening koran dan status rekonsiliasi.',
        ])

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Total</div>
                    <div class="wf-admin-stat__value">{{ number_format($totalCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Pending</div>
                    <div class="wf-admin-stat__value">{{ number_format($pendingCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Processing</div>
                    <div class="wf-admin-stat__value">{{ number_format($processingCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Parsed</div>
                    <div class="wf-admin-stat__value">{{ number_format($parsedCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Failed</div>
                    <div class="wf-admin-stat__value text-rose-700">{{ number_format($failedCount) }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Rekonsiliasi Uploaded</div>
                    <div class="wf-admin-stat__value">{{ number_format($reconUploadedCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Rekonsiliasi Processing</div>
                    <div class="wf-admin-stat__value">{{ number_format($reconProcessingCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Rekonsiliasi Completed</div>
                    <div class="wf-admin-stat__value">{{ number_format($reconCompletedCount) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Rekonsiliasi Failed</div>
                    <div class="wf-admin-stat__value text-rose-700">{{ number_format($reconFailedCount) }}</div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <a href="{{ route('profile.admin-tools.bank-statements.failed') }}"
                    class="inline-flex items-center px-4 py-2 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-sm font-bold hover:bg-rose-100 transition">
                    Lihat yang Failed
                </a>
                <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}"
                    class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-sm font-bold hover:bg-emerald-100 transition">
                    Lihat Rekonsiliasi
                </a>
                <a href="{{ route('profile.admin-tools.bank-statements.guide') }}"
                    class="wf-btn-gold inline-flex items-center px-4 py-2 text-sm">
                    Panduan
                </a>
            </div>
        </div>
    </div>

    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60">
            <h3 class="text-base font-bold text-[var(--wf-navy)]">Ringkasan 12 Bulan Terakhir</h3>
        </div>
        <div class="p-6">
            <div class="wf-admin-table-wrap">
                <table class="wf-admin-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Bulan</th>
                            <th class="text-right">Statement</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                            <th class="text-right">Failed</th>
                            <th class="text-right">Recon Failed</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)]">
                        @forelse($monthlySummary as $row)
                            @php
                                $monthLabel = $row->ym;
                                try {
                                    $monthLabel = \Carbon\Carbon::createFromFormat('Y-m', $row->ym)->locale('id')->translatedFormat('F Y');
                                } catch (\Throwable $e) {
                                    $monthLabel = $row->ym;
                                }
                            @endphp
                            <tr class="text-xs">
                                <td class="font-bold text-[var(--wf-navy)]">{{ $monthLabel }}</td>
                                <td class="text-right tabular-nums">{{ number_format((int) $row->statements_count) }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $row->tot_debit_sum, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $row->tot_credit_sum, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums {{ (int) $row->failed_count > 0 ? 'text-rose-700 font-bold' : '' }}">
                                    {{ number_format((int) $row->failed_count) }}
                                </td>
                                <td class="text-right tabular-nums {{ (int) $row->recon_failed_count > 0 ? 'text-rose-700 font-bold' : '' }}">
                                    {{ number_format((int) $row->recon_failed_count) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-8 text-sm text-[var(--wf-muted)] text-center" colspan="6">Belum ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60 flex items-center justify-between gap-3">
            <h3 class="text-base font-bold text-[var(--wf-navy)]">Statement Terbaru</h3>
            <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}" class="text-sm font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                Monitor Rekonsiliasi
            </a>
        </div>
        <div class="p-6">
            <div class="wf-admin-table-wrap">
                <table class="wf-admin-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Rekening</th>
                            <th>Periode</th>
                            <th>Status</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                            <th class="text-right">Saldo Akhir</th>
                            <th>Rekonsiliasi</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)]">
                        @forelse($latestStatements as $st)
                            <tr class="text-xs">
                                <td>
                                    <div class="font-bold text-[var(--wf-navy)]">{{ $st->paymentMethod?->name ?? '-' }}</div>
                                    <div class="text-[11px] text-[var(--wf-muted)]">#{{ $st->id }}</div>
                                </td>
                                <td class="text-[var(--wf-ink)]">
                                    <div>{{ $st->period_start ? $st->period_start->format('d M Y') : '-' }}</div>
                                    <div class="text-[11px] text-[var(--wf-muted)]">{{ $st->period_end ? $st->period_end->format('d M Y') : '-' }}</div>
                                </td>
                                <td>
                                    <span class="wf-admin-badge {{ $st->status === 'failed' ? 'wf-admin-badge--danger' : 'wf-admin-badge--muted' }}">
                                        {{ $st->status ?? '-' }}
                                    </span>
                                    <div class="text-[11px] text-[var(--wf-muted)] mt-1">trx: {{ number_format((int) $st->transactions_count) }}</div>
                                </td>
                                <td class="text-right tabular-nums">{{ number_format((float) $st->tot_debit, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $st->tot_credit, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums font-semibold">{{ number_format((float) $st->closing_balance, 0, ',', '.') }}</td>
                                <td>
                                    <span class="wf-admin-badge {{ $st->reconciliation_status === 'failed' ? 'wf-admin-badge--danger' : 'wf-admin-badge--muted' }}">
                                        {{ $st->reconciliation_status ?? '-' }}
                                    </span>
                                    <div class="text-[11px] text-[var(--wf-muted)] mt-1">item: {{ number_format((int) $st->reconciliation_items_count) }}</div>
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('profile.admin-tools.bank-statements.show', $st) }}" class="wf-admin-link-chip">Detail</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-sm text-[var(--wf-muted)]">Belum ada statement.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
