@extends('profile.layout')

@section('profile-page-title', 'Ringkasan Admin')
@section('profile-page-subtitle', 'Menu khusus super admin untuk monitoring dan administrasi')

@push('styles')
<style>
    .wf-admin-tile {
        position: relative;
        overflow: hidden;
        display: block;
        border: 1px solid var(--wf-line);
        border-radius: 1.1rem;
        padding: 1.15rem 1.2rem;
        background: #fff;
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }

    .wf-admin-tile::before {
        content: '';
        position: absolute;
        width: 5.5rem;
        height: 5.5rem;
        top: -1.75rem;
        right: -1.5rem;
        border-radius: 40% 60% 55% 45% / 50% 40% 60% 50%;
        background: radial-gradient(circle at 30% 30%, rgba(201, 162, 39, 0.2), transparent 72%);
        pointer-events: none;
    }

    .wf-admin-tile::after {
        content: '';
        position: absolute;
        width: 2.4rem;
        height: 2.4rem;
        bottom: 0.85rem;
        left: -0.65rem;
        border-radius: 999px;
        border: 2px solid rgba(201, 162, 39, 0.2);
        pointer-events: none;
        opacity: 0.7;
    }

    .wf-admin-tile:hover {
        transform: translateY(-2px);
        border-color: rgba(201, 162, 39, 0.45);
        box-shadow: 0 16px 36px -24px rgba(11, 31, 58, 0.35);
    }

    .wf-admin-tile__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 0.75rem;
        background: linear-gradient(145deg, var(--wf-navy) 0%, #14335a 100%);
        color: var(--wf-gold-soft);
        font-size: 0.9rem;
        margin-bottom: 0.85rem;
        position: relative;
        z-index: 1;
    }

    .wf-admin-tile__title {
        position: relative;
        z-index: 1;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--wf-navy);
        letter-spacing: -0.01em;
    }

    .wf-admin-tile__meta {
        position: relative;
        z-index: 1;
        margin-top: 0.3rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--wf-muted);
    }

    .wf-admin-tile__arrow {
        position: absolute;
        top: 1.15rem;
        right: 1.1rem;
        z-index: 1;
        color: var(--wf-gold);
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity .18s ease, transform .18s ease;
        font-size: 0.75rem;
    }

    .wf-admin-tile:hover .wf-admin-tile__arrow {
        opacity: 1;
        transform: translateX(0);
    }
</style>
@endpush

@section('profile-content')
@php
    $tiles = [
        [
            'href' => route('profile.admin-tools.users'),
            'title' => 'Manajemen Pengguna',
            'meta' => number_format($usersCount).' pengguna',
            'icon' => 'fa-users',
        ],
        [
            'href' => route('profile.admin-tools.roles'),
            'title' => 'Role & Permission',
            'meta' => number_format($rolesCount).' role',
            'icon' => 'fa-user-shield',
        ],
        [
            'href' => route('profile.admin-tools.company'),
            'title' => 'Pengaturan Perusahaan',
            'meta' => number_format($companiesCount).' data',
            'icon' => 'fa-building',
        ],
        [
            'href' => route('profile.admin-tools.sops'),
            'title' => 'SOP',
            'meta' => number_format($sopsCount).' SOP',
            'icon' => 'fa-clipboard-list',
        ],
        [
            'href' => route('profile.admin-tools.projects'),
            'title' => 'Proyek Wedding',
            'meta' => number_format($projectsCount).' proyek',
            'icon' => 'fa-ring',
        ],
        [
            'href' => route('profile.admin-tools.nota-dinas'),
            'title' => 'Nota Dinas',
            'meta' => number_format($notaDinasCount).' nota | '.number_format($notaDinasDetailsCount).' detail',
            'icon' => 'fa-file-invoice',
        ],
        [
            'href' => route('profile.admin-tools.bank-statements'),
            'title' => 'Bank Statement',
            'meta' => number_format($bankStatementsCount).' statement',
            'icon' => 'fa-university',
        ],
        [
            'href' => route('profile.admin-tools.documentations'),
            'title' => 'Dokumentasi',
            'meta' => number_format($documentationsCount).' artikel',
            'icon' => 'fa-book',
        ],
        [
            'href' => route('profile.admin-tools.document-categories'),
            'title' => 'Kategori Dokumen',
            'meta' => number_format($documentCategoriesCount).' kategori',
            'icon' => 'fa-folder-open',
        ],
        [
            'href' => route('profile.admin-tools.help-center'),
            'title' => 'Pusat Bantuan',
            'meta' => 'Panduan penggunaan',
            'icon' => 'fa-life-ring',
        ],
    ];
@endphp

<div class="wf-profile-card overflow-hidden">
    <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
            <span class="absolute w-14 h-14 rounded-full left-8 -bottom-6 bg-[rgba(255,255,255,0.08)]"></span>
            <span class="absolute w-9 h-9 rounded-[0.55rem] right-28 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
        </div>
        <div class="relative z-[1]">
            <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Admin Tools</p>
            <div class="mt-1 text-xl font-bold text-white">Ringkasan Admin</div>
            <div class="mt-1 text-sm text-white/70">Akses cepat ke modul monitoring dan administrasi.</div>
        </div>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($tiles as $tile)
                <a href="{{ $tile['href'] }}" class="wf-admin-tile">
                    <span class="wf-admin-tile__arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span>
                    <span class="wf-admin-tile__icon" aria-hidden="true">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </span>
                    <div class="wf-admin-tile__title">{{ $tile['title'] }}</div>
                    <div class="wf-admin-tile__meta">{{ $tile['meta'] }}</div>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
