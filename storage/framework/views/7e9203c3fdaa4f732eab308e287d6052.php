<!-- Employment Information Section -->
<?php
    $user = $user ?? Auth::user();
?>
<div>
    <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2H8a2 2 0 012-2V6m8 0H8m0 0v.01M8 6v6h8V6M8 12v.01"></path>
        </svg>
        Detail Pekerjaan
    </h3>
    <div class="space-y-4">
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Tanggal Bergabung</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->hire_date ? $user->hire_date->format('d F Y') : $user->created_at->format('d F Y')); ?></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Status</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->status_id): ?>
                    <?php echo e($user->status?->status_name ?? 'Status tidak ditemukan'); ?>

                <?php else: ?>
                    Tidak ada status yang ditetapkan
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Pengalaman Kerja</label>
            <?php
                $joinedAt = $user->hire_date ?? $user->created_at;
            ?>
            <p class="text-[var(--wf-gold)] font-semibold text-sm"><?php echo e($joinedAt->diffForHumans(now(), ['parts' => 2, 'join' => ', ', 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE])); ?> <span aria-hidden="true">*</span></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Alamat</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->address ?? 'Tidak ditentukan'); ?></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Tanggal Mulai Kerja</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->hire_date ? $user->hire_date->format('d F Y') : 'Tidak ditentukan'); ?></p>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($showSubscriptionQuota ?? false) && ! ($subscriptionIsSuperAdmin ?? false)): ?>
            <div>
                <label class="text-sm font-medium text-[var(--wf-muted)]">Paket</label>
                <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($subscriptionPlanLabel ?? 'Belum diatur'); ?></p>
            </div>
            <div>
                <label class="text-sm font-medium text-[var(--wf-muted)]">Tanggal Berakhir Paket</label>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($subscriptionExpiresLabel)): ?>
                    <p class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'text-sm font-medium',
                        'text-red-600' => $subscriptionIsExpired ?? false,
                        'text-amber-700' => ! ($subscriptionIsExpired ?? false) && is_int($subscriptionDaysRemaining ?? null) && $subscriptionDaysRemaining <= 14,
                        'text-[var(--wf-ink)]' => ! ($subscriptionIsExpired ?? false) && (! is_int($subscriptionDaysRemaining ?? null) || $subscriptionDaysRemaining > 14),
                    ]); ?>">
                        <?php echo e($subscriptionExpiresLabel); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subscriptionIsExpired ?? false): ?>
                            <span class="text-xs font-semibold">(sudah berakhir)</span>
                        <?php elseif(is_int($subscriptionDaysRemaining ?? null)): ?>
                            <span class="text-xs text-[var(--wf-muted)] font-normal">· sisa <?php echo e($subscriptionDaysRemaining); ?> hari</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="text-[var(--wf-ink)] text-sm font-medium">Belum ditentukan</p>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/profile/sections/employment-info.blade.php ENDPATH**/ ?>