<?php
/**
 * Single-product (PDP) layout — SatireSmart structure, KCJ Soft/Mirror colors.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
}, 20);

add_filter('body_class', function ($classes) {
    if (!function_exists('is_product') || !is_product()) {
        return $classes;
    }
    $tone = function_exists('kcj_product_rail') ? kcj_product_rail(get_queried_object_id()) : 'soft';
    $classes[] = 'kcj-pdp';
    $classes[] = 'kcj-pdp-' . ($tone === 'mirror' ? 'mirror' : 'soft');
    $classes[] = 'kcj-page--' . ($tone === 'mirror' ? 'mirror' : 'soft');
    return $classes;
});

add_action('wp_enqueue_scripts', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }
    wp_enqueue_script(
        'kcj-pdp',
        KCJ_URI . '/assets/js/pdp.js',
        ['jquery', 'wc-add-to-cart-variation'],
        KCJ_VERSION,
        true
    );
}, 30);

/**
 * High-converting PDP — big visual → title → price → short why → swatches → ATC → details.
 */
add_action('wp', function () {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40);
    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
    remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);

    add_filter('woocommerce_output_related_products_args', function ($args) {
        $args['posts_per_page'] = 4;
        $args['columns'] = 4;
        return $args;
    });
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
});

add_action('woocommerce_before_single_product', function () {
    $shop = get_page_by_path('shop');
    $shop_url = $shop ? get_permalink($shop) : home_url('/shop/');
    echo '<nav class="pdp-crumb" aria-label="' . esc_attr__('Breadcrumb', 'kcjdrama') . '">';
    echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'kcjdrama') . '</a>';
    echo '<span class="pdp-crumb-sep" aria-hidden="true">/</span>';
    echo '<a href="' . esc_url($shop_url) . '">' . esc_html__('Shop', 'kcjdrama') . '</a>';
    echo '<span class="pdp-crumb-sep" aria-hidden="true">/</span>';
    echo '<span class="pdp-crumb-current">' . esc_html(get_the_title()) . '</span>';
    echo '</nav>';
}, 3);

add_action('woocommerce_after_single_product_summary', function () {
    if (!is_product()) {
        return;
    }
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $desc = $product->get_description();
    $short = $product->get_short_description();
    $body = $desc ? kcj_pdp_trim_details($desc) : '';
    if ($body === '' && $short) {
        $body = wpautop($short);
    }
    if ($body === '') {
        return;
    }
    echo '<section class="pdp-details" aria-labelledby="pdp-details-title">';
    echo '<h2 id="pdp-details-title">' . esc_html__('The details', 'kcjdrama') . '</h2>';
    echo '<div class="pdp-details-body">' . wp_kses_post($body) . '</div>';
    echo '</section>';
}, 5);

add_filter('woocommerce_product_single_add_to_cart_text', function () {
    return __('Add to cart', 'kcjdrama');
});

add_filter('woocommerce_product_related_products_heading', function () {
    return __('More like this', 'kcjdrama');
});

/** First-paint price in the ATC row for variable products. */
add_action('woocommerce_before_single_variation', function () {
    global $product;
    if (!$product instanceof WC_Product) {
        return;
    }
    $html = $product->get_price_html();
    echo '<div class="kcj-pdp-price" data-kcj-pdp-price>';
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Woo price HTML
    echo '</div>';
    echo '<template data-kcj-pdp-price-default>' . $html . '</template>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 4);

add_filter('woocommerce_ajax_variation_threshold', function ($threshold) {
    return max((int) $threshold, 80);
}, 10, 1);

function kcj_is_size_attribute($raw_attr) {
    $raw_attr = (string) $raw_attr;
    $slug = function_exists('wc_attribute_taxonomy_slug')
        ? wc_attribute_taxonomy_slug($raw_attr)
        : strtolower(str_replace('pa_', '', $raw_attr));
    return ($slug === 'size' || strtolower($raw_attr) === 'pa_size' || strtolower($raw_attr) === 'size');
}

function kcj_sort_size_slugs(array $options) {
    $order = ['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl'];
    $rank = array_flip($order);
    usort($options, function ($a, $b) use ($rank) {
        $sa = strtolower((string) $a);
        $sb = strtolower((string) $b);
        $ra = $rank[$sa] ?? 100;
        $rb = $rank[$sb] ?? 100;
        if ($ra === $rb) {
            return strnatcasecmp($sa, $sb);
        }
        return $ra <=> $rb;
    });
    return $options;
}

add_filter('woocommerce_dropdown_variation_attribute_options_args', function ($args) {
    if (empty($args['attribute']) || !kcj_is_size_attribute((string) $args['attribute'])) {
        return $args;
    }
    $options = $args['options'] ?? null;
    if (empty($options) && !empty($args['product']) && is_object($args['product'])) {
        $attributes = $args['product']->get_variation_attributes();
        $raw = (string) $args['attribute'];
        if (!empty($attributes[$raw])) {
            $options = $attributes[$raw];
        } elseif (!empty($attributes['pa_size'])) {
            $options = $attributes['pa_size'];
        }
    }
    if (empty($options) || !is_array($options)) {
        return $args;
    }
    $args['options'] = kcj_sort_size_slugs($options);
    return $args;
});

add_filter('woocommerce_dropdown_variation_attribute_options_html', function ($html, $args) {
    if (empty($args['attribute']) || !kcj_is_size_attribute((string) $args['attribute'])) {
        return $html;
    }
    if (!preg_match_all('/<option\b[^>]*>.*?<\/option>/is', $html, $matches)) {
        return $html;
    }
    $options = $matches[0];
    if (count($options) < 2) {
        return $html;
    }
    $none = array_shift($options);
    $rank = array_flip(['xs', 's', 'm', 'l', 'xl', '2xl', '3xl', '4xl', '5xl', '6xl']);
    usort($options, function ($a, $b) use ($rank) {
        preg_match('/value="([^"]*)"/', $a, $ma);
        preg_match('/value="([^"]*)"/', $b, $mb);
        $sa = strtolower($ma[1] ?? '');
        $sb = strtolower($mb[1] ?? '');
        $ra = $rank[$sa] ?? 100;
        $rb = $rank[$sb] ?? 100;
        if ($ra === $rb) {
            return strnatcasecmp($sa, $sb);
        }
        return $ra <=> $rb;
    });
    $sorted = $none . implode('', $options);
    $without = preg_replace('/<option\b[^>]*>.*?<\/option>/is', '', $html);
    return preg_replace('/(<select\b[^>]*>)/i', '$1' . $sorted, $without, 1);
}, 10, 2);

add_filter('woocommerce_dropdown_variation_attribute_options_args', function ($args) {
    if (empty($args['attribute']) || !empty($args['selected'])) {
        return $args;
    }
    if (!kcj_is_size_attribute((string) $args['attribute'])) {
        return $args;
    }
    $options = $args['options'] ?? [];
    if (!is_array($options)) {
        return $args;
    }
    $want = ['m', 'l', 's', 'xl'];
    $lower = array_map('strtolower', array_map('strval', $options));
    foreach ($want as $w) {
        $i = array_search($w, $lower, true);
        if ($i !== false) {
            $args['selected'] = $options[$i];
            break;
        }
    }
    return $args;
}, 20);

/** One main photo on first paint; other colors load on swatch click via Woo variation images. */
add_filter('woocommerce_product_get_gallery_image_ids', function ($ids) {
    if (is_admin() || wp_doing_ajax()) {
        return $ids;
    }
    if (!function_exists('is_product') || !is_product()) {
        return $ids;
    }
    return [];
}, 10, 1);

function kcj_pdp_trim_details($html) {
    $html = (string) $html;
    if ($html === '') {
        return '';
    }
    $html = wpautop($html);
    $keep = [];
    if (preg_match_all('/<(p|ul|ol)\b[^>]*>[\s\S]*?<\/\1>/i', $html, $matches)) {
        foreach ($matches[0] as $block) {
            $plain = trim(wp_strip_all_tags($block));
            if ($plain === '' || strcasecmp($plain, 'Why Wear It') === 0) {
                continue;
            }
            $essay = (
                strlen($plain) > 220
                && preg_match('/lettering|typography|distressed|drawn in|features [\'"]|visual gag|brush lettering|grunge type/i', $plain)
            );
            if ($essay) {
                continue;
            }
            $keep[] = $block;
            if (count($keep) >= 4) {
                break;
            }
        }
    }
    return implode('', $keep);
}
