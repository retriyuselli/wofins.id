@extends('profile.layout')

@section('profile-page-title', 'Produk Proyek Wedding')
@section('profile-page-subtitle', $order->name)

@section('profile-content')
<div class="space-y-6">
    <div class="wf-profile-card overflow-hidden">
        <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
            <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
                <span class="absolute w-24 h-24 rounded-full -right-6 -top-8 bg-[rgba(201,162,39,0.22)]"></span>
                <span class="absolute w-12 h-12 rounded-full left-10 -bottom-5 bg-[rgba(255,255,255,0.08)]"></span>
            </div>
            <div class="relative z-[1] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Produk</p>
                    <div class="mt-1 text-xl font-bold text-white">{{ $order->name }}</div>
                    <div class="mt-1 text-sm text-white/70">
                        Nomor: <span class="font-semibold text-white/90">{{ $order->number ?? '-' }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <a href="{{ route('profile.admin-tools.projects.show', $order) }}"
                        class="inline-flex items-center px-3.5 py-1.5 rounded-full border border-white/30 text-xs font-bold text-white hover:bg-white/10 transition">
                        Kembali ke proyek
                    </a>
                    <a href="{{ route('profile.admin-tools.projects') }}"
                        class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-[var(--wf-gold)] text-[var(--wf-navy-deep)] text-xs font-extrabold hover:brightness-105 transition">
                        Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(($products ?? collect())->isEmpty())
        <div class="wf-profile-card p-6">
            <div class="text-sm text-[var(--wf-muted)]">Produk belum ditemukan untuk proyek ini.</div>
        </div>
    @else
        @foreach($products as $product)
            @php
                $discounts = $product->pengurangans ?? collect();
                $additions = $product->penambahanHarga ?? collect();
                $vendors = $product->items ?? collect();
                $vendorsVendorTotal = (int) $vendors->sum('total_price');
                $additionsVendorTotal = (int) $additions->sum('harga_vendor');
                $vendorCostTotal = $vendorsVendorTotal + $additionsVendorTotal;
                $finalPriceTotal = (int) ($product->price ?? 0);
                $profitTotal = $finalPriceTotal - $vendorCostTotal;
            @endphp

            <div class="wf-profile-card overflow-hidden">
                <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Nama Produk</div>
                        <div class="mt-0.5 text-lg font-bold text-[var(--wf-navy)]">{{ \Illuminate\Support\Str::title($product->name) }}</div>
                        <div class="mt-2 text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Kategori</div>
                        <div class="text-sm font-semibold text-[var(--wf-ink)]">{{ $product->category?->name ?? '-' }}</div>
                    </div>
                    <div class="text-left sm:text-right">
                        <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Harga Final</div>
                        <div class="mt-0.5 text-lg font-extrabold text-[var(--wf-navy)] tabular-nums">Rp {{ number_format((int) $product->price, 0, ',', '.') }}</div>
                        <div class="mt-2 text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Total Publish</div>
                        <div class="text-sm font-semibold text-[var(--wf-ink)] tabular-nums">Rp {{ number_format((int) $product->product_price, 0, ',', '.') }}</div>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                        <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4">
                            <div class="text-xs font-semibold text-[var(--wf-muted)]">Pengurangan</div>
                            <div class="mt-1 text-lg font-extrabold text-[var(--wf-navy)] tabular-nums">Rp {{ number_format((int) $product->pengurangan, 0, ',', '.') }}</div>
                        </div>
                        <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                            <div class="text-xs font-semibold text-[var(--wf-muted)]">Penambahan (Publish)</div>
                            <div class="mt-1 text-lg font-extrabold text-[var(--wf-navy)] tabular-nums">Rp {{ number_format((int) $product->penambahan_publish, 0, ',', '.') }}</div>
                        </div>
                        <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                            <div class="text-xs font-semibold text-[var(--wf-muted)]">Keuntungan</div>
                            <div class="mt-1 text-lg font-extrabold tabular-nums {{ $profitTotal >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                Rp {{ number_format((int) $profitTotal, 0, ',', '.') }}
                            </div>
                            <div class="mt-2 text-[11px] text-[var(--wf-muted)]">Total Vendor</div>
                            <div class="font-semibold text-[var(--wf-ink)] tabular-nums">Rp {{ number_format((int) $vendorCostTotal, 0, ',', '.') }}</div>
                            <div class="mt-1 text-[11px] text-[var(--wf-muted)]">Total Publish (Final)</div>
                            <div class="font-semibold text-[var(--wf-ink)] tabular-nums">Rp {{ number_format((int) $finalPriceTotal, 0, ',', '.') }}</div>
                        </div>
                        <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] p-4">
                            <div class="text-xs font-semibold text-white/55">Pax</div>
                            <div class="mt-1 text-lg font-extrabold text-[var(--wf-gold-soft)]">{{ (int) ($product->pax ?? 0) }}</div>
                            <div class="mt-2 text-[11px] text-white/55">Pax Akad</div>
                            <div class="font-semibold text-white/90">{{ (int) ($product->pax_akad ?? 0) }}</div>
                        </div>
                    </div>

                    @if (!empty($product->free_pengurangan))
                        <div>
                            <div class="text-sm font-bold text-[var(--wf-navy)] mb-2">Free</div>
                            <div class="rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/50 p-4 prose max-w-none text-sm text-[var(--wf-ink)]">
                                {!! strip_tags($product->free_pengurangan, '<p><br><b><strong><em><i><ul><ol><li><span><a>') !!}
                            </div>
                        </div>
                    @endif

                    @if($vendors->isNotEmpty())
                        <div>
                            <div class="text-sm font-bold text-[var(--wf-navy)] mb-2">Vendor</div>
                            <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                                            <th class="py-3 px-4 font-semibold">Vendor</th>
                                            <th class="py-3 px-4 font-semibold">Qty</th>
                                            <th class="py-3 px-4 font-semibold text-right">Harga Publish</th>
                                            <th class="py-3 px-4 font-semibold text-right">Harga Vendor</th>
                                            <th class="py-3 px-4 font-semibold text-right">Keuntungan</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--wf-line)]">
                                        @php $vendorsProfitTotal = 0; @endphp
                                        @foreach($vendors as $item)
                                            @php
                                                $qty = (int) ($item->quantity ?? 1);
                                                $itemProfit = (((int) ($item->harga_publish ?? 0)) - ((int) ($item->harga_vendor ?? 0))) * $qty;
                                                $vendorsProfitTotal += $itemProfit;
                                            @endphp
                                            <tr class="text-[var(--wf-ink)]">
                                                <td class="py-3 px-4 font-medium">{{ \Illuminate\Support\Str::title($item->vendor?->name ?? '-') }}</td>
                                                <td class="py-3 px-4 tabular-nums">{{ $qty }}</td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums">Rp {{ number_format((int) ($item->harga_publish ?? 0), 0, ',', '.') }}</td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums">Rp {{ number_format((int) ($item->harga_vendor ?? 0), 0, ',', '.') }}</td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums font-semibold {{ $itemProfit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                                    Rp {{ number_format((int) $itemProfit, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="border-t border-[var(--wf-line)] bg-[var(--wf-cream)]/40">
                                            <td class="py-3 px-4 font-bold text-[var(--wf-navy)]" colspan="4">Total Keuntungan</td>
                                            <td class="py-3 px-4 text-right font-bold text-[var(--wf-navy)] whitespace-nowrap tabular-nums">
                                                Rp {{ number_format((int) $vendorsProfitTotal, 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($discounts->isNotEmpty())
                        <div>
                            <div class="text-sm font-bold text-[var(--wf-navy)] mb-2">Pengurangan Item</div>
                            <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                                            <th class="py-3 px-4 font-semibold">Nama</th>
                                            <th class="py-3 px-4 font-semibold text-right">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--wf-line)]">
                                        @foreach($discounts as $disc)
                                            <tr class="text-[var(--wf-ink)]">
                                                <td class="py-3 px-4 font-medium">{{ \Illuminate\Support\Str::title($disc->description ?? '-') }}</td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums">Rp {{ number_format((int) ($disc->amount ?? 0), 0, ',', '.') }}</td>
                                            </tr>
                                            @if (!empty($disc->notes))
                                                <tr>
                                                    <td class="pb-3 px-4 text-[var(--wf-muted)] text-xs" colspan="2">
                                                        {!! strip_tags($disc->notes, '<p><br><b><strong><em><i><ul><ol><li><span><a>') !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if($additions->isNotEmpty())
                        <div>
                            <div class="text-sm font-bold text-[var(--wf-navy)] mb-2">Penambahan Item</div>
                            <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                                            <th class="py-3 px-4 font-semibold">Vendor</th>
                                            <th class="py-3 px-4 font-semibold text-right">Harga Publish</th>
                                            <th class="py-3 px-4 font-semibold text-right">Harga Vendor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[var(--wf-line)]">
                                        @foreach($additions as $add)
                                            <tr class="text-[var(--wf-ink)]">
                                                <td class="py-3 px-4 font-medium">{{ \Illuminate\Support\Str::title($add->vendor?->name ?? '-') }}</td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums">Rp {{ number_format((int) ($add->harga_publish ?? 0), 0, ',', '.') }}</td>
                                                <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums">Rp {{ number_format((int) ($add->harga_vendor ?? 0), 0, ',', '.') }}</td>
                                            </tr>
                                            @if (!empty($add->description))
                                                <tr>
                                                    <td class="pb-3 px-4 text-[var(--wf-muted)] text-xs" colspan="3">
                                                        {!! strip_tags($add->description, '<p><br><b><strong><em><i><ul><ol><li><span><a>') !!}
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
