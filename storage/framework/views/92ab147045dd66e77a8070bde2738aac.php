<?php $__env->startSection('title', 'Pesanan terkirim — '.$order->order_code); ?>

<?php $__env->startPush('styles'); ?>
<?php echo $__env->make('front.partials.wf-front-base-styles', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="wf-page">
    <?php echo $__env->make('front.partials.wf-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <section class="pt-16 pb-20 bg-gradient-to-b from-white to-[var(--wf-cream)]">
        <div class="max-w-lg mx-auto px-4 sm:px-6 text-center">
            <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--wf-navy)] text-[var(--wf-gold)]">
                <i class="fa-solid fa-check text-2xl"></i>
            </div>
            <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-2">Checkout</p>
            <h1 class="text-3xl font-bold text-[var(--wf-navy)]">Pesanan terkirim</h1>
            <p class="mt-3 text-[var(--wf-muted)] leading-relaxed">
                Terima kasih. Bukti pembayaran Anda sedang ditinjau tim WOFINS.
                Kami juga mengirim konfirmasi ke email <strong class="text-[var(--wf-navy)]"><?php echo e($order->email); ?></strong>
                bahwa aplikasi / paket Anda sedang dalam proses.
            </p>

            <div class="mt-8 rounded-2xl border border-[var(--wf-line)] bg-white text-left p-5 space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Kode pesanan</span>
                    <span class="font-bold text-[var(--wf-navy)]"><?php echo e($order->order_code); ?></span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Paket</span>
                    <span class="font-semibold text-[var(--wf-navy)]"><?php echo e($order->plan_name); ?> · <?php echo e($order->billing_label); ?></span>
                </div>
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Total transfer</span>
                    <span class="font-semibold text-[var(--wf-navy)]"><?php echo e($order->formatted_amount); ?></span>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if((int) $order->unique_amount > 0): ?>
                    <div class="flex justify-between gap-3">
                        <span class="text-[var(--wf-muted)]">Kode unik</span>
                        <span class="font-semibold text-[var(--wf-navy)]"><?php echo e($order->formatted_unique_amount); ?></span>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <div class="flex justify-between gap-3">
                    <span class="text-[var(--wf-muted)]">Status</span>
                    <span class="font-semibold text-amber-700"><?php echo e($order->status_label); ?></span>
                </div>
            </div>

            <p class="mt-4 text-xs text-[var(--wf-muted)]">
                Simpan kode pesanan. Cantumkan pada chat/WA jika menghubungi support.
            </p>

            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="<?php echo e(route('pesanan-saya')); ?>" class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">Pesanan saya</a>
                <a href="https://wa.me/6281373183794?text=<?php echo e(urlencode('Halo, saya sudah kirim bukti bayar paket '.$order->plan_name.'. Kode: '.$order->order_code)); ?>"
                   class="wf-btn-gold inline-flex items-center justify-center px-6 py-3 text-sm" target="_blank" rel="noopener">
                    Hubungi WhatsApp
                </a>
            </div>
        </div>
    </section>

    <?php echo $__env->make('front.partials.wf-footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/front/keranjang-sukses.blade.php ENDPATH**/ ?>