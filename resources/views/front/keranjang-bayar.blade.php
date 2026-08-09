@extends('layouts.app')

@section('title', 'Pembayaran — Paket '.$plan['name'].' · WOFINS')

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
    .wf-bank-box {
        background: var(--wf-cream);
        border: 1px solid var(--wf-line);
        border-radius: 1rem;
        padding: 1rem 1.1rem;
    }
    .wf-alert-err {
        border-radius: 0.9rem;
        border: 1px solid #fecaca;
        background: #fef2f2;
        color: #991b1b;
        padding: 0.9rem 1rem;
    }
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
@php
    $fmt = static fn (int $n) => 'Rp '.number_format($n, 0, ',', '.');
    $amount = (int) ($pricing['amount'] ?? 0);
    $unique = (int) $uniqueAmount;
    $payable = $amount + $unique;
    $periodLabel = match ($billing) {
        'annual' => '12 bulan (hemat 1 bulan)',
        'biennial' => '24 bulan (hemat 2 bulan)',
        'quadrennial' => '48 bulan (hemat 4 bulan)',
        default => '1 bulan',
    };
@endphp

<div class="wf-page">
    @include('front.partials.wf-nav')

    <section class="pt-10 pb-16 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">Checkout</p>
                <h1 class="mt-1 text-3xl font-bold text-[var(--wf-navy)]">Data pemesan & bukti bayar</h1>
                <p class="mt-2 text-sm text-[var(--wf-muted)]">
                    Transfer sesuai nominal, lalu unggah bukti pembayaran.
                </p>
                <a href="{{ route('keranjang', ['paket' => $plan['key'], 'billing' => $billing]) }}"
                   class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    Kembali ke keranjang
                </a>
            </div>

            @if ($errors->any())
                <div class="wf-alert-err mb-6">
                    <p class="text-sm font-semibold mb-1">Periksa kembali form:</p>
                    <ul class="text-sm list-disc list-inside space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid lg:grid-cols-12 gap-6 items-start">
                <div class="lg:col-span-7">
                    <div class="wf-cart-card">
                        <div class="flex items-start gap-3 mb-5">
                            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-[var(--wf-cream)] border border-[var(--wf-line)] text-[var(--wf-navy)]">
                                <i class="fa-solid fa-user-pen"></i>
                            </span>
                            <div>
                                <h2 class="text-lg font-bold text-[var(--wf-navy)]">Data pemesan & bukti bayar</h2>
                                <p class="text-sm text-[var(--wf-muted)]">Isi data singkat, transfer, lalu unggah bukti.</p>
                            </div>
                        </div>

                        <div class="wf-bank-box mb-5">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-[var(--wf-gold)] mb-2">Transfer ke</p>
                            <p class="text-sm font-bold text-[var(--wf-navy)]">{{ $bank['bank_name'] ?? 'Bank' }}</p>
                            <p class="text-sm text-[var(--wf-ink)] mt-1">
                                a.n. <strong>{{ $bank['account_name'] ?? '-' }}</strong><br>
                                No. rek. <strong class="tracking-wide">{{ $bank['account_number'] ?? '-' }}</strong>
                            </p>
                            <p class="text-xs text-[var(--wf-muted)] mt-2">
                                Transfer tepat: <strong class="text-[var(--wf-navy)]">{{ $fmt($payable) }}</strong>
                                (termasuk kode unik {{ $fmt($unique) }})
                                · {{ $bank['notes'] ?? '' }}
                            </p>
                        </div>

                        <form action="{{ route('keranjang.checkout') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <input type="hidden" name="paket" value="{{ $plan['key'] }}">
                            <input type="hidden" name="billing" value="{{ $billing }}">

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)]">Nama lengkap <span class="text-red-500">*</span></label>
                                    <input type="text" name="full_name" required class="wf-field"
                                           value="{{ old('full_name', $user->name ?? '') }}"
                                           placeholder="Nama pemesan">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)]">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" required readonly
                                           class="wf-field bg-[var(--wf-cream)] cursor-not-allowed"
                                           value="{{ old('email', $user->email ?? '') }}"
                                           placeholder="email@domain.com"
                                           tabindex="-1"
                                           aria-readonly="true">
                                    <p class="mt-1 text-xs text-[var(--wf-muted)]">Email terdaftar akun Anda — tidak dapat diubah.</p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)]">No. WhatsApp / HP <span class="text-red-500">*</span></label>
                                    <input type="text" name="phone" required class="wf-field"
                                           value="{{ old('phone', $user->phone_number ?? '') }}"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-[var(--wf-navy)]">Nama WO / perusahaan</label>
                                    <input type="text" name="company_name" class="wf-field"
                                           value="{{ old('company_name') }}"
                                           placeholder="Opsional">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-[var(--wf-navy)]">Catatan</label>
                                <textarea name="notes" rows="2" class="wf-field"
                                          placeholder="Opsional — mis. jadwal aktivasi">{{ old('notes') }}</textarea>
                            </div>

                            <div x-data="{
                                previewUrl: null,
                                fileName: '',
                                isPdf: false,
                                isImage: false,
                                tooLarge: false,
                                onFileChange(event) {
                                    const file = event.target.files?.[0] || null
                                    if (this.previewUrl) {
                                        URL.revokeObjectURL(this.previewUrl)
                                        this.previewUrl = null
                                    }
                                    this.fileName = ''
                                    this.isPdf = false
                                    this.isImage = false
                                    this.tooLarge = false
                                    if (!file) return

                                    this.fileName = file.name
                                    this.tooLarge = file.size > 4 * 1024 * 1024
                                    this.isPdf = file.type === 'application/pdf' || /\.pdf$/i.test(file.name)
                                    this.isImage = /^image\//.test(file.type) || /\.(jpe?g|png|webp)$/i.test(file.name)
                                    if (this.isImage) {
                                        this.previewUrl = URL.createObjectURL(file)
                                    }
                                },
                                clearFile(event) {
                                    const input = event.target.closest('div').querySelector('input[type=file]')
                                    if (input) input.value = ''
                                    if (this.previewUrl) URL.revokeObjectURL(this.previewUrl)
                                    this.previewUrl = null
                                    this.fileName = ''
                                    this.isPdf = false
                                    this.isImage = false
                                    this.tooLarge = false
                                }
                            }">
                                <label class="block text-sm font-semibold text-[var(--wf-navy)]">
                                    Bukti pembayaran <span class="text-red-500">*</span>
                                </label>
                                <input type="file" name="payment_proof" required
                                       accept=".jpg,.jpeg,.png,.pdf,.webp"
                                       class="wf-field"
                                       @change="onFileChange($event)">
                                <p class="mt-1 text-xs text-[var(--wf-muted)]">JPG/PNG/PDF · maks. 4MB</p>
                                <p class="mt-1 text-xs font-semibold text-red-600" x-show="tooLarge" x-cloak>
                                    File melebihi 4MB. Pilih file yang lebih kecil.
                                </p>

                                <div class="mt-3 rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-3"
                                     x-show="fileName"
                                     x-cloak>
                                    <div class="flex items-start justify-between gap-3 mb-2">
                                        <p class="text-xs font-semibold text-[var(--wf-navy)] break-all" x-text="fileName"></p>
                                        <button type="button"
                                                class="text-xs font-semibold text-[var(--wf-muted)] hover:text-red-600 shrink-0"
                                                @click="clearFile($event)">
                                            Hapus
                                        </button>
                                    </div>
                                    <template x-if="isImage && previewUrl">
                                        <img :src="previewUrl"
                                             alt="Preview bukti pembayaran"
                                             class="w-full max-h-72 object-contain rounded-lg bg-white border border-[var(--wf-line)]">
                                    </template>
                                    <template x-if="isPdf">
                                        <div class="flex items-center gap-3 rounded-lg bg-white border border-[var(--wf-line)] px-3 py-3">
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 text-red-600">
                                                <i class="fa-solid fa-file-pdf text-lg"></i>
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-[var(--wf-navy)]">File PDF dipilih</p>
                                                <p class="text-xs text-[var(--wf-muted)]">Preview gambar tidak tersedia untuk PDF.</p>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <button type="submit" class="wf-btn-gold w-full inline-flex items-center justify-center px-5 py-3.5 text-sm">
                                Kirim pesanan & bukti bayar
                            </button>
                            <p class="text-xs text-center text-[var(--wf-muted)]">
                                Tim admin akan meninjau bukti dan mengaktifkan paket Anda.
                            </p>
                        </form>
                    </div>
                </div>

                <div class="lg:col-span-5">
                    <div class="wf-cart-summary">
                        <h2 class="text-lg font-bold text-[var(--wf-navy)] mb-4">Daftar pesanan</h2>

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Paket {{ $plan['name'] }}</span>
                                <span class="font-semibold text-[var(--wf-navy)]">{{ $fmt($amount) }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Durasi</span>
                                <span class="font-semibold text-[var(--wf-navy)]">{{ $periodLabel }}</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Setup / instalasi</span>
                                <span class="font-semibold text-emerald-700">Rp0</span>
                            </div>
                            <div class="flex justify-between gap-3">
                                <span class="text-[var(--wf-muted)]">Kode unik</span>
                                <span class="font-semibold text-[var(--wf-navy)]">{{ $fmt($unique) }}</span>
                            </div>
                        </div>

                        <div class="border-t border-[var(--wf-line)] mt-4 pt-4">
                            <div class="flex justify-between items-end gap-3">
                                <span class="text-sm font-semibold text-[var(--wf-navy)]">Total bayar</span>
                                <span class="text-2xl font-bold text-[var(--wf-navy)]">{{ $fmt($payable) }}</span>
                            </div>
                            <p class="mt-1.5 text-xs text-[var(--wf-muted)] text-right">
                                Transfer sesuai nominal ini agar pembayaran mudah diverifikasi.
                            </p>
                        </div>
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
