@extends('profile.layout')

@section('profile-page-title', 'Detail Laporan Keuangan')
@section('profile-page-subtitle', ($typeLabel ?? '-').' • '.($selectedMonthLabel ?? '-'))

@section('profile-content')
<div class="wf-profile-card overflow-hidden relative">
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <span class="absolute w-24 h-24 rounded-full -right-6 -top-8 bg-[rgba(201,162,39,0.12)]"></span>
        <span class="absolute w-16 h-16 rounded-full left-8 bottom-4 bg-[rgba(11,31,58,0.06)]"></span>
    </div>

    <div class="relative z-[1]">
        <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Detail</p>
                    <div class="mt-1 text-xl font-bold text-white">{{ $typeLabel ?? '-' }}</div>
                    <div class="mt-1 text-sm text-white/70">Periode: {{ $selectedMonthLabel ?? '-' }}</div>
                </div>
                <a href="{{ route('profile.financial-report', ['month' => $selectedMonth ?? now()->format('Y-m')]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-white/30 text-sm font-semibold text-white hover:bg-white/10 transition">
                    Kembali
                </a>
            </div>
        </div>

        <div class="p-6 space-y-6">
            <div class="rounded-xl border border-[var(--wf-line)] p-4 bg-white">
                <div class="text-sm font-semibold text-[var(--wf-muted)]">Total</div>
                <div class="mt-1 text-xl font-extrabold tabular-nums {{ ($kind ?? 'income') === 'income' ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format((int) ($total ?? 0), 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-[var(--wf-line)] overflow-hidden bg-white">
                <div class="px-4 py-3 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/70">
                    <div class="font-bold text-[var(--wf-navy)]">Rincian</div>
                </div>
                <div class="p-4">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-[var(--wf-muted)]">
                                <th class="py-2 w-28 font-semibold">Tanggal</th>
                                <th class="py-2 w-56 font-semibold">Nama</th>
                                <th class="py-2 font-semibold">Keterangan</th>
                                <th class="py-2 w-40 text-right font-semibold">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--wf-line)]">
                            @forelse (($rows ?? []) as $row)
                                <tr>
                                    <td class="py-2.5 text-[var(--wf-ink)]">{{ $row['date'] ?? '-' }}</td>
                                    <td class="py-2.5 text-[var(--wf-ink)]">{{ $row['prospect'] ?? '-' }}</td>
                                    <td class="py-2.5 text-left text-[var(--wf-ink)]">{{ $row['description'] ?? '-' }}</td>
                                    <td class="py-2.5 text-right text-[var(--wf-ink)] font-medium tabular-nums">
                                        Rp {{ number_format((int) ($row['amount'] ?? 0), 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-[var(--wf-muted)]">Tidak ada data pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
