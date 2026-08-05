@php
    $user = $user ?? auth()->user();
    $currentDate = $currentDate ?? now();
    $upcomingLeaves = $upcomingLeaves ?? collect();
    $recentLeaves = $recentLeaves ?? collect();
    $nextLeave = $nextLeave ?? null;
    $daysUntilNextLeave = $daysUntilNextLeave ?? null;
    $statusTranslations = $statusTranslations ?? [
        'approved' => 'Disetujui',
        'pending' => 'Menunggu',
        'rejected' => 'Ditolak',
    ];
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

    $statusBadge = fn (string $status) => match ($status) {
        'approved' => 'bg-[var(--wf-gold)]/15 text-[var(--wf-navy)] border border-[var(--wf-gold)]/30',
        'pending' => 'bg-[var(--wf-gold)]/10 text-[#92400e] border border-[var(--wf-gold)]/25',
        'rejected' => 'bg-[var(--wf-navy)]/8 text-[var(--wf-muted)] border border-[var(--wf-line)]',
        default => 'bg-[var(--wf-cream)] text-[var(--wf-muted)] border border-[var(--wf-line)]',
    };
@endphp

<div class="space-y-6">
    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-5 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Kalender HR</p>
                    <h3 class="mt-1 text-lg sm:text-xl font-bold text-white tracking-tight flex items-center gap-2">
                        <svg class="w-5 h-5 text-[var(--wf-gold)] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Acara Mendatang & Jadwal Cuti
                    </h3>
                    <p class="mt-1 text-sm text-white/65">Aktivitas mendatang dan rencana cuti Anda</p>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6 space-y-5">
            @if ($nextLeave)
                <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4 sm:p-5">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Cuti Terjadwal Berikutnya</p>
                            <p class="mt-1 text-base font-bold text-[var(--wf-navy)]">
                                {{ $leaveTypeTranslations[$nextLeave->leaveType->name ?? ''] ?? ($nextLeave->leaveType->name ?? 'Cuti') }}
                            </p>
                            <p class="mt-1 text-sm text-[var(--wf-muted)]">
                                {{ $nextLeave->start_date->locale('id')->translatedFormat('d F Y') }}
                                –
                                {{ $nextLeave->end_date->locale('id')->translatedFormat('d F Y') }}
                            </p>
                        </div>
                        <div class="rounded-2xl bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] px-5 py-3 text-center shrink-0 self-start sm:self-auto">
                            <div class="text-3xl font-bold text-[var(--wf-gold-soft)] leading-none">{{ $daysUntilNextLeave }}</div>
                            <div class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-white/60">hari lagi</div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($upcomingLeaves->isNotEmpty())
                <div>
                    <h5 class="font-bold text-[var(--wf-navy)] mb-3 flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m2-6v6a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2 6V9a2 2 0 012-2h2"></path>
                        </svg>
                        Jadwal Cuti Mendatang
                    </h5>
                    <div class="space-y-2">
                        @foreach ($upcomingLeaves as $leave)
                            @php
                                $daysFromNow = (int) $currentDate->diffInDays($leave->start_date, false);
                            @endphp
                            <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-4 hover:bg-[var(--wf-cream)]/50 transition">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-bold text-[var(--wf-navy)] text-sm truncate">
                                            {{ $leaveTypeTranslations[$leave->leaveType->name ?? ''] ?? ($leave->leaveType->name ?? 'N/A') }}
                                        </p>
                                        <p class="mt-1 text-xs text-[var(--wf-muted)]">
                                            {{ $leave->start_date->locale('id')->translatedFormat('d M') }}
                                            –
                                            {{ $leave->end_date->locale('id')->translatedFormat('d M Y') }}
                                            · {{ (int) $leave->total_days }} hari
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-1.5 shrink-0">
                                        <span class="px-2.5 py-1 text-[10px] font-bold rounded-full {{ $statusBadge($leave->status) }}">
                                            {{ $statusTranslations[$leave->status] ?? ucfirst($leave->status) }}
                                        </span>
                                        <span class="text-[10px] font-semibold text-[var(--wf-navy)] bg-[var(--wf-cream)] border border-[var(--wf-line)] px-2 py-0.5 rounded-full">
                                            dalam {{ $daysFromNow }} hari
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-dashed border-[var(--wf-line)] bg-[var(--wf-cream)]/70 px-6 py-10 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-white border border-[var(--wf-line)]">
                        <svg class="w-7 h-7 text-[var(--wf-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-[var(--wf-navy)]">Tidak Ada Acara Mendatang</h3>
                    <p class="mt-2 text-sm text-[var(--wf-muted)] max-w-sm mx-auto leading-relaxed">
                        Anda tidak memiliki jadwal cuti atau acara yang akan datang.
                    </p>
                    <a href="/leave/show"
                        class="wf-btn-navy inline-flex items-center mt-5 px-4 py-2.5 text-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Rencanakan Cuti Anda
                    </a>
                </div>
            @endif

            @if ($recentLeaves->isNotEmpty())
                <div class="pt-1">
                    <h5 class="font-bold text-[var(--wf-navy)] mb-3 flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Riwayat Cuti Terbaru
                    </h5>
                    <div class="space-y-2">
                        @foreach ($recentLeaves as $leave)
                            <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/40 p-3.5">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-[var(--wf-navy)] truncate">
                                            {{ $leaveTypeTranslations[$leave->leaveType->name ?? ''] ?? ($leave->leaveType->name ?? 'N/A') }}
                                        </p>
                                        <p class="mt-0.5 text-xs text-[var(--wf-muted)]">
                                            {{ $leave->start_date->locale('id')->translatedFormat('d M') }}
                                            –
                                            {{ $leave->end_date->locale('id')->translatedFormat('d M Y') }}
                                            · {{ (int) $leave->total_days }} hari
                                        </p>
                                    </div>
                                    <span class="px-2.5 py-1 text-[10px] font-bold rounded-full shrink-0 {{ $statusBadge($leave->status) }}">
                                        {{ $statusTranslations[$leave->status] ?? ucfirst($leave->status) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-3">
        <a href="/leave/show"
            class="wf-btn-gold w-full inline-flex items-center justify-center py-3.5 px-6 text-sm group">
            <svg class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform duration-200" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Ajukan Cuti Baru
        </a>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <a href="/admin/leave-requests"
                class="rounded-2xl border border-[var(--wf-line)] bg-white hover:bg-[var(--wf-cream)] text-[var(--wf-navy)] font-semibold py-3.5 px-4 transition flex items-center justify-center text-sm">
                <svg class="w-4 h-4 mr-2 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v6a2 2 0 002 2h2m2-6v6a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2m-2 6V9a2 2 0 012-2h2"></path>
                </svg>
                Lihat Semua Permintaan
            </a>
            <a href="/admin/leave-requests?tableFilters[status][value]=pending"
                class="rounded-2xl border border-[var(--wf-gold)]/35 bg-[var(--wf-gold)]/10 hover:bg-[var(--wf-gold)]/20 text-[var(--wf-navy)] font-semibold py-3.5 px-4 transition flex items-center justify-center text-sm">
                <svg class="w-4 h-4 mr-2 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Menunggu Peninjauan
            </a>
        </div>
    </div>

    <div class="wf-profile-card p-5 sm:p-6">
        <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-3">
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--wf-cream)] border border-[var(--wf-line)]">
                <svg class="w-4 h-4 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </span>
            Tips Pengajuan Cuti
        </h3>
        <ul class="space-y-3 text-sm text-[var(--wf-ink)]">
            @foreach ([
                'Ajukan permohonan minimal 3 hari sebelumnya',
                'Sertakan alasan yang jelas dan detail',
                'Periksa saldo cuti sebelum mengajukan',
                'Upload dokumen pendukung jika diperlukan',
                'Tentukan karyawan pengganti untuk kelancaran kerja',
                'Hubungi HR untuk situasi darurat',
            ] as $tip)
                <li class="flex items-start gap-2.5">
                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)]">
                        <svg class="w-3 h-3 text-[var(--wf-gold-soft)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                    <span class="text-[var(--wf-muted)] leading-relaxed">{{ $tip }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
