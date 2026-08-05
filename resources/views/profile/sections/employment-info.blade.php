<!-- Employment Information Section -->
@php
    $user = $user ?? Auth::user();
@endphp
<div>
    <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2H8a2 2 0 012-2V6m8 0H8m0 0v.01M8 6v6h8V6M8 12v.01"></path>
        </svg>
        Detail Pekerjaan
    </h3>
    <div class="space-y-4">
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Tanggal Bergabung</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium">{{ $user->hire_date ? $user->hire_date->format('d F Y') : $user->created_at->format('d F Y') }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Status</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium">
                @if($user->status_id)
                    {{ $user->status?->status_name ?? 'Status tidak ditemukan' }}
                @else
                    Tidak ada status yang ditetapkan
                @endif
            </p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Pengalaman Kerja</label>
            @php
                $joinedAt = $user->hire_date ?? $user->created_at;
            @endphp
            <p class="text-[var(--wf-gold)] font-semibold text-sm">{{ $joinedAt->diffForHumans(now(), ['parts' => 2, 'join' => ', ', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }} <span aria-hidden="true">*</span></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Alamat</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium">{{ $user->address ?? 'Tidak ditentukan' }}</p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Tanggal Mulai Kerja</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium">{{ $user->hire_date ? $user->hire_date->format('d F Y') : 'Tidak ditentukan' }}</p>
        </div>
    </div>
</div>
