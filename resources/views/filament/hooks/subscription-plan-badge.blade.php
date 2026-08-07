@php
    use App\Support\CompanySubscription;

    $label = CompanySubscription::planLabel();
    $configured = CompanySubscription::hasConfiguredPlan();
@endphp

<div class="fi-topbar-item hidden sm:flex items-center ms-2">
    <span
        @class([
            'inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
            'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20' => $configured,
            'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20' => ! $configured,
        ])
        title="Paket langganan perusahaan — mengatur kuota & menu yang tampil"
    >
        {{ $label }}
    </span>
</div>
