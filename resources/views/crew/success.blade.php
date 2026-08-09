@extends('layouts.app')

@section('title', 'Data Terkirim — '.$company->company_name)

@push('styles')
@include('front.partials.wf-front-base-styles')
@endpush

@section('content')
@php
    $labelCompany = $company->company_name ?: config('app.name');
@endphp

<div class="wf-page">
    @include('front.partials.wf-nav')

    <section class="pt-16 pb-20 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-lg mx-auto px-4 sm:px-6 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[var(--wf-gold)]">
                <i class="fa-solid fa-check text-2xl"></i>
            </div>
            <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-2">Crew Freelance</p>
            <h1 class="text-3xl font-bold text-[var(--wf-navy)]">Data berhasil dikirim</h1>
            <p class="mt-3 text-[var(--wf-muted)] leading-relaxed">
                Terima kasih. Tim <strong class="text-[var(--wf-navy)]">{{ $labelCompany }}</strong>
                akan menghubungi Anda jika dibutuhkan.
            </p>
            @if (session('success'))
                <p class="mt-4 text-sm font-semibold text-[var(--wf-navy)]">{{ session('success') }}</p>
            @endif
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
