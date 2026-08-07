@extends('profile.layout')

@section('profile-page-title', 'Laporan Keuangan')
@section('profile-page-subtitle', 'Ringkasan pemasukan dan pengeluaran per bulan')

@push('styles')
<style>
    .wf-finance-stat {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--wf-line);
        border-radius: 1.15rem;
        padding: 1.15rem 1.25rem;
        background: #fff;
        height: 100%;
        transition: transform .2s ease, box-shadow .2s ease;
    }

    .wf-finance-stat::before {
        content: '';
        position: absolute;
        width: 5rem;
        height: 5rem;
        top: -1.5rem;
        right: -1.25rem;
        border-radius: 40% 60% 55% 45% / 50% 40% 60% 50%;
        background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.18), transparent 70%);
        pointer-events: none;
    }

    .wf-finance-stat:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 32px -22px rgba(11, 31, 58, 0.35);
    }

    .wf-finance-stat__label {
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--wf-muted);
        letter-spacing: 0.02em;
    }

    .wf-finance-stat__value {
        margin-top: 0.35rem;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
    }

    .wf-finance-panel {
        position: relative;
        border: 1px solid var(--wf-line);
        border-radius: 1.15rem;
        overflow: hidden;
        background: #fff;
        height: 100%;
    }

    .wf-finance-panel__head {
        padding: 0.9rem 1.15rem;
        font-weight: 700;
        border-bottom: 1px solid var(--wf-line);
    }

    .wf-finance-panel__head--in {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(201, 162, 39, 0.08));
        color: #047857;
    }

    .wf-finance-panel__head--out {
        background: linear-gradient(135deg, rgba(244, 63, 94, 0.1), rgba(11, 31, 58, 0.04));
        color: #be123c;
    }

    .wf-finance-select {
        border: 1.5px solid var(--wf-line);
        border-radius: 999px;
        padding: 0.55rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--wf-navy);
        background: #fff;
        outline: none;
        min-width: 11rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .wf-finance-select:focus {
        border-color: rgba(201, 162, 39, 0.75);
        box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.18);
    }

    .wf-finance-note {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--wf-line);
        border-radius: 1rem;
        padding: 1rem 1.15rem;
        background: linear-gradient(135deg, var(--wf-cream), #fff);
        color: var(--wf-muted);
        font-size: 0.875rem;
    }

    .wf-finance-note::after {
        content: '';
        position: absolute;
        width: 3.5rem;
        height: 3.5rem;
        right: -0.75rem;
        bottom: -0.75rem;
        border-radius: 999px;
        border: 2px solid rgba(201, 162, 39, 0.22);
        pointer-events: none;
    }
</style>
@endpush

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
            <span class="absolute w-16 h-16 rounded-full left-10 -bottom-8 bg-[rgba(255,255,255,0.08)]"></span>
            <span class="absolute w-10 h-10 rounded-[0.6rem] right-24 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
        </div>
        <div class="relative z-[1] flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Laporan</p>
                <div class="mt-1 text-xl font-bold text-white">Periode: {{ $selectedMonthLabel ?? '-' }}</div>
                <div class="mt-1 text-sm text-white/70">Gunakan filter bulan untuk melihat laporan per periode.</div>
            </div>
            <form method="GET" action="{{ route('profile.financial-report') }}" class="flex items-center gap-3">
                <label for="month" class="text-sm font-semibold text-white/80">Bulan</label>
                <select id="month" name="month" class="wf-finance-select" onchange="this.form.submit()">
                    @foreach (($availableMonths ?? []) as $opt)
                        <option value="{{ $opt['value'] ?? '' }}" {{ ($selectedMonth ?? '') === ($opt['value'] ?? '') ? 'selected' : '' }}>
                            {{ $opt['label'] ?? ($opt['value'] ?? '-') }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="wf-finance-stat">
                <div class="wf-finance-stat__label">Total Pemasukan</div>
                <div class="wf-finance-stat__value text-emerald-600">
                    Rp {{ number_format((int) ($totalIncome ?? 0), 0, ',', '.') }}
                </div>
            </div>
            <div class="wf-finance-stat">
                <div class="wf-finance-stat__label">Total Pengeluaran</div>
                <div class="wf-finance-stat__value text-rose-600">
                    Rp {{ number_format((int) ($totalExpense ?? 0), 0, ',', '.') }}
                </div>
            </div>
            <div class="wf-finance-stat">
                <div class="wf-finance-stat__label">Net Cash Flow</div>
                @php $ncf = (int) ($netCashFlow ?? 0); @endphp
                <div class="wf-finance-stat__value {{ $ncf >= 0 ? 'text-[var(--wf-navy)]' : 'text-rose-700' }}">
                    Rp {{ number_format($ncf, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="wf-finance-panel">
                <div class="wf-finance-panel__head wf-finance-panel__head--in">Pemasukan</div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[var(--wf-muted)]">
                                <th class="py-2 font-semibold">Keterangan</th>
                                <th class="py-2 text-right font-semibold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--wf-line)]">
                            @foreach (($incomeItems ?? []) as $row)
                                <tr>
                                    <td class="py-2.5 text-[var(--wf-ink)]">{{ $row['label'] ?? '-' }}</td>
                                    <td class="py-2.5 text-right text-[var(--wf-ink)] font-medium tabular-nums">
                                        @if (! empty($row['type']))
                                            <a href="{{ route('profile.financial-report.detail', ['type' => $row['type'], 'month' => $selectedMonth ?? now()->format('Y-m')]) }}"
                                                class="text-[var(--wf-navy)] font-semibold hover:text-[var(--wf-gold)]">
                                                Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                            </a>
                                        @else
                                            Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-bold">
                                <td class="py-3 text-[var(--wf-navy)]">Total</td>
                                <td class="py-3 text-right text-emerald-700 tabular-nums">
                                    Rp {{ number_format((int) ($totalIncome ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="wf-finance-panel">
                <div class="wf-finance-panel__head wf-finance-panel__head--out">Pengeluaran</div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[var(--wf-muted)]">
                                <th class="py-2 font-semibold">Keterangan</th>
                                <th class="py-2 text-right font-semibold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--wf-line)]">
                            @foreach (($expenseItems ?? []) as $row)
                                <tr>
                                    <td class="py-2.5 text-[var(--wf-ink)]">{{ $row['label'] ?? '-' }}</td>
                                    <td class="py-2.5 text-right text-[var(--wf-ink)] font-medium tabular-nums">
                                        @if (! empty($row['type']))
                                            <a href="{{ route('profile.financial-report.detail', ['type' => $row['type'], 'month' => $selectedMonth ?? now()->format('Y-m')]) }}"
                                                class="text-[var(--wf-navy)] font-semibold hover:text-[var(--wf-gold)]">
                                                Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                            </a>
                                        @else
                                            Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="font-bold">
                                <td class="py-3 text-[var(--wf-navy)]">Total</td>
                                <td class="py-3 text-right text-rose-700 tabular-nums">
                                    Rp {{ number_format((int) ($totalExpense ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="wf-finance-note">
            Net cash flow dihitung dari <strong class="text-[var(--wf-navy)]">Total Pemasukan − Total Pengeluaran</strong>.
        </div>
    </div>
</div>
@endsection
