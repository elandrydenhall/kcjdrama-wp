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
            <?php
            $kcj_menu_items = [
                ['url' => kcj_page_url('start-here'), 'label' => 'Start here', 'match' => 'start-here'],
                ['url' => kcj_page_url('soft'), 'label' => 'Soft', 'match' => 'soft'],
                ['url' => kcj_page_url('mirror'), 'label' => 'Mirror', 'match' => 'mirror'],
                ['url' => kcj_page_url('shop'), 'label' => 'Shop', 'match' => 'shop'],
                ['url' => kcj_page_url('tropes'), 'label' => 'Tropes', 'match' => 'tropes'],
                ['url' => kcj_page_url('syndromes'), 'label' => 'Syndromes', 'match' => 'syndromes'],
                ['url' => kcj_page_url('glossary'), 'label' => 'Glossary', 'match' => 'glossary'],
                ['url' => kcj_page_url('essays'), 'label' => 'Essays', 'match' => 'essays'],
                ['url' => kcj_field_notes_url(), 'label' => 'Field notes', 'match' => 'field-notes'],
                ['url' => kcj_page_url('stories'), 'label' => 'Stories', 'match' => 'stories'],
                ['url' => kcj_page_url('about'), 'label' => 'About', 'match' => 'about'],
                ['url' => kcj_page_url('editorial-policy'), 'label' => 'Editorial policy', 'match' => 'editorial-policy'],
                ['url' => kcj_page_url('victim-log'), 'label' => 'Victim Log', 'match' => 'victim-log'],
            ];
            $here_path = trim((string) wp_parse_url(home_url(add_query_arg([])), PHP_URL_PATH), '/');
            foreach ($kcj_menu_items as $item) {
                $item_path = trim((string) wp_parse_url($item['url'], PHP_URL_PATH), '/');
                $is_current = ($item_path !== '' && ($here_path === $item_path || str_starts_with($here_path . '/', $item_path . '/')));
                if (!$is_current && !empty($item['match'])) {
                    $is_current = is_page($item['match'])
                        || ($item['match'] === 'field-notes' && (is_home() || is_category() || is_singular('post')))
                        || ($item['match'] === 'shop' && function_exists('is_shop') && (is_shop() || is_product() || is_product_taxonomy()));
                }
                printf(
                    '<a href="%s"%s>%s</a>',
                    esc_url($item['url']),
                    $is_current ? ' class="is-current" aria-current="page"' : '',
                    esc_html($item['label'])
                );
            }
            if (is_user_logged_in()) :
                ?>
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
    var menuHome = panel ? panel.parentNode : null;

    function placeMenu() {
        if (!btn || !panel || panel.hidden) return;
        var rect = btn.getBoundingClientRect();
        var vv = window.visualViewport;
        var viewH = (vv && vv.height) ? vv.height : window.innerHeight;
        var viewW = (vv && vv.width) ? vv.width : window.innerWidth;
        var top = Math.max(8, Math.round(rect.bottom + 8));
        var right = Math.max(8, Math.round(viewW - rect.right));
        var maxH = Math.max(120, Math.round(viewH - top - 20));
        panel.style.position = 'fixed';
        panel.style.zIndex = '1000';
        panel.style.top = top + 'px';
        panel.style.right = right + 'px';
        panel.style.left = 'auto';
        panel.style.bottom = 'auto';
        panel.style.height = 'fit-content';
        panel.style.maxHeight = maxH + 'px';
        panel.style.overflowY = 'scroll';
        panel.style.touchAction = 'pan-y';
        panel.style.webkitOverflowScrolling = 'touch';
        panel.style.pointerEvents = 'auto';
    }

    function openMenu() {
        if (!btn || !panel) return;
        btn.setAttribute('aria-expanded', 'true');
        btn.setAttribute('aria-label', 'Close menu');
        panel.hidden = false;
        /* Brave/Android: scrolling fails inside pointer-events:none chrome — park on body. */
        if (panel.parentNode !== document.body) {
            document.body.appendChild(panel);
        }
        document.body.classList.add('kcj-menu-open');
        if (chrome) chrome.classList.remove('kcj-chrome--away');
        placeMenu();
        var cur = panel.querySelector('a.is-current, a[aria-current="page"]');
        if (cur && typeof cur.scrollIntoView === 'function') {
            cur.scrollIntoView({ block: 'nearest' });
        }
    }

    function closeMenu() {
        if (!btn || !panel) return;
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Open menu');
        panel.hidden = true;
        document.body.classList.remove('kcj-menu-open');
        if (menuHome && panel.parentNode !== menuHome) {
            menuHome.appendChild(panel);
        }
        panel.style.top = '';
        panel.style.right = '';
        panel.style.bottom = '';
        panel.style.maxHeight = '';
        panel.style.height = '';
        panel.style.overflowY = '';
        panel.style.touchAction = '';
        panel.style.zIndex = '';
        panel.style.pointerEvents = '';
    }

    if (btn && panel) {
        btn.addEventListener('click', function () {
            if (btn.getAttribute('aria-expanded') === 'true') {
                closeMenu();
            } else {
                openMenu();
            }
        });
        /* Keep touch scrolling on the panel; don't let the page steal the gesture. */
        panel.addEventListener('touchstart', function (e) { e.stopPropagation(); }, { passive: true });
        panel.addEventListener('touchmove', function (e) { e.stopPropagation(); }, { passive: true });
        window.addEventListener('resize', placeMenu);
        window.addEventListener('orientationchange', function () {
            window.setTimeout(placeMenu, 50);
        });
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', placeMenu);
            window.visualViewport.addEventListener('scroll', placeMenu);
        }
    }
    if (!chrome) return;

    var lastY = window.scrollY || 0;
    var ticking = false;
    var hideAfter = 12;

    function menuOpen() {
        return document.body.classList.contains('kcj-menu-open')
            || (btn && btn.getAttribute('aria-expanded') === 'true');
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
