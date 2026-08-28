<?php
/**
 * Local pack for Hostinger: pages + merch taxonomies. No products.
 */
if (!defined('ABSPATH')) {
    exit;
}

$out_path = dirname(__DIR__) . '/scripts/_hostinger-pack.json';

$page_slugs = [
    'start-here',
    'soft',
    'mirror',
    'shop',
    'tropes',
    'syndromes',
    'glossary',
    'essays',
    'memes',
    'bingo',
    'editorial-policy',
    'about',
    'about-the-roast',
    'victim-log',
    'stories',
    'countries',
    'korea',
    'china',
    'japan',
    'shipping-returns',
    'shop-by-drama',
    'size-guide',
    'contact',
    'faq',
    'terms-of-service',
    'field-notes',
];

$skip_slugs = [
    'cart',
    'checkout',
    'my-account',
    'home',
    'product',
    'blog',
    'privacy-policy',
    'privacy-policy-2',
    'refund_returns',
];

$pages = [];
foreach ($page_slugs as $slug) {
    if (in_array($slug, $skip_slugs, true)) {
        continue;
    }
    $page = get_page_by_path($slug);
    if (!$page && in_array($slug, ['korea', 'china', 'japan'], true)) {
        $page = get_page_by_path('countries/' . $slug);
    }
    if (!$page || $page->post_status !== 'publish') {
        continue;
    }
    $parent_slug = '';
    if ((int) $page->post_parent) {
        $parent = get_post((int) $page->post_parent);
        $parent_slug = $parent ? (string) $parent->post_name : '';
    }
    $pages[] = [
        'slug'        => (string) $page->post_name,
        'title'       => (string) $page->post_title,
        'content'     => (string) $page->post_content,
        'parent_slug' => $parent_slug,
        'status'      => 'publish',
    ];
}

$product_cats = [];
foreach (['soft' => 'Soft', 'mirror' => 'Mirror'] as $slug => $fallback) {
    $term = get_term_by('slug', $slug, 'product_cat');
    $product_cats[] = [
        'slug'        => $slug,
        'name'        => $term && !is_wp_error($term) ? $term->name : $fallback,
        'description' => $term && !is_wp_error($term) ? (string) $term->description : '',
    ];
}

$product_tags = [];
if (taxonomy_exists('product_tag')) {
    $terms = get_terms([
        'taxonomy'   => 'product_tag',
        'hide_empty' => false,
    ]);
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            $product_tags[] = [
                'slug'        => $term->slug,
                'name'        => $term->name,
                'description' => (string) $term->description,
            ];
        }
    }
}

$blog_cats = [];
$wanted_blog = [
    'korea', 'china', 'japan', 'cross-culture', 'trope-deep-dive', 'syndrome',
    'craft', 'starter-pack', 'listicle', 'essay', 'mirror-roast', 'soft-feels', 'glossary',
];
foreach ($wanted_blog as $slug) {
    $term = get_term_by('slug', $slug, 'category');
    $blog_cats[] = [
        'slug' => $slug,
        'name' => $term && !is_wp_error($term) ? $term->name : str_replace('-', ' ', ucfirst($slug)),
    ];
}

$pack = [
    'shipped_at'    => gmdate('c'),
    'theme_version' => defined('KCJ_VERSION') ? KCJ_VERSION : '',
    'pages'         => $pages,
    'product_cats'  => $product_cats,
    'product_tags'  => $product_tags,
    'blog_cats'     => $blog_cats,
];

$json = wp_json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    fwrite(STDERR, "json encode failed\n");
    exit(1);
}
file_put_contents($out_path, $json);
if (class_exists('WP_CLI')) {
    WP_CLI::success($out_path . ' pages=' . count($pages) . ' tags=' . count($product_tags) . ' cats=' . count($product_cats));
}
