<?php
/**
 * Hostinger one-shot: upsert pages + heroes + quotes + merch taxonomies.
 * Does not create, update, delete, or retag WooCommerce products.
 *
 * Usage: php apply-hostinger-content.php /path/to/public_html /path/to/_hostinger-pack.json
 *
 * Hero plate files must already be present under wp-content/uploads/ (SFTP'd by ship).
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
    'blog-legacy', 'refund_returns',
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
        'menu_order'   => (int) ($row['menu_order'] ?? 0),
    ];
    $page_id = 0;
    if ($existing) {
        $page_id = (int) $existing->ID;
        $tpl = (string) get_page_template_slug($page_id);
        if ($tpl !== '' && $tpl !== 'default' && !is_file(get_theme_file_path($tpl))) {
            delete_post_meta($page_id, '_wp_page_template');
            $log[] = "cleared invalid template $path ($tpl)";
        }
        $data['ID'] = $page_id;
        $id = wp_update_post($data, true);
        $log[] = is_wp_error($id) ? "page fail $path " . $id->get_error_message() : "page update $path";
        if (is_wp_error($id)) {
            $page_id = 0;
        }
    } else {
        $id = wp_insert_post($data, true);
        $log[] = is_wp_error($id) ? "page fail $path " . $id->get_error_message() : "page create $path";
        $page_id = is_wp_error($id) ? 0 : (int) $id;
    }

    if ($page_id) {
        $wanted_tpl = (string) ($row['template'] ?? '');
        if ($wanted_tpl !== '' && $wanted_tpl !== 'default') {
            if (is_file(get_theme_file_path($wanted_tpl))) {
                update_post_meta($page_id, '_wp_page_template', $wanted_tpl);
            } else {
                delete_post_meta($page_id, '_wp_page_template');
                $log[] = "skipped missing template $path ($wanted_tpl)";
            }
        }
    }
}

$notes = get_page_by_path('field-notes');
if ($notes) {
    update_option('page_for_posts', (int) $notes->ID);
    $log[] = 'page_for_posts=field-notes';
}

$ensure_attachment = static function (string $rel) use (&$log): int {
    $rel = ltrim(str_replace('\\', '/', $rel), '/');
    if ($rel === '' || strpos($rel, '..') !== false) {
        return 0;
    }
    $uploads = wp_upload_dir();
    $abs = trailingslashit($uploads['basedir']) . $rel;
    if (!is_file($abs)) {
        $log[] = "media missing $rel";
        return 0;
    }

    global $wpdb;
    $existing_id = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1",
        $rel
    ));
    if ($existing_id > 0 && get_post($existing_id)) {
        return $existing_id;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $filetype = wp_check_filetype(basename($abs), null);
    $attachment = [
        'post_mime_type' => $filetype['type'] ?: 'application/octet-stream',
        'post_title'     => preg_replace('/\.[^.]+$/', '', basename($abs)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];
    $attach_id = wp_insert_attachment($attachment, $abs);
    if (is_wp_error($attach_id) || !$attach_id) {
        $log[] = "media attach fail $rel";
        return 0;
    }
    $meta = wp_generate_attachment_metadata((int) $attach_id, $abs);
    if (is_array($meta)) {
        wp_update_attachment_metadata((int) $attach_id, $meta);
    }
    $log[] = "media attach $rel => #$attach_id";
    return (int) $attach_id;
};

$find_hero_by_slug = static function (string $slug): int {
    $slug = sanitize_title($slug);
    if ($slug === '' || !post_type_exists('kcj_hero')) {
        return 0;
    }
    $q = get_posts([
        'post_type'      => 'kcj_hero',
        'post_status'    => ['publish', 'draft', 'private'],
        'name'           => $slug,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);
    return $q ? (int) $q[0] : 0;
};

$hero_ids_by_slug = [];
$heroes_in = $pack['heroes'] ?? [];
if (post_type_exists('kcj_hero') && is_array($heroes_in)) {
    foreach ($heroes_in as $row) {
        if (!is_array($row)) {
            continue;
        }
        $slug = sanitize_title((string) ($row['slug'] ?? ''));
        $title = (string) ($row['title'] ?? '');
        if ($slug === '' || $title === '') {
            continue;
        }
        $existing_id = $find_hero_by_slug($slug);
        $data = [
            'post_type'   => 'kcj_hero',
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_name'   => $slug,
            'menu_order'  => (int) ($row['menu_order'] ?? 0),
        ];
        if ($existing_id) {
            $data['ID'] = $existing_id;
            $id = wp_update_post($data, true);
            $log[] = is_wp_error($id) ? "hero fail $slug " . $id->get_error_message() : "hero update $slug";
            $hero_id = is_wp_error($id) ? 0 : (int) $existing_id;
        } else {
            $id = wp_insert_post($data, true);
            $log[] = is_wp_error($id) ? "hero fail $slug " . $id->get_error_message() : "hero create $slug";
            $hero_id = is_wp_error($id) ? 0 : (int) $id;
        }
        if (!$hero_id) {
            continue;
        }
        $hero_ids_by_slug[$slug] = $hero_id;

        $image_rel = (string) ($row['image_rel'] ?? '');
        if ($image_rel !== '') {
            $attach_id = $ensure_attachment($image_rel);
            if ($attach_id) {
                set_post_thumbnail($hero_id, $attach_id);
            }
        }

        update_post_meta($hero_id, '_kcj_has_baked_menu', !empty($row['has_baked_menu']) ? '1' : '');
        $spots = $row['hotspots'] ?? [];
        $clean = [];
        if (is_array($spots)) {
            foreach ($spots as $spot) {
                if (!is_array($spot)) {
                    continue;
                }
                $clean[] = [
                    'id'    => sanitize_key($spot['id'] ?? uniqid('s', false)),
                    'x'     => round((float) ($spot['x'] ?? 0), 2),
                    'y'     => round((float) ($spot['y'] ?? 0), 2),
                    'w'     => round((float) ($spot['w'] ?? 10), 2),
                    'h'     => round((float) ($spot['h'] ?? 6), 2),
                    'href'  => esc_url_raw((string) ($spot['href'] ?? '')),
                    'label' => sanitize_text_field((string) ($spot['label'] ?? '')),
                    'role'  => sanitize_key((string) ($spot['role'] ?? 'custom')),
                ];
            }
        }
        update_post_meta($hero_id, '_kcj_hotspots', $clean);
    }
} elseif (!empty($heroes_in)) {
    $log[] = 'skip heroes (kcj_hero CPT missing — activate theme first)';
}

$rotation = is_array($pack['rotation'] ?? null) ? $pack['rotation'] : [];
$interval = sanitize_key((string) ($rotation['interval'] ?? 'hourly'));
if (!in_array($interval, ['hourly', 'twicedaily', 'daily'], true)) {
    $interval = 'hourly';
}
update_option('kcj_rotate_interval', $interval);
$current_slug = sanitize_title((string) ($rotation['current_hero_slug'] ?? ''));
$force_slug = sanitize_title((string) ($rotation['force_hero_slug'] ?? ''));
$current_hero_id = $current_slug !== '' ? ($hero_ids_by_slug[$current_slug] ?? $find_hero_by_slug($current_slug)) : 0;
$force_hero_id = $force_slug !== '' ? ($hero_ids_by_slug[$force_slug] ?? $find_hero_by_slug($force_slug)) : 0;
if ($current_hero_id) {
    update_option('kcj_current_hero_id', (int) $current_hero_id, false);
    $log[] = "rotation current=$current_slug (#$current_hero_id)";
}
update_option('kcj_force_hero_id', (int) $force_hero_id);
$log[] = $force_hero_id ? "rotation force=$force_slug (#$force_hero_id)" : 'rotation force=0';

$find_quote_by_key = static function (string $key): int {
    $key = sanitize_title($key);
    if ($key === '' || !post_type_exists('kcj_quote')) {
        return 0;
    }
    $q = get_posts([
        'post_type'      => 'kcj_quote',
        'post_status'    => ['publish', 'draft', 'private'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_key'       => '_kcj_quote_key',
        'meta_value'     => $key,
    ]);
    return $q ? (int) $q[0] : 0;
};

$quote_created = 0;
$quote_updated = 0;
$quotes_in = $pack['quotes'] ?? [];
if (post_type_exists('kcj_quote') && is_array($quotes_in)) {
    foreach ($quotes_in as $row) {
        if (!is_array($row)) {
            continue;
        }
        $key = sanitize_title((string) ($row['key'] ?? ''));
        $text = trim((string) ($row['text'] ?? ''));
        if ($key === '' || $text === '') {
            continue;
        }
        $title = (string) ($row['title'] ?? '');
        if ($title === '') {
            $work = (string) ($row['work'] ?? '');
            $title = trim($work . ': ' . wp_trim_words($text, 8, '…'));
            if ($title === '') {
                $title = $key;
            }
        }
        $existing_id = $find_quote_by_key($key);
        $data = [
            'post_type'    => 'kcj_quote',
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => $text,
        ];
        if ($existing_id) {
            $data['ID'] = $existing_id;
            $id = wp_update_post($data, true);
            if (is_wp_error($id)) {
                $log[] = "quote fail $key " . $id->get_error_message();
                continue;
            }
            $qid = $existing_id;
            $quote_updated++;
        } else {
            $id = wp_insert_post($data, true);
            if (is_wp_error($id) || !$id) {
                $log[] = "quote fail $key";
                continue;
            }
            $qid = (int) $id;
            $quote_created++;
        }
        update_post_meta($qid, '_kcj_quote_key', $key);
        update_post_meta($qid, '_kcj_speaker', sanitize_text_field((string) ($row['speaker'] ?? '')));
        update_post_meta($qid, '_kcj_work', sanitize_text_field((string) ($row['work'] ?? '')));
        update_post_meta($qid, '_kcj_country', strtoupper(sanitize_text_field((string) ($row['country'] ?? ''))));
        update_post_meta($qid, '_kcj_source_name', sanitize_text_field((string) ($row['source_name'] ?? '')));
        update_post_meta($qid, '_kcj_source_url', esc_url_raw((string) ($row['source_url'] ?? '')));
        update_post_meta($qid, '_kcj_tags', sanitize_text_field((string) ($row['tags'] ?? '')));
        update_post_meta($qid, '_kcj_note', sanitize_textarea_field((string) ($row['note'] ?? '')));
        update_post_meta($qid, '_kcj_verified', !empty($row['verified']) ? 1 : 0);
        update_post_meta($qid, '_kcj_locked', !empty($row['locked']) ? 1 : 0);
        update_post_meta($qid, '_kcj_locally_edited', !empty($row['edited']) ? 1 : 0);
        update_post_meta($qid, '_kcj_corpus_id', (int) ($row['corpus_id'] ?? 0));
    }
    $log[] = "quotes created=$quote_created updated=$quote_updated total_in_pack=" . count($quotes_in);
} elseif (!empty($quotes_in)) {
    $log[] = 'skip quotes (kcj_quote CPT missing — activate theme first)';
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
$hero_n = post_type_exists('kcj_hero') ? (int) (wp_count_posts('kcj_hero')->publish ?? 0) : 0;
$quote_n = post_type_exists('kcj_quote') ? (int) (wp_count_posts('kcj_quote')->publish ?? 0) : 0;
fwrite(STDOUT, implode("\n", $log) . "\n");
fwrite(
    STDOUT,
    "DONE tags=$tag_n product_cats=$cat_n pages=" . count($pages)
    . " heroes=$hero_n quotes=$quote_n products_untouched=1\n"
);

