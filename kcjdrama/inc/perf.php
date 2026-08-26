<?php
/**
 * Front-end load: FCP/LCP. No canvas, no DPR math.
 * Homepage critical CSS is the @critical-start/end slice of front.css.
 */
if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function () {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
    remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
    remove_action('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');
});
add_filter('emoji_svg_url', '__return_false');
add_filter('tiny_mce_plugins', function ($plugins) {
    if (!is_array($plugins)) {
        return [];
    }
    return array_values(array_diff($plugins, ['wpemoji']));
});

add_filter('wp_resource_hints', function ($urls, $relation_type) {
    if ($relation_type === 'preconnect') {
        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = [
            'href'        => 'https://fonts.gstatic.com',
            'crossorigin' => 'anonymous',
        ];
    }
    return $urls;
}, 10, 2);

add_action('wp_head', 'kcj_print_lcp_preload', 2);
add_action('wp_head', 'kcj_print_critical_css', 3);

function kcj_print_lcp_preload() {
    if (!is_front_page()) {
        return;
    }
    $hero = kcj_get_current_hero();
    if (!$hero || empty($hero['image_url'])) {
        return;
    }
    $href = $hero['image_url'];
    $type = 'image/webp';
    $path = (string) wp_parse_url($href, PHP_URL_PATH);
    if (preg_match('/\.jpe?g$/i', $path)) {
        $type = 'image/jpeg';
    } elseif (preg_match('/\.png$/i', $path)) {
        $type = 'image/png';
    }
    printf(
        '<link rel="preload" as="image" href="%s" type="%s" fetchpriority="high">' . "\n",
        esc_url($href),
        esc_attr($type)
    );
}

function kcj_print_critical_css() {
    if (!is_front_page()) {
        return;
    }
    $css = kcj_critical_css_slice();
    if ($css === '') {
        return;
    }
    echo '<style id="kcj-critical">' . $css . "</style>\n";
}

function kcj_critical_css_slice() {
    $file = KCJ_PATH . '/assets/css/front.css';
    if (!is_readable($file)) {
        return '';
    }
    $raw = file_get_contents($file);
    if ($raw === false) {
        return '';
    }
    if (!preg_match('/\/\*\s*@critical-start\s*\*\/(.*?)\/\*\s*@critical-end\s*\*\//s', $raw, $m)) {
        return '';
    }
    $css = $m[1];
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    return trim($css);
}

add_filter('style_loader_tag', function ($html, $handle, $href) {
    if (is_admin() || !is_front_page()) {
        return $html;
    }
    if ($handle !== 'kcj-front' && $handle !== 'kcj-fonts') {
        return $html;
    }
    $href = esc_url($href);
    return sprintf(
        "<link rel='stylesheet' id='%s-css' href='%s' media='print' onload=\"this.media='all'\">\n<noscript><link rel='stylesheet' href='%s'></noscript>\n",
        esc_attr($handle),
        $href,
        $href
    );
}, 10, 3);
