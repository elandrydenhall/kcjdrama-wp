<?php
/**
 * Local pack for Hostinger: pages + heroes + quotes + merch taxonomies.
 * Never includes WooCommerce products (local testing only).
 */
if (!defined('ABSPATH')) {
    exit;
}

$out_path = dirname(__DIR__) . '/scripts/_hostinger-pack.json';

// Woo / system pages — do not overwrite Hostinger store plumbing.
$skip_slugs = [
    'cart',
    'checkout',
    'my-account',
    'home',
    'product',
    'blog',
    'blog-legacy',
    'refund_returns',
];

$origin_replacements = [
    home_url(),
    untrailingslashit(home_url()),
    'http://127.0.0.1:8080',
    'http://localhost:8080',
    'https://127.0.0.1:8080',
    'https://localhost:8080',
];

$normalize_content = static function (string $content) use ($origin_replacements): string {
    foreach ($origin_replacements as $origin) {
        if ($origin === '') {
            continue;
        }
        $content = str_replace($origin, '', $content);
    }
    return $content;
};

$normalize_href = static function ($href) use ($normalize_content): string {
    $href = $normalize_content((string) $href);
    if ($href === '') {
        return '';
    }
    // Keep root-relative / absolute-https; drop accidental host-only leftovers.
    if (preg_match('#^https?://#i', $href)) {
        return esc_url_raw($href);
    }
    if ($href[0] !== '/') {
        $href = '/' . ltrim($href, '/');
    }
    return $href;
};

$uploads = wp_upload_dir();
$basedir = isset($uploads['basedir']) ? (string) $uploads['basedir'] : '';
$basedir = $basedir !== '' ? wp_normalize_path($basedir) : '';

$rel_from_abs = static function (string $abs) use ($basedir): string {
    if ($basedir === '' || $abs === '') {
        return '';
    }
    $abs = wp_normalize_path($abs);
    if (strpos($abs, $basedir) !== 0) {
        return '';
    }
    return ltrim(substr($abs, strlen($basedir)), '/');
};

$pages = [];
$query = get_posts([
    'post_type'      => 'page',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => ['menu_order' => 'ASC', 'title' => 'ASC'],
    'no_found_rows'  => true,
]);

foreach ($query as $page) {
    $slug = (string) $page->post_name;
    if ($slug === '' || in_array($slug, $skip_slugs, true)) {
        continue;
    }

    $parent_slug = '';
    if ((int) $page->post_parent) {
        $parent = get_post((int) $page->post_parent);
        $parent_slug = $parent ? (string) $parent->post_name : '';
    }

    $template = (string) get_page_template_slug($page->ID);
    if ($template === 'default') {
        $template = '';
    }

    $pages[] = [
        'slug'        => $slug,
        'title'       => (string) $page->post_title,
        'content'     => $normalize_content((string) $page->post_content),
        'parent_slug' => $parent_slug,
        'template'    => $template,
        'menu_order'  => (int) $page->menu_order,
        'status'      => 'publish',
    ];
}

$media = [];
$heroes = [];
if (post_type_exists('kcj_hero')) {
    $hero_posts = get_posts([
        'post_type'      => 'kcj_hero',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'no_found_rows'  => true,
    ]);

    foreach ($hero_posts as $hero) {
        $slug = (string) $hero->post_name;
        if ($slug === '') {
            $slug = sanitize_title($hero->post_title);
        }

        $thumb_id = (int) get_post_thumbnail_id($hero);
        $abs = $thumb_id ? (string) get_attached_file($thumb_id) : '';
        $image_rel = $abs !== '' ? $rel_from_abs($abs) : '';
        if ($image_rel !== '' && is_file($abs)) {
            $media[$image_rel] = [
                'rel'   => $image_rel,
                'bytes' => (int) filesize($abs),
                'mtime' => (int) filemtime($abs),
            ];
        }

        $hotspots = get_post_meta($hero->ID, '_kcj_hotspots', true);
        if (!is_array($hotspots)) {
            $decoded = json_decode((string) $hotspots, true);
            $hotspots = is_array($decoded) ? $decoded : [];
        }
        $clean_spots = [];
        foreach ($hotspots as $spot) {
            if (!is_array($spot)) {
                continue;
            }
            $clean_spots[] = [
                'id'    => sanitize_key($spot['id'] ?? uniqid('s', false)),
                'x'     => round((float) ($spot['x'] ?? 0), 2),
                'y'     => round((float) ($spot['y'] ?? 0), 2),
                'w'     => round((float) ($spot['w'] ?? 10), 2),
                'h'     => round((float) ($spot['h'] ?? 6), 2),
                'href'  => $normalize_href($spot['href'] ?? ''),
                'label' => sanitize_text_field((string) ($spot['label'] ?? '')),
                'role'  => sanitize_key((string) ($spot['role'] ?? 'custom')),
            ];
        }

        $heroes[] = [
            'slug'            => $slug,
            'title'           => (string) $hero->post_title,
            'menu_order'      => (int) $hero->menu_order,
            'image_rel'       => $image_rel,
            'has_baked_menu'  => (bool) get_post_meta($hero->ID, '_kcj_has_baked_menu', true),
            'hotspots'        => $clean_spots,
        ];
    }
}

$quotes = [];
if (post_type_exists('kcj_quote')) {
    $quote_posts = get_posts([
        'post_type'      => 'kcj_quote',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => ['date' => 'ASC', 'ID' => 'ASC'],
        'no_found_rows'  => true,
    ]);
    foreach ($quote_posts as $qp) {
        $key = (string) get_post_meta($qp->ID, '_kcj_quote_key', true);
        if ($key === '') {
            continue;
        }
        $quotes[] = [
            'key'         => $key,
            'title'       => (string) $qp->post_title,
            'text'        => trim((string) $qp->post_content),
            'speaker'     => (string) get_post_meta($qp->ID, '_kcj_speaker', true),
            'work'        => (string) get_post_meta($qp->ID, '_kcj_work', true),
            'country'     => (string) get_post_meta($qp->ID, '_kcj_country', true),
            'source_name' => (string) get_post_meta($qp->ID, '_kcj_source_name', true),
            'source_url'  => (string) get_post_meta($qp->ID, '_kcj_source_url', true),
            'verified'    => (int) get_post_meta($qp->ID, '_kcj_verified', true) === 1 ? 1 : 0,
            'tags'        => (string) get_post_meta($qp->ID, '_kcj_tags', true),
            'note'        => (string) get_post_meta($qp->ID, '_kcj_note', true),
            'locked'      => (int) get_post_meta($qp->ID, '_kcj_locked', true) === 1 ? 1 : 0,
            'edited'      => (int) get_post_meta($qp->ID, '_kcj_locally_edited', true) === 1 ? 1 : 0,
            'corpus_id'   => (int) get_post_meta($qp->ID, '_kcj_corpus_id', true),
        ];
    }
}

$current_id = (int) get_option('kcj_current_hero_id', 0);
$force_id = (int) get_option('kcj_force_hero_id', 0);
$slug_for = static function ($id) {
    $id = (int) $id;
    if ($id <= 0) {
        return '';
    }
    $p = get_post($id);
    return ($p && $p->post_type === 'kcj_hero') ? (string) $p->post_name : '';
};

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
    'shipped_at'        => gmdate('c'),
    'theme_version'     => defined('KCJ_VERSION') ? KCJ_VERSION : '',
    'pages'             => $pages,
    'heroes'            => $heroes,
    'quotes'            => $quotes,
    'media'             => array_values($media),
    'rotation'          => [
        'interval'           => function_exists('kcj_rotate_interval') ? kcj_rotate_interval() : (string) get_option('kcj_rotate_interval', 'hourly'),
        'current_hero_slug'  => $slug_for($current_id),
        'force_hero_slug'    => $slug_for($force_id),
    ],
    'product_cats'      => $product_cats,
    'product_tags'      => $product_tags,
    'blog_cats'         => $blog_cats,
    'products_excluded' => true,
];

$json = wp_json_encode($pack, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
if ($json === false) {
    fwrite(STDERR, "json encode failed\n");
    exit(1);
}
file_put_contents($out_path, $json);
if (class_exists('WP_CLI')) {
    WP_CLI::success(sprintf(
        '%s pages=%d heroes=%d quotes=%d media=%d products=excluded',
        $out_path,
        count($pages),
        count($heroes),
        count($quotes),
        count($media)
    ));
}
