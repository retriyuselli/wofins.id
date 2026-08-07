@extends('profile.layout')

@section('profile-page-title', 'Detail Bank Statement')
@section('profile-page-subtitle', 'Monitoring data statement, transaksi hasil parsing, dan item rekonsiliasi')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
@php $st = $bankStatement; @endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('profile.admin-tools.bank-statements') }}" class="wf-admin-link-chip">Kembali</a>
        <a href="{{ route('profile.admin-tools.bank-statements.reconciliation') }}" class="wf-admin-link-chip">Monitor Rekonsiliasi</a>
        <a href="{{ route('profile.admin-tools.bank-statements.failed') }}" class="wf-admin-link-chip">Failed</a>
    </div>

    <div class="wf-profile-card overflow-hidden">
        @include('profile.admin-tools.partials.wf-admin-header', [
            'eyebrow' => 'Bank Statement',
            'title' => $st->paymentMethod?->name ?? 'Detail Statement',
            'subtitle' => '#'.$st->id.' · '.($st->period_start ? $st->period_start->format('d M Y') : '-').' – '.($st->period_end ? $st->period_end->format('d M Y') : '-'),
        ])

        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Status Parsing</div>
                    <div class="mt-2">
                        <span class="wf-admin-badge {{ $st->status === 'failed' ? 'wf-admin-badge--danger' : 'wf-admin-badge--muted' }}">
                            {{ $st->status ?? '-' }}
                        </span>
                    </div>
                    <div class="mt-3 text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Processed At</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $st->processed_at ? $st->processed_at->format('d M Y H:i') : '-' }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Status Rekonsiliasi</div>
                    <div class="mt-2">
                        <span class="wf-admin-badge {{ $st->reconciliation_status === 'failed' ? 'wf-admin-badge--danger' : 'wf-admin-badge--muted' }}">
                            {{ $st->reconciliation_status ?? '-' }}
                        </span>
                    </div>
                    <div class="mt-3 text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">File Rekonsiliasi</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $st->reconciliation_original_filename ?? '-' }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Total Trx</div>
                    <div class="wf-admin-stat__value">{{ number_format((int) ($st->no_of_debit + $st->no_of_credit)) }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Total Debit</div>
                    <div class="wf-admin-stat__value">{{ number_format((float) $st->tot_debit, 0, ',', '.') }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Total Kredit</div>
                    <div class="wf-admin-stat__value">{{ number_format((float) $st->tot_credit, 0, ',', '.') }}</div>
                </div>
                <div class="wf-admin-stat">
                    <div class="wf-admin-stat__label">Saldo Akhir</div>
                    <div class="wf-admin-stat__value">{{ number_format((float) $st->closing_balance, 0, ',', '.') }}</div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                @if($st->file_path)
                    <a href="{{ route('bank-statements.download', $st) }}" class="wf-btn-navy inline-flex items-center px-4 py-2 text-sm">
                        Download Statement
                    </a>
                @endif
                @if($st->reconciliation_file)
                    <a href="{{ route('bank-statements.reconciliation.download', $st) }}" class="wf-btn-gold inline-flex items-center px-4 py-2 text-sm">
                        Download Rekonsiliasi
                    </a>
                @endif
            </div>
        </div>
    </div>

    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60">
            <h3 class="text-base font-bold text-[var(--wf-navy)]">Transaksi Hasil Parsing (50 Terbaru)</h3>
        </div>
        <div class="p-6">
            <div class="wf-admin-table-wrap">
                <table class="wf-admin-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                            <th class="text-right">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)]">
                        @forelse($transactions as $tx)
                            <tr class="text-xs">
                                <td>{{ $tx->transaction_date ? $tx->transaction_date->format('d M Y') : '-' }}</td>
                                <td>{{ $tx->description ?? '-' }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $tx->debit_amount, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $tx->credit_amount, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums font-semibold">{{ number_format((float) $tx->balance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-8 text-sm text-[var(--wf-muted)] text-center" colspan="5">Belum ada transaksi hasil parsing.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60">
            <h3 class="text-base font-bold text-[var(--wf-navy)]">Item Rekonsiliasi (50 Terbaru)</h3>
        </div>
        <div class="p-6">
            <div class="wf-admin-table-wrap">
                <table class="wf-admin-table min-w-full text-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Deskripsi</th>
                            <th class="text-right">Debit</th>
                            <th class="text-right">Kredit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)]">
                        @forelse($reconciliationItems as $it)
                            <tr class="text-xs">
                                <td>{{ $it->date ? $it->date->format('d M Y') : '-' }}</td>
                                <td>{{ $it->description ?? '-' }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $it->debit, 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums">{{ number_format((float) $it->credit, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td class="py-8 text-sm text-[var(--wf-muted)] text-center" colspan="4">Belum ada item rekonsiliasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
