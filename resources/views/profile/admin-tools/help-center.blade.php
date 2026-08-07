@extends('profile.layout')

@section('profile-page-title', 'Pusat Bantuan')
@section('profile-page-subtitle', 'Akses cepat ke panduan dan dokumentasi')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'Bantuan',
        'title' => 'Pusat Bantuan',
        'subtitle' => 'Akses cepat ke panduan internal dan dokumentasi publik.',
    ])

    <div class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="{{ route('profile.admin-tools.documentations') }}" class="wf-portal-tile block rounded-2xl border border-[var(--wf-line)] bg-white p-5 hover:border-[var(--wf-gold)] transition">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] text-[var(--wf-gold-soft)] mb-3">
                    <i class="fa-solid fa-book"></i>
                </span>
                <div class="font-bold text-[var(--wf-navy)]">Dokumentasi Internal</div>
                <div class="mt-1 text-xs font-medium text-[var(--wf-muted)]">Kelola artikel panduan dan catatan operasional</div>
            </a>

            <a href="/docs" class="wf-portal-tile block rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5 hover:border-[var(--wf-gold)] transition">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[rgba(201,162,39,0.15)] text-[var(--wf-navy)] mb-3">
                    <i class="fa-solid fa-globe"></i>
                </span>
                <div class="font-bold text-[var(--wf-navy)]">Docs Publik</div>
                <div class="mt-1 text-xs font-medium text-[var(--wf-muted)]">Halaman dokumentasi publik aplikasi</div>
            </a>
        </div>
    </div>
</div>
@endsection
