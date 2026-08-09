@extends('layouts.app')

@section('title', 'Status Akun - WOFINS')

@push('styles')
<style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-gold-soft: #e8d48b;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-pending-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: var(--wf-cream);
            min-height: 100vh;
        }

        .wf-btn-navy {
            background: var(--wf-navy);
            color: #fff;
            border-radius: 999px;
            font-weight: 700;
            transition: background .2s ease, transform .2s ease;
        }

        .wf-btn-navy:hover {
            background: var(--wf-navy-deep);
            transform: translateY(-1px);
        }

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
            transition: background .2s ease;
        }

        .wf-btn-ghost:hover {
            background: var(--wf-cream);
        }

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
            transition: filter .2s ease, transform .2s ease;
        }

        .wf-btn-gold:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }

        .wf-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.65rem;
            font-size: 0.68rem;
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
    use App\Enums\ProspectAppStatus;
    use App\Support\PricingPlans;

    $user = Auth::user();
    $prospect = $prospect ?? null;
    $orders = $orders ?? collect();
    $latestOrder = $latestOrder ?? null;
    $hasOrder = $orders->isNotEmpty();
    $hasPendingOrder = $orders->contains(fn ($o) => $o->status === 'pending_review');
    $hasApprovedOrder = $orders->contains(fn ($o) => $o->status === 'approved');
    $hasSubmitted = $prospect !== null;
    $isRejected = $prospect?->status === ProspectAppStatus::Rejected;
    $justSubmitted = session('registration_submitted') || session('success');
    $serviceLabel = PricingPlans::shortLabel($prospect?->service);

    $statusClass = static fn (string $status): string => match ($status) {
        'approved' => 'is-approved',
        'rejected' => 'is-rejected',
        default => 'is-pending',
    };

    // Prioritas: pesanan paket (checkout) lebih relevan daripada form konsultasi.
    if ($hasPendingOrder) {
        $headerTitle = 'Pesanan sedang ditinjau';
        $headerLead = 'Halo'.($user?->name ? ', '.$user->name : '').'. Bukti pembayaran paket Anda menunggu verifikasi admin.';
        $mode = 'order_pending';
    } elseif ($hasApprovedOrder && ! $user?->hasAssignedRole()) {
        $headerTitle = 'Paket disetujui — akun segera aktif';
        $headerLead = 'Halo'.($user?->name ? ', '.$user->name : '').'. Pembayaran sudah disetujui. Admin sedang menyiapkan akses dashboard.';
        $mode = 'order_approved';
    } elseif ($hasSubmitted && ! $isRejected) {
        $headerTitle = 'Pendaftaran sedang ditinjau';
        $headerLead = 'Halo'.($user?->name ? ', '.$user->name : '').'. Data formulir Anda sudah masuk antrean admin.';
        $mode = 'prospect_waiting';
    } elseif ($isRejected) {
        $headerTitle = 'Pendaftaran perlu diperbarui';
        $headerLead = 'Halo'.($user?->name ? ', '.$user->name : '').'. Pengajuan sebelumnya belum dapat disetujui.';
        $mode = 'prospect_rejected';
    } else {
        $headerTitle = 'Akun belum diaktifkan';
        $headerLead = 'Halo'.($user?->name ? ', '.$user->name : '').'. Login berhasil. Pilih cara aktivasi di bawah.';
        $mode = 'choose_path';
    }
@endphp

<div class="wf-pending-page">
    @include('front.partials.wf-nav')

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="bg-white border border-[var(--wf-line)] rounded-[1.25rem] overflow-hidden shadow-[0_18px_40px_-28px_rgba(11,31,58,0.28)]">
            <div class="px-6 py-5 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
                <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Status Akun</p>
                <h1 class="mt-1 text-xl sm:text-2xl font-bold text-white tracking-tight">{{ $headerTitle }}</h1>
                <p class="mt-2 text-sm text-white/65">{{ $headerLead }}</p>
            </div>

            <div class="p-6 sm:p-8 space-y-6">
                @if (session('info'))
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-4 py-3 text-sm text-[var(--wf-muted)]">
                        {{ session('info') }}
                    </div>
                @endif

                {{-- Mode: ada pesanan paket menunggu / sudah disetujui --}}
                @if (in_array($mode, ['order_pending', 'order_approved'], true))
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                        <div class="flex gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-[var(--wf-line)] text-[var(--wf-gold)]">
                                <i class="fa-solid {{ $mode === 'order_approved' ? 'fa-check' : 'fa-clock' }}"></i>
                            </span>
                            <div>
                                <h2 class="text-base font-bold text-[var(--wf-navy)]">
                                    {{ $mode === 'order_approved' ? 'Pembayaran sudah diverifikasi' : 'Tidak perlu isi formulir lagi' }}
                                </h2>
                                <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                    @if ($mode === 'order_approved')
                                        Tim admin akan mengaktifkan role & paket pada akun
                                        <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong>.
                                        Anda akan mendapat email / notifikasi setelah dashboard siap.
                                    @else
                                        Pesanan paket dan bukti bayar sudah kami terima.
                                        Dashboard akan terbuka setelah admin memverifikasi pembayaran dan mengaktifkan akun
                                        <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong>.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($latestOrder)
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5 space-y-2.5 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Pesanan terbaru</p>
                                <span class="wf-status {{ $statusClass($latestOrder->status) }}">{{ $latestOrder->status_label }}</span>
                            </div>
                            <div><span class="text-[var(--wf-muted)]">Kode</span> : <strong class="text-[var(--wf-navy)]">{{ $latestOrder->order_code }}</strong></div>
                            <div><span class="text-[var(--wf-muted)]">Paket</span> : <strong class="text-[var(--wf-navy)]">{{ $latestOrder->plan_name }} · {{ $latestOrder->billing_label }}</strong></div>
                            <div><span class="text-[var(--wf-muted)]">Total</span> : <strong class="text-[var(--wf-navy)]">{{ $latestOrder->formatted_amount }}</strong></div>
                        </div>
                    @endif

                    <div class="space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Langkah selanjutnya</p>
                        <ul class="space-y-2.5 text-sm text-[var(--wf-muted)]">
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">1</span>
                                <span>Admin meninjau bukti pembayaran di Pesanan Paket.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">2</span>
                                <span>Setelah disetujui, paket & role akun diaktifkan.</span>
                            </li>
                            <li class="flex items-start gap-2.5">
                                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[10px] font-bold text-[var(--wf-gold-soft)]">3</span>
                                <span>Login ulang / buka Dashboard setelah dapat konfirmasi.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <a href="{{ route('pesanan-saya') }}" class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Lihat pesanan saya
                        </a>
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Hubungi kami
                        </a>
                    </div>

                {{-- Mode: form prospect menunggu --}}
                @elseif ($mode === 'prospect_waiting')
                    @if ($justSubmitted)
                        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                            <h2 class="text-base font-bold text-[var(--wf-navy)]">Terima kasih atas pendaftaran Anda</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                {{ session('success') ?: 'Data formulir sudah kami terima. Tim admin akan meninjau dan mengaktifkan akun setelah disetujui.' }}
                            </p>
                        </div>
                    @else
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                            <h2 class="text-base font-bold text-[var(--wf-navy)]">Menunggu aktivasi dari admin</h2>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                                Pendaftaran Anda sudah tercatat untuk akun
                                <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong>.
                            </p>
                        </div>
                    @endif

                    <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5 space-y-2.5 text-sm">
                        <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Ringkasan pengajuan</p>
                        <div><span class="text-[var(--wf-muted)]">Perusahaan</span> : <strong class="text-[var(--wf-navy)]">{{ $prospect->company_name }}</strong></div>
                        <div><span class="text-[var(--wf-muted)]">Minat paket</span> : <strong class="text-[var(--wf-navy)]">{{ $serviceLabel }}</strong></div>
                        @if ($prospect->industry)
                            <div><span class="text-[var(--wf-muted)]">Departemen</span> : <strong class="text-[var(--wf-navy)]">{{ $prospect->industry->industry_name }}</strong></div>
                        @endif
                        <div><span class="text-[var(--wf-muted)]">Status</span> : <strong class="text-[var(--wf-navy)]">{{ $prospect->status_label }}</strong></div>
                    </div>

                    <div class="rounded-2xl border border-dashed border-[var(--wf-line)] bg-white p-4 text-sm text-[var(--wf-muted)]">
                        Ingin langsung berlangganan? Anda juga bisa
                        <a href="{{ route('harga') }}" class="font-semibold text-[var(--wf-navy)] underline underline-offset-2">pilih paket & bayar</a>,
                        lalu pantau di Pesanan saya.
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <a href="{{ route('pesanan-saya') }}" class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">Pesanan saya</a>
                        <a href="{{ route('kontak') }}" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">Hubungi kami</a>
                    </div>

                {{-- Mode: belum ada apa-apa / ditolak — dua jalur jelas --}}
                @else
                    <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-5">
                        <h2 class="text-base font-bold text-[var(--wf-navy)]">
                            {{ $isRejected ? 'Silakan perbarui data atau pilih paket' : 'Pilih cara aktivasi' }}
                        </h2>
                        <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                            @if ($isRejected)
                                Pengajuan formulir sebelumnya ditolak. Anda bisa mengisi ulang formulir, atau langsung checkout paket.
                            @else
                                Akun <strong class="text-[var(--wf-navy)]">{{ $user?->email }}</strong> belum punya role.
                                Pilih salah satu jalur di bawah — tidak perlu keduanya.
                            @endif
                        </p>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-3">
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5 flex flex-col">
                            <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-gold)]">Jalur cepat</p>
                            <h3 class="mt-1 text-base font-bold text-[var(--wf-navy)]">Beli paket</h3>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] flex-1">
                                Pilih paket, transfer, unggah bukti. Admin verifikasi lalu aktifkan akun & paket.
                            </p>
                            <a href="{{ route('harga') }}" class="wf-btn-gold mt-4 inline-flex items-center justify-center px-4 py-2.5 text-sm">
                                Lihat harga
                            </a>
                        </div>
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-5 flex flex-col">
                            <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)]">Jalur konsultasi</p>
                            <h3 class="mt-1 text-base font-bold text-[var(--wf-navy)]">Formulir pendaftaran</h3>
                            <p class="mt-2 text-sm text-[var(--wf-muted)] flex-1">
                                Untuk demo / konsultasi dulu. Admin meninjau data lalu mengaktifkan akses.
                            </p>
                            <a href="{{ route('pendaftaran') }}" class="wf-btn-navy mt-4 inline-flex items-center justify-center px-4 py-2.5 text-sm">
                                Isi formulir
                            </a>
                        </div>
                    </div>

                    @if ($hasOrder)
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-[var(--wf-muted)] mb-2">Pesanan Anda</p>
                            <ul class="space-y-2 text-sm">
                                @foreach ($orders->take(3) as $order)
                                    <li class="flex items-center justify-between gap-2">
                                        <a href="{{ route('pesanan-saya.show', $order->order_code) }}" class="font-semibold text-[var(--wf-navy)] hover:underline">
                                            {{ $order->order_code }} · {{ $order->plan_name }}
                                        </a>
                                        <span class="wf-status {{ $statusClass($order->status) }}">{{ $order->status_label }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif

                <div class="flex flex-col sm:flex-row gap-3 border-t border-[var(--wf-line)] pt-5">
                    <a href="{{ route('home') }}" class="wf-btn-ghost inline-flex flex-1 items-center justify-center px-5 py-2.5 text-sm">
                        Beranda
                    </a>
                    <form method="POST" action="{{ route('logout') }}" class="flex-1">
                        @csrf
                        <button type="submit" class="wf-btn-ghost w-full inline-flex items-center justify-center px-5 py-2.5 text-sm text-[#92400e] border-[#b45309]/40 hover:bg-[#b45309]/10">
                            Log Out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @include('front.partials.wf-footer')
</div>
@endsection
