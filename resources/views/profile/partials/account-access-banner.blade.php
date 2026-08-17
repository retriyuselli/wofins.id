@php
    $accountAccessAlerts = $accountAccessAlerts ?? [];
    $hasAccessAlerts = count($accountAccessAlerts) > 0;
    $primaryAlert = $accountAccessAlerts[0] ?? null;
    $alertTone = $primaryAlert['tone'] ?? 'warning';
    $alertIcon = $alertTone === 'danger' ? 'fa-solid fa-circle-exclamation' : 'fa-solid fa-triangle-exclamation';
    $alertIconWrap = $alertTone === 'danger'
        ? 'bg-rose-50 text-rose-600'
        : 'bg-amber-50 text-amber-700';
    $alertKey = collect($accountAccessAlerts)->pluck('type')->implode('|');
@endphp

@if ($hasAccessAlerts && $primaryAlert)
    <div x-show="accessAlertOpen" x-cloak
         class="fixed inset-0 z-[110] flex items-center justify-center p-4"
         role="dialog" aria-modal="true" aria-labelledby="profile-access-alert-title"
         x-init="
            const key = @js('wf_access_alert_'.$alertKey);
            if (! sessionStorage.getItem(key)) {
                accessAlertOpen = true;
            }
         ">
        <div class="absolute inset-0 wf-modal-backdrop" @click="accessAlertOpen = false; sessionStorage.setItem(@js('wf_access_alert_'.$alertKey), '1')"></div>
        <div class="relative w-full max-w-md rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
             @click.stop
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95">
            <div class="px-6 pt-8 pb-2 text-center">
                <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full {{ $alertIconWrap }}">
                    <i class="{{ $alertIcon }} text-2xl"></i>
                </span>
                <h3 id="profile-access-alert-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                    {{ $primaryAlert['title'] }}
                </h3>
                <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                    {{ $primaryAlert['body'] }}
                </p>

                @if (count($accountAccessAlerts) > 1)
                    <ul class="mt-4 space-y-2 text-left">
                        @foreach (array_slice($accountAccessAlerts, 1) as $extra)
                            <li class="rounded-xl border border-[var(--wf-line)] bg-[var(--wf-cream)] px-3 py-2 text-xs text-[var(--wf-ink)]">
                                <span class="font-semibold text-[var(--wf-navy)]">{{ $extra['title'] }}:</span>
                                {{ $extra['body'] }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <div class="px-6 py-5 flex flex-col sm:flex-row gap-2 justify-center">
                @if (($primaryAlert['type'] ?? '') === 'subscription_expired')
                    @if ($primaryAlert['can_manage'] ?? false)
                        <a href="{{ route('harga') }}"
                           class="wf-btn-gold inline-flex items-center justify-center px-5 py-3 text-sm">
                            Perpanjang paket
                        </a>
                    @elseif (! empty($primaryAlert['admin_email']))
                        <a href="mailto:{{ $primaryAlert['admin_email'] }}?subject={{ rawurlencode('Perpanjang paket WOFINS') }}"
                           class="wf-btn-gold inline-flex items-center justify-center px-5 py-3 text-sm">
                            Hubungi admin WO
                        </a>
                    @endif
                @elseif (($primaryAlert['type'] ?? '') === 'subscription_expiring_soon')
                    @if ($primaryAlert['can_manage'] ?? false)
                        <a href="{{ route('harga') }}"
                           class="wf-btn-gold inline-flex items-center justify-center px-5 py-3 text-sm">
                            Perpanjang sekarang
                        </a>
                    @elseif (! empty($primaryAlert['admin_email']))
                        <a href="mailto:{{ $primaryAlert['admin_email'] }}?subject={{ rawurlencode('Paket WOFINS hampir berakhir') }}"
                           class="wf-btn-gold inline-flex items-center justify-center px-5 py-3 text-sm">
                            Hubungi admin WO
                        </a>
                    @endif
                @endif
                <a href="{{ route('kontak') }}"
                   class="wf-btn-navy inline-flex items-center justify-center px-5 py-3 text-sm">
                    Hubungi support
                </a>
                <button type="button"
                        @click="accessAlertOpen = false; sessionStorage.setItem(@js('wf_access_alert_'.$alertKey), '1')"
                        class="wf-btn-ghost inline-flex items-center justify-center px-5 py-3 text-sm">
                    Mengerti
                </button>
            </div>
        </div>
    </div>
@endif
