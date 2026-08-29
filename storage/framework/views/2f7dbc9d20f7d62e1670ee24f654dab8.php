<!-- Personal Information Section -->
<?php
    $user = $user ?? Auth::user();
?>
<div>
    <h3 class="text-lg font-bold text-[var(--wf-navy)] mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-[var(--wf-gold)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
        </svg>
        Informasi Pribadi
    </h3>
    <div class="space-y-4">
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Nama Lengkap</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->name); ?></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Alamat Email</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->email); ?></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Nomor Telepon</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->phone_number ?? 'Tidak ditentukan'); ?></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Tanggal Lahir</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->date_of_birth ? $user->date_of_birth->format('d F Y') : 'Tidak ditentukan'); ?></p>
        </div>
        <div>
            <label class="text-sm font-medium text-[var(--wf-muted)]">Jenis Kelamin</label>
            <p class="text-[var(--wf-ink)] text-sm font-medium"><?php echo e($user->gender ? ucfirst($user->gender) : 'Tidak ditentukan'); ?></p>
        </div>
    </div>
</div>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/profile/sections/personal-info.blade.php ENDPATH**/ ?>