<?php
/**
 * FAQ — Soft desk Pass OR Fail. Template hierarchy: page-faq.php for /faq/
 */
if (!defined('ABSPATH')) {
    exit;
}

$stories = kcj_page_url('stories');
$policy = kcj_page_url('editorial-policy');
$shipping = kcj_page_url('shipping-returns');
$contact = kcj_page_url('contact');

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-policy kcj-faq">
    <div class="kcj-policy-inner">
        <header class="kcj-policy-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · The desk', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-policy-lede">
                <?php esc_html_e('How a Soft short goes from a hold to a pass. AI Review My Draft & Publish uses this bar before a story can publish itself.', 'kcjdrama'); ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($stories); ?>#kcj-desk"><?php esc_html_e('Write for the desk', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a>
            </div>
        </header>

        <?php
        if (function_exists('kcj_render_desk_faq')) {
            kcj_render_desk_faq('h2');
        }
        ?>

        <section class="kcj-policy-more">
            <article>
                <h2><?php esc_html_e('Orders', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Print, tracking, and returns are not this page. They live on Shipping & returns — merch is made after you order.', 'kcjdrama'); ?></p>
            </article>
        </section>

        <section class="kcj-policy-footer-links" aria-label="<?php esc_attr_e('Related', 'kcjdrama'); ?>">
            <a href="<?php echo esc_url($stories); ?>#kcj-desk"><?php esc_html_e('Stories desk', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($contact); ?>"><?php esc_html_e('Contact', 'kcjdrama'); ?></a>
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a>
        </section>
    </div>
</main>
<?php
get_footer();
