<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    $interval = kcj_rotate_interval();
    $next = wp_next_scheduled('kcj_rotate_hero');
    $event = function_exists('wp_get_scheduled_event') ? wp_get_scheduled_event('kcj_rotate_hero') : null;
    $schedule_ok = is_object($event) && isset($event->schedule) && $event->schedule === $interval;

    if (!$next || !$schedule_ok) {
        wp_clear_scheduled_hook('kcj_rotate_hero');
        wp_schedule_event(time() + HOUR_IN_SECONDS, $interval, 'kcj_rotate_hero');
    }
});

add_action('kcj_rotate_hero', 'kcj_advance_hero');

add_action('update_option_kcj_rotate_interval', function ($old, $new) {
    if ($old === $new) {
        return;
    }
    wp_clear_scheduled_hook('kcj_rotate_hero');
    wp_schedule_event(time() + HOUR_IN_SECONDS, $new, 'kcj_rotate_hero');
}, 10, 2);

function kcj_rotate_interval() {
    $allowed = ['hourly', 'twicedaily', 'daily'];
    $value = get_option('kcj_rotate_interval', 'hourly');
    return in_array($value, $allowed, true) ? $value : 'hourly';
}

function kcj_advance_hero() {
    if ((int) get_option('kcj_force_hero_id', 0) > 0) {
        update_option('kcj_hero_last_rotate_note', 'skipped_force_pin', false);
        return;
    }

    $ids = get_posts([
        'post_type'      => 'kcj_hero',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    if (!$ids) {
        return;
    }

    // MySQL/wpdb may return string IDs; normalize so strict search cannot stick on first.
    $ids = array_map('intval', $ids);

    $current = (int) get_option('kcj_current_hero_id', 0);
    $index = array_search($current, $ids, true);
    $next = ($index === false) ? $ids[0] : $ids[($index + 1) % count($ids)];
    update_option('kcj_current_hero_id', (int) $next, false);
    update_option('kcj_hero_last_rotate_at', time(), false);
    update_option('kcj_hero_last_rotate_note', 'ok:' . (int) $current . '->' . (int) $next, false);
}
