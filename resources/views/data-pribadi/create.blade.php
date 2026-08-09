@extends('profile.layout')

@section('title', 'Tambah Crew Freelance - '.(Auth::user()->name ?? 'WOFINS'))
@section('profile-page-title', 'Tambah Crew Freelance')
@section('profile-page-subtitle', 'Data crew freelance untuk '.($tenantCompanyName ?? ($companyName ?? 'perusahaan Anda')).' — bukan data pribadi akun user')

@section('profile-content')
@php
    $labelCompany = $tenantCompanyName ?? ($companyName ?? config('app.name'));
@endphp

@if (session('error'))
    <div class="wf-alert-err mb-4">
        <p class="text-sm font-semibold">{{ session('error') }}</p>
    </div>
@endif

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

<div class="wf-profile-card overflow-hidden">
    <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-gradient-to-r from-[var(--wf-navy)] to-[#14335a] relative overflow-hidden">
        <div class="absolute -right-8 -top-10 w-40 h-40 rounded-full bg-[rgba(201,162,39,0.2)]" aria-hidden="true"></div>
        <div class="absolute right-16 bottom-0 w-24 h-24 rounded-full border border-[rgba(232,212,139,0.3)]" aria-hidden="true"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--wf-gold-soft)]">Crew Freelance</p>
                <h2 class="text-lg font-bold text-white mt-1">Formulir Data Crew</h2>
                <p class="text-sm text-white/70 mt-1">Bergabung dengan {{ $labelCompany }}</p>
            </div>
            <a href="{{ route('data-pribadi.index') }}" class="inline-flex items-center justify-center rounded-full bg-white/10 border border-white/20 px-4 py-2 text-sm font-semibold text-white hover:bg-white/15 transition">
                <i class="fa-solid fa-arrow-left mr-2 text-xs"></i> Kembali ke daftar
            </a>
        </div>
    </div>

    <form action="{{ route('data-pribadi.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-8"
          x-data="{ tab: 'profil' }">
        @csrf

        <div class="flex flex-wrap gap-2">
            <template x-for="item in [
                { id: 'profil', label: 'Profil', icon: 'fa-user' },
                { id: 'kontak', label: 'Kontak', icon: 'fa-phone' },
                { id: 'detail', label: 'Detail', icon: 'fa-briefcase' },
                { id: 'motivasi', label: 'Motivasi', icon: 'fa-quote-left' },
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
        </div>

        <div x-show="tab === 'kontak'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="nomor_telepon" class="block text-sm font-semibold text-[var(--wf-navy)]">Nomor Telepon</label>
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
        </div>

        <div x-show="tab === 'detail'" x-cloak class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="jenis_kelamin" class="block text-sm font-semibold text-[var(--wf-navy)]">Jenis Kelamin</label>
                    <select id="jenis_kelamin" name="jenis_kelamin" class="wf-field">
                        <option value="">Pilih jenis kelamin</option>
                        <option value="Laki-laki" @selected(old('jenis_kelamin') === 'Laki-laki')>Laki-laki</option>
                        <option value="Perempuan" @selected(old('jenis_kelamin') === 'Perempuan')>Perempuan</option>
                    </select>
                </div>
                <div>
                    <label for="pekerjaan" class="block text-sm font-semibold text-[var(--wf-navy)]">Pekerjaan / Peran</label>
                    <input type="text" id="pekerjaan" name="pekerjaan"
                           value="{{ old('pekerjaan') }}"
                           placeholder="Contoh: Decorator / AM"
                           class="wf-field">
                </div>
            </div>

            <div>
                <label for="alamat" class="block text-sm font-semibold text-[var(--wf-navy)]">Alamat</label>
                <textarea id="alamat" name="alamat" rows="3" class="wf-field"
                          placeholder="Alamat lengkap">{{ old('alamat') }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="gaji" class="block text-sm font-semibold text-[var(--wf-navy)]">Fee {{ $labelCompany }} (Rp)</label>
                    <input type="number" id="gaji" name="gaji" min="0"
                           value="{{ old('gaji') }}"
                           placeholder="300000"
                           class="wf-field">
                </div>
                <div>
                    <label for="foto" class="block text-sm font-semibold text-[var(--wf-navy)]">
                        Foto Profil <span class="text-red-500">*</span>
                    </label>
                    <input type="file" id="foto" name="foto" accept="image/*" required
                           class="wf-field"
                           onchange="previewDataPribadiFoto(this)">
                    <p class="mt-1 text-xs text-[var(--wf-muted)]">Maks. 1MB · JPG/PNG/GIF</p>
                    <img id="foto-preview" src="#" alt="Pratinjau"
                         class="mt-3 hidden h-28 w-28 rounded-xl object-cover border border-[var(--wf-line)]">
                </div>
            </div>
        </div>

        <div x-show="tab === 'motivasi'" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="motivasi_kerja" class="block text-sm font-semibold text-[var(--wf-navy)]">Motivasi Kerja</label>
                <textarea id="motivasi_kerja" name="motivasi_kerja" rows="4" class="wf-field"
                          placeholder="Ceritakan motivasi Anda">{{ old('motivasi_kerja') }}</textarea>
            </div>
            <div>
                <label for="pelatihan" class="block text-sm font-semibold text-[var(--wf-navy)]">Pelatihan {{ $labelCompany }}</label>
                <textarea id="pelatihan" name="pelatihan" rows="4" class="wf-field"
                          placeholder="Pelatihan yang pernah diikuti">{{ old('pelatihan') }}</textarea>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 pt-2 border-t border-[var(--wf-line)]">
            <button type="submit" class="wf-btn-gold inline-flex items-center justify-center px-6 py-3 text-sm">
                <i class="fa-solid fa-check mr-2"></i> Simpan Data
            </button>
            <a href="{{ route('data-pribadi.index') }}" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">
                Batal
            </a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function previewDataPribadiFoto(input) {
        const preview = document.getElementById('foto-preview');
        const file = input.files?.[0];
        if (!file) {
            preview.classList.add('hidden');
            preview.src = '#';
            return;
        }
        if (file.size > 1024 * 1024) {
            alert('Ukuran file terlalu besar! Maksimal 1MB.');
            input.value = '';
            preview.classList.add('hidden');
            preview.src = '#';
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
</script>
@endpush
