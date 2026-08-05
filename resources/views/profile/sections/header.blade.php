@php
    $user = $user ?? Auth::user();
@endphp
<div class="px-6 py-8 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
    <div class="flex items-center gap-6">
        <div class="relative group shrink-0">
            @if($user->avatar_url)
                <img class="h-24 w-24 rounded-full object-cover border-4 border-white/90 shadow-lg transition-transform duration-300 group-hover:scale-105 ring-2 ring-[var(--wf-gold)]/50"
                    src="{{ Storage::url($user->avatar_url) }}"
                    alt="Profile {{ $user->name }}"
                    onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=e8d48b&background=0b1f3a&size=128&font-size=0.4'">
            @else
                <img class="h-24 w-24 rounded-full object-cover border-4 border-white/90 shadow-lg transition-transform duration-300 group-hover:scale-105 ring-2 ring-[var(--wf-gold)]/50"
                    src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&color=e8d48b&background=0b1f3a&size=128&font-size=0.4"
                    alt="Profile {{ $user->name }}">
            @endif
            <div class="absolute -bottom-1 -right-1 h-6 w-6 border-2 border-white rounded-full bg-emerald-400"></div>
        </div>

        <div class="text-white flex-1 min-w-0">
            <h2 class="text-2xl font-bold tracking-tight truncate">{{ $user->name }}</h2>
            <p class="font-medium mt-1 text-white/70 truncate">{{ $user->email }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-white/10 text-white border border-white/15">
                    <svg class="w-4 h-4 inline mr-1 text-[var(--wf-gold-soft)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-4 0v2"></path>
                    </svg>
                    ID: #WO{{ str_pad($user->id, 4, '0', STR_PAD_LEFT) }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-[var(--wf-gold)]/20 text-[var(--wf-gold-soft)] border border-[var(--wf-gold)]/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span>
                    Aktif
                </span>
            </div>
        </div>

        <div class="hidden md:flex flex-col items-end gap-1 text-right shrink-0">
            <p class="text-xs font-medium text-white/55">Profil Diperbarui</p>
            <p class="text-sm font-semibold text-white/90">{{ $user->updated_at->diffForHumans() }}</p>
        </div>
    </div>
</div>
