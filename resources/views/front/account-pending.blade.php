@extends('layouts.app')

@section('title', 'Status Akun - WOFINS')

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
    use App\Enums\ProspectAppStatus;
    use App\Support\PricingPlans;

    $user = Auth::user();
    $prospect = $prospect ?? null;
    $hasSubmitted = $prospect !== null;
    $isRejected = $prospect?->status === ProspectAppStatus::Rejected;
    $justSubmitted = session('registration_submitted') || session('success');
    $serviceLabel = PricingPlans::shortLabel($prospect?->service);
@endphp

<div class="wf-pending-page">
    @include('front.partials.wf-nav')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="bg-white border border-[var(--wf-line)] rounded-[1.25rem] overflow-hidden shadow-[0_18px_40px_-28px_rgba(11,31,58,0.28)]">
            <div class="px-6 py-5 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
                <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Status Akun</p>
                @if ($hasSubmitted && ! $isRejected)
                    <h1 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">Pendaftaran Sedang Ditinjau</h1>
                    <p class="mt-2 text-sm text-white/65">
                        Halo{{ $user?->name ? ', '.$user->name : '' }}. Terima kasih — data Anda sudah masuk antrean admin.
                    </p>
                @elseif ($isRejected)
                    <h1 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">Pendaftaran Perlu Diperbarui</h1>
                    <p class="mt-2 text-sm text-white/65">
                        Halo{{ $user?->name ? ', '.$user->name : '' }}. Pengajuan sebelumnya belum dapat disetujui.
                    </p>
                @else
                    <h1 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">Akun Belum Diaktifkan</h1>
                    <p class="mt-2 text-sm text-white/65">
                        Halo{{ $user?->name ? ', '.$user->name : '' }}. Login berhasil, tetapi akses dashboard belum tersedia.
                    </p>
                @endif
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                @if (session('info'))
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-4 py-3 text-sm text-[var(--wf-muted)]">
                        {{ session('info') }}
                    </div>
                @endif

                @if ($hasSubmitted && ! $isRejected)
                    {{-- Terima kasih / menunggu admin --}}
                    @if ($justSubmitted)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                            <div class="flex gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-emerald-100 text-emerald-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <h2 class="text-base font-bold text-[var(--wf-navy)]">Terima kasih atas pendaftaran Anda</h2>
                                    <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                        {{ session('success') ?: 'Data formulir sudah kami terima. Tim admin WOFINS akan meninjau pengajuan Anda dan mengaktifkan akun setelah disetujui. Konfirmasi juga dikirim ke email Anda.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                            <div class="flex gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-[var(--wf-line)]">
                                    <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </span>
                                <div>
                                    <h2 class="text-base font-bold text-[var(--wf-navy)]">Menunggu aktivasi dari admin</h2>
                                    <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                        Pendaftaran Anda sudah tercatat. Dashboard akan terbuka setelah admin mengaktifkan role pada akun
                                        <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5 space-y-2.5 text-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Ringkasan pengajuan</p>
                        <div><span class="text-[var(--wf-muted)]">Perusahaan</span> : <strong class="text-[var(--wf-navy)]">{{ $prospect->company_name }}</strong></div>
                        <div><span class="text-[var(--wf-muted)]">Minat paket</span> : <strong class="text-[var(--wf-navy)]">{{ $serviceLabel }}</strong></div>
                        @if ($prospect->industry)
                            <div><span class="text-[var(--wf-muted)]">Departemen</span> : <strong class="text-[var(--wf-navy)]">{{ $prospect->industry->industry_name }}</strong></div>
                        @endif
                        <div><span class="text-[var(--wf-muted)]">Status</span> :
                            <strong class="text-[var(--wf-navy)]">{{ $prospect->status_label }}</strong>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Langkah selanjutnya</p>
                        <ul class="space-y-2.5 text-sm text-[var(--wf-muted)]">
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">1</span>
                                <span>Tim admin meninjau data pendaftaran Anda.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">2</span>
                                <span>Setelah disetujui, role akan diaktifkan pada akun ini.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">3</span>
                                <span>Anda mendapat email pemberitahuan, lalu bisa masuk ke Dashboard.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Hubungi Kami
                        </a>
                    </div>
                @else
                    {{-- Belum daftar / ditolak --}}
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                        <div class="flex gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-[var(--wf-line)]">
                                <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                @if ($isRejected)
                                    <h2 class="text-base font-bold text-[var(--wf-navy)]">Silakan perbarui formulir pendaftaran</h2>
                                    <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                        Pengajuan sebelumnya ditolak. Lengkapi ulang formulir dengan data yang benar, atau hubungi kami jika butuh bantuan.
                                    </p>
                                @else
                                    <h2 class="text-base font-bold text-[var(--wf-navy)]">Lengkapi formulir pendaftaran</h2>
                                    <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                        Akun Anda belum memiliki role. Isi formulir pendaftaran WOFINS agar admin dapat meninjau dan mengaktifkan akses Dashboard.
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Langkah selanjutnya</p>
                        <ul class="space-y-2.5 text-sm text-[var(--wf-muted)]">
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">1</span>
                                <span>Isi dan kirim Formulir Pendaftaran.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">2</span>
                                <span>Gunakan email: <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong></span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">3</span>
                                <span>Tunggu admin mengaktifkan akun, lalu login ulang di sini.</span>
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
                @endif

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
