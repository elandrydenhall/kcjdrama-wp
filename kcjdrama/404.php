<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-404">
    <article class="kcj-page-inner">
        <p class="kcj-page-kicker"><?php esc_html_e('Soft World · Lost', 'kcjdrama'); ?></p>
        <h1><?php esc_html_e('That page did not survive contact', 'kcjdrama'); ?></h1>
        <p class="kcj-brand-lede"><?php esc_html_e('The URL does not match anything on this site. Soft desk, Mirror roast, and the shop are still here.', 'kcjdrama'); ?></p>
        <div class="kcj-brand-actions">
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to the split', 'kcjdrama'); ?></a>
            <a class="kcj-brand-cross" href="<?php echo esc_url(kcj_page_url('shop')); ?>"><?php esc_html_e('Shop', 'kcjdrama'); ?></a>
            <a class="kcj-brand-cross" href="<?php echo esc_url(kcj_page_url('soft')); ?>"><?php esc_html_e('Soft', 'kcjdrama'); ?></a>
            <a class="kcj-brand-cross" href="<?php echo esc_url(kcj_page_url('mirror')); ?>"><?php esc_html_e('Mirror', 'kcjdrama'); ?></a>
        </div>
        <?php get_search_form(); ?>
    </article>
</main>
<?php
get_footer();
