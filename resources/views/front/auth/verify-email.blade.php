@extends('layouts.app')

@section('title', 'Verifikasi Email — WOFINS')

@push('styles')
<style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-gold-soft: #e8d48b;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-verify-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background:
                radial-gradient(ellipse 80% 50% at 10% 20%, rgba(201, 162, 39, 0.12), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 80%, rgba(11, 31, 58, 0.08), transparent 50%),
                linear-gradient(180deg, #fff 0%, var(--wf-cream) 100%);
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

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
            transition: background .2s ease;
        }

        .wf-btn-ghost:hover {
            background: var(--wf-cream);
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
    $user = Auth::user();
@endphp

<div class="wf-verify-page">
    @include('front.partials.wf-nav')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="bg-white border border-[var(--wf-line)] rounded-[1.25rem] overflow-hidden shadow-[0_18px_40px_-28px_rgba(11,31,58,0.28)]">
            <div class="px-6 py-5 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
                <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Langkah 1 dari 2</p>
                <h1 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">Verifikasi email Anda</h1>
                <p class="mt-2 text-sm text-white/65">
                    Kami sudah mengirim tautan verifikasi ke
                    <span class="text-white font-semibold">{{ $user?->email }}</span>.
                </p>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                @if (session('status') === 'verification-link-sent')
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-sm text-emerald-800 font-medium">
                            Tautan verifikasi baru telah dikirim. Periksa kotak masuk atau folder spam.
                        </p>
                    </div>
                @endif

                @if (session('info'))
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
                        <p class="text-sm text-sky-800">{{ session('info') }}</p>
                    </div>
                @endif

                <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                    <div class="flex gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-[var(--wf-line)]">
                            <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-[var(--wf-navy)]">Buka email lalu klik tautan</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                Setelah email terverifikasi, Anda bisa melanjutkan ke dashboard
                                (atau halaman aktivasi akun bila role belum diberikan).
                                Tautan berlaku sekitar 60 menit.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <form method="POST" action="{{ route('verification.send') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="wf-btn-gold w-full inline-flex items-center justify-center px-5 py-3 text-sm">
                            Kirim ulang email
                        </button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="wf-btn-ghost w-full inline-flex items-center justify-center px-5 py-3 text-sm">
                            Keluar
                        </button>
                    </form>
                </div>

                <p class="text-xs text-center text-[var(--wf-muted)]">
                    Salah email? Keluar, lalu daftar ulang dengan alamat yang benar.
                    Bantuan: <a href="mailto:support@wofins.id" class="font-semibold text-[var(--wf-navy)] underline-offset-2 hover:underline">support@wofins.id</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
