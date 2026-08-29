<?php
/**
 * Support — order help + human contact. Template: page-support.php
 * Structure mirrors SatireSmart /support (fulfillment, defects, mailto, form).
 */
if (!defined('ABSPATH')) {
    exit;
}

$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
$shipping = kcj_page_url('shipping-returns');
$faq = kcj_page_url('faq');
$support_email = 'support@kcjdrama.com';
$notice = isset($_GET['contact']) ? sanitize_key(wp_unslash($_GET['contact'])) : '';

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-policy kcj-support">
    <div class="kcj-policy-inner">
        <header class="kcj-policy-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Soft · Clear answers', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-policy-lede">
                <?php esc_html_e('Direct answers on print-on-demand orders, production time, and defect reprints. Keep it simple so you can get back to the drama.', 'kcjdrama'); ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="mailto:<?php echo esc_attr($support_email); ?>"><?php esc_html_e('Email a human', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
            </div>
        </header>

        <?php if (function_exists('kcj_the_epigraph')) { kcj_the_epigraph('support-keep-in-heart'); } ?>

        <section class="kcj-policy-more" id="fulfillment-returns" aria-labelledby="kcj-support-fr">
            <article>
                <h2 id="kcj-support-fr"><?php esc_html_e('Fulfillment & returns', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Soft and Mirror merch is printed after you order. Below is how production, tracking, and defects work — no warehouse shelf, no mystery second policy.', 'kcjdrama'); ?></p>
            </article>
        </section>

        <section class="kcj-policy-grid" aria-label="<?php esc_attr_e('Order help', 'kcjdrama'); ?>">
            <article class="kcj-policy-card" id="fulfillment">
                <h2><?php esc_html_e('Fulfillment & tracking', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Every order is printed on demand. Standard production usually takes three to five business days.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('When the printer hands the parcel to a carrier, tracking goes to the email on the order.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Carrier delivery windows are theirs. If tracking never appears or a package stalls, write us with your order number.', 'kcjdrama'); ?></li>
                </ul>
            </article>

            <article class="kcj-policy-card" id="defects">
                <h2><?php esc_html_e('Defects & quality', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Print errors or manufacturing defects get a reprint. Send a clear photo and your order number within thirty days of delivery.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Wrong item or size because we packed it wrong counts as our mistake — same window, same fix.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('A correctly printed piece you no longer want is usually not returnable. Check size notes before you pay.', 'kcjdrama'); ?></li>
                </ul>
            </article>
        </section>

        <section class="kcj-policy-more" id="human">
            <article>
                <h2><?php esc_html_e('No-nonsense support', 'kcjdrama'); ?></h2>
                <p>
                    <?php esc_html_e('Skip the bots. Email order details to', 'kcjdrama'); ?>
                    <a href="mailto:<?php echo esc_attr($support_email); ?>"><?php echo esc_html($support_email); ?></a>
                    <?php esc_html_e('and a human will handle it — or use the form below.', 'kcjdrama'); ?>
                </p>
            </article>
        </section>

        <section class="kcj-support-form-wrap" id="write" aria-labelledby="kcj-support-form-title">
            <h2 id="kcj-support-form-title"><?php esc_html_e('Write us', 'kcjdrama'); ?></h2>

            <?php if ($notice === 'sent') : ?>
                <p class="kcj-support-notice kcj-support-notice--ok" role="status">
                    <?php esc_html_e('Message sent. A human will reply to the address you used.', 'kcjdrama'); ?>
                </p>
            <?php elseif ($notice === 'error') : ?>
                <p class="kcj-support-notice kcj-support-notice--err" role="alert">
                    <?php
                    printf(
                        /* translators: %s: support email */
                        esc_html__('Could not send. Check the fields or email %s directly.', 'kcjdrama'),
                        esc_html($support_email)
                    );
                    ?>
                </p>
            <?php elseif ($notice === 'rate') : ?>
                <p class="kcj-support-notice kcj-support-notice--err" role="alert">
                    <?php esc_html_e('Too many messages from this connection. Wait a minute and try again.', 'kcjdrama'); ?>
                </p>
            <?php endif; ?>

            <form class="kcj-desk-form kcj-support-form" method="post" action="">
                <?php wp_nonce_field('kcj_support', 'kcj_support_nonce'); ?>
                <input type="hidden" name="kcj_support" value="1">
                <input type="hidden" name="kcj_ts" value="<?php echo esc_attr((string) time()); ?>">
                <p class="kcj-support-hp" aria-hidden="true">
                    <label for="kcj_hp"><?php esc_html_e('Leave blank', 'kcjdrama'); ?></label>
                    <input id="kcj_hp" name="kcj_hp" type="text" tabindex="-1" autocomplete="off">
                </p>
                <label for="contact_name">
                    <span><?php esc_html_e('Your name', 'kcjdrama'); ?></span>
                    <input id="contact_name" name="contact_name" type="text" required autocomplete="name">
                </label>
                <label for="contact_email">
                    <span><?php esc_html_e('Your email', 'kcjdrama'); ?></span>
                    <input id="contact_email" name="contact_email" type="email" required autocomplete="email">
                </label>
                <label for="contact_message">
                    <span><?php esc_html_e('Your message', 'kcjdrama'); ?></span>
                    <textarea id="contact_message" name="contact_message" rows="6" required></textarea>
                </label>
                <button class="kcj-btn kcj-btn--soft" type="submit"><?php esc_html_e('Send message', 'kcjdrama'); ?></button>
            </form>
        </section>

        <section class="kcj-policy-footer-links" aria-label="<?php esc_attr_e('Related', 'kcjdrama'); ?>">
            <a href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($faq); ?>"><?php esc_html_e('Desk FAQ', 'kcjdrama'); ?></a>
            <a href="mailto:<?php echo esc_attr($support_email); ?>"><?php echo esc_html($support_email); ?></a>
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Open the catalog', 'kcjdrama'); ?></a>
        </section>
    </div>
</main>
<?php
get_footer();
