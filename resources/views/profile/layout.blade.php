@extends('layouts.app')

@section('title', 'Dashboard - ' . (($user ?? null)?->name ?? Auth::user()->name))

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
            --wf-white: #ffffff;
        }

        .wf-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: var(--wf-cream);
        }

        .wf-page h1,
        .wf-page h2,
        .wf-page h3 {
            letter-spacing: -0.02em;
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

        .wf-profile-card {
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 18px 40px -28px rgba(11, 31, 58, 0.28);
        }

        .wf-profile-sidebar {
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            overflow: hidden;
            box-shadow: 0 18px 40px -28px rgba(11, 31, 58, 0.28);
        }

        .wf-profile-sidebar-head {
            background: linear-gradient(145deg, var(--wf-navy) 0%, #14335a 100%);
            padding: 1rem 1.15rem;
        }

        .wf-profile-nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.55rem 0.75rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--wf-muted);
            transition: background .15s ease, color .15s ease;
        }

        .wf-profile-nav-link:hover {
            background: var(--wf-cream);
            color: var(--wf-navy);
        }

        .wf-profile-nav-link.is-active {
            background: rgba(11, 31, 58, 0.06);
            color: var(--wf-navy);
        }

        .wf-profile-nav-link.is-active svg {
            color: var(--wf-gold);
        }

        .wf-pro-badge {
            margin-left: auto;
            font-size: 0.625rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--wf-navy-deep);
            background: var(--wf-gold);
            border-radius: 999px;
            padding: 0.15rem 0.45rem;
            line-height: 1.2;
            flex-shrink: 0;
        }

        /* Preview Pro: konten bisa dilihat, aksi tidak bisa dipakai */
        .wf-pro-readonly form,
        .wf-pro-readonly button[type="submit"],
        .wf-pro-readonly input:not([type="hidden"]),
        .wf-pro-readonly select,
        .wf-pro-readonly textarea,
        .wf-pro-readonly a[href]:not(.wf-pro-allow) {
            pointer-events: none !important;
            opacity: 0.55;
            cursor: not-allowed !important;
        }

        .wf-pro-readonly button[type="button"].wf-pro-allow,
        .wf-pro-readonly .wf-pro-allow {
            pointer-events: auto !important;
            opacity: 1 !important;
            cursor: pointer !important;
        }

        .wf-alert-ok {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
        }

        .wf-alert-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
        }

        .wf-mobile-menu-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 0.9rem;
            padding: 0.85rem 1rem;
            box-shadow: 0 10px 24px -20px rgba(11, 31, 58, 0.3);
        }

        .wf-tab-active {
            background: var(--wf-navy);
            color: #fff;
            box-shadow: 0 1px 2px rgba(11, 31, 58, 0.12);
        }

        .wf-tab-idle {
            color: var(--wf-muted);
        }

        .wf-tab-idle:hover {
            background: var(--wf-cream);
            color: var(--wf-navy);
        }

        /* Form field seragam di portal profil */
        .wf-page .wf-field {
            display: block;
            width: 100%;
            min-height: 2.625rem;
            margin-top: 0.375rem;
            border-radius: 0.75rem;
            border: 1px solid var(--wf-line);
            background-color: #fff;
            padding: 0.625rem 0.75rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            color: var(--wf-ink);
            box-sizing: border-box;
        }

        .wf-page .wf-field:focus {
            outline: none;
            border-color: var(--wf-gold);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.28);
        }

        .wf-page .wf-field:disabled,
        .wf-page .wf-field[readonly] {
            background-color: var(--wf-cream);
            color: var(--wf-muted);
            cursor: not-allowed;
        }

        .wf-page select.wf-field {
            appearance: none;
            -webkit-appearance: none;
            height: 2.625rem;
            padding-top: 0;
            padding-bottom: 0;
            padding-right: 2.5rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%235c6675'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
        }

        .wf-page input[type="date"].wf-field {
            height: 2.625rem;
            min-height: 2.625rem;
            padding: 0 0.75rem;
            line-height: normal;
            display: flex;
            align-items: center;
            -webkit-appearance: none;
            appearance: none;
        }

        /* WebKit/Safari: teks tanggal sering nempel ke atas — paksa center */
        .wf-page input[type="date"].wf-field::-webkit-datetime-edit,
        .wf-page input[type="date"].wf-field::-webkit-datetime-edit-fields-wrapper {
            display: flex;
            align-items: center;
            height: 100%;
            padding: 0;
            margin: 0;
        }

        .wf-page input[type="date"].wf-field::-webkit-datetime-edit-text,
        .wf-page input[type="date"].wf-field::-webkit-datetime-edit-month-field,
        .wf-page input[type="date"].wf-field::-webkit-datetime-edit-day-field,
        .wf-page input[type="date"].wf-field::-webkit-datetime-edit-year-field {
            padding: 0;
            margin: 0;
            line-height: 1.25rem;
        }

        .wf-page input[type="date"].wf-field::-webkit-calendar-picker-indicator {
            opacity: 0.55;
            cursor: pointer;
            margin: 0;
            padding: 0;
            align-self: center;
        }

        .wf-page textarea.wf-field {
            min-height: 5.5rem;
            resize: vertical;
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
    $profileUser = $user ?? Auth::user();
@endphp

<div class="wf-page min-h-screen" x-data="{
    sidebarOpen: false,
    successOpen: {{ session('success') ? 'true' : 'false' }},
    errorOpen: {{ session('error') ? 'true' : 'false' }}
}">
    @include('front.partials.wf-nav')

    <div class="py-8 lg:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)] mb-2">Portal Karyawan</p>
                <h1 class="text-3xl font-bold text-[var(--wf-navy)]">@yield('profile-page-title', 'Dashboard Profil')</h1>
                <p class="text-[var(--wf-muted)] mt-2">@yield('profile-page-subtitle', 'Kelola informasi akun dan data HR Anda')</p>
            </div>

            <div class="lg:hidden mb-6">
                <button type="button" class="wf-mobile-menu-btn" @click="sidebarOpen = !sidebarOpen">
                    <span class="text-sm font-semibold text-[var(--wf-navy)]">Menu Dashboard</span>
                    <svg class="w-5 h-5 text-[var(--wf-muted)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <aside class="lg:w-64 shrink-0" :class="sidebarOpen ? 'block' : 'hidden lg:block'">
                    @include('profile.partials.sidebar', ['profileUser' => $profileUser])
                </aside>

                <main class="flex-1 space-y-6">
                    @if (($adminToolsReadonly ?? false) && request()->routeIs('profile.admin-tools*'))
                        @include('profile.partials.admin-tools-preview-banner')
                        <div class="wf-pro-readonly">
                            @yield('profile-content')
                        </div>
                    @else
                        @yield('profile-content')
                    @endif
                </main>
            </div>
        </div>
    </div>

    @include('front.partials.wf-footer')

    {{-- Success modal --}}
    <div x-show="successOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="profile-success-title">
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
                    <i class="fa-solid fa-circle-check text-2xl"></i>
                </span>
                <h3 id="profile-success-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                    Berhasil
                </h3>
                <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                    {{ session('success', 'Profil berhasil diperbarui.') }}
                </p>
            </div>
            <div class="px-6 py-5 flex justify-center">
                <button type="button" @click="successOpen = false"
                        class="wf-btn-navy inline-flex items-center justify-center px-6 py-3 text-sm">
                    Mengerti
                </button>
            </div>
        </div>
    </div>

    {{-- Error modal --}}
    <div x-show="errorOpen" x-cloak
         class="fixed inset-0 z-[100] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="profile-error-title">
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
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-red-50 text-red-600">
                    <i class="fa-solid fa-circle-exclamation text-2xl"></i>
                </span>
                <h3 id="profile-error-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                    Terjadi kesalahan
                </h3>
                <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                    {{ session('error', 'Terjadi kesalahan. Silakan coba lagi.') }}
                </p>
            </div>
            <div class="px-6 py-5 flex justify-center">
                <button type="button" @click="errorOpen = false"
                        class="wf-btn-navy inline-flex items-center justify-center px-6 py-3 text-sm">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@include('profile.sections.scripts')
@endsection
