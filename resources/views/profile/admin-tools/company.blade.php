@extends('profile.layout')

@section('profile-page-title', 'Pengaturan Perusahaan')
@section('profile-page-subtitle', 'Profil perusahaan & paket langganan Anda')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
            <span class="absolute w-14 h-14 rounded-full left-8 -bottom-6 bg-[rgba(255,255,255,0.08)]"></span>
            <span class="absolute w-9 h-9 rounded-[0.55rem] right-28 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
        </div>
        <div class="relative z-[1] flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Perusahaan</p>
                <div class="mt-1 text-xl font-bold text-white">
                    {{ $company?->company_name ?? 'Pengaturan Perusahaan' }}
                </div>
                <div class="mt-1 text-sm text-white/70">Ringkasan profil dan paket langganan.</div>
            </div>
            @if(! empty($filamentEditUrl))
                <a href="{{ $filamentEditUrl }}" class="wf-btn-gold inline-flex items-center justify-center px-4 py-2 text-sm shrink-0">
                    Edit di Admin
                </a>
            @endif
        </div>
    </div>

    <div class="p-6">
        @if(! $company)
            <div class="rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/50 px-4 py-6 text-sm text-[var(--wf-muted)] text-center">
                Belum ada data perusahaan. Setelah akun di-Approve, Company akan dibuat otomatis.
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Nama Perusahaan</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $company->company_name }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-gold)]/40 bg-[rgba(201,162,39,0.12)] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Paket</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $planLabel ?? '—' }}</div>
                    <div class="mt-1 text-xs text-[var(--wf-muted)]">{{ $seatSummary ?? '' }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Owner</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $company->owner_name ?? '-' }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Email</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)] break-all">{{ $company->email ?? '-' }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Telepon</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $company->phone ?? '-' }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4 md:col-span-2">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Alamat</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ $company->address ?? '-' }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Website</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)] break-all">
                        @if(! empty($company->website))
                            <a href="{{ $company->website }}" target="_blank" rel="noopener"
                                class="hover:text-[var(--wf-gold)] transition">{{ $company->website }}</a>
                        @else
                            -
                        @endif
                    </div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Updated</div>
                    <div class="mt-1 font-bold text-[var(--wf-navy)]">{{ optional($company->updated_at)->diffForHumans() }}</div>
                </div>

                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Logo</div>
                    @if ($company->logo_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->logo_url))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_url) }}"
                            alt="Logo" class="h-12 w-auto mt-2 rounded-lg border border-[var(--wf-line)] bg-white p-1.5">
                    @else
                        <div class="mt-1 font-bold text-[var(--wf-muted)]">-</div>
                    @endif
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Favicon</div>
                    @if ($company->favicon_url && \Illuminate\Support\Facades\Storage::disk('public')->exists($company->favicon_url))
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->favicon_url) }}"
                            alt="Favicon" class="h-10 w-10 mt-2 rounded-lg border border-[var(--wf-line)] bg-white p-1">
                    @else
                        <div class="mt-1 font-bold text-[var(--wf-muted)]">-</div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
