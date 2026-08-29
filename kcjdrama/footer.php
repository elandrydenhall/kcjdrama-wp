<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<footer class="kcj-site-footer">
    <div class="kcj-site-footer-inner">
        <p class="kcj-site-footer-brand">kcjdrama — Soft sincerity, Mirror chaos. Original words only.</p>
        <nav class="kcj-site-footer-nav" aria-label="Footer">
            <div class="kcj-footer-group">
                <p class="kcj-footer-label"><?php esc_html_e('Brand', 'kcjdrama'); ?></p>
                <a href="<?php echo esc_url(kcj_page_url('start-here')); ?>">Start here</a>
                <a href="<?php echo esc_url(kcj_page_url('soft')); ?>">Soft</a>
                <a href="<?php echo esc_url(kcj_page_url('mirror')); ?>">Mirror</a>
                <a href="<?php echo esc_url(kcj_page_url('about')); ?>">About</a>
            </div>
            <div class="kcj-footer-group">
                <p class="kcj-footer-label"><?php esc_html_e('Read', 'kcjdrama'); ?></p>
                <a href="<?php echo esc_url(kcj_page_url('tropes')); ?>">Tropes</a>
                <a href="<?php echo esc_url(kcj_page_url('essays')); ?>">Essays</a>
                <a href="<?php echo esc_url(kcj_page_url('stories')); ?>">Stories</a>
                <a href="<?php echo esc_url(kcj_page_url('syndromes')); ?>">Syndromes</a>
                <a href="<?php echo esc_url(kcj_page_url('victim-log')); ?>">Victim Log</a>
                <a href="<?php echo esc_url(kcj_field_notes_url()); ?>">Field notes</a>
                <a href="<?php echo esc_url(kcj_page_url('countries/korea')); ?>">Korea</a>
                <a href="<?php echo esc_url(kcj_page_url('countries/china')); ?>">China</a>
                <a href="<?php echo esc_url(kcj_page_url('countries/japan')); ?>">Japan</a>
            </div>
            <div class="kcj-footer-group">
                <p class="kcj-footer-label"><?php esc_html_e('Shop', 'kcjdrama'); ?></p>
                <a href="<?php echo esc_url(kcj_page_url('shop')); ?>">Shop</a>
            </div>
            <div class="kcj-footer-group">
                <p class="kcj-footer-label"><?php esc_html_e('Help', 'kcjdrama'); ?></p>
                <a href="<?php echo esc_url(kcj_page_url('support')); ?>">Support</a>
                <a href="<?php echo esc_url(kcj_page_url('shipping-returns')); ?>">Shipping &amp; returns</a>
                <a href="<?php echo esc_url(kcj_page_url('faq')); ?>">FAQ</a>
                <a href="<?php echo esc_url(kcj_page_url('size-guide')); ?>">Size guide</a>
            </div>
            <div class="kcj-footer-group">
                <p class="kcj-footer-label"><?php esc_html_e('Legal', 'kcjdrama'); ?></p>
                <a href="<?php echo esc_url(kcj_page_url('privacy-policy')); ?>">Privacy</a>
                <a href="<?php echo esc_url(kcj_page_url('terms-of-service')); ?>">Terms</a>
                <a href="<?php echo esc_url(kcj_page_url('editorial-policy')); ?>">Editorial policy</a>
            </div>
            <div class="kcj-footer-group kcj-footer-group--account">
                <p class="kcj-footer-label"><?php esc_html_e('Account', 'kcjdrama'); ?></p>
                <?php if (is_user_logged_in()) : ?>
                    <a href="<?php echo esc_url(kcj_sign_out_url(home_url('/'))); ?>"><?php esc_html_e('Sign out', 'kcjdrama'); ?></a>
                <?php else : ?>
                    <a href="<?php echo esc_url(kcj_sign_in_url()); ?>"><?php esc_html_e('Sign in', 'kcjdrama'); ?></a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</footer>
<?php
wp_footer();
?>
</body>
</html>
