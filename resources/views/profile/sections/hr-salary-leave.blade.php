@php
    $user = $user ?? auth()->user();
    $latestPayroll = $latestPayroll ?? null;
    $currentYear = $currentYear ?? (int) date('Y');
    $period = $period ?? request()->get('period', 'year');
    $leaveStats = $leaveStats ?? ['approved' => 0, 'pending' => 0, 'rejected' => 0];
    $leaveByType = $leaveByType ?? collect();
    $annualLeaveAllowance = $annualLeaveAllowance ?? ($user?->annual_leave_quota ?? 12);
    $usedLeave = $usedLeave ?? ($leaveStats['approved'] ?? 0);
    $remainingLeave = $remainingLeave ?? max(0, $annualLeaveAllowance - $usedLeave);
    $prevYear = $prevYear ?? ((int) $currentYear - 1);
    $prevUsedLeave = $prevUsedLeave ?? 0;
    $prevUsagePercentage = $prevUsagePercentage ?? 0;
    $carryOver = $carryOver ?? 0;
    $effectiveAllowanceYear = $effectiveAllowanceYear ?? $annualLeaveAllowance;
    $leaveTypeTranslations = $leaveTypeTranslations ?? [
        'Annual Leave' => 'Cuti Tahunan',
        'Sick Leave' => 'Cuti Sakit',
        'Emergency Leave' => 'Cuti Darurat',
        'Unpaid Leave' => 'Cuti Tanpa Gaji',
        'Maternity Leave' => 'Cuti Melahirkan',
        'Paternity Leave' => 'Cuti Ayah',
        'Marriage Leave' => 'Cuti Menikah',
        'Bereavement Leave' => 'Cuti Duka',
    ];

    $displayAllowance = $period === 'year' ? $effectiveAllowanceYear : $annualLeaveAllowance;
    $usagePercentage = $displayAllowance > 0 ? round(($usedLeave / $displayAllowance) * 100, 1) : 0;
    $displayUsagePercentage = min(100, $usagePercentage);
    $remainingPercentage = max(0, 100 - $displayUsagePercentage);

    if ($usagePercentage > 100) {
        $barTone = 'over';
    } elseif ($usagePercentage <= 50) {
        $barTone = 'good';
    } elseif ($usagePercentage <= 80) {
        $barTone = 'warn';
    } else {
        $barTone = 'critical';
    }
@endphp

<div class="space-y-6">
    {{-- Hero strip --}}
    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-5 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Ringkasan HR</p>
                <h3 class="text-lg sm:text-xl font-bold text-white mt-1">Kompensasi & Manajemen Cuti {{ $currentYear }}</h3>
            </div>
            <div class="inline-flex items-center gap-2 self-start sm:self-auto rounded-full bg-white/10 border border-white/15 px-3 py-1.5 text-xs font-semibold text-white/90">
                <span class="w-1.5 h-1.5 rounded-full bg-[var(--wf-gold)]"></span>
                Tahun {{ $currentYear }}
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8">
                {{-- SALARY --}}
                <section class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-base font-bold text-[var(--wf-navy)] flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--wf-cream)] border border-[var(--wf-line)]">
                                <svg class="w-4 h-4 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </span>
                            Informasi Gaji
                        </h4>
                        @if($latestPayroll)
                            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[var(--wf-gold)]/15 text-[var(--wf-navy)] border border-[var(--wf-gold)]/30">
                                Aktif
                            </span>
                        @endif
                    </div>

                    @if($latestPayroll)
                        <div class="space-y-3">
                            <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">Gaji Bulanan</p>
                                        <p class="mt-1 text-2xl font-bold text-[var(--wf-navy)] tracking-tight">
                                            {{ $latestPayroll->formatted_monthly_salary_with_prefix }}
                                        </p>
                                    </div>
                                    <div class="h-10 w-10 rounded-full bg-white border border-[var(--wf-line)] flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">Gaji Tahunan</p>
                                        <p class="mt-1 text-2xl font-bold text-[var(--wf-navy)] tracking-tight">
                                            {{ $latestPayroll->formatted_calculated_annual_salary_with_prefix }}
                                        </p>
                                        <p class="mt-1 text-xs text-[var(--wf-muted)]">Gaji Pokok + Tunjangan × 12</p>
                                    </div>
                                    <div class="h-10 w-10 rounded-full bg-[var(--wf-cream)] border border-[var(--wf-line)] flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 text-[var(--wf-navy)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="rounded-xl border border-[var(--wf-line)] bg-white p-4 text-center">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-[var(--wf-muted)]">Bonus</p>
                                    <p class="mt-1 text-base font-bold text-[var(--wf-navy)]">
                                        {{ $latestPayroll->formatted_bonus_with_prefix }}
                                    </p>
                                </div>
                                <div class="rounded-xl border border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] p-4 text-center">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-white/65">Total Kompensasi</p>
                                    <p class="mt-1 text-base font-bold text-[var(--wf-gold-soft)]">
                                        {{ $latestPayroll->formatted_total_compensation_with_prefix }}
                                    </p>
                                </div>
                            </div>

                            <div class="rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/60 px-4 py-3 space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[var(--wf-muted)]">Terakhir diperbarui</span>
                                    <span class="font-semibold text-[var(--wf-navy)]">{{ $latestPayroll->updated_at->format('d F Y') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-[var(--wf-muted)]">Periode gaji</span>
                                    <span class="font-semibold text-[var(--wf-navy)]">{{ $latestPayroll->period_name }}</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-[var(--wf-line)] bg-[var(--wf-cream)]/70 px-6 py-10 text-center">
                            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white border border-[var(--wf-line)]">
                                <svg class="w-7 h-7 text-[var(--wf-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-[var(--wf-navy)]">Data Gaji Tidak Tersedia</h3>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] max-w-xs mx-auto leading-relaxed">
                                Informasi gaji Anda belum diatur. Hubungi Departemen HR untuk pengaturan detail gaji.
                            </p>
                        </div>
                    @endif
                </section>

                {{-- LEAVE --}}
                <section class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="text-base font-bold text-[var(--wf-navy)] flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--wf-cream)] border border-[var(--wf-line)]">
                                <svg class="w-4 h-4 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            Manajemen Cuti {{ $currentYear }}
                        </h4>
                    </div>

                    {{-- Balance --}}
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                            <h5 class="font-bold text-[var(--wf-navy)]">Saldo Cuti Tahunan</h5>
                            <span class="text-xs font-semibold text-[var(--wf-muted)]">
                                {{ $usedLeave }} digunakan / {{ $displayAllowance }} total
                            </span>
                        </div>

                        <div class="h-3 w-full rounded-full bg-white border border-[var(--wf-line)] overflow-hidden relative">
                            @if($usagePercentage > 100)
                                <div class="absolute inset-0 bg-[#b45309]"></div>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-[10px] font-bold text-white tracking-wide">MELEBIHI KUOTA</span>
                                </div>
                            @else
                                @if($displayUsagePercentage > 0)
                                    <div class="absolute left-0 top-0 h-full transition-all duration-700
                                        {{ $barTone === 'good' ? 'bg-[var(--wf-navy)]/35' : ($barTone === 'warn' ? 'bg-[var(--wf-gold)]/70' : 'bg-[#b45309]/70') }}"
                                         style="width: {{ $displayUsagePercentage }}%"></div>
                                @endif
                                @if($remainingPercentage > 0)
                                    <div class="absolute right-0 top-0 h-full transition-all duration-700
                                        {{ $barTone === 'good' ? 'bg-[var(--wf-gold)]' : ($barTone === 'warn' ? 'bg-[var(--wf-navy)]' : 'bg-[var(--wf-navy-deep)]') }}"
                                         style="width: {{ $remainingPercentage }}%"></div>
                                @endif
                            @endif
                        </div>

                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm">
                            <div class="flex items-center gap-2">
                                @if($usagePercentage > 100)
                                    <span class="h-2.5 w-2.5 rounded-full bg-[#b45309]"></span>
                                    <span class="font-bold text-[#92400e]">Melebihi {{ $usedLeave - $displayAllowance }} hari</span>
                                @else
                                    <span class="h-2.5 w-2.5 rounded-full bg-[var(--wf-gold)]"></span>
                                    <span class="font-semibold text-[var(--wf-navy)]">{{ $remainingLeave }} hari tersisa</span>
                                @endif
                            </div>
                            <span class="font-bold text-[var(--wf-navy)]">{{ number_format($usagePercentage) }}% digunakan</span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-[var(--wf-line)] flex flex-wrap justify-between gap-2 text-sm">
                            <span class="text-[var(--wf-muted)]">Digunakan: <span class="font-semibold text-[var(--wf-navy)]">{{ $usedLeave }}/{{ $displayAllowance }} hari</span></span>
                            @if($usagePercentage > 100)
                                <span class="font-bold text-[#92400e]">Melebihi kuota</span>
                            @elseif($remainingLeave > 0)
                                <span class="font-bold text-[var(--wf-navy)]">{{ $remainingLeave }} hari tersedia</span>
                            @else
                                <span class="font-bold text-[#92400e]">Tidak ada hari tersisa</span>
                            @endif
                        </div>
                    </div>

                    {{-- Previous year --}}
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5">
                        <h5 class="font-bold text-[var(--wf-navy)] mb-3 flex items-center gap-2 text-sm">
                            <svg class="w-4 h-4 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Cuti Tahun Sebelumnya ({{ $prevYear }})
                        </h5>
                        <div class="grid grid-cols-3 gap-2 sm:gap-3">
                            <div class="rounded-xl bg-[var(--wf-cream)] border border-[var(--wf-line)] p-3 text-center">
                                <div class="text-xl font-bold text-[var(--wf-navy)]">{{ $prevUsedLeave }}</div>
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-[var(--wf-muted)] mt-0.5">Digunakan</div>
                            </div>
                            <div class="rounded-xl bg-[var(--wf-cream)] border border-[var(--wf-line)] p-3 text-center">
                                <div class="text-xl font-bold text-[var(--wf-navy)]">{{ $annualLeaveAllowance }}</div>
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-[var(--wf-muted)] mt-0.5">Kuota</div>
                            </div>
                            <div class="rounded-xl bg-[var(--wf-navy)] p-3 text-center">
                                <div class="text-xl font-bold text-[var(--wf-gold-soft)]">{{ number_format($prevUsagePercentage) }}%</div>
                                <div class="text-[10px] font-semibold uppercase tracking-wide text-white/60 mt-0.5">Persentase</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-start gap-2 rounded-xl bg-[var(--wf-gold)]/10 border border-[var(--wf-gold)]/25 px-3 py-2.5 text-xs text-[var(--wf-navy)] leading-relaxed">
                            <svg class="w-4 h-4 text-[var(--wf-gold)] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Sisa cuti tahun sebelumnya akan hangus jika tidak digunakan sampai akhir Februari {{ $currentYear }}.</span>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="wf-profile-card p-5 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div>
                <h4 class="text-base font-bold text-[var(--wf-navy)]">Statistik Pengajuan</h4>
                <p class="text-xs text-[var(--wf-muted)] mt-0.5">Ringkasan status cuti berdasarkan periode</p>
            </div>
            <div class="inline-flex rounded-full overflow-hidden border border-[var(--wf-line)] bg-white self-start">
                <a href="{{ request()->fullUrlWithQuery(['period' => 'year']) }}"
                   class="px-3.5 py-1.5 text-xs font-semibold transition {{ $period === 'year' ? 'bg-[var(--wf-navy)] text-white' : 'text-[var(--wf-muted)] hover:bg-[var(--wf-cream)] hover:text-[var(--wf-navy)]' }}">
                    Tahun berjalan
                </a>
                <a href="{{ request()->fullUrlWithQuery(['period' => 'last_year']) }}"
                   class="px-3.5 py-1.5 text-xs font-semibold border-l border-[var(--wf-line)] transition {{ $period === 'last_year' ? 'bg-[var(--wf-navy)] text-white' : 'text-[var(--wf-muted)] hover:bg-[var(--wf-cream)] hover:text-[var(--wf-navy)]' }}">
                    Tahun lalu
                </a>
                <a href="{{ request()->fullUrlWithQuery(['period' => 'all']) }}"
                   class="px-3.5 py-1.5 text-xs font-semibold border-l border-[var(--wf-line)] transition {{ $period === 'all' ? 'bg-[var(--wf-navy)] text-white' : 'text-[var(--wf-muted)] hover:bg-[var(--wf-cream)] hover:text-[var(--wf-navy)]' }}">
                    Semua tahun
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4 text-center">
                <div class="text-2xl font-bold text-[var(--wf-navy)]">{{ $leaveStats['approved'] }}</div>
                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">Disetujui</div>
            </div>
            <div class="rounded-2xl border border-[var(--wf-gold)]/35 bg-[var(--wf-gold)]/10 p-4 text-center">
                <div class="text-2xl font-bold text-[var(--wf-navy)]">{{ $leaveStats['pending'] }}</div>
                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-[var(--wf-gold)]">Menunggu</div>
            </div>
            <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-4 text-center">
                <div class="text-2xl font-bold text-[var(--wf-muted)]">{{ $leaveStats['rejected'] }}</div>
                <div class="mt-1 text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">Ditolak</div>
            </div>
        </div>

        @if($leaveByType->isNotEmpty())
            <div class="mt-5 pt-5 border-t border-[var(--wf-line)]">
                <h5 class="font-bold text-[var(--wf-navy)] mb-3 text-sm">Rincian berdasarkan Jenis</h5>
                <div class="space-y-1">
                    @foreach($leaveByType as $type => $days)
                        <div class="flex items-center justify-between py-2.5 px-3 rounded-xl hover:bg-[var(--wf-cream)] transition">
                            <span class="text-sm font-medium text-[var(--wf-ink)]">{{ $leaveTypeTranslations[$type] ?? $type }}</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[var(--wf-navy)] text-[var(--wf-gold-soft)]">
                                {{ $days }} hari
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
