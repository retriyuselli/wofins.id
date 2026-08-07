@extends('profile.layout')

@section('profile-page-title', 'Manajemen Pengguna')
@section('profile-page-subtitle', 'Daftar pengguna sistem (khusus super admin)')

@push('styles')
<style>
    .wf-users-input {
        width: 100%;
        border: 1.5px solid var(--wf-line);
        border-radius: 0.85rem;
        padding: 0.65rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--wf-navy);
        background: #fff;
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .wf-users-input:focus {
        border-color: rgba(201, 162, 39, 0.75);
        box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.18);
    }

    .wf-users-role {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.7rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        border: 1px solid rgba(201, 162, 39, 0.28);
        background: rgba(201, 162, 39, 0.12);
        color: var(--wf-navy);
    }
</style>
@endpush

@section('profile-content')
<div class="wf-profile-card overflow-hidden">
    <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
        <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
            <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
            <span class="absolute w-14 h-14 rounded-full left-8 -bottom-6 bg-[rgba(255,255,255,0.08)]"></span>
            <span class="absolute w-9 h-9 rounded-[0.55rem] right-28 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
        </div>
        <div class="relative z-[1]">
            <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Pengguna</p>
            <div class="mt-1 text-xl font-bold text-white">Manajemen Pengguna</div>
            <div class="mt-1 text-sm text-white/70">Cari dan pantau daftar pengguna sistem.</div>
        </div>
    </div>

    <div class="p-6 space-y-5">
        <form method="GET" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama / email"
                class="wf-users-input">
            <button type="submit" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm shrink-0">
                Cari
            </button>
        </form>

        <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                        <th class="py-3 px-4 font-semibold">Nama</th>
                        <th class="py-3 px-4 font-semibold">Email</th>
                        <th class="py-3 px-4 font-semibold">Role</th>
                        <th class="py-3 px-4 font-semibold">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--wf-line)]">
                    @forelse($users as $u)
                        <tr class="text-[var(--wf-ink)] hover:bg-[var(--wf-cream)]/40 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-[var(--wf-navy)]">{{ $u->name }}</td>
                            <td class="py-3.5 px-4 text-[var(--wf-ink)]">{{ $u->email }}</td>
                            <td class="py-3.5 px-4">
                                @php $roleNames = $u->getRoleNames(); @endphp
                                @if($roleNames->isEmpty())
                                    <span class="text-xs text-[var(--wf-muted)]">-</span>
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($roleNames as $rn)
                                            <span class="wf-users-role">{{ $rn }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-xs font-medium text-[var(--wf-muted)]">
                                {{ optional($u->updated_at)->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 px-4 text-center text-sm text-[var(--wf-muted)]">
                                Tidak ada pengguna ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-1">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
