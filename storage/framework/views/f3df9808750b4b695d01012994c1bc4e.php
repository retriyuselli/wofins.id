<?php $__env->startSection('title', 'Paket Berakhir — WOFINS'); ?>

<?php $__env->startPush('styles'); ?>
<style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-expired-page {
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
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Support\CompanySubscription;

    $planLabel = CompanySubscription::planLabel();
    $expiresLabel = CompanySubscription::expiresAtLabel();
    $canManage = CompanySubscription::canManageSubscription();
    $adminContact = CompanySubscription::subscriptionAdminContact();
?>
<div class="wf-expired-page">
    <?php echo $__env->make('front.partials.wf-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="py-16 sm:py-20">
        <div class="max-w-xl mx-auto px-4 sm:px-6">
            <div class="rounded-3xl border border-[var(--wf-line)] bg-white p-8 sm:p-10 shadow-sm space-y-6">
                <div class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600">
                    <i class="fa-solid fa-calendar-xmark text-lg"></i>
                </div>

                <div>
                    <p class="text-xs font-bold tracking-[0.18em] uppercase text-[var(--wf-gold)]">Masa aktif berakhir</p>
                    <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-[var(--wf-navy)] leading-tight">
                        Akses dashboard ditangguhkan
                    </h1>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                        Paket <strong class="text-[var(--wf-navy)]"><?php echo e($planLabel); ?></strong>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($expiresLabel): ?>
                            aktif sampai <strong class="text-[var(--wf-navy)]"><?php echo e($expiresLabel); ?></strong> dan sudah berakhir.
                        <?php else: ?>
                            sudah berakhir.
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        Seluruh tim di perusahaan Anda terdampak sampai paket diperpanjang.
                    </p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canManage): ?>
                        <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                            Sebagai admin perusahaan, Anda dapat perpanjang paket agar semua user kembali aktif.
                        </p>
                    <?php else: ?>
                        <p class="mt-2 text-sm text-[var(--wf-muted)] leading-relaxed">
                            Hubungi admin perusahaan Anda
                            (<strong class="text-[var(--wf-navy)]"><?php echo e($adminContact['label']); ?></strong>
                            untuk perpanjang paket. Staf tidak perlu memesan sendiri.
                        </p>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <?php echo e(session('error')); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                <div class="flex flex-col sm:flex-row gap-3 pt-1">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canManage): ?>
                        <a href="<?php echo e(route('harga')); ?>" class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Perpanjang paket
                        </a>
                        <a href="<?php echo e(route('kontak')); ?>" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Hubungi support WOFINS
                        </a>
                    <?php else: ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($adminContact['email'])): ?>
                            <a href="mailto:<?php echo e($adminContact['email']); ?>?subject=<?php echo e(rawurlencode('Perpanjang paket WOFINS')); ?>"
                               class="wf-btn-gold inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                                Hubungi admin WO
                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <a href="<?php echo e(route('profile')); ?>" class="wf-btn-navy inline-flex flex-1 items-center justify-center px-5 py-3 text-sm">
                            Kembali ke profil
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php echo $__env->make('front.partials.wf-footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/front/subscription-expired.blade.php ENDPATH**/ ?>