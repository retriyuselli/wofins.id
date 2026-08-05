@extends('layouts.app')

@section('title', 'Pendaftaran — WOFINS')

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

        .wf-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: #fff;
        }

        .wf-page h1,
        .wf-page h2,
        .wf-page h3 {
            letter-spacing: -0.02em;
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

        .wf-input {
            width: 100%;
            border: 1px solid var(--wf-line);
            border-radius: 0.85rem;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
            background: #fff;
            color: var(--wf-ink);
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
            appearance: none;
        }

        .wf-input:focus {
            border-color: rgba(201, 162, 39, 0.7);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
        }

        .wf-input.is-error {
            border-color: #fca5a5;
        }

        .wf-input[readonly] {
            background: var(--wf-cream);
            color: var(--wf-navy);
            cursor: not-allowed;
        }

        .wf-input[readonly]:focus {
            border-color: var(--wf-line);
            box-shadow: none;
        }

        .wf-hero-reg {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 60% at 10% 0%, rgba(201, 162, 39, 0.18), transparent 55%),
                radial-gradient(ellipse 70% 50% at 90% 20%, rgba(11, 31, 58, 0.08), transparent 50%),
                linear-gradient(180deg, #fff 0%, var(--wf-cream) 100%);
        }

        .wf-hero-reg .orb {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            animation: wf-float 8s ease-in-out infinite;
        }

        .wf-hero-reg .orb-a {
            width: 14rem;
            height: 14rem;
            right: -3rem;
            top: -4rem;
            background: rgba(201, 162, 39, 0.12);
        }

        .wf-hero-reg .orb-b {
            width: 10rem;
            height: 10rem;
            left: -2rem;
            bottom: -3rem;
            background: rgba(11, 31, 58, 0.08);
            animation-delay: -3s;
        }

        @keyframes wf-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(12px); }
        }

        .wf-fade-up {
            animation: wf-fade-up .7s ease both;
        }

        .wf-fade-up-delay {
            animation: wf-fade-up .7s ease .12s both;
        }

        @keyframes wf-fade-up {
            from { opacity: 0; transform: translateY(14px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .wf-modal-backdrop {
            background: rgba(7, 21, 38, 0.55);
            backdrop-filter: blur(4px);
        }

        [x-cloak] { display: none !important; }
    </style>
@endpush

@section('content')
@php
    $plan = request('plan');
    $planLabel = match ($plan) {
        'hastana' => 'Paket Anggota Hastana',
        'non-hastana' => 'Paket Non Hastana',
        default => null,
    };
    $prospect = $prospect ?? null;
    $authUser = Auth::user();
    $userAlreadyActive = $authUser?->hasAssignedRole() ?? false;
    $isApproved = $userAlreadyActive || $prospect?->status === \App\Enums\ProspectAppStatus::Approved;
    $isRejected = ! $isApproved && $prospect?->status === \App\Enums\ProspectAppStatus::Rejected;
    $isPending = ! $isApproved && $prospect?->status === \App\Enums\ProspectAppStatus::Pending;
    $canRegister = ! $isApproved;

    $defaultFullName = old('full_name', $prospect?->full_name ?: $authUser?->name);
    $defaultCompany = old('company_name', $prospect?->company_name);
    $defaultIndustryId = old('industry_id', $prospect?->industry_id);
    $defaultUserSize = old('user_size', $prospect?->user_size);
    $defaultPhone = old('phone', $prospect?->phone ?: $authUser?->phone_number);
    $defaultService = old('service') ?? $prospect?->service ?? match ($plan) {
        'hastana' => 'hastana',
        'non-hastana' => 'non_hastana',
        default => '',
    };
    $defaultReason = old('reason_for_interest', $prospect?->reason_for_interest);
    $defaultTerms = old('terms', $prospect?->position === 'Decision Maker' ? '1' : null);

    $serviceLabel = match ($prospect?->service) {
        'hastana' => 'Paket Anggota Hastana',
        'non_hastana' => 'Paket Non Hastana',
        default => $prospect?->service ?: '—',
    };
@endphp

<div class="wf-page" x-data="{ termsOpen: false, privacyOpen: false, successOpen: {{ session('success') ? 'true' : 'false' }}, errorOpen: {{ ($errors->any() || session('error')) ? 'true' : 'false' }} }">
    @include('front.partials.wf-nav')

    <section class="wf-hero-reg pt-12 pb-10">
        <span class="orb orb-a" aria-hidden="true"></span>
        <span class="orb orb-b" aria-hidden="true"></span>
        <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center wf-fade-up">
            <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Pendaftaran WOFINS</p>
            <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[var(--wf-navy)] leading-tight">
                @if ($isApproved)
                    Pendaftaran Anda telah disetujui
                @elseif ($isRejected)
                    Pendaftaran perlu ditinjau ulang
                @elseif ($plan === 'hastana')
                    Bergabung dengan Komunitas Hastana
                @elseif ($plan === 'non-hastana')
                    Mulai Perjalanan Bisnis Anda
                @else
                    Diskusikan kebutuhan bisnis Anda dengan kami
                @endif
            </h1>
            <p class="mt-4 text-[var(--wf-muted)] max-w-xl mx-auto">
                @if ($isApproved)
                    Akun Anda sudah aktif. Silakan lanjut ke Dashboard untuk mulai menggunakan WOFINS.
                @elseif ($isRejected)
                    Tim kami menandai pendaftaran sebelumnya sebagai ditolak. Anda dapat menghubungi support atau mengisi ulang formulir.
                @else
                    Isi formulir singkat — tim kami akan menghubungi Anda untuk menjadwalkan meeting dan menyiapkan akses WOFINS.
                @endif
            </p>
            @if ($planLabel && $canRegister)
                <div class="mt-5 inline-flex items-center gap-2 rounded-full bg-[rgba(201,162,39,0.14)] px-3.5 py-1.5 text-xs font-bold text-[#9a7a12]">
                    <i class="fa-solid fa-tag"></i>
                    {{ $planLabel }}
                </div>
            @endif
        </div>
    </section>

    <section class="pb-16 pt-8 sm:pt-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-5 gap-8 items-start wf-fade-up-delay">
                {{-- Main column --}}
                <div class="lg:col-span-3 rounded-2xl border border-[var(--wf-line)] bg-white p-6 sm:p-8 shadow-[0_18px_40px_-28px_rgba(11,31,58,0.28)]">
                    @if ($isApproved)
                        <div class="text-center sm:text-left">
                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
                                <i class="fa-solid fa-circle-check"></i>
                                Status: Disetujui
                            </span>
                            <h2 class="mt-4 text-xl sm:text-2xl font-bold text-[var(--wf-navy)]">Pendaftaran sudah aktif</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)]">
                                Form pendaftaran tidak lagi tersedia karena akun Anda sudah disetujui admin.
                            </p>
                        </div>

                        <div class="mt-6 rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5 space-y-3 text-sm">
                            <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Ringkasan data</p>
                            <div class="text-[var(--wf-ink)]"><span class="text-[var(--wf-muted)]">Nama</span> : <strong>{{ $prospect->full_name }}</strong></div>
                            <div class="text-[var(--wf-ink)]"><span class="text-[var(--wf-muted)]">Perusahaan</span> : <strong>{{ $prospect->company_name }}</strong></div>
                            <div class="text-[var(--wf-ink)]"><span class="text-[var(--wf-muted)]">Email</span> : <strong>{{ $prospect->email }}</strong></div>
                            <div class="text-[var(--wf-ink)]"><span class="text-[var(--wf-muted)]">Paket</span> : <strong>{{ $serviceLabel }}</strong></div>
                            <div class="text-[var(--wf-ink)]"><span class="text-[var(--wf-muted)]">Departemen</span> : <strong>{{ $prospect->industry?->industry_name ?? '—' }}</strong></div>
                        </div>

                        <div class="mt-6 flex flex-col sm:flex-row gap-3">
                            <a href="{{ route('profile') }}" class="wf-btn-gold inline-flex flex-1 items-center justify-center gap-2 px-5 py-3 text-sm">
                                <i class="fa-solid fa-gauge-high"></i>
                                Buka Dashboard
                            </a>
                            <a href="{{ route('kontak') }}" class="wf-btn-ghost inline-flex flex-1 items-center justify-center gap-2 px-5 py-3 text-sm">
                                Hubungi support
                            </a>
                        </div>
                    @else
                        <h2 class="text-xl sm:text-2xl font-bold text-[var(--wf-navy)]">Formulir pendaftaran</h2>
                        <p class="mt-1.5 text-sm text-[var(--wf-muted)]">Lengkapi data di bawah. Semua field bertanda * wajib diisi.</p>
                        @if ($isPending)
                            <p class="mt-2 text-xs font-medium text-[var(--wf-gold)]">
                                Data pendaftaran sebelumnya ditampilkan. Anda dapat memperbarui lalu mengirim ulang.
                            </p>
                        @elseif ($isRejected)
                            <p class="mt-2 text-xs font-medium text-red-600">
                                Pendaftaran sebelumnya ditolak. Silakan perbarui data dan daftar ulang, atau hubungi support.
                            </p>
                        @endif

                    <form action="{{ route('prospect-app.store') }}" method="POST" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="plan" value="{{ $plan }}">

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="full_name" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama lengkap *</label>
                                <input id="full_name" name="full_name" type="text" required
                                    value="{{ $defaultFullName }}"
                                    class="wf-input @error('full_name') is-error @enderror"
                                    placeholder="Contoh: Budi Santoso">
                                @error('full_name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="company_name" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama perusahaan *</label>
                                <input id="company_name" name="company_name" type="text" required value="{{ $defaultCompany }}"
                                    class="wf-input @error('company_name') is-error @enderror"
                                    placeholder="Contoh: PT Sukses Maju">
                                @error('company_name')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="industry_id" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Departemen *</label>
                                <select id="industry_id" name="industry_id" required
                                    class="wf-input @error('industry_id') is-error @enderror">
                                    <option value="">Pilih departemen</option>
                                    @foreach ($industries as $industry)
                                        <option value="{{ $industry->id }}" @selected((string) $defaultIndustryId === (string) $industry->id)>
                                            {{ $industry->industry_name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('industry_id')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="user_size" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Jumlah karyawan *</label>
                                <select id="user_size" name="user_size" required
                                    class="wf-input @error('user_size') is-error @enderror">
                                    <option value="">Pilih jumlah karyawan</option>
                                    @foreach (['1-10', '11-50', '51-200', '201-500', '501-1000', '1000+'] as $size)
                                        <option value="{{ $size }}" @selected($defaultUserSize === $size)>
                                            {{ $size === '1000+' ? 'Lebih dari 1000 karyawan' : $size.' karyawan' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_size')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="phone" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nomor ponsel *</label>
                                <input id="phone" name="phone" type="tel" required value="{{ $defaultPhone }}"
                                    class="wf-input @error('phone') is-error @enderror"
                                    placeholder="08xxxxxxxxxx">
                                @error('phone')
                                    <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Alamat email *</label>
                                <input id="email" name="email" type="email" required
                                    value="{{ Auth::check() ? Auth::user()->email : old('email') }}"
                                    class="wf-input"
                                    placeholder="nama@perusahaan.com"
                                    @auth readonly tabindex="-1" @endauth>
                                @auth
                                    <p class="mt-1.5 text-xs text-[var(--wf-muted)]">Email mengikuti akun login Anda dan tidak dapat diubah.</p>
                                @endauth
                            </div>
                        </div>

                        <div>
                            <label for="service" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Paket layanan *</label>
                            <select id="service" name="service" required
                                class="wf-input @error('service') is-error @enderror">
                                <option value="">Pilih paket layanan</option>
                                <option value="hastana" @selected($defaultService === 'hastana')>
                                    Paket Anggota Hastana — Rp 8.500.000 / 2 tahun
                                </option>
                                <option value="non_hastana" @selected($defaultService === 'non_hastana')>
                                    Paket Non Hastana — Rp 10.000.000 / 2 tahun
                                </option>
                            </select>
                            @error('service')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="reason_for_interest" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Kebutuhan & tantangan bisnis *</label>
                            <textarea id="reason_for_interest" name="reason_for_interest" rows="4" required
                                class="wf-input @error('reason_for_interest') is-error @enderror"
                                placeholder="Ceritakan singkat kebutuhan dan tantangan operasional Anda...">{{ $defaultReason }}</textarea>
                            @error('reason_for_interest')
                                <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <input type="hidden" name="position" value="Decision Maker">
                        <input type="hidden" name="notes"
                            value="Form submitted via consultation page - interested in {{ $plan === 'hastana' ? 'Hastana Member Package' : ($plan === 'non-hastana' ? 'Non Hastana Package' : 'WOFINS') }}">

                        <label class="flex items-start gap-3 cursor-pointer">
                            <input id="terms" name="terms" type="checkbox" required value="1"
                                class="mt-1 h-4 w-4 rounded border-[var(--wf-line)] text-[var(--wf-navy)] focus:ring-[var(--wf-gold)]"
                                @checked($defaultTerms)>
                            <span class="text-sm text-[var(--wf-muted)]">
                                Saya pengambil keputusan dalam pembelian software.
                            </span>
                        </label>

                        <button type="submit" class="wf-btn-gold w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm">
                            <i class="fa-solid fa-user-plus"></i>
                            Daftar
                        </button>

                        <p class="text-xs text-[var(--wf-muted)] leading-relaxed">
                            Dengan klik tombol di atas, Anda menyetujui
                            <button type="button" @click="termsOpen = true" class="font-semibold text-[var(--wf-navy)] underline underline-offset-2 hover:text-[var(--wf-gold)]">syarat &amp; ketentuan</button>
                            serta
                            <button type="button" @click="privacyOpen = true" class="font-semibold text-[var(--wf-navy)] underline underline-offset-2 hover:text-[var(--wf-gold)]">pernyataan privasi</button>
                            WOFINS.
                        </p>
                    </form>
                    @endif
                </div>

                {{-- Sidebar --}}
                <div class="lg:col-span-2 space-y-4">
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-6">
                        <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Setelah mendaftar</h3>
                        <ul class="space-y-3 text-sm text-[var(--wf-muted)]">
                            @foreach ([
                                'Tim sales menghubungi Anda dalam 1–2 hari kerja',
                                'Jadwalkan demo sesuai kebutuhan bisnis',
                                'Onboarding & aktivasi akses WOFINS',
                            ] as $i => $step)
                                <li class="flex items-start gap-2.5">
                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">{{ $i + 1 }}</span>
                                    <span>{{ $step }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-6">
                        <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Butuh bantuan cepat?</h3>
                        <ul class="space-y-3 text-sm">
                            <li>
                                <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin konsultasi pendaftaran WOFINS.') }}"
                                   target="_blank" rel="noopener"
                                   class="flex items-center gap-3 group">
                                    <span class="w-10 h-10 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center shrink-0">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </span>
                                    <span>
                                        <span class="block font-bold text-[var(--wf-navy)] group-hover:text-[var(--wf-gold)]">WhatsApp sales</span>
                                        <span class="text-[var(--wf-muted)]">+62 813-7318-3794</span>
                                    </span>
                                </a>
                            </li>
                            <li>
                                <a href="mailto:support@wofins.id" class="flex items-center gap-3 group">
                                    <span class="w-10 h-10 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center shrink-0">
                                        <i class="fa-solid fa-envelope"></i>
                                    </span>
                                    <span>
                                        <span class="block font-bold text-[var(--wf-navy)] group-hover:text-[var(--wf-gold)]">Email support</span>
                                        <span class="text-[var(--wf-muted)]">support@wofins.id</span>
                                    </span>
                                </a>
                            </li>
                            <li class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center shrink-0">
                                    <i class="fa-solid fa-clock"></i>
                                </span>
                                <span>
                                    <span class="block font-bold text-[var(--wf-navy)]">Jam kerja</span>
                                    <span class="text-[var(--wf-muted)]">Senin–Jumat · 08:00–17:00 WIB</span>
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Login CTA di bawah form --}}
            <div class="mt-10 rounded-2xl overflow-hidden relative text-white p-6 sm:p-8"
                 style="background: linear-gradient(135deg, #071526 0%, #0b1f3a 60%, #14335a 100%);">
                <span class="absolute w-32 h-32 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.16)]" aria-hidden="true"></span>
                <span class="absolute w-20 h-20 rounded-full left-8 -bottom-8 bg-[rgba(201,162,39,0.1)]" aria-hidden="true"></span>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-5">
                    <div>
                        <h3 class="text-lg sm:text-xl font-bold">Sudah punya akun?</h3>
                        <p class="mt-2 text-sm text-white/75 max-w-xl">Masuk untuk mengakses dashboard setelah role diaktifkan.</p>
                    </div>
                    <a href="{{ route('front.login') }}" class="wf-btn-gold inline-flex items-center justify-center gap-2 px-6 py-3 text-sm shrink-0">
                        Masuk ke WOFINS
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Terms modal --}}
    <div x-show="termsOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="terms-title">
        <div class="absolute inset-0 wf-modal-backdrop" @click="termsOpen = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="px-6 py-4 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
                <h3 id="terms-title" class="text-lg font-bold text-white">Syarat &amp; Ketentuan</h3>
            </div>
            <div class="px-6 py-5 max-h-72 overflow-y-auto text-sm text-[var(--wf-muted)] space-y-2">
                <p>Selamat datang di WOFINS. Dengan menggunakan layanan kami, Anda menyetujui syarat dan ketentuan berikut:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Layanan disediakan “sebagaimana adanya”.</li>
                    <li>Kami berhak mengubah fitur layanan sewaktu-waktu.</li>
                    <li>Pengguna bertanggung jawab atas keamanan akun masing-masing.</li>
                    <li>Dilarang menggunakan layanan untuk kegiatan ilegal.</li>
                    <li>Pembayaran yang sudah dilakukan tidak dapat dikembalikan (non-refundable), kecuali ditentukan lain.</li>
                </ul>
            </div>
            <div class="px-6 py-4 border-t border-[var(--wf-line)] bg-[var(--wf-cream)] flex justify-end">
                <button type="button" @click="termsOpen = false" class="wf-btn-navy px-5 py-2.5 text-sm">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Privacy modal --}}
    <div x-show="privacyOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="privacy-title">
        <div class="absolute inset-0 wf-modal-backdrop" @click="privacyOpen = false"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0">
            <div class="px-6 py-4 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
                <h3 id="privacy-title" class="text-lg font-bold text-white">Pernyataan Privasi</h3>
            </div>
            <div class="px-6 py-5 max-h-72 overflow-y-auto text-sm text-[var(--wf-muted)] space-y-2">
                <p>Kami menghargai privasi Anda. Berikut ringkasan bagaimana kami mengelola data Anda:</p>
                <ul class="list-disc pl-5 space-y-1.5">
                    <li>Kami mengumpulkan data hanya untuk keperluan layanan.</li>
                    <li>Data Anda tidak akan dijual ke pihak ketiga.</li>
                    <li>Kami menggunakan enkripsi untuk melindungi data sensitif.</li>
                    <li>Anda berhak meminta penghapusan data Anda kapan saja.</li>
                    <li>Cookie digunakan untuk meningkatkan pengalaman pengguna.</li>
                </ul>
            </div>
            <div class="px-6 py-4 border-t border-[var(--wf-line)] bg-[var(--wf-cream)] flex justify-end">
                <button type="button" @click="privacyOpen = false" class="wf-btn-navy px-5 py-2.5 text-sm">Tutup</button>
            </div>
        </div>
    </div>

    {{-- Error modal --}}
    @if ($errors->any() || session('error'))
    <div x-show="errorOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="pendaftaran-error-title">
        <div class="absolute inset-0 wf-modal-backdrop" @click="errorOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95">
            <div class="px-6 pt-8 pb-2 text-center">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-500">
                    <i class="fa-solid fa-circle-exclamation text-2xl"></i>
                </span>
                <h3 id="pendaftaran-error-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                    {{ session('error') ? 'Pemberitahuan' : 'Terdapat kesalahan pada form' }}
                </h3>
                <ul class="mt-4 text-left space-y-2 text-sm text-[var(--wf-muted)]">
                    @if (session('error'))
                        <li class="flex items-start gap-2.5 rounded-xl border border-red-100 bg-red-50/80 px-3.5 py-2.5 text-red-700">
                            <i class="fa-solid fa-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ session('error') }}</span>
                        </li>
                    @endif
                    @foreach ($errors->all() as $error)
                        <li class="flex items-start gap-2.5 rounded-xl border border-red-100 bg-red-50/80 px-3.5 py-2.5 text-red-700">
                            <i class="fa-solid fa-xmark mt-0.5 text-red-400 shrink-0"></i>
                            <span>{{ $error }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="px-6 py-5 flex justify-center">
                <button type="button" @click="errorOpen = false"
                        class="wf-btn-navy inline-flex items-center justify-center px-6 py-3 text-sm">
                    {{ session('error') ? 'Mengerti' : 'Perbaiki form' }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Success modal --}}
    <div x-show="successOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="pendaftaran-success-title">
        <div class="absolute inset-0 wf-modal-backdrop" @click="successOpen = false"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95">
            <div class="px-6 pt-8 pb-2 text-center">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)]">
                    <i class="fa-solid fa-envelope-circle-check text-2xl"></i>
                </span>
                <h3 id="pendaftaran-success-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                    Pendaftaran berhasil dikirim
                </h3>
                <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                    {{ session('success', 'Data Anda sudah kami terima. Tim admin WOFINS akan segera menghubungi Anda.') }}
                </p>
                <p class="mt-3 text-xs text-[var(--wf-muted)]">
                    Kami juga mengirim konfirmasi ke email Anda.
                </p>
            </div>
            <div class="px-6 py-5 flex flex-col sm:flex-row gap-2.5 justify-center">
                <button type="button" @click="successOpen = false"
                        class="wf-btn-navy inline-flex items-center justify-center px-6 py-3 text-sm">
                    Mengerti
                </button>
                <a href="{{ route('home') }}"
                   class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">
                    Kembali ke beranda
                </a>
            </div>
        </div>
    </div>

    @include('front.partials.wf-footer')
</div>
@endsection
