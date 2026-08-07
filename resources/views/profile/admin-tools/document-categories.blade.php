@extends('profile.layout')

@section('profile-page-title', 'Kategori Dokumen')
@section('profile-page-subtitle', 'Daftar kategori dokumen (khusus super admin)')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'Dokumen',
        'title' => 'Kategori Dokumen',
        'subtitle' => 'Struktur kategori dan aturan approval dokumen.',
    ])

    <div class="p-6 space-y-5">
        <div class="wf-admin-table-wrap">
            <table class="wf-admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kode</th>
                        <th>Tipe</th>
                        <th>Parent</th>
                        <th>Approval</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($categories as $cat)
                        <tr>
                            <td class="font-bold text-[var(--wf-navy)]">{{ $cat->name }}</td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ $cat->code ?? '-' }}</td>
                            <td class="text-xs text-[var(--wf-ink)]">{{ $cat->type ?? '-' }}</td>
                            <td class="text-xs text-[var(--wf-muted)]">{{ $cat->parent?->name ?? '-' }}</td>
                            <td>
                                <span class="wf-admin-badge {{ $cat->is_approval_required ? 'wf-admin-badge--warn' : 'wf-admin-badge--muted' }}">
                                    {{ $cat->is_approval_required ? 'Ya' : 'Tidak' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-[var(--wf-muted)]">Belum ada kategori.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $categories->links() }}</div>
    </div>
</div>
@endsection
