<?php
/**
 * kcjdrama theme bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KCJ_VERSION', '1.2.0');
define('KCJ_PATH', get_template_directory());
define('KCJ_URI', get_template_directory_uri());

require_once KCJ_PATH . '/inc/cpt-hero.php';
require_once KCJ_PATH . '/inc/cron.php';
require_once KCJ_PATH . '/inc/admin.php';
require_once KCJ_PATH . '/inc/seed.php';
require_once KCJ_PATH . '/inc/seed-megaset.php';

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    remove_theme_support('core-block-patterns');
    add_theme_support('woocommerce');
    register_nav_menus([
        'primary' => 'Primary menu',
        'footer'  => 'Footer menu',
    ]);
});

/** Soft/Mirror brand pages — our theme owns the chrome. */
function kcj_is_brand_stage() {
    if (is_admin()) {
        return false;
    }
    if (is_front_page()) {
        return true;
    }
    return is_page(['soft', 'mirror', 'about', 'stories', 'about-the-roast', 'victim-log', 'tropes', 'syndromes']);
}

/** Woo screens that need plugin CSS/JS. */
function kcj_needs_woo_assets() {
    if (!function_exists('is_woocommerce')) {
        return false;
    }
    return is_woocommerce() || is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url();
}

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'kcj-fonts',
        'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@300;400;500;600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style('kcj-front', KCJ_URI . '/assets/css/front.css', ['kcj-fonts'], KCJ_VERSION);

    if (kcj_is_brand_stage()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }
}, 20);

// This is OUR theme — Woo is inventory, not the skin.
add_action('wp_enqueue_scripts', function () {
    if (is_admin() || kcj_needs_woo_assets()) {
        return;
    }
    foreach ([
        'woocommerce-general',
        'woocommerce-layout',
        'woocommerce-smallscreen',
        'woocommerce-inline',
        'wc-blocks-style',
        'wc-blocks-vendors-style',
        'wc-block-style',
    ] as $h) {
        wp_dequeue_style($h);
        wp_deregister_style($h);
    }
    foreach ([
        'woocommerce',
        'wc-add-to-cart',
        'wc-cart-fragments',
        'wc-checkout',
        'wc-single-product',
        'jquery-blockui',
        'js-cookie',
        'sourcebuster-js',
        'wc-order-attribution',
    ] as $h) {
        wp_dequeue_script($h);
        wp_deregister_script($h);
    }
}, 100);

add_filter('show_admin_bar', function ($show) {
    if (is_front_page() && !is_admin()) {
        return false;
    }
    return $show;
});

add_action('template_redirect', function () {
    // Always no-cache on front while iterating Soft/Mirror + shop wall.
    if (!is_admin()) {
        nocache_headers();
    }
});

/**
 * Current hero payload for the front page.
 *
 * @return array{id:int,image_url:string,has_baked_menu:bool,hotspots:array,alt:string}|null
 */
function kcj_get_current_hero() {
    $forced = (int) get_option('kcj_force_hero_id', 0);
    $current = $forced > 0 ? $forced : (int) get_option('kcj_current_hero_id', 0);

    $post = $current ? get_post($current) : null;
    if (!$post || $post->post_type !== 'kcj_hero' || $post->post_status !== 'publish') {
        $post = kcj_first_hero();
    }

    if (!$post) {
        return null;
    }

    $image_url = get_the_post_thumbnail_url($post, 'full');
    if (!$image_url) {
        return null;
    }

    $hotspots = get_post_meta($post->ID, '_kcj_hotspots', true);
    if (!is_array($hotspots)) {
        $decoded = json_decode((string) $hotspots, true);
        $hotspots = is_array($decoded) ? $decoded : [];
    }

    return [
        'id'             => (int) $post->ID,
        'image_url'      => $image_url,
        'has_baked_menu' => (bool) get_post_meta($post->ID, '_kcj_has_baked_menu', true),
        'hotspots'       => $hotspots,
        'alt'            => get_the_title($post),
    ];
}

function kcj_first_hero() {
    $q = new WP_Query([
        'post_type'      => 'kcj_hero',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'no_found_rows'  => true,
    ]);
    return $q->have_posts() ? $q->posts[0] : null;
}

function kcj_page_url($slug, $fallback = '#') {
    $page = get_page_by_path($slug);
    $url = $page ? get_permalink($page) : home_url($fallback === '#' ? '/' . trim($slug, '/') . '/' : $fallback);
    return kcj_local_href($url);
}

/**
 * Turn same-machine absolute URLs into paths so LAN and localhost share one hero.
 */
function kcj_local_href($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '#';
    }
    if ($url[0] === '/' || $url[0] === '#') {
        return $url;
    }
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['host'])) {
        return $url;
    }
    $host = strtolower($parts['host']);
    $local = ($host === 'localhost' || $host === '127.0.0.1');
    if (!$local && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $local = !filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
    if (!$local) {
        return $url;
    }
    $path = isset($parts['path']) && $parts['path'] !== '' ? $parts['path'] : '/';
    if (!empty($parts['query'])) {
        $path .= '?' . $parts['query'];
    }
    if (!empty($parts['fragment'])) {
        $path .= '#' . $parts['fragment'];
    }
    return $path;
}

function kcj_esc_hotspot_attr($value) {
    return esc_attr((string) $value);
}

/**
 * Soft | Everything | Mirror rail from ?rail= query.
 *
 * @return string soft|mirror|all
 */
function kcj_shop_rail() {
    $rail = isset($_GET['rail']) ? strtolower((string) wp_unslash($_GET['rail'])) : 'all';
    if (!in_array($rail, ['soft', 'mirror', 'all'], true)) {
        return 'all';
    }
    return $rail;
}

/**
 * Map a Woo product to Soft | Mirror rail.
 *
 * soft: soft, soft-world, romantic-chaos
 * mirror: mirror, mirror-world, classic-tropes
 */
function kcj_product_rail($product_id) {
    $terms = get_the_terms((int) $product_id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return 'soft';
    }
    $slugs = wp_list_pluck($terms, 'slug');
    $mirror_slugs = ['mirror', 'mirror-world', 'classic-tropes'];
    foreach ($mirror_slugs as $s) {
        if (in_array($s, $slugs, true)) {
            return 'mirror';
        }
    }
    return 'soft';
}

/**
 * Featured merch for homepage + shop wall.
 * Prefers live Woo catalog; falls back to placeholders only if Woo empty.
 *
 * @return array<int, array{rail:string,rail_label:string,title:string,meta:string,url:string,image:string}>
 */
function kcj_featured_merch($limit = 8) {
    $rail = kcj_shop_rail();
    $items = [];

    if (function_exists('wc_get_products')) {
        $args = [
            'status'  => 'publish',
            'limit'   => max(4, (int) $limit * 3),
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        ];
        if ($rail === 'soft') {
            $args['category'] = ['soft', 'romantic-chaos'];
        } elseif ($rail === 'mirror') {
            $args['category'] = ['mirror', 'classic-tropes'];
        }
        $products = wc_get_products($args);
        foreach ($products as $product) {
            $id = $product->get_id();
            $item_rail = kcj_product_rail($id);
            if ($rail !== 'all' && $item_rail !== $rail) {
                continue;
            }
            $img = get_the_post_thumbnail_url($id, 'medium_large');
            if (!$img) {
                $img = '';
            }
            $price = '';
            if (method_exists($product, 'get_price_html')) {
                $price = wp_strip_all_tags($product->get_price_html());
            }
            $items[] = [
                'rail'       => $item_rail,
                'rail_label' => $item_rail === 'mirror' ? 'Mirror' : 'Soft',
                'title'      => $product->get_name(),
                'meta'       => $price !== '' ? $price : 'In stock',
                'url'        => get_permalink($id),
                'image'      => $img,
            ];
            if (count($items) >= (int) $limit) {
                break;
            }
        }
    }

    if ($items) {
        return $items;
    }

    // Fallback placeholders only when catalog empty.
    return kcj_featured_merch_placeholders_fallback();
}

/**
 * @deprecated Prefer kcj_featured_merch(); kept as empty-catalog fallback.
 */
function kcj_featured_merch_placeholders() {
    return kcj_featured_merch(4);
}

function kcj_featured_merch_placeholders_fallback() {
    $items = [
        [
            'rail'       => 'soft',
            'rail_label' => 'Soft',
            'title'      => 'Shared Umbrella Tee',
            'meta'       => 'Porcelain romance · coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
        [
            'rail'       => 'soft',
            'rail_label' => 'Soft',
            'title'      => 'Wrist-Grab Hoodie',
            'meta'       => 'Craft desk comfort · coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
        [
            'rail'       => 'mirror',
            'rail_label' => 'Mirror',
            'title'      => 'Plot Armor Optional Tee',
            'meta'       => 'Violet circuit roast · coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
        [
            'rail'       => 'mirror',
            'rail_label' => 'Mirror',
            'title'      => 'Syndrome Clinic Hoodie',
            'meta'       => 'Affectionate chaos · coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
    ];
    $rail = kcj_shop_rail();
    if ($rail === 'all') {
        return $items;
    }
    return array_values(array_filter($items, static function ($item) use ($rail) {
        return $item['rail'] === $rail;
    }));
}

/**
 * Ensure the Shop page exists (template: page-shop.php).
 */
function kcj_ensure_shop_page() {
    if (get_page_by_path('shop')) {
        return;
    }
    $id = wp_insert_post([
        'post_title'   => 'Shop',
        'post_name'    => 'shop',
        'post_content' => '',
        'post_status'  => 'publish',
        'post_type'    => 'page',
    ]);
    if (!is_wp_error($id) && $id) {
        update_post_meta($id, '_wp_page_template', 'page-shop.php');
    }
}

add_action('init', 'kcj_ensure_shop_page', 30);

// Woo's /shop archive would hide our Soft|Mirror wall — use page-shop.php instead.
add_filter('template_include', function ($template) {
    if (function_exists('is_shop') && is_shop()) {
        $custom = KCJ_PATH . '/page-shop.php';
        if (is_readable($custom)) {
            return $custom;
        }
    }
    return $template;
}, 99);

add_filter('body_class', function ($classes) {
    if (is_page('shop') || (function_exists('is_shop') && is_shop())) {
        $classes[] = 'kcj-is-shop';
        $classes[] = 'kcj-shop-rail-' . kcj_shop_rail();
    }
    return $classes;
});
