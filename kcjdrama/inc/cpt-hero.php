<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    register_post_type('kcj_hero', [
        'labels' => [
            'name'               => 'Heroes',
            'singular_name'      => 'Hero',
            'add_new'            => 'Add Hero',
            'add_new_item'       => 'Add Hero',
            'edit_item'          => 'Edit Hero',
            'new_item'           => 'New Hero',
            'view_item'          => 'View Hero',
            'search_items'       => 'Search Heroes',
            'not_found'          => 'No heroes found',
            'not_found_in_trash' => 'No heroes in trash',
            'all_items'          => 'All Heroes',
            'menu_name'          => 'Heroes',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-format-image',
        'supports'            => ['title', 'thumbnail', 'page-attributes'],
        'has_archive'         => false,
        'exclude_from_search' => true,
        'rewrite'             => false,
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'delete_with_user'    => false,
    ]);
});

add_filter('manage_kcj_hero_posts_columns', function ($cols) {
    $new = [];
    $new['cb'] = $cols['cb'];
    $new['thumb'] = 'Image';
    $new['title'] = $cols['title'];
    $new['baked'] = 'Baked menu';
    $new['spots'] = 'Hotspots';
    $new['order'] = 'Order';
    $new['date'] = $cols['date'];
    return $new;
});

add_action('manage_kcj_hero_posts_custom_column', function ($col, $post_id) {
    if ($col === 'thumb') {
        echo get_the_post_thumbnail($post_id, [72, 40]);
        return;
    }
    if ($col === 'baked') {
        echo get_post_meta($post_id, '_kcj_has_baked_menu', true) ? 'Yes' : 'No';
        return;
    }
    if ($col === 'spots') {
        $spots = get_post_meta($post_id, '_kcj_hotspots', true);
        if (!is_array($spots)) {
            $spots = json_decode((string) $spots, true);
        }
        echo is_array($spots) ? count($spots) : 0;
        return;
    }
    if ($col === 'order') {
        echo (int) get_post_field('menu_order', $post_id);
    }
}, 10, 2);
