@extends('layouts.app')

@section('title', 'Kontak — WOFINS')

@push('styles')
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

        .wf-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--wf-line);
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
        }

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
        }

        .wf-contact-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.35rem;
            height: 100%;
        }

        .wf-contact-card .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            background: rgba(201, 162, 39, 0.12);
        }

        .wf-input {
            width: 100%;
            border: 1px solid var(--wf-line);
            border-radius: 0.85rem;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
            line-height: 1.4;
            min-height: 3rem;
            box-sizing: border-box;
            background: #fff;
            color: var(--wf-ink);
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        select.wf-input {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235c6675' d='M2.1 4.2L6 8.1l3.9-3.9 1.1 1.1L6 10.3 1 5.3z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }

        textarea.wf-input {
            min-height: auto;
            resize: vertical;
        }

        .wf-input:focus {
            border-color: rgba(201, 162, 39, 0.7);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
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
    $paket = request('paket');
    $paketLabel = match ($paket) {
        'starter' => 'Paket Starter',
        'professional' => 'Paket Professional',
        'business' => 'Paket Business',
        'enterprise' => 'Paket Enterprise',
        default => null,
    };
@endphp

    <div class="wf-page" x-data="{ successOpen: {{ session('success') ? 'true' : 'false' }} }">
        @include('front.partials.wf-nav')

        <section class="pt-12 pb-8 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Kontak</p>
                <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[var(--wf-navy)] leading-tight">
                    Mari Diskusikan Kebutuhan Wedding Organizer Anda
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] max-w-2xl mx-auto">
                    Jadwalkan demo gratis atau konsultasikan paket yang paling sesuai. Tim WOFINS siap membantu.
                </p>
            </div>
        </section>

        <section class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                    @foreach ([
                        ['fa-brands fa-whatsapp', 'WhatsApp', '+62 813-7318-3794', 'https://wa.me/6281373183794'],
                        ['fa-solid fa-envelope', 'Email', 'support@wofins.id', 'mailto:support@wofins.id'],
                        ['fa-solid fa-globe', 'Website', 'wofins.id', 'https://wofins.id'],
                        ['fa-solid fa-location-dot', 'Lokasi', 'Palembang, Indonesia', null],
                    ] as $item)
                        <div class="wf-contact-card">
                            <span class="ornament w-16 h-16 -right-4 -top-4" aria-hidden="true"></span>
                            <div class="relative z-10">
                                <div class="w-10 h-10 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center mb-3">
                                    <i class="{{ $item[0] }}"></i>
                                </div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[var(--wf-muted)]">{{ $item[1] }}</p>
                                @if ($item[3])
                                    <a href="{{ $item[3] }}" class="mt-1 block font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">{{ $item[2] }}</a>
                                @else
                                    <p class="mt-1 font-bold text-[var(--wf-navy)]">{{ $item[2] }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="grid lg:grid-cols-5 gap-8 items-start">
                    <div class="lg:col-span-3 rounded-2xl border border-[var(--wf-line)] bg-white p-6 sm:p-8">
                        <h2 class="text-2xl font-bold text-[var(--wf-navy)]">Kirim pesan / jadwalkan demo</h2>
                        <p class="mt-2 text-sm text-[var(--wf-muted)]">
                            Isi formulir — pesan akan dikirim ke <strong class="text-[var(--wf-navy)]">support@wofins.id</strong>.
                        </p>

                        @if ($paketLabel)
                            <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-[rgba(201,162,39,0.12)] px-3 py-1.5 text-xs font-bold text-[#9a7a12]">
                                <i class="fa-solid fa-tag"></i>
                                Tertarik: {{ $paketLabel }}
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                <ul class="list-disc list-inside text-sm text-red-700 space-y-0.5">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form class="mt-6 space-y-4" method="POST" action="{{ route('kontak.store') }}">
                            @csrf
                            <input type="hidden" name="paket" value="{{ $paketLabel }}">
                            <input type="hidden" name="paket_slug" value="{{ $paket }}">

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="contact_name" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama lengkap *</label>
                                    <input id="contact_name" name="name" type="text" class="wf-input" required
                                           value="{{ old('name', Auth::user()?->name) }}"
                                           placeholder="Nama Anda">
                                </div>
                                <div>
                                    <label for="contact_company" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama WO / perusahaan</label>
                                    <input id="contact_company" name="company" type="text" class="wf-input"
                                           value="{{ old('company') }}"
                                           placeholder="Nama wedding organizer">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="contact_email" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Email *</label>
                                    <input id="contact_email" name="email" type="email" class="wf-input" required
                                           value="{{ old('email', Auth::user()?->email) }}"
                                           placeholder="email@domain.com"
                                           @auth readonly @endauth>
                                </div>
                                <div>
                                    <label for="contact_phone" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">WhatsApp *</label>
                                    <input id="contact_phone" name="phone" type="tel" class="wf-input" required
                                           value="{{ old('phone') }}"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                            </div>

                            <div>
                                <label for="contact_need" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Kebutuhan *</label>
                                <select id="contact_need" name="need" class="wf-input" required>
                                    <option value="">Pilih kebutuhan</option>
                                    @foreach ([
                                        'Demo gratis' => 'Jadwalkan demo gratis',
                                        'Konsultasi paket' => 'Konsultasi paket harga',
                                        'Pertanyaan fitur' => 'Pertanyaan fitur',
                                        'Onboarding' => 'Onboarding / migrasi',
                                        'Lainnya' => 'Lainnya',
                                    ] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('need', $paketLabel ? 'Konsultasi paket' : '') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="contact_message" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Pesan *</label>
                                <textarea id="contact_message" name="message" class="wf-input" rows="5" required
                                          placeholder="Ceritakan singkat kebutuhan WO Anda...">{{ old('message', $paketLabel ? "Saya tertarik dengan {$paketLabel} dan ingin konsultasi lebih lanjut." : '') }}</textarea>
                            </div>

                            <button type="submit" class="wf-btn-navy w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm">
                                <i class="fa-solid fa-paper-plane"></i>
                                Kirim ke support@wofins.id
                            </button>
                        </form>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-6">
                            <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Jam respons</h3>
                            <ul class="space-y-2 text-sm text-[var(--wf-muted)]">
                                <li class="flex gap-2"><i class="fa-solid fa-clock mt-0.5 text-[var(--wf-gold)]"></i> Senin–Jumat · 09:00–17:00 WIB</li>
                                <li class="flex gap-2"><i class="fa-solid fa-bolt mt-0.5 text-[var(--wf-gold)]"></i> Demo biasanya bisa dijadwalkan dalam 1–2 hari kerja</li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-6">
                            <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Yang bisa kami bantu</h3>
                            <ul class="space-y-2.5 text-sm text-[var(--wf-ink)]">
                                @foreach ([
                                    'Demo walkthrough modul WOFINS',
                                    'Rekomendasi paket sesuai ukuran tim',
                                    'Diskusi onboarding & migrasi data',
                                    'Pertanyaan fitur absensi, keuangan, rekonsiliasi',
                                ] as $help)
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 w-5 h-5 rounded-full bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)] inline-flex items-center justify-center text-[0.6rem] shrink-0">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        {{ $help }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="rounded-2xl overflow-hidden relative text-white p-6"
                             style="background: linear-gradient(135deg, #071526 0%, #0b1f3a 60%, #14335a 100%);">
                            <span class="absolute w-24 h-24 rounded-full -right-6 -top-8 bg-[rgba(201,162,39,0.16)]" aria-hidden="true"></span>
                            <div class="relative z-10">
                                <h3 class="text-lg font-bold">Lebih cepat via WhatsApp</h3>
                                <p class="mt-2 text-sm text-white/75">Chat langsung dengan tim sales WOFINS.</p>
                                <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin jadwalkan demo WOFINS.') }}"
                                   class="wf-btn-gold inline-flex items-center justify-center gap-2 mt-5 px-5 py-3 text-sm">
                                    <i class="fa-brands fa-whatsapp"></i>
                                    Chat sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @include('front.partials.wf-footer')

        {{-- Success modal --}}
        <div x-show="successOpen" x-cloak
             class="fixed inset-0 z-[100] flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="contact-success-title">
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
                    <h3 id="contact-success-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                        Email berhasil terkirim
                    </h3>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                        {{ session('success', 'Pesan Anda sudah kami terima. Tim admin WOFINS akan segera menghubungi Anda.') }}
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
    </div>
@endsection
