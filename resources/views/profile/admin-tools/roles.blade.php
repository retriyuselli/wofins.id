@extends('profile.layout')

@section('profile-page-title', 'Role & Permission')
@section('profile-page-subtitle', 'Daftar role beserta jumlah permission (khusus super admin)')

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
            <span class="absolute w-14 h-14 rounded-full left-8 -bottom-6 bg-[rgba(255,255,255,0.08)]"></span>
            <span class="absolute w-9 h-9 rounded-[0.55rem] right-28 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
        </div>
        <div class="relative z-[1]">
            <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Akses</p>
            <div class="mt-1 text-xl font-bold text-white">Role & Permission</div>
            <div class="mt-1 text-sm text-white/70">Pantau role sistem dan jumlah permission yang terpasang.</div>
        </div>
    </div>

    <div class="p-6">
        <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                        <th class="py-3 px-4 font-semibold">Role</th>
                        <th class="py-3 px-4 font-semibold text-right">Permissions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($roles as $role)
                        <tr class="text-[var(--wf-ink)] hover:bg-[var(--wf-cream)]/40 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[var(--wf-navy)]">{{ $role->name }}</td>
                            <td class="py-3.5 px-4 text-right">
                                <span class="inline-flex items-center justify-center min-w-[2.5rem] px-3 py-1 rounded-full text-xs font-bold border border-[rgba(201,162,39,0.28)] bg-[rgba(201,162,39,0.12)] text-[var(--wf-navy)] tabular-nums">
                                    {{ number_format((int) $role->permissions_count) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-8 px-4 text-center text-sm text-[var(--wf-muted)]">
                                Belum ada role.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
