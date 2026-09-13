<?php $__env->startSection('title', 'Pesanan saya · WOFINS'); ?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('front.partials.wf-front-base-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<style>
    .wf-order-card {
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1.15rem;
        padding: 1.15rem 1.25rem;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .wf-order-card:hover {
        border-color: rgba(11, 31, 58, 0.25);
        box-shadow: 0 14px 32px -24px rgba(11, 31, 58, 0.4);
    }
    .wf-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 0.2rem 0.65rem;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .wf-status.is-pending { background: #fef3c7; color: #92400e; }
    .wf-status.is-approved { background: #d1fae5; color: #065f46; }
    .wf-status.is-rejected { background: #fee2e2; color: #991b1b; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    $statusClass = static fn (string $status): string => match ($status) {
        'approved' => 'is-approved',
        'rejected' => 'is-rejected',
        default => 'is-pending',
    };
?>

<div class="wf-page">
    <?php echo $__env->make('front.partials.wf-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="pt-10 pb-16 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)]">Akun</p>
                <h1 class="mt-1 text-3xl font-bold text-[var(--wf-navy)]">Pesanan saya</h1>
                <p class="mt-2 text-sm text-[var(--wf-muted)]">
                    Riwayat pesanan paket WOFINS untuk <?php echo e($user->email); ?>.
                </p>
            </div>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <?php echo e(session('error')); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($orders->isEmpty()): ?>
                <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-8 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--wf-cream)] text-[var(--wf-navy)]">
                        <i class="fa-solid fa-receipt text-xl"></i>
                    </div>
                    <h2 class="text-lg font-bold text-[var(--wf-navy)]">Belum ada pesanan</h2>
                    <p class="mt-2 text-sm text-[var(--wf-muted)]">Pilih paket di halaman Harga untuk mulai berlangganan.</p>
                    <a href="<?php echo e(route('harga')); ?>" class="wf-btn-navy mt-5 inline-flex items-center justify-center px-6 py-3 text-sm">
                        Lihat paket
                    </a>
                </div>
            <?php else: ?>
                <div class="space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $order): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <a href="<?php echo e(route('pesanan-saya.show', $order->order_code)); ?>" class="wf-order-card block">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--wf-muted)]">
                                        <?php echo e($order->order_code); ?>

                                    </p>
                                    <h2 class="mt-1 text-base font-bold text-[var(--wf-navy)]">
                                        Paket <?php echo e($order->plan_name); ?>

                                    </h2>
                                    <p class="mt-0.5 text-sm text-[var(--wf-muted)]">
                                        <?php echo e($order->billing_label); ?>

                                        · <?php echo e(optional($order->submitted_at)->timezone(config('app.timezone'))->format('d M Y H:i') ?? $order->created_at->timezone(config('app.timezone'))->format('d M Y H:i')); ?>

                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="wf-status <?php echo e($statusClass($order->status)); ?>"><?php echo e($order->status_label); ?></span>
                                    <p class="mt-2 text-sm font-bold text-[var(--wf-navy)]"><?php echo e($order->formatted_amount); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                <div class="mt-6">
                    <?php echo e($orders->links()); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?php echo e(route('harga')); ?>" class="wf-btn-ghost inline-flex items-center justify-center px-5 py-2.5 text-sm">Lihat harga</a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->hasAssignedRole()): ?>
                        <a href="<?php echo e(route('profile')); ?>" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm">Ke dashboard</a>
                    <?php else: ?>
                        <a href="<?php echo e(route('account.pending')); ?>" class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm">Status akun</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </section>

    <?php echo $__env->make('front.partials.wf-footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/front/pesanan-saya.blade.php ENDPATH**/ ?>