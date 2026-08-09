@extends('layouts.app')

@section('title', 'Pesanan '.$order->order_code.' · WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
<style>
    .wf-detail-card {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        padding: 1.35rem;
    }
    .wf-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.25rem 0.7rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .wf-status.is-pending { background: #fef3c7; color: #92400e; }
    .wf-status.is-approved { background: #d1fae5; color: #065f46; }
    .wf-status.is-rejected { background: #fee2e2; color: #991b1b; }
    .wf-bank-box {
        background: var(--wf-cream);
        border: 1px solid var(--wf-line);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
    }
    .wf-proof-modal {
        position: fixed;
        inset: 0;
        z-index: 80;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .wf-proof-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(7, 21, 38, 0.7);
    }
    .wf-proof-modal__img {
        position: relative;
        z-index: 1;
        max-width: min(100%, 42rem);
        max-height: 90vh;
        width: auto;
        height: auto;
        object-fit: contain;
    }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
@php
    $statusClass = match ($order->status) {
        'approved' => 'is-approved',
        'rejected' => 'is-rejected',
        default => 'is-pending',
    };
    $proofExt = strtolower(pathinfo((string) $order->payment_proof_path, PATHINFO_EXTENSION));
    $proofIsImage = in_array($proofExt, ['jpg', 'jpeg', 'png', 'webp'], true);
@endphp

<div class="wf-page" x-data="{ proofOpen: false }" @keydown.escape.window="proofOpen = false">
    @include('front.partials.wf-nav')

    <section class="pt-10 pb-16 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="{{ route('pesanan-saya') }}"
               class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                Kembali ke pesanan saya
            </a>

            @if (session('error'))
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mt-5 mb-6 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">Detail pesanan</p>
                    <h1 class="mt-1 text-2xl sm:text-3xl font-bold text-[var(--wf-navy)]">{{ $order->order_code }}</h1>
                </div>
                <span class="wf-status {{ $statusClass }}">{{ $order->status_label }}</span>
            </div>

            <div class="wf-detail-card space-y-3 text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Paket</span>
                    <span class="font-semibold text-[var(--wf-navy)] text-right">{{ $order->plan_name }} · {{ $order->billing_label }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Total transfer</span>
                    <span class="font-semibold text-[var(--wf-navy)]">{{ $order->formatted_amount }}</span>
                </div>
                @if ((int) $order->unique_amount > 0)
                    <div class="flex justify-between gap-3">
                        <span class="text-[var(--wf-muted)]">Kode unik</span>
                        <span class="font-semibold text-[var(--wf-navy)]">{{ $order->formatted_unique_amount }}</span>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Nama pemesan</span>
                    <span class="font-semibold text-[var(--wf-navy)] text-right">{{ $order->full_name }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Email</span>
                    <span class="font-semibold text-[var(--wf-navy)] text-right break-all">{{ $order->email }}</span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">WhatsApp / HP</span>
                    <span class="font-semibold text-[var(--wf-navy)]">{{ $order->phone }}</span>
                </div>
                @if ($order->company_name)
                    <div class="flex justify-between gap-3">
                        <span class="text-[var(--wf-muted)]">Perusahaan / WO</span>
                        <span class="font-semibold text-[var(--wf-navy)] text-right">{{ $order->company_name }}</span>
                    </div>
                @endif
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Dikirim</span>
                    <span class="font-semibold text-[var(--wf-navy)]">
                        {{ optional($order->submitted_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? $order->created_at->timezone(config('app.timezone'))->format('d M Y H:i') }}
                    </span>
                </div>
                @if ($order->notes)
                    <div class="pt-2 border-t border-[var(--wf-line)]">
                        <p class="text-[var(--wf-muted)] mb-1">Catatan</p>
                        <p class="text-[var(--wf-navy)] whitespace-pre-line">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>

            @if ($order->status === 'pending_review')
                <div class="wf-bank-box mt-5">
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-[var(--wf-gold)] mb-2">Referensi transfer</p>
                    <p class="text-sm font-bold text-[var(--wf-navy)]">{{ $bank['bank_name'] ?? 'Bank' }}</p>
                    <p class="text-sm text-[var(--wf-ink)] mt-1">
                        a.n. <strong>{{ $bank['account_name'] ?? '-' }}</strong><br>
                        No. rek. <strong class="tracking-wide">{{ $bank['account_number'] ?? '-' }}</strong>
                    </p>
                    <p class="text-xs text-[var(--wf-muted)] mt-2">
                        Nominal yang diverifikasi: <strong class="text-[var(--wf-navy)]">{{ $order->formatted_amount }}</strong>
                    </p>
                </div>
            @endif

            @if ($order->payment_proof_url)
                <div class="wf-detail-card mt-5 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-[var(--wf-navy)] mb-0">Bukti pembayaran</p>
                    <button type="button"
                            class="wf-btn-navy inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm"
                            @click="proofOpen = true">
                        <i class="fa-solid fa-eye text-xs"></i>
                        Lihat bukti
                    </button>
                </div>

                <div class="wf-proof-modal"
                     x-show="proofOpen"
                     x-cloak
                     x-transition.opacity.duration.150ms
                     role="dialog"
                     aria-modal="true"
                     aria-label="Bukti pembayaran"
                     @click="proofOpen = false">
                    <div class="wf-proof-modal__backdrop"></div>
                    @if ($proofIsImage)
                        <img src="{{ $order->payment_proof_url }}"
                             alt="Bukti pembayaran {{ $order->order_code }}"
                             class="wf-proof-modal__img"
                             @click.stop>
                    @else
                        <a href="{{ $order->payment_proof_url }}"
                           target="_blank"
                           rel="noopener"
                           class="relative z-[1] text-sm font-semibold text-white underline"
                           @click.stop>
                            Buka file PDF
                        </a>
                    @endif
                </div>
            @endif

            <div class="mt-8 flex flex-col sm:flex-row gap-3">
                <a href="{{ route('pesanan-saya') }}" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">Semua pesanan</a>
                <a href="https://wa.me/6281373183794?text={{ urlencode('Halo, saya ingin menanyakan pesanan paket '.$order->plan_name.'. Kode: '.$order->order_code) }}"
                   class="wf-btn-gold inline-flex items-center justify-center px-6 py-3 text-sm"
                   target="_blank"
                   rel="noopener">
                    Hubungi WhatsApp
                </a>
            </div>
        </div>
    </section>

    @include('front.partials.wf-footer')
</div>
@endsection
