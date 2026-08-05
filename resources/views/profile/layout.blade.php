@extends('layouts.app')

@section('title', 'Dashboard - ' . (($user ?? null)?->name ?? Auth::user()->name))

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
    </style>
@endpush

@section('content')
@php
    $profileUser = $user ?? Auth::user();
@endphp

<div class="wf-page min-h-screen" x-data="{ sidebarOpen: false }">
    @include('front.partials.wf-nav')

    <div class="py-8 lg:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="wf-alert-ok mb-6" role="alert">
                    <span class="block sm:inline text-sm font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="wf-alert-err mb-6" role="alert">
                    <span class="block sm:inline text-sm font-medium">{{ session('error') }}</span>
                </div>
            @endif

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
                    @yield('profile-content')
                </main>
            </div>
        </div>
    </div>

    @include('front.partials.wf-footer')
</div>

@include('profile.sections.scripts')
@endsection
