<?php $__env->startSection('profile-page-title', 'Dashboard Profil'); ?>
<?php $__env->startSection('profile-page-subtitle', 'Kelola informasi akun dan data HR Anda'); ?>

<?php $__env->startSection('profile-content'); ?>
<div class="wf-profile-card">
    <?php echo $__env->make('profile.sections.header', ['user' => $user], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php echo $__env->make('profile.sections.personal-info', ['user' => $user], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php echo $__env->make('profile.sections.employment-info', ['user' => $user], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
</div>

<?php echo $__env->make('profile.sections.subscription-quota', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('profile.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/profile/show.blade.php ENDPATH**/ ?>