<?php
    use App\Support\CompanySubscription;

    $label = CompanySubscription::planLabel();
    $configured = CompanySubscription::hasConfiguredPlan();
    $expiresLabel = CompanySubscription::expiresAtLabel();
    $expired = CompanySubscription::isExpired();
    $soon = CompanySubscription::isExpiringSoon();
?>

<div class="fi-topbar-item hidden sm:flex items-center ms-2">
    <span
        class="<?php echo \Illuminate\Support\Arr::toCssClasses([
            'inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
            'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/20' => $expired,
            'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20' => ! $expired && ($soon || ! $configured),
            'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20' => ! $expired && ! $soon && $configured,
        ]); ?>"
        title="<?php echo e($expiresLabel ? 'Aktif sampai '.$expiresLabel : 'Paket langganan perusahaan — mengatur kuota & menu yang tampil'); ?>"
    >
        <?php echo e($label); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiresLabel): ?>
            <span class="opacity-80">· s/d <?php echo e($expiresLabel); ?></span>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </span>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/filament/hooks/subscription-plan-badge.blade.php ENDPATH**/ ?>