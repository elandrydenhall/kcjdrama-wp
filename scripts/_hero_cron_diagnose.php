<?php
/**
 * Hero rotation cron diagnose + optional catch-up.
 * Usage: php _hero_cron_diagnose.php /path/to/wp [--run]
 */
if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$args = array_slice($argv, 1);
$do_run = in_array('--run', $args, true);
$args = array_values(array_filter($args, static function ($a) {
    return $a !== '--run';
}));
$wp = isset($args[0]) ? rtrim($args[0], "/\\") : dirname(__DIR__) . '/wordpress';

if (!is_file($wp . '/wp-load.php')) {
    fwrite(STDERR, "Bad WP root: $wp\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require $wp . '/wp-load.php';

$event = function_exists('wp_get_scheduled_event') ? wp_get_scheduled_event('kcj_rotate_hero') : null;
$next = wp_next_scheduled('kcj_rotate_hero');
$current = (int) get_option('kcj_current_hero_id', 0);
$force = (int) get_option('kcj_force_hero_id', 0);
$interval = function_exists('kcj_rotate_interval') ? kcj_rotate_interval() : (string) get_option('kcj_rotate_interval', '');

echo "wp=$wp\n";
echo "interval_option=$interval\n";
echo "force=$force current=$current\n";
if ($current) {
    $p = get_post($current);
    echo 'current_title=' . ($p ? $p->post_title : '?') . ' slug=' . ($p ? $p->post_name : '?') . "\n";
}
echo 'next=' . ($next ? gmdate('c', $next) : 'NONE') . "\n";
echo 'now=' . gmdate('c') . "\n";
echo 'overdue=' . ($next && $next < time() ? 'YES' : 'no') . "\n";
if (is_object($event)) {
    echo 'event_schedule=' . (isset($event->schedule) ? $event->schedule : '?') . "\n";
    echo 'event_interval_sec=' . (isset($event->interval) ? (int) $event->interval : 0) . "\n";
} else {
    echo "event_schedule=MISSING_OR_OLD_WP\n";
}
echo 'DISABLE_WP_CRON=' . ((defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? '1' : '0') . "\n";

if ($do_run) {
    $before = $current;
    if (function_exists('spawn_cron')) {
        spawn_cron();
    }
    $next_now = wp_next_scheduled('kcj_rotate_hero');
    if (!$next_now || $next_now < (time() - 60)) {
        if (function_exists('kcj_advance_hero')) {
            kcj_advance_hero();
        }
        wp_clear_scheduled_hook('kcj_rotate_hero');
        $sched = $interval !== '' ? $interval : 'hourly';
        wp_schedule_event(time() + HOUR_IN_SECONDS, $sched, 'kcj_rotate_hero');
        echo "action=forced_advance_and_reschedule\n";
    } else {
        echo "action=spawn_cron_only\n";
    }
    $after = (int) get_option('kcj_current_hero_id', 0);
    echo "before=$before after=$after\n";
    if ($after) {
        $p = get_post($after);
        echo 'after_title=' . ($p ? $p->post_title : '?') . "\n";
    }
    $next2 = wp_next_scheduled('kcj_rotate_hero');
    echo 'next_after=' . ($next2 ? gmdate('c', $next2) : 'NONE') . "\n";
}
