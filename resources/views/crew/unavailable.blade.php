@extends('layouts.app')

@section('title', 'Undangan tidak tersedia — WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
@endpush

@section('content')
<div class="wf-page">
    @include('front.partials.wf-nav')

    <section class="pt-16 pb-20 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-lg mx-auto px-4 sm:px-6 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full border border-[var(--wf-line)] bg-white text-[var(--wf-gold)]">
                <i class="fa-solid fa-link-slash text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-[var(--wf-navy)]">Link undangan tidak aktif</h1>
            <p class="mt-3 text-[var(--wf-muted)] leading-relaxed">
                Link ini sudah dinonaktifkan, diganti, atau tidak valid.
                Hubungi tim wedding organizer yang mengundang Anda untuk mendapatkan link baru.
            </p>
            <div class="mt-8">
                <a href="{{ url('/') }}" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">
                    Kembali ke beranda
                </a>
            </div>
        </div>
    </section>

    @include('front.partials.wf-footer')
</div>
@endsection
