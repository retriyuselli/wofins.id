<?php
    $showSubscriptionQuota = $showSubscriptionQuota ?? false;
    $subscriptionIsSuperAdmin = $subscriptionIsSuperAdmin ?? false;
?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showSubscriptionQuota): ?>
    <div class="wf-profile-card mt-6">
        <div class="px-6 py-5 border-b border-[var(--wf-line)] bg-[linear-gradient(145deg,var(--wf-navy)_0%,#14335a_100%)] rounded-t-[inherit]">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subscriptionIsSuperAdmin): ?>
                        <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Super Admin</p>
                    <?php else: ?>
                        <p class="text-[10px] font-bold tracking-[0.18em] uppercase text-[var(--wf-gold-soft)]">Paket Tim Anda</p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <h2 class="mt-1 text-lg sm:text-xl font-bold text-white">Paket & Matriks Kuota</h2>
                    <p class="mt-1 text-sm text-white/65">
                        <?php echo e($subscriptionPlanLabel ?? 'Paket belum diatur'); ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($subscriptionExpiresLabel)): ?>
                            · <?php echo e(($subscriptionIsExpired ?? false) ? 'Berakhir' : 'Aktif sampai'); ?>

                            <span class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                                'font-semibold',
                                'text-red-200' => $subscriptionIsExpired ?? false,
                                'text-[var(--wf-gold-soft)]' => ! ($subscriptionIsExpired ?? false),
                            ]); ?>"><?php echo e($subscriptionExpiresLabel); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! ($subscriptionIsExpired ?? false) && is_int($subscriptionDaysRemaining ?? null)): ?>
                                <span class="text-white/55">(sisa <?php echo e($subscriptionDaysRemaining); ?> hari)</span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! ($subscriptionConfigured ?? false)): ?>
                            <span class="text-[var(--wf-gold-soft)]">· lihat Admin → Perusahaan</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="<?php echo e(route('harga')); ?>"
                       class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-bold text-white hover:bg-white/15 transition">
                        Lihat Harga
                    </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subscriptionIsSuperAdmin): ?>
                        <?php
                            $companyEditUrl = null;
                            try {
                                $companyEditUrl = \App\Filament\Resources\Companies\CompanyResource::getUrl('index');
                            } catch (\Throwable) {
                                $companyEditUrl = url('/admin');
                            }
                        ?>
                        <a href="<?php echo e($companyEditUrl); ?>"
                           class="inline-flex items-center gap-2 rounded-full bg-[var(--wf-gold)] px-4 py-2 text-xs font-extrabold text-[var(--wf-navy-deep)] hover:brightness-105 transition">
                            Kelola Paket
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-8">
            
            <div>
                <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Penggunaan kuota
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $subscriptionQuotaRows ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $barColor = $row['full']
                                ? 'bg-red-500'
                                : ($row['percent'] >= 80 ? 'bg-amber-500' : 'bg-[var(--wf-navy)]');
                        ?>
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)]/60 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]"><?php echo e($row['label']); ?></p>
                                    <p class="mt-1 text-2xl font-extrabold text-[var(--wf-navy)] tabular-nums">
                                        <?php echo e($row['used']); ?>

                                        <span class="text-sm font-semibold text-[var(--wf-muted)]">
                                            / <?php echo e($row['limit'] === null ? '∞' : $row['limit']); ?>

                                        </span>
                                    </p>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($row['full']): ?>
                                    <span class="rounded-full bg-red-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-red-700">Penuh</span>
                                <?php elseif($row['limit'] === null): ?>
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Tak terbatas</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-white border border-[var(--wf-line)] px-2.5 py-1 text-[10px] font-bold text-[var(--wf-navy)]">
                                        Sisa <?php echo e($row['remaining']); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div class="mt-3 h-2 rounded-full bg-white border border-[var(--wf-line)] overflow-hidden">
                                <div class="h-full <?php echo e($barColor); ?> transition-all"
                                     style="width: <?php echo e($row['limit'] === null ? '8' : $row['percent']); ?>%"></div>
                            </div>
                            <p class="mt-2 text-xs text-[var(--wf-muted)]"><?php echo e($row['summary']); ?></p>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>

            
            <div>
                <h3 class="text-base font-bold text-[var(--wf-navy)] mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Matriks fitur paket
                </h3>

                <div class="rounded-2xl border border-[var(--wf-line)] overflow-hidden">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 divide-y sm:divide-y-0 sm:auto-rows-fr">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $subscriptionFeatureMatrix ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-[var(--wf-line)] sm:border-r last:border-b-0">
                                <span class="text-sm text-[var(--wf-ink)]"><?php echo e($feature['label']); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($feature['allowed']): ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-bold text-emerald-700">
                                        <i class="fa-solid fa-check text-[10px]"></i> Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-500">
                                        <i class="fa-solid fa-lock text-[10px]"></i> Terkunci
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                <p class="mt-3 text-xs text-[var(--wf-muted)] leading-relaxed">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($subscriptionIsSuperAdmin): ?>
                        Matriks di atas mengikuti <strong class="text-[var(--wf-navy)]">paket perusahaan</strong>
                        (untuk user biasa). Sebagai <strong class="text-[var(--wf-navy)]">super admin</strong>,
                        Anda tetap bisa mengakses semua fitur dan melebihi kuota bila diperlukan.
                    <?php else: ?>
                        Angka kuota dihitung dari data <strong class="text-[var(--wf-navy)]">tim paket Anda</strong>
                        (bukan seluruh platform). Fitur terkunci bisa dibuka dengan upgrade paket.
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </p>
            </div>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/profile/sections/subscription-quota.blade.php ENDPATH**/ ?>