<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_switch_theme', function () {
    delete_option('kcj_seeded');
});

add_action('init', function () {
    if (!get_option('kcj_seeded')) {
        kcj_seed_content();
        update_option('kcj_seeded', 1);
    }
}, 20);

function kcj_seed_content() {
    if (!get_option('permalink_structure')) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();
    }
    kcj_seed_pages();
    kcj_seed_hero_one();
}

function kcj_seed_pages() {
    $pages = [
        'soft'             => ['title' => 'Soft', 'content' => '<p>Some inheritances change everything. Love stays.</p><p>Soft merch and craft commentary live on this rail — shop the full split under Shop.</p>'],
        'mirror'           => ['title' => 'Mirror', 'content' => '<p>We roast the tropes so you don\'t have to.</p><p>Mirror merch and syndrome energy live on this rail — shop the full split under Shop.</p>'],
        'shop'             => ['title' => 'Shop', 'content' => ''],
        'about'            => ['title' => 'About', 'content' => '<p>kcjdrama sits between the romance you came for and the satire you needed.</p>'],
        'stories'          => ['title' => 'Stories', 'content' => '<p>Stories live here.</p>'],
        'about-the-roast'  => ['title' => 'About the Roast', 'content' => '<p>The Mirror side is the roast. Same tropes, no plot armor.</p>'],
        'victim-log'       => ['title' => 'Victim Log', 'content' => '<p>A running list of tropes that did not survive.</p>'],
        'faq'              => ['title' => 'FAQ', 'content' => '<p>Pass OR Fail for the Soft desk.</p>'],
    ];

    foreach ($pages as $slug => $data) {
        if (get_page_by_path($slug)) {
            continue;
        }
        wp_insert_post([
            'post_title'   => $data['title'],
            'post_name'    => $slug,
            'post_content' => $data['content'],
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}

function kcj_seed_hero_one() {
    $existing = get_posts([
        'post_type'      => 'kcj_hero',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);
    if ($existing) {
        if (!(int) get_option('kcj_current_hero_id', 0)) {
            update_option('kcj_current_hero_id', (int) $existing[0], false);
        }
        return;
    }

    $file = KCJ_PATH . '/assets/seed/home-01.webp';
    if (!file_exists($file)) {
        $file = KCJ_PATH . '/assets/seed/home-01.jpg';
    }
    if (!file_exists($file)) {
        return;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = wp_tempnam(basename($file));
    copy($file, $tmp);
    $file_array = [
        'name'     => basename($file),
        'tmp_name' => $tmp,
    ];
    $attach_id = media_handle_sideload($file_array, 0, 'Soft | Mirror 01');
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        return;
    }

    $home = '/';
    $soft = '/soft/';
    $mirror = '/mirror/';

    $hero_id = wp_insert_post([
        'post_title'  => 'Soft | Mirror 01',
        'post_type'   => 'kcj_hero',
        'post_status' => 'publish',
        'menu_order'  => 1,
    ]);
    if (is_wp_error($hero_id) || !$hero_id) {
        return;
    }

    set_post_thumbnail($hero_id, $attach_id);
    update_post_meta($hero_id, '_kcj_has_baked_menu', '');
    update_post_meta($hero_id, '_kcj_hotspots', [
        ['id' => 'logo', 'role' => 'logo', 'x' => 40.0, 'y' => 0.0, 'w' => 20.0, 'h' => 7.5, 'href' => $home, 'label' => 'kcjdrama'],
        ['id' => 'soft', 'role' => 'soft', 'x' => 5.4, 'y' => 54.2, 'w' => 16.2, 'h' => 6.8, 'href' => $soft, 'label' => 'Enter Soft'],
        ['id' => 'mirror', 'role' => 'mirror', 'x' => 62.8, 'y' => 88.4, 'w' => 21.6, 'h' => 7.6, 'href' => $mirror, 'label' => 'Enter Mirror'],
    ]);
    update_option('kcj_current_hero_id', (int) $hero_id, false);
    update_option('kcj_rotate_interval', 'hourly');
}
