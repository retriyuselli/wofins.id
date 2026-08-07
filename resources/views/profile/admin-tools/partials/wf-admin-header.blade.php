@php
    $eyebrow = $eyebrow ?? 'Admin';
    $title = $title ?? '';
    $subtitle = $subtitle ?? null;
@endphp
<div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <span class="absolute w-28 h-28 rounded-full -right-8 -top-10 bg-[rgba(201,162,39,0.22)]"></span>
        <span class="absolute w-14 h-14 rounded-full left-8 -bottom-6 bg-[rgba(255,255,255,0.08)]"></span>
        <span class="absolute w-9 h-9 rounded-[0.55rem] right-28 bottom-3 rotate-[18deg] border-2 border-[rgba(201,162,39,0.35)]"></span>
    </div>
    <div class="relative z-[1]">
        <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">{{ $eyebrow }}</p>
        <div class="mt-1 text-xl font-bold text-white">{{ $title }}</div>
        @if($subtitle)
            <div class="mt-1 text-sm text-white/70">{{ $subtitle }}</div>
        @endif
    </div>
</div>
