<?php
/**
 * Support form + trust URL redirects (/contact, old privacy slug).
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', function () {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    if ($path === 'contact' || is_page('contact')) {
        wp_safe_redirect(kcj_page_url('support'), 301);
        exit;
    }
    if ($path === 'privacy-policy-2') {
        wp_safe_redirect(kcj_page_url('privacy-policy'), 301);
        exit;
    }
}, 5);

add_action('template_redirect', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || empty($_POST['kcj_support'])) {
        return;
    }
    if (!isset($_POST['kcj_support_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_support_nonce'])), 'kcj_support')) {
        wp_die(esc_html__('Invalid form submission.', 'kcjdrama'));
    }

    $target = wp_get_referer() ?: home_url('/support/');
    $hp = isset($_POST['kcj_hp']) ? trim((string) wp_unslash($_POST['kcj_hp'])) : '';
    $ts = isset($_POST['kcj_ts']) ? (int) $_POST['kcj_ts'] : 0;
    if ($hp !== '' || ($ts > 0 && (time() - $ts) < 3)) {
        wp_safe_redirect(add_query_arg('contact', 'sent', $target));
        exit;
    }

    if (function_exists('kcj_desk_rate_ok') && !kcj_desk_rate_ok('support')) {
        wp_safe_redirect(add_query_arg('contact', 'rate', $target));
        exit;
    }

    $name = sanitize_text_field(wp_unslash($_POST['contact_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['contact_email'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['contact_message'] ?? ''));
    $ok = ($name !== '' && is_email($email) && $message !== ''
        && strlen($name) <= 120 && strlen($message) <= 5000);

    $sent = false;
    if ($ok) {
        $to = 'support@kcjdrama.com';
        $sent = (bool) wp_mail(
            $to,
            'kcjdrama support from ' . $name,
            $message . "\n\nFrom: {$name} <{$email}>",
            ['Reply-To: ' . $email]
        );
    }

    wp_safe_redirect(add_query_arg('contact', $sent ? 'sent' : 'error', $target));
    exit;
});
