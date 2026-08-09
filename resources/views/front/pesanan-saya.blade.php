@extends('layouts.app')

@section('title', 'Pesanan saya · WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
<style>
    .wf-order-card {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.15rem;
        padding: 1.15rem 1.25rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .wf-order-card:hover {
        border-color: rgba(11, 31, 58, 0.25);
        box-shadow: 0 14px 32px -24px rgba(11, 31, 58, 0.4);
    }
    .wf-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .wf-status.is-pending { background: #fef3c7; color: #92400e; }
    .wf-status.is-approved { background: #d1fae5; color: #065f46; }
    .wf-status.is-rejected { background: #fee2e2; color: #991b1b; }
</style>
@endpush

@section('content')
@php
    $statusClass = static fn (string $status): string => match ($status) {
        'approved' => 'is-approved',
        'rejected' => 'is-rejected',
        default => 'is-pending',
    };
@endphp

<div class="wf-page">
    @include('front.partials.wf-nav')

    <section class="pt-10 pb-16 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">Akun</p>
                <h1 class="mt-1 text-3xl font-bold text-[var(--wf-navy)]">Pesanan saya</h1>
                <p class="mt-2 text-sm text-[var(--wf-muted)]">
                    Riwayat pesanan paket WOFINS untuk {{ $user->email }}.
                </p>
            </div>

            @if (session('error'))
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if ($orders->isEmpty())
                <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-8 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--wf-cream)] text-[var(--wf-navy)]">
                        <i class="fa-solid fa-receipt text-xl"></i>
                    </div>
                    <h2 class="text-lg font-bold text-[var(--wf-navy)]">Belum ada pesanan</h2>
                    <p class="mt-2 text-sm text-[var(--wf-muted)]">Pilih paket di halaman Harga untuk mulai berlangganan.</p>
                    <a href="{{ route('harga') }}" class="wf-btn-navy mt-5 inline-flex items-center justify-center px-6 py-3 text-sm">
                        Lihat paket
                    </a>
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($orders as $order)
                        <a href="{{ route('pesanan-saya.show', $order->order_code) }}" class="wf-order-card block">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">
                                        {{ $order->order_code }}
                                    </p>
                                    <h2 class="mt-1 text-base font-bold text-[var(--wf-navy)]">
                                        Paket {{ $order->plan_name }}
                                    </h2>
                                    <p class="mt-0.5 text-sm text-[var(--wf-muted)]">
                                        {{ $order->billing_label }}
                                        · {{ optional($order->submitted_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? $order->created_at->timezone(config('app.timezone'))->format('d M Y H:i') }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="wf-status {{ $statusClass($order->status) }}">{{ $order->status_label }}</span>
                                    <p class="mt-2 text-sm font-bold text-[var(--wf-navy)]">{{ $order->formatted_amount }}</p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('harga') }}" class="wf-btn-ghost inline-flex items-center justify-center px-5 py-2.5 text-sm">Lihat harga</a>
                @auth
                    @if (Auth::user()->hasAssignedRole())
                        <a href="{{ route('profile') }}" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm">Ke dashboard</a>
                    @else
                        <a href="{{ route('account.pending') }}" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm">Status akun</a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    @include('front.partials.wf-footer')
</div>
@endsection
