@if ($proFeatureLocked ?? \App\Support\ProFeatures::locked(\App\Support\PricingPlans::FEATURE_EMPLOYEE_PORTAL))
    @php
        $planLabel = $subscriptionPlanLabel ?? \App\Support\CompanySubscription::planLabel();
    @endphp
    <div class="wf-profile-card mb-6 overflow-hidden border border-[var(--wf-gold)]/35">
        <div class="px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-3 bg-[rgba(201,162,39,0.10)]">
            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[var(--wf-gold)] text-[var(--wf-navy-deep)]">
                <i class="fa-solid fa-lock text-sm"></i>
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <p class="text-sm font-bold text-[var(--wf-navy)]">Fitur terkunci untuk {{ $planLabel }}</p>
                    <span class="wf-pro-badge">Upgrade</span>
                </div>
                <p class="mt-0.5 text-xs text-[var(--wf-muted)] leading-relaxed">
                    Anda dapat melihat tampilan fitur ini, tetapi aksi (absen, ajukan cuti, unduh laporan, dll.) dinonaktifkan.
                    Fitur HRIS tersedia mulai paket Professional.
                    <a href="{{ route('harga') }}" class="font-semibold text-[var(--wf-navy)] underline underline-offset-2 hover:text-[var(--wf-gold)]">Lihat paket</a>
                </p>
            </div>
        </div>
    </div>
@endif
