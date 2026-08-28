<?php
/**
 * Hostinger one-shot: upsert pages + merch taxonomies.
 * Does not create, update, delete, or retag products.
 *
 * Usage: php apply-hostinger-content.php /path/to/public_html /path/to/_hostinger-pack.json
 */
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$wp_root = isset($argv[1]) ? rtrim($argv[1], "/\\") : '';
$pack_path = isset($argv[2]) ? $argv[2] : '';
if ($wp_root === '' || !is_file($wp_root . '/wp-load.php')) {
    fwrite(STDERR, "Bad WP root\n");
    exit(1);
}
if ($pack_path === '' || !is_file($pack_path)) {
    fwrite(STDERR, "Missing pack json\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require $wp_root . '/wp-load.php';

$raw = file_get_contents($pack_path);
$pack = json_decode($raw, true);
if (!is_array($pack)) {
    fwrite(STDERR, "Pack JSON invalid\n");
    exit(1);
}

$log = [];
$ensure_term = static function ($taxonomy, $name, $slug, $description = '') use (&$log) {
    if (!taxonomy_exists($taxonomy)) {
        $log[] = "skip tax missing $taxonomy";
        return;
    }
    $existing = get_term_by('slug', $slug, $taxonomy);
    if ($existing && !is_wp_error($existing)) {
        $args = [];
        if ($description !== '' && (string) $existing->description !== $description) {
            $args['description'] = $description;
        }
        if ($args) {
            wp_update_term((int) $existing->term_id, $taxonomy, $args);
            $log[] = "updated $taxonomy:$slug";
        } else {
            $log[] = "exists $taxonomy:$slug";
        }
        return;
    }
    $r = wp_insert_term($name, $taxonomy, [
        'slug'        => $slug,
        'description' => $description,
    ]);
    if (is_wp_error($r)) {
        $log[] = "fail $taxonomy:$slug " . $r->get_error_message();
        return;
    }
    $log[] = "created $taxonomy:$slug";
};

foreach ($pack['product_cats'] ?? [] as $row) {
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    $name = (string) ($row['name'] ?? '');
    if ($slug === '' || $name === '' || $slug === 'uncategorized') {
        continue;
    }
    $ensure_term('product_cat', $name, $slug, (string) ($row['description'] ?? ''));
}

foreach ($pack['product_tags'] ?? [] as $row) {
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    $name = (string) ($row['name'] ?? '');
    if ($slug === '' || $name === '') {
        continue;
    }
    $ensure_term('product_tag', $name, $slug, (string) ($row['description'] ?? ''));
}

foreach ($pack['blog_cats'] ?? [] as $row) {
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    $name = (string) ($row['name'] ?? '');
    if ($slug === '' || $name === '') {
        continue;
    }
    $ensure_term('category', $name, $slug);
}

$skip_pages = [
    'cart', 'checkout', 'my-account', 'home', 'product', 'blog',
    'privacy-policy', 'privacy-policy-2', 'refund_returns',
];

$legacy_blog = get_page_by_path('blog');
$notes_page = get_page_by_path('field-notes');
if ($legacy_blog) {
    delete_post_meta((int) $legacy_blog->ID, '_wp_page_template');
}
if ($legacy_blog && !$notes_page) {
    $ren = wp_update_post([
        'ID'          => (int) $legacy_blog->ID,
        'post_name'   => 'field-notes',
        'post_title'  => 'Field notes',
        'post_status' => 'publish',
    ], true);
    $log[] = is_wp_error($ren) ? 'rename blog fail ' . $ren->get_error_message() : 'renamed blog → field-notes';
} elseif ($legacy_blog && $notes_page && (int) $legacy_blog->ID !== (int) $notes_page->ID) {
    wp_update_post([
        'ID'          => (int) $legacy_blog->ID,
        'post_status' => 'draft',
        'post_name'   => 'blog-legacy',
    ]);
    $log[] = 'drafted leftover blog page';
}

$pages = $pack['pages'] ?? [];
usort($pages, static function ($a, $b) {
    $ap = (string) ($a['parent_slug'] ?? '');
    $bp = (string) ($b['parent_slug'] ?? '');
    if ($ap === '' && $bp !== '') {
        return -1;
    }
    if ($ap !== '' && $bp === '') {
        return 1;
    }
    return strcmp((string) ($a['slug'] ?? ''), (string) ($b['slug'] ?? ''));
});

foreach ($pages as $row) {
    $slug = sanitize_title((string) ($row['slug'] ?? ''));
    if ($slug === '' || in_array($slug, $skip_pages, true)) {
        continue;
    }
    $parent_slug = sanitize_title((string) ($row['parent_slug'] ?? ''));
    $parent_id = 0;
    $path = $slug;
    if ($parent_slug !== '') {
        $parent = get_page_by_path($parent_slug);
        $parent_id = $parent ? (int) $parent->ID : 0;
        $path = $parent_slug . '/' . $slug;
    }
    $existing = get_page_by_path($path) ?: get_page_by_path($slug);
    $data = [
        'post_title'   => (string) ($row['title'] ?? $slug),
        'post_name'    => $slug,
        'post_content' => (string) ($row['content'] ?? ''),
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_parent'  => $parent_id,
    ];
    if ($existing) {
        $eid = (int) $existing->ID;
        $tpl = (string) get_page_template_slug($eid);
        if ($tpl !== '' && $tpl !== 'default' && !is_file(get_theme_file_path($tpl))) {
            delete_post_meta($eid, '_wp_page_template');
            $log[] = "cleared invalid template $path ($tpl)";
        }
        $data['ID'] = $eid;
        $id = wp_update_post($data, true);
        $log[] = is_wp_error($id) ? "page fail $path " . $id->get_error_message() : "page update $path";
    } else {
        $id = wp_insert_post($data, true);
        $log[] = is_wp_error($id) ? "page fail $path " . $id->get_error_message() : "page create $path";
    }
}

$notes = get_page_by_path('field-notes');
if ($notes) {
    update_option('page_for_posts', (int) $notes->ID);
    $log[] = 'page_for_posts=field-notes';
}

update_option('kcj_rail_cats_v2', 1, false);
update_option('kcj_seeded', 1, false);
// Store-only coming soon hijacks /shop/ before page-shop.php can paint the wall.
if (get_option('woocommerce_coming_soon') === 'yes') {
    update_option('woocommerce_coming_soon', 'no');
    $log[] = 'woocommerce_coming_soon set to no';
}
flush_rewrite_rules(false);

$tag_n = taxonomy_exists('product_tag') ? (int) wp_count_terms(['taxonomy' => 'product_tag', 'hide_empty' => false]) : 0;
$cat_n = taxonomy_exists('product_cat') ? (int) wp_count_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]) : 0;
fwrite(STDOUT, implode("\n", $log) . "\n");
fwrite(STDOUT, "DONE tags=$tag_n product_cats=$cat_n pages=" . count($pages) . " products_untouched=1\n");
