<?php
/**
 * WooCommerce wrapper — Satire-like page shell with Soft/Mirror chrome.
 */
if (!defined('ABSPATH')) {
    exit;
}

$tone = 'soft';
if (function_exists('is_product') && is_product() && function_exists('kcj_product_rail')) {
    $tone = kcj_product_rail(get_queried_object_id()) === 'mirror' ? 'mirror' : 'soft';
} elseif (function_exists('is_cart') && (is_cart() || is_checkout() || is_account_page())) {
    $tone = 'soft';
}

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--<?php echo esc_attr($tone); ?> kcj-woo">
    <div class="kcj-page-shell">
        <?php
        if (function_exists('is_cart') && is_cart()) {
            echo '<p class="kcj-page-kicker">' . esc_html__('Soft World · Bag', 'kcjdrama') . '</p>';
        } elseif (function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page())) {
            echo '<p class="kcj-page-kicker">' . esc_html__('Soft World · Checkout', 'kcjdrama') . '</p>';
        } elseif (function_exists('is_account_page') && is_account_page()) {
            echo '<p class="kcj-page-kicker">' . esc_html__('Soft World · Account', 'kcjdrama') . '</p>';
        }
        woocommerce_content();
        ?>
    </div>
</main>
<?php
get_footer();
