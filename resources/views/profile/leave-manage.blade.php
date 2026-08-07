@extends('profile.layout')

@section('profile-page-title', 'Kelola Cuti')
@section('profile-page-subtitle', 'Persetujuan dan monitoring pengajuan cuti karyawan')

@include('profile.admin-tools.partials.wf-admin-styles')

@section('profile-content')
@php
    $statusLabels = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];
@endphp

<div class="wf-profile-card overflow-hidden">
    @include('profile.admin-tools.partials.wf-admin-header', [
        'eyebrow' => 'HR',
        'title' => 'Kelola Cuti',
        'subtitle' => 'Tinjau, setujui, atau tolak pengajuan cuti karyawan.',
    ])

    <div class="p-6 space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Menunggu</div>
                <div class="wf-admin-stat__value text-[#8a6d12]">{{ number_format($pendingCount) }}</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Disetujui</div>
                <div class="wf-admin-stat__value text-emerald-700">{{ number_format($approvedCount) }}</div>
            </div>
            <div class="wf-admin-stat">
                <div class="wf-admin-stat__label">Ditolak</div>
                <div class="wf-admin-stat__value text-rose-700">{{ number_format($rejectedCount) }}</div>
            </div>
        </div>

        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari karyawan / alasan"
                class="wf-admin-input md:col-span-2">

            <select name="status" class="wf-admin-select">
                <option value="">Semua Status</option>
                @foreach ($statusLabels as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="leave_type_id" class="wf-admin-select">
                <option value="">Semua Jenis</option>
                @foreach ($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected((string) $leaveTypeId === (string) $type->id)>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>

            <div class="md:col-span-4 flex flex-wrap gap-2">
                <button type="submit" class="wf-btn-gold inline-flex items-center justify-center px-5 py-2.5 text-sm">
                    Terapkan
                </button>
                <a href="{{ route('profile.leave-manage') }}" class="wf-admin-link-chip">Reset</a>
            </div>
        </form>

        <div class="wf-admin-table-wrap">
            <table class="wf-admin-table min-w-full text-sm">
                <thead>
                    <tr>
                        <th>Karyawan</th>
                        <th>Jenis</th>
                        <th>Periode</th>
                        <th>Hari</th>
                        <th>Status</th>
                        <th>Disetujui Oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse ($requests as $item)
                        @php
                            $badgeClass = match ($item->status) {
                                'approved' => 'wf-admin-badge--ok',
                                'rejected' => 'wf-admin-badge--danger',
                                'pending' => 'wf-admin-badge--warn',
                                default => 'wf-admin-badge--muted',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="font-bold text-[var(--wf-navy)]">{{ $item->user?->name ?? '-' }}</div>
                                <div class="text-[11px] text-[var(--wf-muted)]">{{ $item->user?->email ?? '' }}</div>
                                @if ($item->reason)
                                    <div class="mt-1 text-[11px] text-[var(--wf-muted)] line-clamp-2">{{ $item->reason }}</div>
                                @endif
                            </td>
                            <td class="text-xs text-[var(--wf-ink)]">{{ $item->leaveType?->name ?? '-' }}</td>
                            <td class="text-xs text-[var(--wf-ink)]">
                                <div>{{ optional($item->start_date)->format('d M Y') ?? '-' }}</div>
                                <div class="text-[11px] text-[var(--wf-muted)]">s/d {{ optional($item->end_date)->format('d M Y') ?? '-' }}</div>
                            </td>
                            <td class="text-xs font-bold tabular-nums text-[var(--wf-navy)]">{{ (int) $item->total_days }}</td>
                            <td>
                                <span class="wf-admin-badge {{ $badgeClass }}">
                                    {{ $statusLabels[$item->status] ?? $item->status }}
                                </span>
                            </td>
                            <td class="text-xs text-[var(--wf-muted)]">
                                {{ $item->approver?->name ?? ($item->status === 'pending' ? '—' : '-') }}
                            </td>
                            <td class="text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-2">
                                    @if ($item->status === 'approved')
                                        <a href="{{ route('leave-request.approval-detail', $item) }}"
                                            target="_blank" rel="noopener"
                                            class="wf-admin-link-chip">
                                            Detail
                                        </a>
                                    @endif

                                    @if ($canDecide && $item->status === 'pending')
                                        <form method="POST" action="{{ route('profile.leave-manage.approve', $item) }}"
                                            onsubmit="return confirm('Setujui pengajuan cuti ini?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-full bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition">
                                                Setuju
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('profile.leave-manage.reject', $item) }}"
                                            onsubmit="return confirm('Tolak pengajuan cuti ini?')">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-full bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition">
                                                Tolak
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-[var(--wf-muted)]">
                                Tidak ada pengajuan cuti pada filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $requests->links() }}</div>
    </div>
</div>
@endsection
