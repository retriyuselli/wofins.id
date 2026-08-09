@extends('profile.layout')

@section('title', 'Data Tersimpan - '.(Auth::user()->name ?? 'WOFINS'))
@section('profile-page-title', 'Terima Kasih')
@section('profile-page-subtitle', 'Data crew freelance berhasil disimpan')

@section('profile-content')
@php
    $labelCompany = $tenantCompanyName ?? ($companyName ?? config('app.name'));
@endphp

<div class="wf-profile-card overflow-hidden">
    <div class="px-6 py-8 sm:px-10 border-b border-[var(--wf-line)] bg-gradient-to-r from-[var(--wf-navy)] to-[#14335a] relative overflow-hidden text-center">
        <div class="absolute -left-8 -top-10 w-40 h-40 rounded-full bg-[rgba(201,162,39,0.2)]" aria-hidden="true"></div>
        <div class="absolute -right-6 bottom-0 w-28 h-28 rounded-full border border-[rgba(232,212,139,0.28)]" aria-hidden="true"></div>
        <div class="relative z-10">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--wf-gold)] text-[var(--wf-navy-deep)]">
                <i class="fa-solid fa-check text-xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-white">Data berhasil disimpan</h2>
            <p class="mt-2 text-sm text-white/75 max-w-lg mx-auto">
                Terima kasih sudah melengkapi data untuk <span class="text-[var(--wf-gold-soft)] font-semibold">{{ $labelCompany }}</span>.
            </p>
        </div>
    </div>

    <div class="p-6 sm:p-8">
        <p class="text-sm text-[var(--wf-muted)] leading-relaxed">
            Tim admin akan meninjau data Anda. Jika ada yang perlu dilengkapi, kami akan menghubungi melalui email atau WhatsApp.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('data-pribadi.index') }}" class="wf-btn-navy inline-flex items-center justify-center px-5 py-3 text-sm">
                <i class="fa-solid fa-list mr-2 text-xs"></i> Lihat daftar
            </a>
            <a href="{{ route('data-pribadi.create') }}" class="wf-btn-ghost inline-flex items-center justify-center px-5 py-3 text-sm">
                <i class="fa-solid fa-plus mr-2 text-xs"></i> Tambah lagi
            </a>
            <a href="{{ url('/profile') }}" class="wf-btn-ghost inline-flex items-center justify-center px-5 py-3 text-sm">
                Kembali ke profil
            </a>
        </div>
    </div>
</div>
@endsection
