@extends('profile.layout')

@section('title', 'Crew Freelance - '.(Auth::user()->name ?? 'WOFINS'))
@section('profile-page-title', 'Crew Freelance')
@section('profile-page-subtitle', 'Daftar crew freelance '.($tenantCompanyName ?? ($companyName ?? 'perusahaan Anda')).' — bukan data pribadi akun user')

@section('profile-content')
@php
    $labelCompany = $tenantCompanyName ?? ($companyName ?? config('app.name'));
@endphp

@if (session('success'))
    <div class="mb-4 rounded-xl border border-[rgba(31,122,77,0.25)] bg-[#f0faf4] px-4 py-3 text-sm text-[#1f7a4d]">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="wf-alert-err mb-4">
        <p class="text-sm font-semibold">{{ session('error') }}</p>
    </div>
@endif

<div class="wf-profile-card overflow-hidden">
    <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-gradient-to-r from-[var(--wf-navy)] to-[#14335a] relative overflow-hidden">
        <div class="absolute -right-6 -top-8 w-36 h-36 rounded-full bg-[rgba(201,162,39,0.18)]" aria-hidden="true"></div>
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-[var(--wf-gold-soft)]">Crew Freelance</p>
                <h2 class="text-lg font-bold text-white mt-1">Crew {{ $labelCompany }}</h2>
                <p class="text-sm text-white/70 mt-1">{{ $dataPribadis->total() }} data tersimpan</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <form action="{{ route('data-pribadi.index') }}" method="GET" class="flex-1 sm:min-w-[240px]">
                    <div class="relative">
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Cari nama lengkap..."
                               class="w-full rounded-full border border-white/20 bg-white/10 px-4 py-2.5 pr-10 text-sm text-white placeholder:text-white/55 focus:outline-none focus:ring-2 focus:ring-[var(--wf-gold)]">
                        <button type="submit" class="absolute inset-y-0 right-0 px-3 text-white/70 hover:text-white" aria-label="Cari">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>
                    </div>
                </form>
                <a href="{{ route('data-pribadi.create') }}"
                   class="inline-flex items-center justify-center rounded-full bg-[var(--wf-gold)] px-5 py-2.5 text-sm font-bold text-[var(--wf-navy-deep)] hover:brightness-105 transition">
                    <i class="fa-solid fa-plus mr-2 text-xs"></i> Tambah Data
                </a>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        @if ($dataPribadis->isEmpty())
            <div class="rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-6 py-10 text-center">
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white border border-[var(--wf-line)] text-[var(--wf-gold)]">
                    <i class="fa-solid fa-users"></i>
                </div>
                @if (request()->filled('search'))
                    <h3 class="text-base font-bold text-[var(--wf-navy)]">Tidak ada hasil</h3>
                    <p class="mt-1 text-sm text-[var(--wf-muted)]">
                        Tidak ada data untuk “{{ request('search') }}”.
                        <a href="{{ route('data-pribadi.index') }}" class="font-semibold text-[var(--wf-navy)] underline">Lihat semua</a>
                    </p>
                @else
                    <h3 class="text-base font-bold text-[var(--wf-navy)]">Belum ada data</h3>
                    <p class="mt-1 text-sm text-[var(--wf-muted)]">
                        Mulai dengan
                        <a href="{{ route('data-pribadi.create') }}" class="font-semibold text-[var(--wf-navy)] underline">menambah data crew</a>.
                    </p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
                <table class="min-w-full text-sm">
                    <thead class="bg-[var(--wf-navy)] text-[var(--wf-cream)]">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold">No</th>
                            <th class="px-4 py-3 text-left font-semibold">Foto</th>
                            <th class="px-4 py-3 text-left font-semibold">Nama</th>
                            <th class="px-4 py-3 text-left font-semibold">Email</th>
                            <th class="px-4 py-3 text-left font-semibold">Telepon</th>
                            <th class="px-4 py-3 text-left font-semibold">Pekerjaan</th>
                            <th class="px-4 py-3 text-left font-semibold">Fee</th>
                            <th class="px-4 py-3 text-left font-semibold">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)] bg-white">
                        @foreach ($dataPribadis as $index => $data)
                            <tr class="hover:bg-[var(--wf-cream)]/70 transition">
                                <td class="px-4 py-3 text-[var(--wf-muted)]">{{ $dataPribadis->firstItem() + $index }}</td>
                                <td class="px-4 py-3">
                                    @if ($data->foto_url)
                                        <img src="{{ $data->foto_url }}" alt="{{ $data->nama_lengkap }}"
                                             class="h-10 w-10 rounded-full object-cover border border-[var(--wf-line)]">
                                    @else
                                        <div class="h-10 w-10 rounded-full bg-[var(--wf-navy)] text-[var(--wf-gold-soft)] flex items-center justify-center text-xs font-bold">
                                            {{ $data->initials }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold text-[var(--wf-navy)]">{{ $data->nama_lengkap }}</td>
                                <td class="px-4 py-3 text-[var(--wf-muted)]">{{ $data->email }}</td>
                                <td class="px-4 py-3 text-[var(--wf-muted)]">
                                    {{ $data->nomor_telepon ? '+62'.$data->nomor_telepon : '—' }}
                                </td>
                                <td class="px-4 py-3 text-[var(--wf-muted)]">{{ $data->pekerjaan ?: '—' }}</td>
                                <td class="px-4 py-3 font-medium text-[var(--wf-navy)]">{{ $data->formatted_gaji }}</td>
                                <td class="px-4 py-3 text-[var(--wf-muted)]">{{ $data->created_at?->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $dataPribadis->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
