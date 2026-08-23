@extends('layouts.app')

@section('title', 'Daftar Crew — '.$company->company_name)

@push('styles')
@include('front.partials.wf-front-base-styles')
<style>
    [x-cloak] { display: none !important; }
    .wf-field,
    select.wf-field {
        display: block;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        min-height: 2.75rem;
        margin-top: 0.35rem;
        border-radius: 0.75rem;
        border: 1px solid var(--wf-line);
        background: #fff;
        padding: 0.65rem 0.9rem;
        font-size: 0.925rem;
        color: var(--wf-ink);
        -webkit-appearance: none;
        appearance: none;
    }
    select.wf-field {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='none' stroke='%2364748b' stroke-width='2' viewBox='0 0 24 24'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.25rem;
    }
    .wf-field:focus {
        outline: none;
        border-color: var(--wf-gold);
        box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.2);
    }
    .wf-alert-err {
        border-radius: 0.9rem;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
        padding: 0.9rem 1rem;
    }
</style>
@endpush

@section('content')
@php
    $labelCompany = $company->company_name ?: config('app.name');
@endphp

<div class="wf-page">
    @include('front.partials.wf-nav')

    <section class="pt-12 pb-16 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-8">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-2">Crew Freelance</p>
                <h1 class="text-3xl sm:text-4xl font-bold text-[var(--wf-navy)] leading-tight">
                    Daftar sebagai Crew
                </h1>
                <p class="mt-3 text-[var(--wf-muted)]">
                    Lengkapi data Anda untuk bergabung dengan <strong class="text-[var(--wf-navy)]">{{ $labelCompany }}</strong>.
                    Tidak perlu akun WOFINS.
                </p>
            </div>

            @if ($errors->any())
                <div class="wf-alert-err mb-4">
                    <p class="text-sm font-semibold mb-1">Terdapat kesalahan dalam form:</p>
                    <ul class="text-sm list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="rounded-2xl border border-[var(--wf-line)] bg-white shadow-sm overflow-hidden"
                 x-data="{ tab: 'profil' }">
                <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-gradient-to-r from-[var(--wf-navy)] to-[#14335a] relative overflow-hidden">
                    <div class="absolute -right-8 -top-10 w-40 h-40 rounded-full bg-[rgba(201,162,39,0.2)]" aria-hidden="true"></div>
                    <div class="relative z-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--wf-gold-soft)]">Form undangan</p>
                        <h2 class="text-lg font-bold text-white mt-1">{{ $labelCompany }}</h2>
                        <p class="text-sm text-white/70 mt-1">Isi profil dan peran Anda di lapangan.</p>
                    </div>
                </div>

                <form action="{{ route('crew.invite.store', ['token' => $token]) }}" method="POST"
                      class="p-6 space-y-8">
                    @csrf

                    <div class="flex flex-wrap gap-2">
                        <template x-for="item in [
                            { id: 'profil', label: 'Profil', icon: 'fa-user' },
                            { id: 'detail', label: 'Detail', icon: 'fa-briefcase' },
                        ]" :key="item.id">
                            <button type="button"
                                    @click="tab = item.id"
                                    :class="tab === item.id
                                        ? 'bg-[var(--wf-navy)] text-white border-[var(--wf-navy)]'
                                        : 'bg-white text-[var(--wf-navy)] border-[var(--wf-line)] hover:border-[var(--wf-gold)]'"
                                    class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-sm font-semibold transition">
                                <i class="fa-solid text-xs" :class="item.icon"></i>
                                <span x-text="item.label"></span>
                            </button>
                        </template>
                    </div>

                    <div x-show="tab === 'profil'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="nama_lengkap" class="block text-sm font-semibold text-[var(--wf-navy)]">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nama_lengkap" name="nama_lengkap" required
                                   value="{{ old('nama_lengkap') }}"
                                   placeholder="Masukkan nama lengkap"
                                   class="wf-field">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-semibold text-[var(--wf-navy)]">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" id="email" name="email" required
                                   value="{{ old('email') }}"
                                   placeholder="contoh@email.com"
                                   class="wf-field">
                        </div>
                        <div>
                            <label for="nomor_telepon" class="block text-sm font-semibold text-[var(--wf-navy)]">Nomor Telepon / WA</label>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-[var(--wf-line)] bg-[var(--wf-cream)] text-sm text-[var(--wf-muted)]">+62</span>
                                <input type="tel" id="nomor_telepon" name="nomor_telepon"
                                       value="{{ old('nomor_telepon') }}"
                                       placeholder="8123456789"
                                       class="wf-field !rounded-l-none">
                            </div>
                        </div>
                        <div>
                            <label for="tanggal_lahir" class="block text-sm font-semibold text-[var(--wf-navy)]">Tanggal Lahir</label>
                            <input type="date" id="tanggal_lahir" name="tanggal_lahir"
                                   value="{{ old('tanggal_lahir') }}"
                                   max="{{ date('Y-m-d') }}"
                                   class="wf-field">
                        </div>
                        <div class="w-full min-w-0">
                            <label for="jenis_kelamin" class="block text-sm font-semibold text-[var(--wf-navy)]">Jenis Kelamin</label>
                            <select id="jenis_kelamin" name="jenis_kelamin" class="wf-field w-full">
                                <option value="">Pilih jenis kelamin</option>
                                <option value="Laki-laki" @selected(old('jenis_kelamin') === 'Laki-laki')>Laki-laki</option>
                                <option value="Perempuan" @selected(old('jenis_kelamin') === 'Perempuan')>Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="tab === 'detail'" x-cloak class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="pekerjaan" class="block text-sm font-semibold text-[var(--wf-navy)]">Peran / Keahlian</label>
                                <input type="text" id="pekerjaan" name="pekerjaan"
                                       value="{{ old('pekerjaan') }}"
                                       placeholder="Contoh: Decorator / MUA / Runner"
                                       class="wf-field">
                            </div>
                            <div>
                                <label for="alamat" class="block text-sm font-semibold text-[var(--wf-navy)]">Alamat</label>
                                <textarea id="alamat" name="alamat" rows="3" class="wf-field"
                                          placeholder="Kota / alamat singkat">{{ old('alamat') }}</textarea>
                            </div>
                            <div>
                                <label for="motivasi_kerja" class="block text-sm font-semibold text-[var(--wf-navy)]">Pengalaman / Motivasi</label>
                                <textarea id="motivasi_kerja" name="motivasi_kerja" rows="4" class="wf-field"
                                          placeholder="Ceritakan pengalaman wedding Anda">{{ old('motivasi_kerja') }}</textarea>
                            </div>
                            <div>
                                <label for="pelatihan" class="block text-sm font-semibold text-[var(--wf-navy)]">Pelatihan / Sertifikasi</label>
                                <textarea id="pelatihan" name="pelatihan" rows="4" class="wf-field"
                                          placeholder="Opsional">{{ old('pelatihan') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2 border-t border-[var(--wf-line)]">
                        <button type="submit" class="wf-btn-gold inline-flex items-center justify-center px-6 py-3 text-sm">
                            <i class="fa-solid fa-paper-plane mr-2"></i> Kirim Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    @include('front.partials.wf-footer')
</div>
@endsection
