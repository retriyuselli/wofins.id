@extends('profile.layout')

@section('profile-page-title', 'SOP')
@section('profile-page-subtitle', 'Daftar SOP (khusus super admin)')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'Dokumen',
        'title' => 'SOP',
        'subtitle' => 'Cari dan pantau daftar standar operasional prosedur.',
    ])

    <div class="p-6 space-y-5">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari judul / deskripsi / keyword" class="wf-admin-input">
            <button type="submit" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm shrink-0">Cari</button>
        </form>

        <div class="wf-admin-table-wrap">
            <table class="wf-admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Versi</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($sops as $sop)
                        <tr>
                            <td class="font-bold text-[var(--wf-navy)]">{{ $sop->title }}</td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ $sop->category?->name ?? '-' }}</td>
                            <td class="text-xs text-[var(--wf-ink)]">{{ $sop->formatted_version }}</td>
                            <td>
                                <span class="wf-admin-badge {{ $sop->is_active ? 'wf-admin-badge--ok' : 'wf-admin-badge--muted' }}">
                                    {{ $sop->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ optional($sop->updated_at)->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-[var(--wf-muted)]">Tidak ada SOP.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $sops->links() }}</div>
    </div>
</div>
@endsection
