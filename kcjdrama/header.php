<?php
if (!defined('ABSPATH')) {
    exit;
}
$is_home = is_front_page();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php
$skip = '#kcj-main';
if ($is_home) {
    $skip = '#kcj-stage';
} elseif (function_exists('is_page') && is_page('shop')) {
    $skip = '#kcj-shop-split';
}
?>
<a class="kcj-skip" href="<?php echo esc_attr($skip); ?>"><?php esc_html_e('Skip to content', 'kcjdrama'); ?></a>
<header class="kcj-chrome<?php echo $is_home ? ' kcj-chrome--home' : ' kcj-chrome--page'; ?>">
    <?php if (!$is_home) : ?>
        <a class="kcj-mini-mark" href="<?php echo esc_url(home_url('/')); ?>">kcjdrama</a>
    <?php endif; ?>

    <div class="kcj-chrome-end">
        <?php if (is_user_logged_in()) :
            $who = wp_get_current_user();
            $who_label = $who->display_name !== '' ? $who->display_name : $who->user_login;
            ?>
            <a class="kcj-account-link" href="<?php echo esc_url(kcj_sign_in_url()); ?>" title="<?php echo esc_attr(sprintf(__('Signed in as %s', 'kcjdrama'), $who_label)); ?>"><?php echo esc_html($who_label); ?></a>
        <?php else : ?>
            <a class="kcj-account-link" href="<?php echo esc_url(kcj_sign_in_url()); ?>"><?php esc_html_e('Guest', 'kcjdrama'); ?></a>
        <?php endif; ?>
        <button class="kcj-burger" type="button" aria-expanded="false" aria-controls="kcj-menu" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>
        <nav id="kcj-menu" class="kcj-menu" hidden>
            <a href="<?php echo esc_url(kcj_page_url('start-here')); ?>">Start here</a>
            <a href="<?php echo esc_url(kcj_page_url('soft')); ?>">Soft</a>
            <a href="<?php echo esc_url(kcj_page_url('mirror')); ?>">Mirror</a>
            <a href="<?php echo esc_url(kcj_page_url('shop')); ?>">Shop</a>
            <a href="<?php echo esc_url(kcj_page_url('tropes')); ?>">Tropes</a>
            <a href="<?php echo esc_url(kcj_page_url('syndromes')); ?>">Syndromes</a>
            <a href="<?php echo esc_url(kcj_page_url('glossary')); ?>">Glossary</a>
            <a href="<?php echo esc_url(kcj_page_url('essays')); ?>">Essays</a>
            <a href="<?php echo esc_url(kcj_field_notes_url()); ?>">Field notes</a>
            <a href="<?php echo esc_url(kcj_page_url('stories')); ?>">Stories</a>
            <a href="<?php echo esc_url(kcj_page_url('about')); ?>">About</a>
            <a href="<?php echo esc_url(kcj_page_url('editorial-policy')); ?>">Editorial policy</a>
            <a href="<?php echo esc_url(kcj_page_url('victim-log')); ?>">Victim Log</a>
            <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(kcj_sign_out_url(home_url(add_query_arg([])))); ?>"><?php esc_html_e('Sign out', 'kcjdrama'); ?></a>
            <?php else : ?>
                <a href="<?php echo esc_url(kcj_sign_in_url()); ?>"><?php esc_html_e('Sign in', 'kcjdrama'); ?></a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<script>
(function () {
    var chrome = document.querySelector('.kcj-chrome');
    var btn = document.querySelector('.kcj-burger');
    var panel = document.getElementById('kcj-menu');
    if (btn && panel) {
        btn.addEventListener('click', function () {
            var open = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', open ? 'false' : 'true');
            btn.setAttribute('aria-label', open ? 'Open menu' : 'Close menu');
            panel.hidden = open;
            if (!open && chrome) {
                chrome.classList.remove('kcj-chrome--away');
            }
        });
    }
    if (!chrome) return;

    var lastY = window.scrollY || 0;
    var ticking = false;
    var hideAfter = 12;

    function menuOpen() {
        return btn && btn.getAttribute('aria-expanded') === 'true';
    }

    function onScroll() {
        ticking = false;
        var y = window.scrollY || 0;
        if (menuOpen() || y <= 8) {
            chrome.classList.remove('kcj-chrome--away');
            lastY = y;
            return;
        }
        if (y < lastY) {
            chrome.classList.remove('kcj-chrome--away');
        } else if (y > lastY + hideAfter) {
            chrome.classList.add('kcj-chrome--away');
        }
        lastY = y;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(onScroll);
        }
    }, { passive: true });
})();
</script>
