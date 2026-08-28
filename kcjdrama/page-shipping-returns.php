<?php
/**
 * Shipping & returns — purchase trust. Template: page-shipping-returns.php
 */
if (!defined('ABSPATH')) {
    exit;
}

$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
$about = kcj_page_url('about');
$policy = kcj_page_url('editorial-policy');
$contact = kcj_page_url('contact');

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-policy kcj-ship">
    <div class="kcj-policy-inner">
        <header class="kcj-policy-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Trust · Before you pay', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-policy-lede">
                <?php esc_html_e('Soft and Mirror merch is printed after you order — not pulled off a warehouse shelf. Here is what that means, in plain language, so you can check out without guessing.', 'kcjdrama'); ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($contact); ?>"><?php esc_html_e('Talk to a human', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($about); ?>"><?php esc_html_e('About', 'kcjdrama'); ?></a>
            </div>
        </header>

        <section class="kcj-policy-grid" aria-label="<?php esc_attr_e('Fulfillment', 'kcjdrama'); ?>">
            <article class="kcj-policy-card" id="fulfillment">
                <h2><?php esc_html_e('After you order', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Payment clears, then the printer starts. Nothing ships the same hour you click buy.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Production usually takes a few business days. Carrier time is extra and depends on where you live.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('The rate and method you pay for are the ones shown at checkout for your address — we do not hide a second price on this page.', 'kcjdrama'); ?></li>
                </ul>
            </article>

            <article class="kcj-policy-card" id="tracking">
                <h2><?php esc_html_e('Tracking', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('When the printer hands the parcel to a carrier, a tracking note goes to the email on the order.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('If days pass with no tracking, write us with your order number. We will look it up with you.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Once it is with the carrier, delivery windows belong to them. We will still help if a package stalls or vanishes.', 'kcjdrama'); ?></li>
                </ul>
            </article>
        </section>

        <section class="kcj-policy-more" id="returns">
            <article>
                <h2><?php esc_html_e('If we got it wrong', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Print error, damage in transit, or the wrong item/size because we packed it wrong: that is on us. Email a photo and your order number within 30 days of delivery. We will reprint or make it right — no runaround.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('If you changed your mind', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Each piece is made for your order. We generally cannot take back a correctly printed garment because you wanted a different size or color. Check the size notes on the product before you pay. If you received a size we did not list on the order, that counts as our mistake.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('A human, not a maze', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Use the contact page. Include the order number and, for defects, a photo in good light. We read the mail. We do not outsource the apology.', 'kcjdrama'); ?></p>
            </article>
        </section>

        <section class="kcj-policy-footer-links" aria-label="<?php esc_attr_e('Related', 'kcjdrama'); ?>">
            <a href="<?php echo esc_url($contact); ?>"><?php esc_html_e('Contact', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($about); ?>"><?php esc_html_e('About', 'kcjdrama'); ?></a>
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Open the catalog', 'kcjdrama'); ?></a>
        </section>
    </div>
</main>
<?php
get_footer();
