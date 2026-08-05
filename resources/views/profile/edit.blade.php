@extends('profile.layout')

@section('profile-page-title', 'Edit Profil')
@section('profile-page-subtitle', 'Perbarui informasi profil dan pengaturan akun Anda')

@section('profile-content')
@if ($errors->any())
    <div class="wf-alert-err mb-2">
        <p class="text-sm font-semibold mb-1">Terdapat beberapa kesalahan:</p>
        <ul class="text-sm list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('PATCH')

    <div class="wf-profile-card">
        <div class="p-6 space-y-6">
            <h2 class="text-lg font-bold text-[var(--wf-navy)]">Informasi Pribadi</h2>

            <div class="flex items-center gap-6">
                <div class="shrink-0">
                    @if ($user->avatar_url)
                        <img class="h-24 w-24 object-cover rounded-full border-4 border-white shadow-sm ring-2 ring-[var(--wf-gold)]/40"
                             src="{{ Storage::url($user->avatar_url) }}"
                             alt="Current profile photo">
                    @else
                        <div class="h-24 w-24 rounded-full bg-[var(--wf-cream)] border border-[var(--wf-line)] flex items-center justify-center text-[var(--wf-muted)] text-3xl">
                            <i class="fas fa-user"></i>
                        </div>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-[var(--wf-navy)]">Foto Profil</label>
                    <div class="mt-1">
                        <input type="file" name="avatar" id="avatar" accept="image/*"
                               class="block w-full text-sm text-[var(--wf-muted)] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[var(--wf-cream)] file:text-[var(--wf-navy)] hover:file:bg-[var(--wf-gold-soft)]">
                    </div>
                    <p class="mt-2 text-xs text-[var(--wf-muted)]">JPG, GIF or PNG. Max 2MB.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-y-5 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-semibold text-[var(--wf-navy)]">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>

                <div class="sm:col-span-3">
                    <label for="email" class="block text-sm font-semibold text-[var(--wf-navy)]">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required readonly
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-3 py-2.5 text-sm text-[var(--wf-muted)] cursor-not-allowed">
                    <p class="mt-1 text-xs text-[var(--wf-muted)]">Email mengikuti akun login dan tidak dapat diubah.</p>
                </div>

                <div class="sm:col-span-3">
                    <label for="phone_number" class="block text-sm font-semibold text-[var(--wf-navy)]">Nomor Telepon</label>
                    <input type="text" name="phone_number" id="phone_number" value="{{ old('phone_number', $user->phone_number) }}"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>

                <div class="sm:col-span-3">
                    <label for="gender" class="block text-sm font-semibold text-[var(--wf-navy)]">Jenis Kelamin</label>
                    <select id="gender" name="gender"
                            class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                        <option value="">Pilih...</option>
                        <option value="male" {{ old('gender', $user->gender) == 'male' ? 'selected' : '' }}>Laki-laki</option>
                        <option value="female" {{ old('gender', $user->gender) == 'female' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label for="date_of_birth" class="block text-sm font-semibold text-[var(--wf-navy)]">Tanggal Lahir</label>
                    <input type="date" name="date_of_birth" id="date_of_birth"
                           value="{{ old('date_of_birth', $user->date_of_birth ? $user->date_of_birth->format('Y-m-d') : '') }}"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>

                <div class="sm:col-span-3">
                    <label for="hire_date" class="block text-sm font-semibold text-[var(--wf-navy)]">Tanggal Mulai Bekerja</label>
                    <input type="date" name="hire_date" id="hire_date"
                           value="{{ old('hire_date', $user->hire_date ? $user->hire_date->format('Y-m-d') : '') }}"
                           readonly
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-3 py-2.5 text-sm text-[var(--wf-muted)] cursor-not-allowed">
                    <p class="mt-1 text-xs text-[var(--wf-muted)]">Hubungi HR untuk mengubah data ini.</p>
                </div>

                <div class="sm:col-span-6">
                    <label for="address" class="block text-sm font-semibold text-[var(--wf-navy)]">Alamat</label>
                    <textarea id="address" name="address" rows="3"
                              class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">{{ old('address', $user->address) }}</textarea>
                </div>

                <div class="sm:col-span-6">
                    <label for="emergency_contact" class="block text-sm font-semibold text-[var(--wf-navy)]">Kontak Darurat</label>
                    <input type="text" name="emergency_contact" id="emergency_contact"
                           value="{{ old('emergency_contact', $user->emergency_contact) }}"
                           placeholder="Nama - Hubungan - No. Telepon"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>

                <div class="sm:col-span-6 border-t border-[var(--wf-line)] pt-4 mt-1">
                    <label class="block text-sm font-semibold text-[var(--wf-navy)]">Tanda Tangan Digital</label>
                    <div class="mt-2 flex items-center gap-6">
                        @if ($user->signature_url)
                            <div class="shrink-0 border border-[var(--wf-line)] rounded-xl p-2 bg-white">
                                <img class="h-16 object-contain"
                                     src="{{ Storage::url($user->signature_url) }}"
                                     alt="Current signature">
                            </div>
                        @endif
                        <div class="grow">
                            <input type="file" name="signature" id="signature" accept="image/png"
                                   class="block w-full text-sm text-[var(--wf-muted)] file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[var(--wf-cream)] file:text-[var(--wf-navy)] hover:file:bg-[var(--wf-gold-soft)]">
                            <p class="mt-1 text-xs text-[var(--wf-muted)]">Format PNG transparan. Max 1MB.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wf-profile-card">
        <div class="p-6 space-y-5">
            <div>
                <h2 class="text-lg font-bold text-[var(--wf-navy)]">Ubah Password</h2>
                <p class="text-sm text-[var(--wf-muted)] mt-1">Kosongkan jika tidak ingin mengubah password.</p>
            </div>

            <div class="grid grid-cols-1 gap-y-5 gap-x-4 sm:grid-cols-6">
                <div class="sm:col-span-6">
                    <label for="current_password" class="block text-sm font-semibold text-[var(--wf-navy)]">Password Saat Ini</label>
                    <input type="password" name="current_password" id="current_password"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>
                <div class="sm:col-span-3">
                    <label for="password" class="block text-sm font-semibold text-[var(--wf-navy)]">Password Baru</label>
                    <input type="password" name="password" id="password"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>
                <div class="sm:col-span-3">
                    <label for="password_confirmation" class="block text-sm font-semibold text-[var(--wf-navy)]">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="mt-1.5 block w-full rounded-xl border border-[var(--wf-line)] bg-white px-3 py-2.5 text-sm text-[var(--wf-ink)] focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]/40 focus:border-[var(--wf-gold)]">
                </div>
            </div>
        </div>
    </div>

    <div class="flex justify-end gap-3">
        <a href="{{ route('profile') }}" class="wf-btn-ghost inline-flex items-center px-5 py-2.5 text-sm">
            Batal
        </a>
        <button type="submit" class="wf-btn-navy inline-flex items-center px-5 py-2.5 text-sm">
            Perbarui Profil
        </button>
    </div>
</form>
@endsection
