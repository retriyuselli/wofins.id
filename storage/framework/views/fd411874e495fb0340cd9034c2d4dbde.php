<script>
    const t = <?php echo e((int) config('session.lifetime') * 60 * 1000); ?>;
    let x;

    function s() {
        clearTimeout(x);
        x = setTimeout(() => {
            window.location.href = "<?php echo e(url('/')); ?>"
        }, t)
    } ['mousemove', 'keydown', 'scroll', 'click', 'touchstart', 'touchmove'].forEach(e => addEventListener(e, s, {
        passive: true
    }));
    s();
</script>
<?php /**PATH /Applications/XAMPP/xamppfiles/htdocs/application/wofins/resources/views/filament/inactivity-redirect.blade.php ENDPATH**/ ?>