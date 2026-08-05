@extends('layouts.app')

@section('title', 'Akun Belum Aktif - WOFINS')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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

        .wf-pending-page {
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

<div class="wf-pending-page">
    @include('front.partials.wf-nav')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="bg-white border border-[var(--wf-line)] rounded-[1.25rem] overflow-hidden shadow-[0_18px_40px_-28px_rgba(11,31,58,0.28)]">
            <div class="px-6 py-5 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
                <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Status Akun</p>
                <h1 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">Akun Belum Diaktifkan</h1>
                <p class="mt-2 text-sm text-white/65">
                    Halo{{ $user?->name ? ', '.$user->name : '' }}. Login berhasil, tetapi akses dashboard belum tersedia.
                </p>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                    <div class="flex gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-[var(--wf-line)]">
                            <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </span>
                        <div>
                            <h2 class="text-base font-bold text-[var(--wf-navy)]">Lakukan pendaftaran lewat aplikasi WOFINS</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                Akun Anda belum memiliki role karyawan/admin. Untuk mengaktifkan akses,
                                silakan daftar atau hubungkan akun melalui <strong class="text-[var(--wf-navy)]">aplikasi WOFINS</strong>
                                sesuai undangan perusahaan Anda. Setelah role diberikan oleh admin, Anda bisa masuk ke Dashboard.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Langkah selanjutnya</p>
                    <ul class="space-y-2.5 text-sm text-[var(--wf-muted)]">
                        <li class="flex items-start gap-2.5">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">1</span>
                            <span>Buka aplikasi WOFINS di perangkat Anda dan selesaikan pendaftaran.</span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">2</span>
                            <span>Gunakan email yang sama: <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong></span>
                        </li>
                        <li class="flex items-start gap-2.5">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">3</span>
                            <span>Tunggu admin perusahaan mengaktifkan role, lalu login ulang di sini.</span>
                        </li>
                    </ul>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a href="{{ route('pendaftaran') }}" class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                        Formulir Pendaftaran
                    </a>
                    <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                        Hubungi Kami
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 border-t border-[var(--wf-line)] pt-5">
                    <a href="{{ route('home') }}" class="wf-btn-ghost inline-flex flex-1 items-center justify-center px-5 py-2.5 text-sm">
                        Kembali ke Beranda
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="wf-btn-ghost w-full inline-flex items-center justify-center px-5 py-2.5 text-sm text-[#92400e] border-[#b45309]/40 hover:bg-[#b45309]/10">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('front.partials.wf-footer')
</div>
@endsection
