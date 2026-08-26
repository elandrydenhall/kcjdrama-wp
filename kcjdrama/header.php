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
<header class="kcj-chrome<?php echo $is_home ? ' kcj-chrome--home' : ' kcj-chrome--page'; ?>">
    <?php if (!$is_home) : ?>
        <a class="kcj-mini-mark" href="<?php echo esc_url(home_url('/')); ?>">kcjdrama</a>
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
        <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts')) ?: home_url('/blog/')); ?>">Blog</a>
        <a href="<?php echo esc_url(kcj_page_url('stories')); ?>">Stories</a>
        <a href="<?php echo esc_url(kcj_page_url('about')); ?>">About</a>
        <a href="<?php echo esc_url(kcj_page_url('editorial-policy')); ?>">Editorial policy</a>
        <a href="<?php echo esc_url(kcj_page_url('victim-log')); ?>">Victim Log</a>
    </nav>
</header>
<script>
(function () {
    var btn = document.querySelector('.kcj-burger');
    var panel = document.getElementById('kcj-menu');
    if (!btn || !panel) return;
    btn.addEventListener('click', function () {
        var open = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', open ? 'false' : 'true');
        btn.setAttribute('aria-label', open ? 'Open menu' : 'Close menu');
        panel.hidden = open;
    });
})();
</script>
