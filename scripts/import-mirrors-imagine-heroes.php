<?php
/**
 * Import C:\Scripts\Mirrors Imagine\*.jpg as kcj_hero posts.
 * Each gets Soft + Mirror hotspot placeholders (operator places coords in wp-admin).
 *
 * Usage: .\scripts\wp.ps1 eval-file scripts/import-mirrors-imagine-heroes.php
 */

if (!defined('ABSPATH')) {
    exit(1);
}

$dir = 'C:\\Scripts\\Mirrors Imagine';
if (!is_dir($dir)) {
    fwrite(STDERR, "Missing directory: {$dir}\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$files = glob($dir . DIRECTORY_SEPARATOR . '*.jpg') ?: [];
sort($files, SORT_NATURAL | SORT_FLAG_CASE);

$soft = '/soft/';
$mirror = '/mirror/';

// Placeholder boxes — operator will reposition in Heroes editor.
$hotspots = [
    [
        'id'    => 'soft',
        'role'  => 'soft',
        'x'     => 12.0,
        'y'     => 80.0,
        'w'     => 16.0,
        'h'     => 8.0,
        'href'  => $soft,
        'label' => 'Enter Soft',
    ],
    [
        'id'    => 'mirror',
        'role'  => 'mirror',
        'x'     => 62.0,
        'y'     => 80.0,
        'w'     => 16.0,
        'h'     => 8.0,
        'href'  => $mirror,
        'label' => 'Enter Mirror',
    ],
];

$created = 0;
$skipped = 0;
$failed = 0;

foreach ($files as $file) {
    $base = basename($file, '.jpg');
    $title = trim(preg_replace('/\s+/', ' ', str_replace('-', ' ', $base)));
    $slug = sanitize_title($base);

    $existing = get_posts([
        'post_type'      => 'kcj_hero',
        'name'           => $slug,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);
    if ($existing) {
        echo "SKIP existing slug={$slug} id={$existing[0]}\n";
        $skipped++;
        continue;
    }

    $tmp = wp_tempnam(basename($file));
    if (!$tmp || !@copy($file, $tmp)) {
        echo "FAIL copy {$file}\n";
        $failed++;
        if ($tmp) {
            @unlink($tmp);
        }
        continue;
    }

    $file_array = [
        'name'     => basename($file),
        'tmp_name' => $tmp,
    ];
    $attach_id = media_handle_sideload($file_array, 0, $title);
    if (is_wp_error($attach_id)) {
        @unlink($tmp);
        echo 'FAIL media ' . $base . ': ' . $attach_id->get_error_message() . "\n";
        $failed++;
        continue;
    }

    $hero_id = wp_insert_post([
        'post_title'  => $title,
        'post_name'   => $slug,
        'post_type'   => 'kcj_hero',
        'post_status' => 'publish',
        'menu_order'  => 0,
    ], true);

    if (is_wp_error($hero_id) || !$hero_id) {
        $msg = is_wp_error($hero_id) ? $hero_id->get_error_message() : 'unknown';
        echo "FAIL post {$base}: {$msg}\n";
        $failed++;
        continue;
    }

    set_post_thumbnail($hero_id, $attach_id);
    update_post_meta($hero_id, '_kcj_has_baked_menu', '');
    update_post_meta($hero_id, '_kcj_hotspots', $hotspots);

    if (!(int) get_option('kcj_current_hero_id', 0)) {
        update_option('kcj_current_hero_id', (int) $hero_id, false);
    }

    echo "OK #{$hero_id} {$title}\n";
    $created++;
}

echo "\nDone. created={$created} skipped={$skipped} failed={$failed} total_files=" . count($files) . "\n";
