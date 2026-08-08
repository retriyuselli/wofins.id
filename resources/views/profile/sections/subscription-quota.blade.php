@php
    $showSubscriptionQuota = $showSubscriptionQuota ?? false;
    $subscriptionIsSuperAdmin = $subscriptionIsSuperAdmin ?? false;
@endphp

@if ($showSubscriptionQuota)
    <div class="wf-profile-card mt-6">
        <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] rounded-t-[inherit]">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    @if ($subscriptionIsSuperAdmin)
                        <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Super Admin</p>
                    @else
                        <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Paket Tim Anda</p>
                    @endif
                    <h2 class="mt-1 text-lg sm:text-xl font-bold text-white">Paket & Matriks Kuota</h2>
                    <p class="mt-1 text-sm text-white/65">
                        {{ $subscriptionPlanLabel ?? 'Paket belum diatur' }}
                        @if (! ($subscriptionConfigured ?? false))
                            <span class="text-[var(--wf-gold-soft)]">· lihat Admin → Perusahaan</span>
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('harga') }}"
                       class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white hover:bg-white/15 transition">
                        Lihat Harga
                    </a>
                    @if ($subscriptionIsSuperAdmin)
                        @php
                            $companyEditUrl = null;
                            try {
                                $companyEditUrl = \App\Filament\Resources\Companies\CompanyResource::getUrl('index');
                            } catch (\Throwable) {
                                $companyEditUrl = url('/admin');
                            }
                        @endphp
                        <a href="{{ $companyEditUrl }}"
                           class="inline-flex items-center gap-2 rounded-full bg-[var(--wf-gold)] px-4 py-2 text-xs font-extrabold text-[var(--wf-navy-deep)] hover:brightness-105 transition">
                            Kelola Paket
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6 space-y-8">
            {{-- Kuota resource --}}
            <div>
                <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Penggunaan kuota
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach ($subscriptionQuotaRows ?? [] as $row)
                        @php
                            $barColor = $row['full']
                                ? 'bg-red-500'
                                : ($row['percent'] >= 80 ? 'bg-amber-500' : 'bg-[var(--wf-navy)]');
                        @endphp
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/60 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">{{ $row['label'] }}</p>
                                    <p class="mt-1 text-2xl font-extrabold text-[var(--wf-navy)] tabular-nums">
                                        {{ $row['used'] }}
                                        <span class="text-sm font-semibold text-[var(--wf-muted)]">
                                            / {{ $row['limit'] === null ? '∞' : $row['limit'] }}
                                        </span>
                                    </p>
                                </div>
                                @if ($row['full'])
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-red-700">Penuh</span>
                                @elseif ($row['limit'] === null)
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Tak terbatas</span>
                                @else
                                    <span class="rounded-full bg-white border border-[var(--wf-line)] px-2.5 py-1 text-[10px] font-bold text-[var(--wf-navy)]">
                                        Sisa {{ $row['remaining'] }}
                                    </span>
                                @endif
                            </div>

                            <div class="mt-3 h-2 rounded-full bg-white border border-[var(--wf-line)] overflow-hidden">
                                <div class="h-full {{ $barColor }} transition-all"
                                     style="width: {{ $row['limit'] === null ? '8' : $row['percent'] }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-[var(--wf-muted)]">{{ $row['summary'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Matriks fitur --}}
            <div>
                <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Matriks fitur paket
                </h3>

                <div class="rounded-2xl border border-[var(--wf-line)] overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 sm:auto-rows-fr">
                        @foreach ($subscriptionFeatureMatrix ?? [] as $feature)
                            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-[var(--wf-line)] sm:border-r last:border-b-0">
                                <span class="text-sm text-[var(--wf-ink)]">{{ $feature['label'] }}</span>
                                @if ($feature['allowed'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                        <i class="fa-solid fa-check text-[10px]"></i> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">
                                        <i class="fa-solid fa-lock text-[10px]"></i> Terkunci
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <p class="mt-3 text-xs text-[var(--wf-muted)] leading-relaxed">
                    @if ($subscriptionIsSuperAdmin)
                        Matriks di atas mengikuti <strong class="text-[var(--wf-navy)]">paket perusahaan</strong>
                        (untuk user biasa). Sebagai <strong class="text-[var(--wf-navy)]">super admin</strong>,
                        Anda tetap bisa mengakses semua fitur dan melebihi kuota bila diperlukan.
                    @else
                        Angka kuota dihitung dari data <strong class="text-[var(--wf-navy)]">tim paket Anda</strong>
                        (bukan seluruh platform). Fitur terkunci bisa dibuka dengan upgrade paket.
                    @endif
                </p>
            </div>
        </div>
    </div>
@endif
