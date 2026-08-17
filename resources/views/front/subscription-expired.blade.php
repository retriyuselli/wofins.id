@extends('layouts.app')

@section('title', 'Paket Berakhir — WOFINS')

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

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
            transition: filter .2s ease, transform .2s ease;
        }

        .wf-btn-gold:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }
</style>
@endpush

@section('content')
@php
    use App\Support\CompanySubscription;

    $planLabel = CompanySubscription::planLabel();
    $expiresLabel = CompanySubscription::expiresAtLabel();
    $canManage = CompanySubscription::canManageSubscription();
    $adminContact = CompanySubscription::subscriptionAdminContact();
@endphp
<div class="wf-expired-page">
    @include('front.partials.wf-nav')

    <section class="py-16 sm:py-20">
        <div class="max-w-xl mx-auto px-4 sm:px-6">
            <div class="rounded-3xl border border-[var(--wf-line)] bg-white p-8 sm:p-10 shadow-sm space-y-6">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <i class="fa-solid fa-calendar-xmark text-lg"></i>
                </div>

                <div>
                    <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Masa aktif berakhir</p>
                    <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-[var(--wf-navy)] leading-tight">
                        Akses dashboard ditangguhkan
                    </h1>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                        Paket <strong class="text-[var(--wf-navy)]">{{ $planLabel }}</strong>
                        @if ($expiresLabel)
                            aktif sampai <strong class="text-[var(--wf-navy)]">{{ $expiresLabel }}</strong> dan sudah berakhir.
                        @else
                            sudah berakhir.
                        @endif
                        Seluruh tim di perusahaan Anda terdampak sampai paket diperpanjang.
                    </p>
                    @if ($canManage)
                        <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                            Sebagai admin perusahaan, Anda dapat perpanjang paket agar semua user kembali aktif.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                            Hubungi admin perusahaan Anda
                            (<strong class="text-[var(--wf-navy)]">{{ $adminContact['label'] }}</strong>
                            untuk perpanjang paket. Staf tidak perlu memesan sendiri.
                        </p>
                    @endif
                </div>

                @if (session('error'))
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="flex flex-col sm:flex-row gap-3 pt-1">
                    @if ($canManage)
                        <a href="{{ route('harga') }}" class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Perpanjang paket
                        </a>
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Hubungi support WOFINS
                        </a>
                    @else
                        @if (! empty($adminContact['email']))
                            <a href="mailto:{{ $adminContact['email'] }}?subject={{ rawurlencode('Perpanjang paket WOFINS') }}"
                               class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                                Hubungi admin WO
                            </a>
                        @endif
                        <a href="{{ route('profile') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Kembali ke profil
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @include('front.partials.wf-footer')
</div>
@endsection
