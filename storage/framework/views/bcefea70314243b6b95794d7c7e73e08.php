<?php
    $user = $user ?? Auth::user();
?>
<div class="px-6 py-8 bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)]">
    <div class="flex items-center gap-6">
        <div class="relative group shrink-0">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($user->avatar_url): ?>
                <img class="h-24 w-24 rounded-full object-cover border-4 border-white/90 shadow-lg transition-transform duration-300 group-hover:scale-105 ring-2 ring-[var(--wf-gold)]/50"
                    src="<?php echo e(Storage::url($user->avatar_url)); ?>"
                    alt="Profile <?php echo e($user->name); ?>"
                    onerror="this.src='https://ui-avatars.com/api/?name=<?php echo e(urlencode($user->name)); ?>&color=e8d48b&background=0b1f3a&size=128&font-size=0.4'">
            <?php else: ?>
                <img class="h-24 w-24 rounded-full object-cover border-4 border-white/90 shadow-lg transition-transform duration-300 group-hover:scale-105 ring-2 ring-[var(--wf-gold)]/50"
                    src="https://ui-avatars.com/api/?name=<?php echo e(urlencode($user->name)); ?>&color=e8d48b&background=0b1f3a&size=128&font-size=0.4"
                    alt="Profile <?php echo e($user->name); ?>">
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="absolute -bottom-1 -right-1 h-6 w-6 border-2 border-white rounded-full bg-emerald-400"></div>
        </div>

        <div class="text-white flex-1 min-w-0">
            <h2 class="text-2xl font-bold tracking-tight truncate"><?php echo e($user->name); ?></h2>
            <p class="font-medium mt-1 text-white/70 truncate"><?php echo e($user->email); ?></p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-white/10 text-white border border-white/15">
                    <svg class="w-4 h-4 inline mr-1 text-[var(--wf-gold-soft)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-4 0v2"></path>
                    </svg>
                    ID: #WO<?php echo e(str_pad($user->id, 4, '0', STR_PAD_LEFT)); ?>

                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-[var(--wf-gold)]/20 text-[var(--wf-gold-soft)] border border-[var(--wf-gold)]/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 mr-2"></span>
                    Aktif
                </span>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($subscriptionPlanLabel) && ($subscriptionConfigured ?? false)): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-white/10 text-white border border-white/15">
                        <?php echo e($subscriptionPlanLabel); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($subscriptionExpiresLabel)): ?>
                    <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                        'inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border',
                        'bg-red-500/20 text-red-100 border-red-400/40' => $subscriptionIsExpired ?? false,
                        'bg-amber-500/20 text-amber-100 border-amber-400/40' => ! ($subscriptionIsExpired ?? false) && is_int($subscriptionDaysRemaining ?? null) && $subscriptionDaysRemaining <= 14,
                        'bg-white/10 text-white border-white/15' => ! ($subscriptionIsExpired ?? false) && (! is_int($subscriptionDaysRemaining ?? null) || $subscriptionDaysRemaining > 14),
                    ]); ?>">
                        <?php echo e(($subscriptionIsExpired ?? false) ? 'Berakhir' : 'Aktif s/d'); ?>

                        <?php echo e($subscriptionExpiresLabel); ?>

                    </span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <div class="hidden md:flex flex-col items-end gap-1 text-right shrink-0">
            <p class="text-xs font-medium text-white/55">Profil Diperbarui</p>
            <p class="text-sm font-semibold text-white/90"><?php echo e($user->updated_at->diffForHumans()); ?></p>
        </div>
    </div>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/profile/sections/header.blade.php ENDPATH**/ ?>