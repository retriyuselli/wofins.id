@extends('profile.layout')

@section('profile-page-title', 'Detail Proyek Wedding')
@section('profile-page-subtitle', $order->name)

@section('profile-content')
@php
    $totalPengeluaran = (int) $order->tot_pengeluaran;
    $grandTotal = (int) $order->grand_total;
    $profit = (int) $order->laba_kotor;
    $status = $order->status?->value ?? (string) $order->status;
    $statusKey = strtolower(trim($status));
    $badgeClass = match (true) {
        in_array($statusKey, ['done', 'selesai', 'completed'], true) => 'bg-emerald-50 text-emerald-800 border-emerald-200',
        in_array($statusKey, ['processing', 'proses', 'in_progress', 'ongoing'], true) => 'bg-[rgba(201,162,39,0.15)] text-[#8a6d12] border-[rgba(201,162,39,0.35)]',
        in_array($statusKey, ['cancelled', 'canceled', 'batal'], true) => 'bg-rose-50 text-rose-800 border-rose-200',
        default => 'bg-[var(--wf-cream)] text-[var(--wf-muted)] border-[var(--wf-line)]',
    };
@endphp

<div class="space-y-6">
    <div class="wf-profile-card overflow-hidden">
        <div class="relative px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
            <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
                <span class="absolute w-24 h-24 rounded-full -right-6 -top-8 bg-[rgba(201,162,39,0.22)]"></span>
                <span class="absolute w-12 h-12 rounded-full left-10 -bottom-5 bg-[rgba(255,255,255,0.08)]"></span>
            </div>
            <div class="relative z-[1] flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Detail Proyek</p>
                    <div class="mt-1 text-xl font-bold text-white">{{ $order->name }}</div>
                    <div class="mt-1 text-sm text-white/70">
                        Nomor: <span class="font-semibold text-white/90">{{ $order->number ?? '-' }}</span>
                        @if($order->no_kontrak)
                            · Kontrak: <span class="font-semibold text-white/90">{{ $order->no_kontrak }}</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 self-start">
                    <span class="inline-flex px-3 py-1.5 rounded-full text-xs font-bold capitalize border {{ $badgeClass }} bg-white/95">
                        {{ $status !== '' ? $status : '-' }}
                    </span>
                    <a href="{{ route('profile.admin-tools.projects') }}"
                        class="inline-flex items-center px-3.5 py-1.5 rounded-full border border-white/30 text-xs font-bold text-white hover:bg-white/10 transition">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Keuntungan</div>
                    <div class="mt-1 text-lg font-extrabold tabular-nums {{ $profit >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        Rp {{ number_format($profit, 0, ',', '.') }}
                    </div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-[var(--wf-muted)]">Pengeluaran</div>
                    <div class="mt-1 text-lg font-extrabold tabular-nums text-[var(--wf-navy)]">
                        Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}
                    </div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] p-4">
                    <div class="text-[10px] font-bold uppercase tracking-wide text-white/55">Grand Total</div>
                    <div class="mt-1 text-lg font-extrabold tabular-nums text-[var(--wf-gold-soft)]">
                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 text-sm">
                <div>
                    <div class="text-xs font-semibold text-[var(--wf-muted)]">PIC</div>
                    <div class="mt-0.5 font-bold text-[var(--wf-navy)]">{{ $order->employee?->name ?? '-' }}</div>
                    <div class="text-xs text-[var(--wf-muted)] mt-0.5">{{ $order->user?->name ?? '' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-[var(--wf-muted)]">Tanggal Akad</div>
                    <div class="mt-0.5 font-bold text-[var(--wf-navy)]">{{ $order->prospect?->date_akad ? $order->prospect->date_akad->format('d M Y') : '-' }}</div>
                    <div class="text-xs font-semibold text-[var(--wf-muted)] mt-3">Tanggal Resepsi</div>
                    <div class="mt-0.5 font-bold text-[var(--wf-navy)]">{{ $order->prospect?->date_resepsi ? $order->prospect->date_resepsi->format('d M Y') : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-[var(--wf-muted)]">Tanggal Closing</div>
                    <div class="mt-0.5 font-bold text-[var(--wf-navy)]">{{ $order->closing_date ? $order->closing_date->format('d M Y') : '-' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold text-[var(--wf-muted)]">Created</div>
                    <div class="mt-0.5 font-bold text-[var(--wf-navy)]">{{ optional($order->created_at)->format('d M Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h3 class="text-base font-bold text-[var(--wf-navy)]">Item Proyek</h3>
            <a href="{{ route('profile.admin-tools.projects.product', $order) }}"
                class="inline-flex items-center px-3.5 py-1.5 rounded-full bg-[var(--wf-gold)] text-[var(--wf-navy-deep)] text-xs font-extrabold hover:brightness-105 transition">
                Lihat Produk
            </a>
        </div>

        <div class="p-6">
            <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                            <th class="py-3 px-4 font-semibold">Produk</th>
                            <th class="py-3 px-4 font-semibold">Qty</th>
                            <th class="py-3 px-4 font-semibold">Harga</th>
                            <th class="py-3 px-4 font-semibold text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--wf-line)]">
                        @php
                            $items = $order->items ?? collect();
                            $itemsSubtotal = 0;
                        @endphp
                        @forelse($items as $item)
                            @php
                                $subtotal = ((int) $item->quantity) * ((int) $item->unit_price);
                                $itemsSubtotal += $subtotal;
                            @endphp
                            <tr class="text-[var(--wf-ink)]">
                                <td class="py-3 px-4 font-medium">{{ $item->product_name ?? $item->product?->name ?? '-' }}</td>
                                <td class="py-3 px-4 tabular-nums">{{ (int) $item->quantity }}</td>
                                <td class="py-3 px-4 tabular-nums">Rp {{ number_format((int) $item->unit_price, 0, ',', '.') }}</td>
                                <td class="py-3 px-4 text-right tabular-nums">Rp {{ number_format((int) $subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 px-4 text-center text-[var(--wf-muted)]">Belum ada item proyek.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($items->isNotEmpty())
                        <tfoot>
                            <tr class="border-t border-[var(--wf-line)] bg-[var(--wf-cream)]/40">
                                <td class="py-3 px-4 font-bold text-[var(--wf-navy)]" colspan="3">Total Item</td>
                                <td class="py-3 px-4 text-right font-bold text-[var(--wf-navy)] tabular-nums">Rp {{ number_format((int) $itemsSubtotal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>

            <div class="mt-5 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-4">
                    <div class="text-xs font-semibold text-[var(--wf-muted)]">Total Harga</div>
                    <div class="mt-1 text-lg font-extrabold text-[var(--wf-navy)] tabular-nums">Rp {{ number_format((int) $order->total_price, 0, ',', '.') }}</div>
                </div>
                <div class="wf-portal-tile rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                    <div class="text-xs font-semibold text-[var(--wf-muted)]">Grand Total</div>
                    <div class="mt-1 text-lg font-extrabold text-[var(--wf-navy)] tabular-nums">Rp {{ number_format($grandTotal, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="wf-profile-card overflow-hidden">
        <div class="px-6 py-4 border-b border-[var(--wf-line)] bg-[var(--wf-cream)]/60">
            <h3 class="text-base font-bold text-[var(--wf-navy)]">Item Pengeluaran</h3>
        </div>

        <div class="p-6">
            @php
                $expenses = $order->dataPengeluaran ?? collect();
                $expensesTotal = (int) $expenses->sum('amount');
            @endphp

            @if($expenses->isEmpty())
                <div class="text-sm text-[var(--wf-muted)]">Belum ada data pengeluaran.</div>
            @else
                <div class="overflow-x-auto rounded-xl border border-[var(--wf-line)]">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-[11px] uppercase tracking-wide text-[var(--wf-muted)] bg-[var(--wf-cream)]/70 border-b border-[var(--wf-line)]">
                                <th class="py-3 px-4 font-semibold">Tanggal</th>
                                <th class="py-3 px-4 font-semibold">Vendor</th>
                                <th class="py-3 px-4 font-semibold">Tahap</th>
                                <th class="py-3 px-4 font-semibold">Catatan</th>
                                <th class="py-3 px-4 font-semibold">Invoice</th>
                                <th class="py-3 px-4 font-semibold text-right">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--wf-line)]">
                            @foreach($expenses as $expense)
                                <tr class="text-xs text-[var(--wf-ink)]">
                                    <td class="py-3 px-4">
                                        {{ $expense->date_expense ? $expense->date_expense->format('d M Y') : '-' }}
                                    </td>
                                    <td class="py-3 px-4 font-medium">
                                        {{ \Illuminate\Support\Str::title($expense->vendor?->name ?? '-') }}
                                    </td>
                                    <td class="py-3 px-4">
                                        {{ \App\Models\Expense::getPaymentStageLabel($expense->payment_stage) ?? '-' }}
                                    </td>
                                    <td class="py-3 px-4 text-[var(--wf-muted)]">
                                        {{ $expense->note ?? '-' }}
                                    </td>
                                    <td class="py-3 px-4">
                                        @php $ndd = $expense->notaDinasDetail; @endphp
                                        <div>
                                            @if($ndd && $ndd->invoice_file)
                                                <a href="{{ route('profile.admin-tools.nota-dinas-details.invoice.view', $ndd) }}"
                                                    class="font-semibold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]"
                                                    target="_blank" rel="noopener">
                                                    {{ $ndd->invoice_number ?? 'Lihat' }}
                                                </a>
                                            @else
                                                {{ $ndd?->invoice_number ?? '-' }}
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-[var(--wf-muted)]">
                                            {{ $ndd?->status_invoice ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="py-3 px-4 text-right whitespace-nowrap tabular-nums font-semibold">
                                        Rp {{ number_format((int) $expense->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t border-[var(--wf-line)] bg-[var(--wf-cream)]/40">
                                <td class="py-3 px-4 font-bold text-[var(--wf-navy)]" colspan="5">Total Pengeluaran</td>
                                <td class="py-3 px-4 text-right font-bold text-[var(--wf-navy)] whitespace-nowrap tabular-nums">
                                    Rp {{ number_format($expensesTotal, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
