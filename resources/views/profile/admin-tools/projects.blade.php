@extends('profile.layout')

@section('profile-page-title', 'Proyek Wedding')
@section('profile-page-subtitle', 'Daftar order/proyek wedding (khusus super admin)')

@push('styles')
<style>
    .wf-proj-seg {
        display: inline-flex;
        border: 1px solid var(--wf-line);
        border-radius: 999px;
        overflow: hidden;
        background: #fff;
    }

    .wf-proj-seg a {
        padding: 0.55rem 1rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--wf-muted);
        transition: background .15s ease, color .15s ease;
    }

    .wf-proj-seg a + a {
        border-left: 1px solid var(--wf-line);
    }

    .wf-proj-seg a.is-active {
        background: var(--wf-navy);
        color: #fff;
    }

    .wf-proj-seg a:not(.is-active):hover {
        background: var(--wf-cream);
        color: var(--wf-navy);
    }

    .wf-proj-select,
    .wf-proj-input {
        border: 1.5px solid var(--wf-line);
        border-radius: 999px;
        padding: 0.55rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--wf-navy);
        background: #fff;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .wf-proj-input {
        border-radius: 0.85rem;
        font-weight: 500;
        width: 100%;
    }

    .wf-proj-select:focus,
    .wf-proj-input:focus {
        border-color: rgba(201, 162, 39, 0.75);
        box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.18);
    }

    .wf-proj-stat {
        position: relative;
        overflow: hidden;
        border: 1px solid var(--wf-line);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
        background: #fff;
        height: 100%;
    }

    .wf-proj-stat::before {
        content: '';
        position: absolute;
        width: 4.5rem;
        height: 4.5rem;
        top: -1.4rem;
        right: -1.2rem;
        border-radius: 40% 60% 55% 45% / 50% 40% 60% 50%;
        background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.18), transparent 70%);
        pointer-events: none;
    }

    .wf-proj-stat__label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--wf-muted);
    }

    .wf-proj-stat__value {
        margin-top: 0.35rem;
        font-size: 0.95rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        font-variant-numeric: tabular-nums;
        color: var(--wf-navy);
    }

    .wf-proj-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: capitalize;
        border: 1px solid var(--wf-line);
        background: var(--wf-cream);
        color: var(--wf-navy);
    }

    .wf-proj-badge--done {
        background: rgba(16, 185, 129, 0.12);
        border-color: rgba(16, 185, 129, 0.25);
        color: #047857;
    }

    .wf-proj-badge--processing {
        background: rgba(201, 162, 39, 0.15);
        border-color: rgba(201, 162, 39, 0.35);
        color: #8a6d12;
    }

    .wf-proj-badge--cancelled {
        background: rgba(244, 63, 94, 0.1);
        border-color: rgba(244, 63, 94, 0.25);
        color: #be123c;
    }

    .wf-proj-badge--pending {
        background: rgba(11, 31, 58, 0.06);
        border-color: var(--wf-line);
        color: var(--wf-muted);
    }
</style>
@endpush

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
            <span class="absolute w-14 h-14 rounded-full left-8 -bottom-6 bg-[rgba(255,255,255,0.08)]"></span>
            <span class="absolute w-9 h-9 rounded-[0.55rem] right-28 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
        </div>
        <div class="relative z-[1] flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Proyek</p>
                <div class="mt-1 text-xl font-bold text-white">Daftar Proyek Wedding</div>
                <div class="mt-1 text-sm text-white/70">Filter periode dan cari proyek berdasarkan nama atau nomor.</div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <div class="wf-proj-seg">
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'all', 'month' => null]) }}"
                        class="{{ ($period ?? 'all') === 'all' ? 'is-active' : '' }}">Semua</a>
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'year', 'month' => null]) }}"
                        class="{{ ($period ?? 'all') === 'year' ? 'is-active' : '' }}">Tahun berjalan</a>
                    <a href="{{ request()->fullUrlWithQuery(['period' => 'month', 'month' => null]) }}"
                        class="{{ ($period ?? 'all') === 'month' ? 'is-active' : '' }}">Bulan berjalan</a>
                </div>

                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="q" value="{{ $q }}">
                    <input type="hidden" name="period" value="custom">
                    @php
                        $monthOptions = collect(range(0, 23))
                            ->map(fn ($i) => now()->startOfMonth()->subMonths($i))
                            ->reverse()
                            ->map(fn ($d) => [
                                'value' => $d->format('Y-m'),
                                'label' => $d->locale('id')->translatedFormat('F Y'),
                            ]);
                    @endphp
                    <select name="month" class="wf-proj-select">
                        <option value="">Pilih bulan</option>
                        @foreach($monthOptions as $opt)
                            <option value="{{ $opt['value'] }}" @selected(($month ?? '') === $opt['value'])>
                                {{ $opt['label'] }}
                            </option>
                        @endforeach
                    </select>
                    <button type="submit" class="wf-btn-gold inline-flex items-center justify-center h-10 px-4 text-xs shrink-0">
                        Terapkan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="p-6 space-y-5">
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div class="wf-proj-stat">
                <div class="wf-proj-stat__label">Total Proyek</div>
                <div class="wf-proj-stat__value">{{ number_format((int) $projectsCount) }}</div>
            </div>
            <div class="wf-proj-stat">
                <div class="wf-proj-stat__label">Nilai Proyek</div>
                <div class="wf-proj-stat__value">{{ number_format((int) $grandTotalSum, 0, ',', '.') }}</div>
            </div>
            <div class="wf-proj-stat">
                <div class="wf-proj-stat__label">Pengeluaran</div>
                <div class="wf-proj-stat__value">{{ number_format((int) $expensesSum, 0, ',', '.') }}</div>
            </div>
            <div class="wf-proj-stat">
                <div class="wf-proj-stat__label">Keuntungan</div>
                <div class="wf-proj-stat__value {{ (int) $profitSum >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ number_format((int) $profitSum, 0, ',', '.') }}
                </div>
            </div>
            <div class="wf-proj-stat">
                <div class="wf-proj-stat__label">Rata-rata</div>
                <div class="wf-proj-stat__value {{ (int) $profitAvg >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    {{ number_format((int) $profitAvg, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / nomor / no kontrak"
                class="wf-proj-input">
            <button type="submit" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm shrink-0">
                Cari
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                        <th class="py-3 px-4 font-semibold">Nama</th>
                        <th class="py-3 px-4 font-semibold">PIC</th>
                        <th class="py-3 px-4 font-semibold">Keuntungan</th>
                        <th class="py-3 px-4 font-semibold">Status</th>
                        <th class="py-3 px-4 font-semibold"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($projects as $order)
                        @php
                            $profit = (int) $order->laba_kotor;
                            $status = $order->status?->value ?? (string) $order->status;
                            $statusKey = strtolower(trim($status));
                            $badgeClass = match (true) {
                                in_array($statusKey, ['done', 'selesai', 'completed'], true) => 'wf-proj-badge--done',
                                in_array($statusKey, ['processing', 'proses', 'in_progress', 'ongoing'], true) => 'wf-proj-badge--processing',
                                in_array($statusKey, ['cancelled', 'canceled', 'batal'], true) => 'wf-proj-badge--cancelled',
                                in_array($statusKey, ['pending', 'menunggu'], true) => 'wf-proj-badge--pending',
                                default => '',
                            };
                        @endphp
                        <tr class="text-[var(--wf-ink)] hover:bg-[var(--wf-cream)]/40 transition-colors">
                            <td class="py-3.5 px-4 text-xs font-medium">
                                <a href="{{ route('profile.admin-tools.projects.show', $order) }}"
                                    class="font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)] transition">
                                    {{ $order->name }}
                                </a>
                                <div class="mt-0.5 text-[11px] text-[var(--wf-muted)]">
                                    {{ $order->prospect?->venue ?? '-' }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-xs">
                                <div class="font-semibold text-[var(--wf-ink)]">{{ $order->employee?->name ?? '-' }}</div>
                                <div class="text-[11px] text-[var(--wf-muted)]">{{ $order->user?->name ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="text-xs font-bold tabular-nums {{ $profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ number_format($profit, 0, ',', '.') }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="wf-proj-badge {{ $badgeClass }}">
                                    {{ $status !== '' ? $status : '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('profile.admin-tools.projects.show', $order) }}"
                                    class="inline-flex items-center px-3.5 py-1.5 rounded-full border border-[var(--wf-line)] bg-white text-[var(--wf-navy)] text-xs font-bold hover:border-[var(--wf-gold)] hover:text-[var(--wf-gold)] transition">
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 px-4 text-center text-sm text-[var(--wf-muted)]">
                                Tidak ada proyek pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-1">
            {{ $projects->links() }}
        </div>
    </div>
</div>
@endsection
