<?php
if (!defined('ABSPATH')) {
    exit;
}

$hero = kcj_get_current_hero();
if (!empty($_GET['hotspots'])) {
    add_filter('body_class', function ($classes) {
        $classes[] = 'kcj-debug-hotspots';
        return $classes;
    });
}

$shop_url = kcj_page_url('shop');
$tropes_url = kcj_page_url('tropes');
$syndromes_url = kcj_page_url('syndromes');
$home_url = home_url('/');
$rail = function_exists('kcj_shop_rail') ? kcj_shop_rail() : 'all';

get_header();
?>
<main class="kcj-stage" id="kcj-stage">
    <?php if ($hero) :
        $hero_src = $hero['image_url'];
        $hero_alt = $hero['alt'];
        $spots = is_array($hero['hotspots']) ? $hero['hotspots'] : [];
        $logo = kcj_hero_logo_spot($spots);
        ?>
        <div class="kcj-hero kcj-hero--desktop">
            <div class="kcj-hero-plate">
                <img
                    src="<?php echo esc_url($hero_src); ?>"
                    alt="<?php echo esc_attr($hero_alt); ?>"
                    width="1920"
                    height="1080"
                    fetchpriority="high"
                >
                <?php
                kcj_render_logo_link($logo, 'desktop');
                foreach ($spots as $spot) {
                    if (is_array($spot)) {
                        kcj_render_hotspot($spot, 'full');
                    }
                }
                ?>
            </div>
        </div>

        <div class="kcj-hero kcj-hero--stack">
            <?php kcj_render_logo_link($logo, 'stack'); ?>
            <div class="kcj-hero-panel kcj-hero-panel--soft">
                <div class="kcj-hero-crop">
                    <img
                        src="<?php echo esc_url($hero_src); ?>"
                        alt=""
                        width="1920"
                        height="1080"
                        decoding="async"
                    >
                </div>
                <?php
                foreach ($spots as $spot) {
                    if (is_array($spot)) {
                        kcj_render_hotspot($spot, 'soft');
                    }
                }
                ?>
            </div>
            <div class="kcj-hero-panel kcj-hero-panel--mirror">
                <div class="kcj-hero-crop">
                    <img
                        src="<?php echo esc_url($hero_src); ?>"
                        alt=""
                        width="1920"
                        height="1080"
                        loading="lazy"
                        decoding="async"
                    >
                </div>
                <?php
                foreach ($spots as $spot) {
                    if (is_array($spot)) {
                        kcj_render_hotspot($spot, 'mirror');
                    }
                }
                ?>
            </div>
        </div>
    <?php else : ?>
        <div class="kcj-empty">
            <p>No hero images yet. Add one under <strong>Heroes</strong> in wp-admin.</p>
        </div>
    <?php endif; ?>
</main>

<section class="kcj-below" aria-label="<?php esc_attr_e('Shop and editorial', 'gsolo-kcjdrama'); ?>">
    <?php /* Soft/Mirror intro + Enter links live on /soft/ and /mirror/ — hero hotspots already enter those worlds. */ ?>

    <div class="kcj-below-band" id="kcj-shop-split">
        <div class="kcj-below-band-head">
            <h2>Shop the split</h2>
            <p>One catalog. Two moods. Soft merch for the sincere desk — Mirror merch for the roast.</p>
        </div>

        <?php
        if (function_exists('kcj_render_rail_toggle')) {
            kcj_render_rail_toggle($home_url, $rail);
        }
        ?>

        <?php get_template_part('template-parts/home-search'); ?>

        <?php
        if (function_exists('kcj_render_merch_wall')) {
            kcj_render_merch_wall('home');
        }
        ?>
        <p class="kcj-wall-shop-link">
            <a
                data-kcj-shop-link
                data-soft="<?php echo esc_url(kcj_rail_url('soft', $shop_url)); ?>"
                data-all="<?php echo esc_url(kcj_rail_url('all', $shop_url)); ?>"
                data-mirror="<?php echo esc_url(kcj_rail_url('mirror', $shop_url)); ?>"
                href="<?php echo esc_url(function_exists('kcj_rail_url') ? kcj_rail_url($rail, $shop_url) : $shop_url); ?>"
            >
                <span data-kcj-shop-link-label>
                <?php
                if ($rail === 'soft') {
                    esc_html_e('See all Soft in Shop', 'gsolo-kcjdrama');
                } elseif ($rail === 'mirror') {
                    esc_html_e('See all Mirror in Shop', 'gsolo-kcjdrama');
                } else {
                    esc_html_e('Open full Shop', 'gsolo-kcjdrama');
                }
                ?>
                </span>
            </a>
        </p>
    </div>

    <div class="kcj-below-band">
        <div class="kcj-below-band-head">
            <h2>Read both brains</h2>
            <p>Editorial on-ramps that keep Soft craft and Mirror chaos in conversation.</p>
        </div>
        <div class="kcj-teaser-grid">
            <a class="kcj-teaser kcj-teaser--soft" href="<?php echo esc_url($tropes_url); ?>">
                <p class="kcj-teaser-kicker">Soft · Tropes</p>
                <h3>Trope encyclopedia</h3>
                <p>Shared patterns with Soft craft notes — why the device works before the joke lands.</p>
            </a>
            <a class="kcj-teaser kcj-teaser--mirror" href="<?php echo esc_url($syndromes_url); ?>">
                <p class="kcj-teaser-kicker">Mirror · Syndromes</p>
                <h3>Syndrome clinic</h3>
                <p>Name the affliction. Second-lead magnetic, OST tear reflex, slow-burn starvation…</p>
            </a>
        </div>
    </div>

    <div class="kcj-gold-band">
        <h2>Profit loves a dual audience</h2>
        <p>Wear Soft sincerity or Mirror chaos. Same store. One gold door.</p>
        <a class="kcj-btn kcj-btn--gold" href="<?php echo esc_url($shop_url); ?>">Shop the split</a>
    </div>
</section>
<?php
get_footer();
