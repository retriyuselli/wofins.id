<?php $__env->startSection('title', 'Kontak — WOFINS'); ?>

<?php $__env->startPush('styles'); ?>
<style>
        :root {
            --wf-navy: #0b1f3a;
            --wf-navy-deep: #071526;
            --wf-gold: #c9a227;
            --wf-gold-soft: #e8d48b;
            --wf-cream: #f7f4ee;
            --wf-ink: #1a2332;
            --wf-muted: #5c6675;
            --wf-line: #e6e2d9;
        }

        .wf-page {
            font-family: 'Poppins', system-ui, sans-serif;
            color: var(--wf-ink);
            background: #fff;
        }

        .wf-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--wf-line);
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

        .wf-btn-ghost {
            border: 1.5px solid var(--wf-navy);
            color: var(--wf-navy);
            border-radius: 999px;
            font-weight: 700;
            background: #fff;
        }

        .wf-btn-gold {
            background: var(--wf-gold);
            color: var(--wf-navy-deep);
            border-radius: 999px;
            font-weight: 800;
        }

        .wf-contact-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--wf-line);
            border-radius: 1.25rem;
            padding: 1.35rem;
            height: 100%;
        }

        .wf-contact-card .ornament {
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
            background: rgba(201, 162, 39, 0.12);
        }

        .wf-input {
            width: 100%;
            border: 1px solid var(--wf-line);
            border-radius: 0.85rem;
            padding: 0.8rem 1rem;
            font-size: 0.9rem;
            line-height: 1.4;
            min-height: 3rem;
            box-sizing: border-box;
            background: #fff;
            color: var(--wf-ink);
            outline: none;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        select.wf-input {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%235c6675' d='M2.1 4.2L6 8.1l3.9-3.9 1.1 1.1L6 10.3 1 5.3z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }

        textarea.wf-input {
            min-height: auto;
            resize: vertical;
        }

        [contenteditable].wf-input {
            outline: none;
            min-height: 8.5rem;
        }

        [contenteditable].wf-input strong {
            font-weight: 800;
            color: var(--wf-navy);
        }

        .wf-input:focus {
            border-color: rgba(201, 162, 39, 0.7);
            box-shadow: 0 0 0 3px rgba(201, 162, 39, 0.15);
        }

        .wf-modal-backdrop {
            background: rgba(7, 21, 38, 0.55);
            backdrop-filter: blur(4px);
        }

        [x-cloak] { display: none !important; }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<?php
    use App\Enums\ProspectAppStatus;

    $authUser = Auth::user();
    $prospect = null;
    if ($authUser) {
        $prospect = \App\Models\ProspectApp::query()
            ->where(function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id)
                    ->orWhere('email', $authUser->email);
            })
            ->latest('submitted_at')
            ->latest('id')
            ->first();
    }

    $accountPendingApproval = $authUser && ! $authUser->hasAssignedRole();
    $registrationInReview = $prospect?->status === ProspectAppStatus::Pending;

    $prefillName = $authUser?->name ?: $prospect?->full_name;
    $prefillEmail = $authUser?->email ?: $prospect?->email;
    $prefillPhone = $authUser?->phone_number ?: $prospect?->phone;
    $prefillCompany = $prospect?->company_name;

    $paket = request('paket');
    $billing = request('billing');
    $billingKey = is_string($billing) ? $billing : null;
    $billingLabel = $billingKey && in_array($billingKey, \App\Support\PricingPlans::billingKeys(), true)
        ? 'pembayaran '.\App\Support\PricingPlans::billingLabel($billingKey)
        : null;
    $planMeta = \App\Support\PricingPlans::find($paket);
    $paketInfo = $planMeta
        ? [
            'label' => \App\Support\PricingPlans::shortLabel($paket),
            'price' => \App\Support\PricingPlans::priceDisplay($paket),
        ]
        : null;
    $paketLabel = $paketInfo['label'] ?? null;
    $paketPrice = $paketInfo['price'] ?? null;
    $paketFull = $paketInfo
        ? "{$paketInfo['label']} ({$paketInfo['price']})"
        : null;
    if ($paketFull && $billingLabel) {
        $paketFull .= " — {$billingLabel}";
    }
    $defaultMessage = $paketFull
        ? "Saya tertarik dengan {$paketFull} dan ingin konsultasi lebih lanjut."
        : '';
?>

    <div class="wf-page" x-data="{
        successOpen: <?php echo e(session('success') ? 'true' : 'false'); ?>,
        loginOpen: <?php echo e(Auth::check() ? 'false' : 'true'); ?>

    }">
        <?php echo $__env->make('front.partials.wf-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <section class="pt-12 pb-8 bg-gradient-to-b from-white to-[var(--wf-cream)]">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-[var(--wf-gold)] mb-3">Kontak</p>
                <h1 class="text-3xl sm:text-4xl lg:text-[2.75rem] font-bold text-[var(--wf-navy)] leading-tight">
                    Mari Diskusikan Kebutuhan Wedding Organizer Anda
                </h1>
                <p class="mt-4 text-[var(--wf-muted)] max-w-2xl mx-auto">
                    Jadwalkan demo gratis atau konsultasikan paket yang paling sesuai. Tim WOFINS siap membantu.
                </p>
            </div>
        </section>

        <section class="py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-12">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
                        ['fa-brands fa-whatsapp', 'WhatsApp', '+62 813-7318-3794', 'https://wa.me/6281373183794'],
                        ['fa-solid fa-envelope', 'Email', 'support@wofins.id', 'mailto:support@wofins.id'],
                        ['fa-solid fa-globe', 'Website', 'wofins.id', 'https://wofins.id'],
                        ['fa-solid fa-location-dot', 'Lokasi', 'Palembang, Indonesia', null],
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="wf-contact-card">
                            <span class="ornament w-16 h-16 -right-4 -top-4" aria-hidden="true"></span>
                            <div class="relative z-10">
                                <div class="w-10 h-10 rounded-xl bg-[rgba(201,162,39,0.12)] text-[var(--wf-gold)] inline-flex items-center justify-center mb-3">
                                    <i class="<?php echo e($item[0]); ?>"></i>
                                </div>
                                <p class="text-xs font-bold uppercase tracking-wider text-[var(--wf-muted)]"><?php echo e($item[1]); ?></p>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item[3]): ?>
                                    <a href="<?php echo e($item[3]); ?>" class="mt-1 block font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]"><?php echo e($item[2]); ?></a>
                                <?php else: ?>
                                    <p class="mt-1 font-bold text-[var(--wf-navy)]"><?php echo e($item[2]); ?></p>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>

                <div class="grid lg:grid-cols-5 gap-8 items-start">
                    <div class="lg:col-span-3 rounded-2xl border border-[var(--wf-line)] bg-white p-6 sm:p-8">
                        <h2 class="text-2xl font-bold text-[var(--wf-navy)]">Kirim pesan / jadwalkan demo</h2>
                        <p class="mt-2 text-sm text-[var(--wf-muted)]">
                            Isi formulir — pesan akan dikirim ke <strong class="text-[var(--wf-navy)]">support@wofins.id</strong>.
                        </p>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($accountPendingApproval): ?>
                            <div class="mt-5 rounded-2xl border border-[var(--wf-gold)]/40 bg-[rgba(201,162,39,0.1)] p-5">
                                <div class="flex gap-3 items-start">
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white border border-[var(--wf-gold)]/30 text-[var(--wf-gold)]">
                                        <i class="fa-solid fa-hourglass-half"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-[var(--wf-navy)]">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($registrationInReview): ?>
                                                Akun Anda sedang dalam proses persetujuan
                                            <?php else: ?>
                                                Akun belum diaktifkan
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </h3>
                                        <p class="mt-1.5 text-sm text-[var(--wf-muted)] leading-relaxed">
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($registrationInReview): ?>
                                                Pendaftaran sudah kami terima dan menunggu tinjauan admin.
                                                Dashboard belum bisa diakses sampai akun disetujui.
                                                Anda tetap dapat mengirim pesan di bawah jika butuh bantuan.
                                            <?php else: ?>
                                                Akses Dashboard belum tersedia. Lengkapi formulir pendaftaran terlebih dahulu,
                                                atau kirim pesan di bawah jika butuh bantuan tim support.
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a href="<?php echo e(route('account.pending')); ?>"
                                               class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                                                Lihat status akun
                                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                            </a>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($registrationInReview)): ?>
                                                <span class="text-[var(--wf-line)]">·</span>
                                                <a href="<?php echo e(route('pendaftaran')); ?>"
                                                   class="inline-flex items-center gap-1.5 text-xs font-bold text-[var(--wf-navy)] hover:text-[var(--wf-gold)]">
                                                    Formulir pendaftaran
                                                </a>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paketLabel): ?>
                            <div class="mt-4 inline-flex flex-wrap items-center gap-2 rounded-full bg-[rgba(201,162,39,0.12)] px-3 py-1.5 text-xs font-bold text-[#9a7a12]">
                                <i class="fa-solid fa-tag"></i>
                                <span>Tertarik:</span>
                                <strong class="text-[var(--wf-navy)]"><?php echo e($paketLabel); ?></strong>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paketPrice): ?>
                                    <span class="text-[var(--wf-muted)] font-semibold">· <?php echo e($paketPrice); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->guest()): ?>
                            <div class="mt-6 rounded-2xl border border-[var(--wf-gold)]/35 bg-[rgba(201,162,39,0.08)] p-5 sm:p-6">
                                <div class="flex flex-col sm:flex-row gap-4 items-start">
                                    <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[var(--wf-gold)] text-[var(--wf-navy-deep)]">
                                        <i class="fa-solid fa-user-lock"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="text-base font-bold text-[var(--wf-navy)]">Login diperlukan</h3>
                                        <p class="mt-1.5 text-sm text-[var(--wf-muted)] leading-relaxed">
                                            Untuk mengirim pesan, menjadwalkan demo, atau berkonsultasi tentang paket,
                                            Anda harus login terlebih dahulu. Setelah login, data akun Anda akan terisi otomatis di formulir.
                                        </p>
                                        <div class="mt-4 flex flex-col sm:flex-row gap-2.5">
                                            <a href="<?php echo e(route('kontak.require-login', array_filter(['paket' => $paket]))); ?>"
                                               class="wf-btn-navy inline-flex items-center justify-center px-5 py-2.5 text-sm">
                                                Login terlebih dahulu
                                            </a>
                                            <a href="<?php echo e(wofins_route('front.register')); ?>"
                                               class="wf-btn-ghost inline-flex items-center justify-center px-5 py-2.5 text-sm">
                                                Belum punya akun? Daftar
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <?php echo e(session('error')); ?>

                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                    <ul class="list-disc list-inside text-sm text-red-700 space-y-0.5">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <li><?php echo e($error); ?></li>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                            <form class="mt-6 space-y-4" method="POST" action="<?php echo e(route('kontak.store')); ?>"
                                  @submit="
                                      const editor = $event.target.querySelector('[contenteditable]');
                                      const input = $event.target.querySelector('input[name=message]');
                                      if (editor && input) {
                                          input.value = editor.innerText.replace(/\u00a0/g, ' ').trim();
                                          if (!input.value) {
                                              $event.preventDefault();
                                              editor.focus();
                                          }
                                      }
                                  ">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="paket" value="<?php echo e($paketFull ?? $paketLabel); ?>">
                                <input type="hidden" name="paket_slug" value="<?php echo e($paket); ?>">

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="contact_name" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama lengkap *</label>
                                    <input id="contact_name" name="name" type="text" class="wf-input" required
                                           value="<?php echo e(old('name', $prefillName)); ?>"
                                           placeholder="Nama Anda">
                                </div>
                                <div>
                                    <label for="contact_company" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Nama WO / perusahaan</label>
                                    <input id="contact_company" name="company" type="text" class="wf-input"
                                           value="<?php echo e(old('company', $prefillCompany)); ?>"
                                           placeholder="Nama wedding organizer">
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="contact_email" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Email *</label>
                                    <input id="contact_email" name="email" type="email" class="wf-input <?php echo e($prefillEmail ? 'bg-[var(--wf-cream)] text-[var(--wf-muted)] cursor-not-allowed' : ''); ?>" required
                                           value="<?php echo e(old('email', $prefillEmail)); ?>"
                                           placeholder="email@domain.com"
                                           <?php if($prefillEmail): ?> readonly <?php endif; ?>>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($prefillEmail): ?>
                                        <p class="mt-1 text-xs text-[var(--wf-muted)]">Email mengikuti akun Anda.</p>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <label for="contact_phone" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">WhatsApp *</label>
                                    <input id="contact_phone" name="phone" type="tel" class="wf-input" required
                                           value="<?php echo e(old('phone', $prefillPhone)); ?>"
                                           placeholder="08xxxxxxxxxx">
                                </div>
                            </div>

                            <div>
                                <label for="contact_need" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Kebutuhan *</label>
                                <select id="contact_need" name="need" class="wf-input" required>
                                    <option value="">Pilih kebutuhan</option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
                                        'Demo gratis' => 'Jadwalkan demo gratis',
                                        'Konsultasi paket' => 'Konsultasi paket harga',
                                        'Pertanyaan fitur' => 'Pertanyaan fitur',
                                        'Onboarding' => 'Onboarding / migrasi',
                                        'Lainnya' => 'Lainnya',
                                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <option value="<?php echo e($value); ?>" <?php if(old('need', request('need', $paketLabel ? 'Konsultasi paket' : '')) === $value): echo 'selected'; endif; ?>>
                                            <?php echo e($label); ?>

                                        </option>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </select>
                            </div>

                            <div>
                                <label for="contact_message" class="block text-sm font-semibold text-[var(--wf-navy)] mb-1.5">Pesan *</label>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($paketFull && ! old('message')): ?>
                                    <div
                                        x-data="{
                                            sync() {
                                                const text = this.$refs.editor.innerText.replace(/\u00a0/g, ' ').trim();
                                                this.$refs.input.value = text;
                                            }
                                        }"
                                        x-init="sync()"
                                    >
                                        <div
                                            id="contact_message"
                                            x-ref="editor"
                                            class="wf-input min-h-[8.5rem] whitespace-pre-wrap"
                                            contenteditable="true"
                                            role="textbox"
                                            aria-multiline="true"
                                            aria-label="Pesan"
                                            @input="sync()"
                                            @blur="sync()"
                                            @keydown.enter="$nextTick(() => sync())"
                                        >Saya tertarik dengan <strong><?php echo e($paketFull); ?></strong> dan ingin konsultasi lebih lanjut.</div>
                                        <input type="hidden" name="message" x-ref="input" value="<?php echo e($defaultMessage); ?>">
                                        <p class="mt-1.5 text-xs text-[var(--wf-muted)]">Nama paket dan harga ditampilkan tebal. Anda bisa mengedit pesan sebelum dikirim.</p>
                                    </div>
                                <?php else: ?>
                                    <textarea id="contact_message" name="message" class="wf-input" rows="5" required
                                              placeholder="Ceritakan singkat kebutuhan WO Anda..."><?php echo e(old('message', $defaultMessage)); ?></textarea>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <button type="submit" class="wf-btn-navy w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 text-sm">
                                <i class="fa-solid fa-paper-plane"></i>
                                Kirim ke support@wofins.id
                            </button>
                        </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <div class="rounded-2xl border border-[var(--wf-line)] bg-[var(--wf-cream)] p-6">
                            <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Jam respons</h3>
                            <ul class="space-y-2 text-sm text-[var(--wf-muted)]">
                                <li class="flex gap-2"><i class="fa-solid fa-clock mt-0.5 text-[var(--wf-gold)]"></i> Senin–Jumat · 09:00–17:00 WIB</li>
                                <li class="flex gap-2"><i class="fa-solid fa-bolt mt-0.5 text-[var(--wf-gold)]"></i> Demo biasanya bisa dijadwalkan dalam 1–2 hari kerja</li>
                            </ul>
                        </div>

                        <div class="rounded-2xl border border-[var(--wf-line)] bg-white p-6">
                            <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-3">Yang bisa kami bantu</h3>
                            <ul class="space-y-2.5 text-sm text-[var(--wf-ink)]">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
                                    'Demo walkthrough modul WOFINS',
                                    'Rekomendasi paket sesuai ukuran tim',
                                    'Diskusi onboarding & migrasi data',
                                    'Pertanyaan fitur payroll, keuangan, rekonsiliasi',
                                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $help): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <li class="flex items-start gap-2.5">
                                        <span class="mt-0.5 w-5 h-5 rounded-full bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)] inline-flex items-center justify-center text-[0.6rem] shrink-0">
                                            <i class="fa-solid fa-check"></i>
                                        </span>
                                        <?php echo e($help); ?>

                                    </li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </ul>
                        </div>

                        <div class="rounded-2xl overflow-hidden relative text-white p-6"
                             style="background: linear-gradient(135deg, #071526 0%, #0b1f3a 60%, #14335a 100%);">
                            <span class="absolute w-24 h-24 rounded-full -right-6 -top-8 bg-[rgba(201,162,39,0.16)]" aria-hidden="true"></span>
                            <div class="relative z-10">
                                <h3 class="text-lg font-bold">Lebih cepat via WhatsApp</h3>
                                <p class="mt-2 text-sm text-white/75">Chat langsung dengan tim sales WOFINS.</p>
                                <a href="https://wa.me/6281373183794?text=<?php echo e(urlencode('Halo, saya ingin jadwalkan demo WOFINS.')); ?>"
                                   class="wf-btn-gold inline-flex items-center justify-center gap-2 mt-5 px-5 py-3 text-sm">
                                    <i class="fa-brands fa-whatsapp"></i>
                                    Chat sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <?php echo $__env->make('front.partials.wf-footer', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->guest()): ?>
        <div x-show="loginOpen" x-cloak
             class="fixed inset-0 z-[100] flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="contact-login-title">
            <div class="absolute inset-0 wf-modal-backdrop" @click="loginOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95">
                <div class="px-6 pt-8 pb-2 text-center">
                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)]">
                        <i class="fa-solid fa-user-lock text-2xl"></i>
                    </span>
                    <h3 id="contact-login-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                        Login diperlukan
                    </h3>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                        Untuk mengirim pesan atau konsultasi paket<?php echo e($paketLabel ? ' ('.$paketLabel.')' : ''); ?>,
                        Anda harus login terlebih dahulu.
                    </p>
                </div>
                <div class="px-6 py-5 flex flex-col sm:flex-row gap-2.5 justify-center">
                    <a href="<?php echo e(route('kontak.require-login', array_filter(['paket' => $paket]))); ?>"
                       class="wf-btn-navy inline-flex items-center justify-center px-6 py-3 text-sm">
                        Login terlebih dahulu
                    </a>
                    <button type="button" @click="loginOpen = false"
                            class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <div x-show="successOpen" x-cloak
             class="fixed inset-0 z-[100] flex items-center justify-center p-4"
             role="dialog" aria-modal="true" aria-labelledby="contact-success-title">
            <div class="absolute inset-0 wf-modal-backdrop" @click="successOpen = false"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white border border-[var(--wf-line)] shadow-xl overflow-hidden"
                 @click.stop
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-2 scale-95">
                <div class="px-6 pt-8 pb-2 text-center">
                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-full bg-[rgba(201,162,39,0.15)] text-[var(--wf-gold)]">
                        <i class="fa-solid fa-envelope-circle-check text-2xl"></i>
                    </span>
                    <h3 id="contact-success-title" class="mt-4 text-xl font-bold text-[var(--wf-navy)]">
                        Email berhasil terkirim
                    </h3>
                    <p class="mt-3 text-sm text-[var(--wf-muted)] leading-relaxed">
                        <?php echo e(session('success', 'Pesan Anda sudah kami terima. Tim admin WOFINS akan segera menghubungi Anda.')); ?>

                    </p>
                    <p class="mt-3 text-xs text-[var(--wf-muted)]">
                        Kami juga mengirim konfirmasi ke email Anda.
                    </p>
                </div>
                <div class="px-6 py-5 flex flex-col sm:flex-row gap-2.5 justify-center">
                    <button type="button" @click="successOpen = false"
                            class="wf-btn-navy inline-flex items-center justify-center px-6 py-3 text-sm">
                        Mengerti
                    </button>
                    <a href="<?php echo e(route('home')); ?>"
                       class="wf-btn-ghost inline-flex items-center justify-center px-6 py-3 text-sm">
                        Kembali ke beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/front/kontak.blade.php ENDPATH**/ ?>