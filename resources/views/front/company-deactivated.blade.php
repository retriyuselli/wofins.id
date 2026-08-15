@extends('layouts.app')

@section('title', 'Perusahaan Nonaktif — WOFINS')

@push('styles')
<style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-expired-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: var(--wf-cream);
            min-height: 100vh;
        }

        .wf-btn-navy {
            background: var(--wf-navy);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            transition: background .2s ease, transform .2s ease;
        }

        .wf-btn-navy:hover {
            background: var(--wf-navy-deep);
            transform: translateY(-1px);
        }
</style>
@endpush

@section('content')
@php
    $companyName = auth()->user()?->company?->company_name ?? 'Perusahaan Anda';
@endphp
<div class="wf-expired-page">
    @include('front.partials.wf-nav')

    <section class="py-16 sm:py-20">
        <div class="max-w-xl mx-auto px-4 sm:px-6">
            <div class="rounded-3xl border border-[var(--wf-line)] bg-white p-8 sm:p-10 shadow-sm space-y-6">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-700">
                    <i class="fa-solid fa-building-circle-xmark text-lg"></i>
                </div>

                <div>
                    <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Perusahaan nonaktif</p>
                    <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-[var(--wf-navy)] leading-tight">
                        Akses dashboard ditangguhkan
                    </h1>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                        <strong class="text-[var(--wf-navy)]">{{ $companyName }}</strong>
                        sedang dinonaktifkan oleh admin. Data Anda tetap tersimpan (arsip),
                        tetapi login ke backend tidak tersedia sampai perusahaan diaktifkan kembali.
                    </p>
                </div>

                @if (session('error'))
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3 pt-1">
                    <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                        Hubungi support
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-5 py-3 text-sm rounded-full border border-[var(--wf-line)] font-semibold text-[var(--wf-navy)]">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
