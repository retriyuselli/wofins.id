<?php
    $isHome = request()->routeIs('home');
    $isFitur = request()->routeIs('fitur') || request()->routeIs('fitur.show');
    $isHarga = request()->routeIs('harga');
    $isDocs = request()->routeIs('docs.*');
    $isKontak = request()->routeIs('kontak');
    $initialNav = $isFitur ? 'fitur' : ($isHarga ? 'harga' : ($isDocs ? 'docs' : ($isKontak ? 'kontak' : '')));
    $fiturMenus = array_values(config('wofins_features', []));
    $currentFiturSlug = request()->route('slug');
?>

<style>
    .wf-nav-link {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        color: var(--wf-muted);
        font-weight: 600;
        transition: color .2s ease;
    }

    .wf-nav-link:hover {
        color: var(--wf-navy);
    }

    .wf-nav-link.is-active {
        color: var(--wf-navy);
        font-weight: 700;
    }

    .wf-nav-link.is-active::after {
        content: '';
        position: absolute;
        left: 0.75rem;
        right: 0.75rem;
        bottom: 0.15rem;
        height: 2px;
        border-radius: 999px;
        background: var(--wf-gold);
    }

    .wf-nav-dropdown {
        position: absolute;
        top: calc(100% + 0.55rem);
        left: 0;
        min-width: 18rem;
        background: #fff;
        border: 1px solid var(--wf-line);
        border-radius: 1rem;
        box-shadow: 0 18px 40px -24px rgba(11, 31, 58, 0.4);
        padding: 0.5rem;
        z-index: 60;
    }

    .wf-nav-dropdown a {
        display: flex;
        align-items: center;
        gap: 0.7rem;
        padding: 0.65rem 0.75rem;
        border-radius: 0.75rem;
        color: var(--wf-ink);
        font-size: 0.875rem;
        font-weight: 600;
        transition: background .15s ease;
    }

    .wf-nav-dropdown a:hover,
    .wf-nav-dropdown a.is-active {
        background: rgba(201, 162, 39, 0.1);
        color: var(--wf-navy);
    }

    .wf-nav-dropdown .dd-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.55rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.8rem;
    }

    .wf-nav-link-mobile {
        display: block;
        padding: 0.5rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--wf-ink);
        border-radius: 0.5rem;
    }

    .wf-nav-link-mobile.is-active {
        color: var(--wf-navy);
        background: rgba(201, 162, 39, 0.12);
        box-shadow: inset 3px 0 0 var(--wf-gold);
    }

    .wf-nav-mobile-sub a {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.55rem 0.75rem 0.55rem 1.1rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--wf-muted);
        border-radius: 0.5rem;
    }

    .wf-nav-mobile-sub a:hover,
    .wf-nav-mobile-sub a.is-active {
        color: var(--wf-navy);
        background: rgba(201, 162, 39, 0.08);
    }

    .wf-brand {
        display: inline-flex;
        align-items: center;
        text-decoration: none;
    }

    .wf-brand-mark {
        position: relative;
        display: inline-block;
        overflow: visible;
    }

    .wf-brand-word {
        display: inline-flex;
        font-size: 1.5rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        line-height: 1;
        color: var(--wf-navy);
    }

    .wf-brand-letter {
        display: inline-block;
        animation: wf-brand-in 0.55s cubic-bezier(.22, 1, .36, 1) both;
        animation-delay: calc(var(--i) * 70ms);
        transition: transform .25s ease, color .25s ease;
    }

    .wf-brand-wo {
        display: inline-flex;
        animation: wf-brand-wo-swing 1.7s ease-in-out 0.7s infinite;
    }

    .wf-brand-letter.is-wo {
        color: var(--wf-gold);
    }

    .wf-brand:hover .wf-brand-letter {
        transform: translateY(-2px);
    }

    .wf-brand:hover .wf-brand-letter.is-wo {
        color: var(--wf-gold-soft);
    }

    .wf-brand:hover .wf-brand-letter:nth-child(even) {
        transition-delay: 40ms;
    }

    @keyframes wf-brand-in {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes wf-brand-wo-swing {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-6px); }
        75% { transform: translateX(5px); }
    }

    @media (prefers-reduced-motion: reduce) {
        .wf-brand-letter,
        .wf-brand-wo {
            animation: none !important;
        }

        .wf-brand:hover .wf-brand-letter {
            transform: none;
        }
    }
</style>

<header
    class="wf-nav"
    x-data="{
        mobileOpen: false,
        fiturOpen: false,
        mobileFiturOpen: <?php echo e($isFitur ? 'true' : 'false'); ?>,
        activeNav: <?php echo \Illuminate\Support\Js::from($initialNav)->toHtml() ?>,
        isHome: <?php echo \Illuminate\Support\Js::from($isHome)->toHtml() ?>,
    }"
    @keydown.escape.window="fiturOpen = false"
>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="<?php echo e(wofins_route('home')); ?>" class="wf-brand shrink-0" aria-label="WOFINS">
                <span class="wf-brand-mark">
                    <span class="wf-brand-word" aria-hidden="true">
                        <span class="wf-brand-wo">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = str_split('WO'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $letter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <span class="wf-brand-letter is-wo" style="--i: <?php echo e($i); ?>"><?php echo e($letter); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = str_split('FINS'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $letter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <span class="wf-brand-letter" style="--i: <?php echo e($i + 2); ?>"><?php echo e($letter); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </span>
                </span>
            </a>

            <nav class="hidden lg:flex items-center gap-1 text-sm font-semibold" aria-label="Menu utama">
                <div
                    class="relative"
                    @mouseenter="fiturOpen = true"
                    @mouseleave="fiturOpen = false"
                >
                    <button
                        type="button"
                        class="wf-nav-link <?php echo e($isFitur ? 'is-active' : ''); ?>"
                        :class="{ 'is-active': activeNav === 'fitur' }"
                        @click="fiturOpen = !fiturOpen"
                        :aria-expanded="fiturOpen.toString()"
                        <?php if($isFitur): ?> aria-current="page" <?php endif; ?>
                    >
                        Fitur
                        <i class="fa-solid fa-chevron-down text-[0.65rem] transition-transform" :class="fiturOpen && 'rotate-180'"></i>
                    </button>

                    <div
                        x-show="fiturOpen"
                        x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="wf-nav-dropdown"
                    >
                        <a href="<?php echo e(route('fitur')); ?>" class="<?php echo e(request()->routeIs('fitur') && ! request()->routeIs('fitur.show') ? 'is-active' : ''); ?>">
                            <span class="dd-icon bg-[rgba(201,162,39,0.14)] text-[var(--wf-gold)]">
                                <i class="fa-solid fa-layer-group"></i>
                            </span>
                            <span>
                                <span class="block">Semua Fitur</span>
                                <span class="block text-[11px] font-medium text-[var(--wf-muted)]">Ringkasan modul WOFINS</span>
                            </span>
                        </a>
                        <div class="my-1 border-t border-[var(--wf-line)]"></div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $fiturMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <a
                                href="<?php echo e(route('fitur.show', $item['slug'])); ?>"
                                class="<?php echo e($currentFiturSlug === $item['slug'] ? 'is-active' : ''); ?>"
                            >
                                <span class="dd-icon <?php echo e($item['color']); ?>">
                                    <i class="fa-solid <?php echo e($item['icon']); ?>"></i>
                                </span>
                                <span><?php echo e($item['title']); ?></span>
                            </a>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>

                <a
                    href="<?php echo e(route('harga')); ?>"
                    class="wf-nav-link <?php echo e($isHarga ? 'is-active' : ''); ?>"
                    :class="{ 'is-active': activeNav === 'harga' }"
                    <?php if($isHarga): ?> aria-current="page" <?php endif; ?>
                >Harga</a>
                <a
                    href="<?php echo e(route('docs.index')); ?>"
                    class="wf-nav-link <?php echo e($isDocs ? 'is-active' : ''); ?>"
                    :class="{ 'is-active': activeNav === 'docs' }"
                    <?php if($isDocs): ?> aria-current="page" <?php endif; ?>
                >Docs</a>
                <a
                    href="<?php echo e(route('kontak')); ?>"
                    class="wf-nav-link <?php echo e($isKontak ? 'is-active' : ''); ?>"
                    :class="{ 'is-active': activeNav === 'kontak' }"
                    <?php if($isKontak): ?> aria-current="page" <?php endif; ?>
                >Kontak</a>
            </nav>

            <div class="hidden lg:flex items-center gap-3">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(Auth::user()->hasAssignedRole() ? wofins_route('profile') : wofins_route('account.pending')); ?>" class="wf-btn-ghost px-5 py-2.5 text-sm">Dashboard</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->canAccessAdmin()): ?>
                        <a href="<?php echo e(wofins_route('dashboard')); ?>" class="wf-btn-navy px-5 py-2.5 text-sm">Admin</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo e(wofins_route('front.login')); ?>" class="wf-btn-ghost px-5 py-2.5 text-sm">Masuk</a>
                    <a href="<?php echo e(wofins_route('kontak')); ?>" class="wf-btn-navy px-5 py-2.5 text-sm">Jadwalkan Demo Gratis</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <button type="button" class="lg:hidden p-2 text-[var(--wf-navy)]" @click="mobileOpen = !mobileOpen" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <div x-show="mobileOpen" x-cloak class="lg:hidden pb-4 border-t border-[var(--wf-line)] pt-3 space-y-1">
            <button
                type="button"
                class="wf-nav-link-mobile w-full flex items-center justify-between <?php echo e($isFitur ? 'is-active' : ''); ?>"
                :class="{ 'is-active': activeNav === 'fitur' }"
                @click="mobileFiturOpen = !mobileFiturOpen"
            >
                <span>Fitur</span>
                <i class="fa-solid fa-chevron-down text-[0.65rem] transition-transform" :class="mobileFiturOpen && 'rotate-180'"></i>
            </button>

            <div x-show="mobileFiturOpen" x-cloak class="wf-nav-mobile-sub space-y-0.5 pb-1">
                <a href="<?php echo e(route('fitur')); ?>" class="<?php echo e(request()->routeIs('fitur') && ! request()->routeIs('fitur.show') ? 'is-active' : ''); ?>">
                    Semua Fitur
                </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $fiturMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a
                        href="<?php echo e(route('fitur.show', $item['slug'])); ?>"
                        class="<?php echo e($currentFiturSlug === $item['slug'] ? 'is-active' : ''); ?>"
                    >
                        <i class="fa-solid <?php echo e($item['icon']); ?> text-[var(--wf-gold)] text-xs w-4"></i>
                        <?php echo e($item['title']); ?>

                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>

            <a href="<?php echo e(route('harga')); ?>" class="wf-nav-link-mobile <?php echo e($isHarga ? 'is-active' : ''); ?>" :class="{ 'is-active': activeNav === 'harga' }">Harga</a>
            <a href="<?php echo e(route('docs.index')); ?>" class="wf-nav-link-mobile <?php echo e($isDocs ? 'is-active' : ''); ?>" :class="{ 'is-active': activeNav === 'docs' }">Docs</a>
            <a href="<?php echo e(route('kontak')); ?>" class="wf-nav-link-mobile <?php echo e($isKontak ? 'is-active' : ''); ?>" :class="{ 'is-active': activeNav === 'kontak' }">Kontak</a>
            <div class="flex flex-col gap-2 pt-2">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                    <a href="<?php echo e(Auth::user()->hasAssignedRole() ? wofins_route('profile') : wofins_route('account.pending')); ?>" class="wf-btn-ghost px-4 py-2.5 text-center text-sm">Dashboard</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(Auth::user()->canAccessAdmin()): ?>
                        <a href="<?php echo e(wofins_route('dashboard')); ?>" class="wf-btn-navy px-4 py-2.5 text-center text-sm">Admin</a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo e(wofins_route('front.login')); ?>" class="wf-btn-ghost px-4 py-2.5 text-center text-sm">Masuk</a>
                    <a href="<?php echo e(wofins_route('kontak')); ?>" class="wf-btn-navy px-4 py-2.5 text-center text-sm">Jadwalkan Demo Gratis</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>
    </div>
</header>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/front/partials/wf-nav.blade.php ENDPATH**/ ?>