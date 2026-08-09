<x-filament-panels::page>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/payment/paymentmethod.css') }}?v={{ @filemtime(public_path('assets/payment/paymentmethod.css')) }}">

    <div class="pm-wf space-y-6">
        <header class="pm-hero">
            <div class="pm-hero__shapes" aria-hidden="true">
                <span class="pm-shape pm-shape--blob pm-shape--l1"></span>
                <span class="pm-shape pm-shape--ring pm-shape--l2"></span>
                <span class="pm-shape pm-shape--square pm-shape--l3"></span>
                <span class="pm-shape pm-shape--dot pm-shape--l4"></span>
                <span class="pm-shape pm-shape--tri pm-shape--l5"></span>

                <span class="pm-shape pm-shape--blob pm-shape--r1"></span>
                <span class="pm-shape pm-shape--ring pm-shape--r2"></span>
                <span class="pm-shape pm-shape--square pm-shape--r3"></span>
                <span class="pm-shape pm-shape--dot pm-shape--r4"></span>
                <span class="pm-shape pm-shape--blob pm-shape--r5"></span>

                <span class="pm-shape pm-shape--blob pm-shape--t1"></span>
                <span class="pm-shape pm-shape--blob pm-shape--b1"></span>
            </div>
            <div class="pm-hero__glow" aria-hidden="true"></div>
            <div class="pm-hero__body">
                @php
                    $companyName = $record->company?->company_name
                        ?? ($record->company_id
                            ? \App\Models\Company::query()->whereKey($record->company_id)->value('company_name')
                            : null);
                @endphp
                <div class="pm-hero__meta">
                    <p class="pm-hero__eyebrow">Daftar Rekening</p>
                    @if ($companyName)
                        <p class="pm-hero__company">{{ $companyName }}</p>
                    @endif
                    <h1 class="pm-hero__title">{{ $record->name }}</h1>
                    <p class="pm-hero__sub">
                        <span>{{ $record->bank_name }}</span>
                        <span class="pm-hero__dot">·</span>
                        <span class="pm-hero__account">{{ $record->no_rekening }}</span>
                        @if ($record->cabang)
                            <span class="pm-hero__dot">·</span>
                            <span>{{ $record->cabang }}</span>
                        @endif
                    </p>
                    <div class="pm-hero__badges">
                        @if ($record->is_cash)
                            <span class="pm-badge pm-badge--gold">Kas Tunai</span>
                        @else
                            <span class="pm-badge pm-badge--cream">Rekening Bank</span>
                        @endif
                        @if ($companyName)
                            <span class="pm-badge pm-badge--outline">{{ $companyName }}</span>
                        @endif
                    </div>
                </div>

                <div class="pm-hero__balance">
                    <p class="pm-hero__balance-label">Saldo Saat Ini</p>
                    <p @class([
                        'pm-hero__balance-value',
                        'is-positive' => $record->saldo >= 0,
                        'is-negative' => $record->saldo < 0,
                    ])>
                        Rp {{ number_format($record->saldo, 0, ',', '.') }}
                    </p>
                    @if ($record->perubahan_saldo != 0)
                        <p @class([
                            'pm-hero__balance-delta',
                            'is-positive' => $record->perubahan_saldo >= 0,
                            'is-negative' => $record->perubahan_saldo < 0,
                        ])>
                            {{ $record->perubahan_saldo >= 0 ? '+' : '' }}Rp
                            {{ number_format($record->perubahan_saldo, 0, ',', '.') }}
                            dari saldo awal
                        </p>
                    @else
                        <p class="pm-hero__balance-delta is-flat">Belum ada perubahan dari saldo awal</p>
                    @endif
                </div>
            </div>
        </header>

        <div class="pm-tabs-shell">
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
