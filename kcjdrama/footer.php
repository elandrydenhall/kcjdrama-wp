<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="kcj-site-footer">
    <div class="kcj-site-footer-inner">
        <p class="kcj-site-footer-brand">kcjdrama — Soft sincerity, Mirror chaos. Original words only.</p>
        <nav class="kcj-site-footer-nav" aria-label="Footer">
            <a href="<?php echo esc_url(kcj_page_url('start-here')); ?>">Start here</a>
            <a href="<?php echo esc_url(kcj_page_url('soft')); ?>">Soft</a>
            <a href="<?php echo esc_url(kcj_page_url('mirror')); ?>">Mirror</a>
            <a href="<?php echo esc_url(kcj_page_url('shop')); ?>">Shop</a>
            <a href="<?php echo esc_url(kcj_page_url('tropes')); ?>">Tropes</a>
            <a href="<?php echo esc_url(kcj_page_url('syndromes')); ?>">Syndromes</a>
            <a href="<?php echo esc_url(kcj_page_url('countries/korea')); ?>">Korea</a>
            <a href="<?php echo esc_url(kcj_page_url('countries/china')); ?>">China</a>
            <a href="<?php echo esc_url(kcj_page_url('countries/japan')); ?>">Japan</a>
            <a href="<?php echo esc_url(kcj_page_url('editorial-policy')); ?>">Policy</a>
            <a href="<?php echo esc_url(kcj_page_url('privacy-policy')); ?>">Privacy</a>
        </nav>
    </div>
</footer>
<?php
wp_footer();
?>
</body>
</html>
