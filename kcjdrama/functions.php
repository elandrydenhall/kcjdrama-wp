<?php
/**
 * kcjdrama theme bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KCJ_VERSION', '1.5.85');
define('KCJ_PATH', get_template_directory());
define('KCJ_URI', get_template_directory_uri());

require_once KCJ_PATH . '/inc/cpt-hero.php';
require_once KCJ_PATH . '/inc/cron.php';
require_once KCJ_PATH . '/inc/admin.php';
require_once KCJ_PATH . '/inc/seed.php';
require_once KCJ_PATH . '/inc/seed-megaset.php';
require_once KCJ_PATH . '/inc/perf.php';
require_once KCJ_PATH . '/inc/search.php';
require_once KCJ_PATH . '/inc/pdp.php';
require_once KCJ_PATH . '/inc/catalog.php';
require_once KCJ_PATH . '/inc/editorial-hub.php';
require_once KCJ_PATH . '/inc/contribute.php';
require_once KCJ_PATH . '/inc/ai-story.php';
require_once KCJ_PATH . '/inc/support.php';
require_once KCJ_PATH . '/inc/quotes.php';
require_once KCJ_PATH . '/inc/country-desk.php';

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

add_filter('document_title_parts', function ($parts) {
    if (function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page())) {
        $parts['title'] = __('Checkout', 'kcjdrama');
    } elseif (function_exists('is_cart') && is_cart()) {
        $parts['title'] = __('Your bag', 'kcjdrama');
    } elseif (is_page('sign-in') && is_user_logged_in()) {
        $parts['title'] = __('Account', 'kcjdrama');
    }
    return $parts;
});

add_filter('the_title', function ($title, $post_id) {
    if (is_admin()) {
        return $title;
    }
    $cart_id = (int) get_option('woocommerce_cart_page_id');
    $check_id = (int) get_option('woocommerce_checkout_page_id');
    if ($cart_id && (int) $post_id === $cart_id && function_exists('is_cart') && is_cart()) {
        return __('Your bag', 'kcjdrama');
    }
    if ($check_id && (int) $post_id === $check_id && function_exists('is_checkout') && is_checkout() && !(function_exists('is_order_received_page') && is_order_received_page())) {
        return __('Checkout', 'kcjdrama');
    }
    return $title;
}, 10, 2);

add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        return single_cat_title('', false);
    }
    if (is_tag()) {
        return single_tag_title('', false);
    }
    return $title;
});

/** Soft/Mirror brand pages â€” our theme owns the chrome. */
function kcj_is_brand_stage() {
    if (is_admin()) {
        return false;
    }
    if (is_front_page() || is_home() || is_archive() || is_singular('kcj_story')) {
        return true;
    }
    return is_page(['soft', 'mirror', 'about', 'stories', 'about-the-roast', 'victim-log', 'tropes', 'syndromes', 'essays', 'editorial-policy', 'glossary', 'start-here', 'shipping-returns', 'sign-in', 'faq', 'support', 'countries', 'korea', 'china', 'japan']);
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
        'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500;1,9..144,600&family=Great+Vibes&family=Montserrat:wght@300;400;500;600;700&family=Syne:wght@600;700&display=swap',
        [],
        null
    );
    wp_enqueue_style('kcj-front', KCJ_URI . '/assets/css/front.css', [], KCJ_VERSION);

    $smoke = !is_front_page()
        && !kcj_needs_woo_assets()
        && (is_home() || is_archive() || is_page(['tropes', 'syndromes', 'essays', 'glossary']));
    if ($smoke) {
        wp_enqueue_script('kcj-smoke-lib', KCJ_URI . '/assets/js/smoke.js', [], KCJ_VERSION, true);
        wp_enqueue_script('kcj-smoke', KCJ_URI . '/assets/js/kcj-smoke.js', ['kcj-smoke-lib'], KCJ_VERSION, true);
    }

    if (kcj_is_brand_stage()) {
        wp_dequeue_style('wp-block-library');
        wp_dequeue_style('wp-block-library-theme');
        wp_dequeue_style('global-styles');
        wp_dequeue_style('classic-theme-styles');
    }
}, 20);

// This is OUR theme â€” Woo is inventory, not the skin.
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

/**
 * Red rose favicons for all devices. Source: Commons Rose_red_on_white_background.jpg (PD).
 */
add_action('wp_head', function () {
    $icons = KCJ_URI . '/assets/img/icons';
    echo '<link rel="icon" href="' . esc_url($icons . '/favicon.ico') . '" sizes="any">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="16x16" href="' . esc_url($icons . '/favicon-16x16.png') . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="32x32" href="' . esc_url($icons . '/favicon-32x32.png') . '">' . "\n";
    echo '<link rel="icon" type="image/png" sizes="48x48" href="' . esc_url($icons . '/favicon-48x48.png') . '">' . "\n";
    echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($icons . '/apple-touch-icon.png') . '">' . "\n";
    echo '<link rel="manifest" href="' . esc_url($icons . '/site.webmanifest') . '">' . "\n";
    echo '<meta name="theme-color" content="#c97b9a">' . "\n";
    echo '<meta name="msapplication-TileColor" content="#c97b9a">' . "\n";
    echo '<meta name="msapplication-TileImage" content="' . esc_url($icons . '/mstile-150x150.png') . '">' . "\n";
    echo '<meta name="msapplication-config" content="' . esc_url($icons . '/browserconfig.xml') . '">' . "\n";
}, 1);

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

    // Bust CDN/browser cache when the upload file changes (Hostinger max-age is long).
    $thumb_id = (int) get_post_thumbnail_id($post);
    $file = $thumb_id ? get_attached_file($thumb_id) : '';
    if (is_string($file) && $file !== '' && is_file($file)) {
        $image_url = add_query_arg('v', (string) filemtime($file), $image_url);
    }

    $hotspots = get_post_meta($post->ID, '_kcj_hotspots', true);
    if (!is_array($hotspots)) {
        $decoded = json_decode((string) $hotspots, true);
        $hotspots = is_array($decoded) ? $decoded : [];
    }

    return apply_filters('kcj_current_hero', [
        'id'             => (int) $post->ID,
        'image_url'      => $image_url,
        'has_baked_menu' => (bool) get_post_meta($post->ID, '_kcj_has_baked_menu', true),
        'hotspots'       => $hotspots,
        'alt'            => get_the_title($post),
    ]);
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

function kcj_field_notes_url() {
    $page = get_page_by_path('field-notes');
    if ($page && $page->post_status === 'publish') {
        return kcj_local_href(get_permalink($page));
    }
    $id = (int) get_option('page_for_posts');
    if ($id && get_post_type($id) === 'page') {
        $url = get_permalink($id);
        if ($url) {
            return kcj_local_href($url);
        }
    }
    return kcj_page_url('field-notes');
}

function kcj_page_url($slug, $fallback = '#') {
    $page = get_page_by_path($slug);
    $url = $page ? get_permalink($page) : home_url($fallback === '#' ? '/' . trim($slug, '/') . '/' : $fallback);
    return kcj_local_href($url);
}

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($path === 'blog') {
        wp_safe_redirect(kcj_field_notes_url(), 301);
        exit;
    }
});

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

function kcj_hotspot_role(array $spot) {
    $role = sanitize_key($spot['role'] ?? 'custom');
    return $role !== '' ? $role : 'custom';
}

/**
 * Remap a hotspot from full-plate % into a CSS crop panel.
 * Soft = left 50% of the plate; Mirror = right 50%. Never uses devicePixelRatio.
 *
 * @return array{x:float,y:float,w:float,h:float}|null
 */
function kcj_hotspot_box(array $spot, $panel = 'full') {
    $x = isset($spot['x']) ? (float) $spot['x'] : 0.0;
    $y = isset($spot['y']) ? (float) $spot['y'] : 0.0;
    $w = isset($spot['w']) ? (float) $spot['w'] : 10.0;
    $h = isset($spot['h']) ? (float) $spot['h'] : 6.0;
    if ($panel !== 'soft' && $panel !== 'mirror') {
        return ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h];
    }
    $mid = $x + ($w / 2.0);
    if ($panel === 'soft') {
        if ($mid >= 50.0) {
            return null;
        }
        return ['x' => $x / 0.5, 'y' => $y, 'w' => $w / 0.5, 'h' => $h];
    }
    if ($mid < 50.0) {
        return null;
    }
    return ['x' => ($x - 50.0) / 0.5, 'y' => $y, 'w' => $w / 0.5, 'h' => $h];
}

function kcj_hero_logo_spot(array $hotspots) {
    foreach ($hotspots as $spot) {
        if (!is_array($spot)) {
            continue;
        }
        if (kcj_hotspot_role($spot) === 'logo') {
            return $spot;
        }
    }
    return [
        'role'  => 'logo',
        'x'     => 40.0,
        'y'     => 0.0,
        'w'     => 20.0,
        'h'     => 7.5,
        'href'  => '/',
        'label' => 'kcjdrama',
    ];
}

function kcj_logo_float_url() {
    $path = KCJ_PATH . '/assets/brand/kcj-logo-float.png';
    if (is_readable($path)) {
        return KCJ_URI . '/assets/brand/kcj-logo-float.png?v=' . (string) filemtime($path);
    }
    return '';
}

function kcj_render_logo_link(array $spot, $variant = 'desktop') {
    $href  = isset($spot['href']) ? kcj_local_href($spot['href']) : '/';
    $label = !empty($spot['label']) ? (string) $spot['label'] : 'kcjdrama';
    $class = $variant === 'stack' ? 'kcj-logo-link kcj-logo-link--stack' : 'kcj-logo-link';
    // Evening CSS-crop accept: HTML/CSS text overlay (Great Vibes). Bitmap float only when a real wordmark cutout is present.
    $logo  = kcj_logo_float_url();
    $use_float = $logo !== '' && kcj_logo_float_is_wordmark();
    $inner = $use_float
        ? sprintf(
            '<img class="kcj-logo-float" src="%s" alt="%s" width="960" height="292" decoding="async">',
            esc_url($logo),
            esc_attr($label)
        )
        : esc_html($label);

    if ($variant === 'stack') {
        printf(
            '<a class="%s" href="%s">%s</a>',
            esc_attr($class),
            esc_url($href),
            $inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above
        );
        return;
    }
    $box = kcj_hotspot_box($spot, 'full');
    printf(
        '<a class="%s" href="%s" style="left:%s%%;top:%s%%;width:%s%%;height:%s%%;" aria-label="%s">%s</a>',
        esc_attr($class),
        esc_url($href),
        esc_attr((string) $box['x']),
        esc_attr((string) $box['y']),
        esc_attr((string) $box['w']),
        esc_attr((string) $box['h']),
        esc_attr($label),
        $inner // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above
    );
}

/**
 * Reject abstract wash / non-wordmark PNGs accidentally dropped into brand/.
 * Real cutouts are wide wordmarks; blotches are nearer square and huge opaque fills.
 */
function kcj_logo_float_is_wordmark() {
    $path = KCJ_PATH . '/assets/brand/kcj-logo-float.png';
    if (!is_readable($path)) {
        return false;
    }
    $size = @getimagesize($path);
    if (!$size || empty($size[0]) || empty($size[1])) {
        return false;
    }
    $w = (int) $size[0];
    $h = (int) $size[1];
    if ($w < 200 || $h < 40) {
        return false;
    }
    // Wordmark aspect is wide; abstract blotches in this saga were ~1.7:1 with full-bleed paint.
    $ratio = $w / max(1, $h);
    if ($ratio < 2.2) {
        return false;
    }
    return true;
}

function kcj_render_hotspot(array $spot, $panel = 'full') {
    if (kcj_hotspot_role($spot) === 'logo') {
        return;
    }
    $box = kcj_hotspot_box($spot, $panel);
    if ($box === null) {
        return;
    }
    $href  = isset($spot['href']) ? kcj_local_href($spot['href']) : '#';
    $label = isset($spot['label']) ? (string) $spot['label'] : 'Open';
    printf(
        '<a class="kcj-hotspot" href="%s" style="left:%s%%;top:%s%%;width:%s%%;height:%s%%;" aria-label="%s"></a>',
        esc_url($href),
        esc_attr((string) $box['x']),
        esc_attr((string) $box['y']),
        esc_attr((string) $box['w']),
        esc_attr((string) $box['h']),
        esc_attr($label)
    );
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
 * Rails a product belongs on. `everything` is Woo's required default, not a rail.
 *
 * @return list<string> `soft` and/or `mirror` (empty if only Everything / uncategorized)
 */
function kcj_product_rails($product_id) {
    $id = (int) $product_id;
    $rails = [];
    if (has_term('soft', 'product_cat', $id)) {
        $rails[] = 'soft';
    }
    if (has_term('mirror', 'product_cat', $id)) {
        $rails[] = 'mirror';
    }
    if ($rails) {
        return $rails;
    }
    $terms = get_the_terms($id, 'product_cat');
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }
    $slugs = wp_list_pluck($terms, 'slug');
    foreach (['classic-tropes', 'powerplays', 'everyday', 'mirror-world'] as $s) {
        if (in_array($s, $slugs, true)) {
            return ['mirror'];
        }
    }
    foreach (['romantic-chaos', 'family-obligation'] as $s) {
        if (in_array($s, $slugs, true)) {
            return ['soft'];
        }
    }
    return [];
}

/**
 * Primary rail for PDP chrome. Mixed Soft+Mirror uses Soft (porcelain) on the product page.
 * Everything-only is not a rail — falls back to soft chrome so the page still paints.
 */
function kcj_product_rail($product_id) {
    $rails = kcj_product_rails($product_id);
    if (in_array('mirror', $rails, true) && !in_array('soft', $rails, true)) {
        return 'mirror';
    }
    return 'soft';
}

function kcj_legacy_mirror_cat_slugs() {
    return ['classic-tropes', 'powerplays', 'everyday', 'mirror-world', 'mirror'];
}

/**
 * Ensure Soft / Mirror product categories exist; one-time move from tropes-cats.
 * Old category names become product tags so search still works.
 */
function kcj_migrate_product_rail_cats() {
    if (get_option('kcj_rail_cats_v2')) {
        return;
    }
    if (!taxonomy_exists('product_cat') || !post_type_exists('product')) {
        return;
    }
    foreach (['soft' => 'Soft', 'mirror' => 'Mirror'] as $slug => $name) {
        if (!term_exists($slug, 'product_cat')) {
            wp_insert_term($name, 'product_cat', ['slug' => $slug]);
        }
    }
    $soft_term = get_term_by('slug', 'soft', 'product_cat');
    $mirror_term = get_term_by('slug', 'mirror', 'product_cat');
    if (!$soft_term || !$mirror_term || is_wp_error($soft_term) || is_wp_error($mirror_term)) {
        return;
    }

    $ids = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);
    $legacy_mirror = kcj_legacy_mirror_cat_slugs();
    foreach ($ids as $id) {
        $id = (int) $id;
        $cats = get_the_terms($id, 'product_cat');
        $tag_names = [];
        $slugs = [];
        if ($cats && !is_wp_error($cats)) {
            foreach ($cats as $c) {
                $slugs[] = $c->slug;
                if (!in_array($c->slug, ['soft', 'mirror'], true) && $c->slug !== 'uncategorized') {
                    $tag_names[] = $c->name;
                }
            }
        }
        $is_mirror = (bool) array_intersect($slugs, $legacy_mirror);
        wp_set_object_terms($id, [(int) ($is_mirror ? $mirror_term->term_id : $soft_term->term_id)], 'product_cat');
        if ($tag_names && taxonomy_exists('product_tag')) {
            wp_set_object_terms($id, $tag_names, 'product_tag', true);
        }
    }
    update_option('kcj_rail_cats_v2', 1, false);
}
add_action('init', 'kcj_migrate_product_rail_cats', 40);

/** Product tile photo: real <img loading=lazy>, not CSS background (keeps LCP clear). */
function kcj_render_product_media(array $item) {
    echo '<span class="kcj-product-media" aria-hidden="true">';
    if (!empty($item['image'])) {
        printf(
            '<img src="%s" alt="" width="600" height="600" loading="lazy" decoding="async">',
            esc_url($item['image'])
        );
    }
    echo '</span>';
}

require_once KCJ_PATH . '/inc/merch-wall.php';

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
            'meta'       => 'Porcelain romance Â· coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
        [
            'rail'       => 'soft',
            'rail_label' => 'Soft',
            'title'      => 'Wrist-Grab Hoodie',
            'meta'       => 'Craft desk comfort Â· coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
        [
            'rail'       => 'mirror',
            'rail_label' => 'Mirror',
            'title'      => 'Plot Armor Optional Tee',
            'meta'       => 'Violet circuit roast Â· coming soon',
            'url'        => kcj_page_url('shop'),
            'image'      => '',
        ],
        [
            'rail'       => 'mirror',
            'rail_label' => 'Mirror',
            'title'      => 'Syndrome Clinic Hoodie',
            'meta'       => 'Affectionate chaos Â· coming soon',
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

// Woo's /shop archive would hide our Soft|Mirror wall â€” use page-shop.php instead.
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
