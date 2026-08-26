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

$featured = function_exists('kcj_featured_merch')
    ? kcj_featured_merch(8)
    : [];

get_header();
?>
<main class="kcj-stage" id="kcj-stage">
    <?php if ($hero) : ?>
        <div class="kcj-hero">
            <img
                src="<?php echo esc_url($hero['image_url']); ?>"
                alt="<?php echo esc_attr($hero['alt']); ?>"
                width="1920"
                height="1080"
            >
            <?php foreach ($hero['hotspots'] as $spot) :
                $x = isset($spot['x']) ? (float) $spot['x'] : 0;
                $y = isset($spot['y']) ? (float) $spot['y'] : 0;
                $w = isset($spot['w']) ? (float) $spot['w'] : 10;
                $h = isset($spot['h']) ? (float) $spot['h'] : 6;
                $href = isset($spot['href']) ? kcj_local_href($spot['href']) : '#';
                $label = isset($spot['label']) ? $spot['label'] : 'Open';
                ?>
                <a
                    class="kcj-hotspot"
                    href="<?php echo esc_url($href); ?>"
                    style="left:<?php echo esc_attr($x); ?>%;top:<?php echo esc_attr($y); ?>%;width:<?php echo esc_attr($w); ?>%;height:<?php echo esc_attr($h); ?>%;"
                    aria-label="<?php echo esc_attr($label); ?>"
                ></a>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="kcj-empty">
            <p>No hero images yet. Add one under <strong>Heroes</strong> in wp-admin.</p>
        </div>
    <?php endif; ?>
</main>

<section class="kcj-below" aria-label="<?php esc_attr_e('Shop and editorial', 'gsolo-kcjdrama'); ?>">
    <?php /* Soft/Mirror intro + Enter links live on /soft/ and /mirror/ — hero hotspots already enter those worlds. */ ?>

    <div class="kcj-below-band">
        <div class="kcj-below-band-head">
            <h2>Shop the split</h2>
            <p>One catalog. Two moods. Soft merch for the sincere desk — Mirror merch for the roast.</p>
        </div>

        <nav class="kcj-rail-toggle" aria-label="<?php esc_attr_e('Shop rail', 'gsolo-kcjdrama'); ?>">
            <a href="<?php echo esc_url(add_query_arg('rail', 'soft', $shop_url)); ?>" data-rail="soft">Soft</a>
            <span class="kcj-rail-sep" aria-hidden="true">|</span>
            <a class="is-active" href="<?php echo esc_url($shop_url); ?>" data-rail="all">Everything</a>
            <span class="kcj-rail-sep" aria-hidden="true">|</span>
            <a href="<?php echo esc_url(add_query_arg('rail', 'mirror', $shop_url)); ?>" data-rail="mirror">Mirror</a>
        </nav>

        <ul class="kcj-product-wall">
            <?php foreach ($featured as $item) :
                $href = !empty($item['url']) ? $item['url'] : $shop_url;
                $media_style = !empty($item['image'])
                    ? 'background-image:url(' . esc_url($item['image']) . ');background-size:cover;background-position:center;'
                    : '';
                ?>
                <li class="kcj-product kcj-product--<?php echo esc_attr($item['rail']); ?>">
                    <a class="kcj-product-link" href="<?php echo esc_url($href); ?>">
                        <span class="kcj-product-media" style="<?php echo esc_attr($media_style); ?>" aria-hidden="true"></span>
                        <span class="kcj-product-body">
                            <span class="kcj-product-rail"><?php echo esc_html($item['rail_label']); ?></span>
                            <span class="kcj-product-title"><?php echo esc_html($item['title']); ?></span>
                            <span class="kcj-product-meta"><?php echo esc_html($item['meta']); ?></span>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
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
