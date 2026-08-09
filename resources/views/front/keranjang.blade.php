@extends('layouts.app')

@section('title', 'Keranjang — Paket '.$plan['name'].' · WOFINS')

@push('styles')
@include('front.partials.wf-front-base-styles')
<style>
    .wf-cart-card {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        padding: 1.25rem 1.35rem;
    }
    .wf-cart-summary {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.25rem;
        padding: 1.35rem;
        position: sticky;
        top: 5.5rem;
    }
    .wf-field {
        width: 100%;
        margin-top: 0.35rem;
        border-radius: 0.75rem;
        border: 1px solid var(--wf-line);
        background: #fff;
        padding: 0.7rem 0.9rem;
        font-size: 0.925rem;
        color: var(--wf-ink);
    }
    .wf-field:focus {
        outline: none;
        border-color: var(--wf-gold);
        box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.2);
    }
    .wf-save-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        padding: 0.25rem 0.6rem;
    }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
@php
    $fmt = static fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
    $monthly = (int) ($plan['price_monthly'] ?? 0);
    $annual = (int) ($plan['price_annual'] ?? 0);
    $biennial = (int) ($plan['price_biennial'] ?? (int) round($monthly * 22));
    $quadrennial = (int) ($plan['price_quadrennial'] ?? (int) round($monthly * 44));
@endphp

<div class="wf-page" x-data="{
    billing: '{{ $billing }}',
    uniqueAmount: {{ (int) $uniqueAmount }},
    amountMonthly: {{ $monthly }},
    amountAnnual: {{ $annual }},
    amountBiennial: {{ $biennial }},
    amountQuadrennial: {{ $quadrennial }},
    get amount() {
        if (this.billing === 'quadrennial') return this.amountQuadrennial
        if (this.billing === 'biennial') return this.amountBiennial
        if (this.billing === 'annual') return this.amountAnnual
        return this.amountMonthly
    },
    get payable() {
        return this.amount + this.uniqueAmount
    },
    get periodLabel() {
        if (this.billing === 'quadrennial') return '48 bulan (hemat 4 bulan)'
        if (this.billing === 'biennial') return '24 bulan (hemat 2 bulan)'
        if (this.billing === 'annual') return '12 bulan (hemat 1 bulan)'
        return '1 bulan'
    },
    get months() {
        if (this.billing === 'quadrennial') return 48
        if (this.billing === 'biennial') return 24
        if (this.billing === 'annual') return 12
        return 1
    },
    get monthlyEquiv() {
        return Math.round(this.amount / this.months)
    },
    get savings() {
        return Math.max(0, (this.amountMonthly * this.months) - this.amount)
    },
    format(n) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(n)
    }
}">
    @include('front.partials.wf-nav')

    <section class="pt-10 pb-16 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">Checkout</p>
                <h1 class="mt-1 text-3xl font-bold text-[var(--wf-navy)]">Keranjang Anda</h1>
                <p class="mt-2 text-sm text-[var(--wf-muted)]">
                    Pilih durasi, lalu lanjutkan ke halaman pembayaran untuk transfer & unggah bukti.
                </p>
            </div>

            <div class="grid lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-7 space-y-5">
                    <div class="wf-cart-card">
                        <div class="flex items-start gap-3 mb-4">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--wf-navy)] text-[var(--wf-gold-soft)]">
                                <i class="fa-solid fa-box"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold text-[var(--wf-navy)]">Paket {{ $plan['name'] }}</h2>
                                <p class="text-sm text-[var(--wf-muted)]">{{ $plan['desc'] }}</p>
                            </div>
                        </div>

                        <form action="{{ route('keranjang.update') }}" method="POST" class="space-y-4" id="cart-config-form">
                            @csrf
                            <input type="hidden" name="paket" value="{{ $plan['key'] }}">

                            <div>
                                <label for="billing" class="block text-sm font-semibold text-[var(--wf-navy)]">Durasi</label>
                                <select id="billing" name="billing" class="wf-field"
                                        x-model="billing"
                                        @change="$el.form.submit()">
                                    <option value="monthly" @selected($billing === 'monthly')>1 bulan — {{ $fmt($monthly) }}</option>
                                    <option value="annual" @selected($billing === 'annual')>12 bulan (bayar 11) — {{ $fmt($annual) }}</option>
                                    <option value="biennial" @selected($billing === 'biennial')>24 bulan (bayar 22) — {{ $fmt($biennial) }}</option>
                                    <option value="quadrennial" @selected($billing === 'quadrennial')>48 bulan (bayar 44) — {{ $fmt($quadrennial) }}</option>
                                </select>
                            </div>

                            <div class="flex flex-wrap items-end justify-between gap-3 pt-1">
                                <div>
                                    <template x-if="savings > 0">
                                        <span class="wf-save-pill" x-text="'Hemat ' + format(savings)"></span>
                                    </template>
                                    <p class="mt-2 text-xl font-bold text-[var(--wf-navy)]">
                                        <span x-text="format(monthlyEquiv)"></span>
                                        <span class="text-sm font-semibold text-[var(--wf-muted)]">/bln</span>
                                    </p>
                                    <p class="text-xs text-[var(--wf-muted)]" x-show="billing !== 'monthly'">
                                        Total dibayar di muka: <span class="font-semibold text-[var(--wf-navy)]" x-text="format(amount)"></span>
                                    </p>
                                </div>
                                <a href="{{ route('harga') }}" class="text-sm font-semibold text-[var(--wf-navy)] underline underline-offset-2 hover:text-[var(--wf-gold)]">
                                    Ganti paket
                                </a>
                            </div>
                        </form>

                        <ul class="mt-5 space-y-2 border-t border-[var(--wf-line)] pt-4">
                            @foreach (array_slice(\App\Support\PricingPlans::featureItems($plan), 0, 5) as $feature)
                                <li class="flex items-start gap-2 text-sm text-[var(--wf-ink)]">
                                    <i class="fa-solid fa-check text-emerald-600 mt-0.5 text-xs"></i>
                                    <span>{{ $feature['label'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="wf-cart-summary">
                        <h2 class="text-lg font-bold text-[var(--wf-navy)] mb-4">Daftar pesanan</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Paket {{ $plan['name'] }}</span>
                                <span class="font-semibold text-[var(--wf-navy)]" x-text="format(amount)"></span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Durasi</span>
                                <span class="font-semibold text-[var(--wf-navy)]" x-text="periodLabel"></span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Setup / instalasi</span>
                                <span class="font-semibold text-emerald-700">Rp0</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Kode unik</span>
                                <span class="font-semibold text-[var(--wf-navy)]" x-text="format(uniqueAmount)"></span>
                            </div>
                        </div>

                        <div class="border-t border-[var(--wf-line)] mt-4 pt-4">
                            <div class="flex justify-between items-end gap-3">
                                <span class="text-sm font-semibold text-[var(--wf-navy)]">Total bayar</span>
                                <span class="text-2xl font-bold text-[var(--wf-navy)]" x-text="format(payable)"></span>
                            </div>
                            <p class="mt-1.5 text-xs text-[var(--wf-muted)] text-right">
                                Transfer sesuai nominal ini agar pembayaran mudah diverifikasi.
                            </p>
                        </div>

                        <a href="{{ route('keranjang.bayar') }}"
                           class="wf-btn-navy w-full mt-5 inline-flex items-center justify-center px-5 py-3.5 text-sm">
                            Lanjutkan
                        </a>

                        <p class="mt-4 text-xs text-center text-[var(--wf-muted)]">
                            Langkah berikutnya: transfer bank & unggah bukti pembayaran.
                        </p>
                    </div>

                    <div class="mt-4 flex items-center gap-2 text-sm text-[var(--wf-navy)] px-1">
                        <i class="fa-solid fa-shield-halved text-[var(--wf-gold)]"></i>
                        <span class="font-medium">Data & bukti hanya untuk verifikasi tim WOFINS.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('front.partials.wf-footer')
</div>
@endsection
