@extends('profile.layout')

@section('profile-page-title', 'Dokumentasi')
@section('profile-page-subtitle', 'Daftar artikel dokumentasi (khusus super admin)')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'Dokumen',
        'title' => 'Dokumentasi',
        'subtitle' => 'Kelola artikel panduan dan catatan operasional.',
    ])

    <div class="p-6 space-y-5">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari judul / keyword" class="wf-admin-input">
            <button type="submit" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm shrink-0">Cari</button>
        </form>

        <div class="wf-admin-table-wrap">
            <table class="wf-admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Publikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($docs as $doc)
                        <tr>
                            <td class="font-bold text-[var(--wf-navy)]">{{ $doc->title }}</td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ $doc->category?->name ?? '-' }}</td>
                            <td>
                                <span class="wf-admin-badge {{ $doc->is_published ? 'wf-admin-badge--ok' : 'wf-admin-badge--muted' }}">
                                    {{ $doc->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-sm text-[var(--wf-muted)]">Tidak ada dokumentasi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $docs->links() }}</div>
    </div>
</div>
@endsection
