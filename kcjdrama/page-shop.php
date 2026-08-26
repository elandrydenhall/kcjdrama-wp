<?php
/**
 * Template Name: Shop (Soft | Mirror rails)
 * Dual-rail shop shell — SatireSmart wall DNA, KCJ Soft/Mirror skins.
 * Pulls live Woo catalog (Soft / Mirror rails via product categories).
 */
if (!defined('ABSPATH')) {
    exit;
}

$rail = kcj_shop_rail();
$shop_url = kcj_page_url('shop');
$items = function_exists('kcj_featured_merch') ? kcj_featured_merch(24) : [];

$blurbs = [
    'soft'   => 'Comfort merch for the sincere desk — soft ink, champagne accents, clean story energy.',
    'mirror' => 'Roast merch for the meme-fluent half — violet circuit, orchid heat, no plot armor.',
    'all'    => 'One catalog. Soft porcelain romance and Mirror violet chaos under the same gold door.',
];

get_header();
?>
<main class="kcj-shop kcj-shop--<?php echo esc_attr($rail); ?>">
    <div class="kcj-shop-inner">
        <header class="kcj-shop-identity">
            <p class="kcj-page-kicker"><?php echo $rail === 'mirror' ? 'Mirror Merch' : ($rail === 'soft' ? 'Soft Merch' : 'Shop the Split'); ?></p>
            <h1><?php echo esc_html(get_the_title()); ?></h1>
            <p><?php echo esc_html($blurbs[$rail]); ?></p>
        </header>

        <nav class="kcj-rail-toggle" aria-label="<?php esc_attr_e('Shop rail', 'gsolo-kcjdrama'); ?>">
            <a
                class="<?php echo $rail === 'soft' ? 'is-active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('rail', 'soft', $shop_url)); ?>"
                data-rail="soft"
                <?php echo $rail === 'soft' ? 'aria-current="page"' : ''; ?>
            >Soft</a>
            <span class="kcj-rail-sep" aria-hidden="true">|</span>
            <a
                class="<?php echo $rail === 'all' ? 'is-active' : ''; ?>"
                href="<?php echo esc_url($shop_url); ?>"
                data-rail="all"
                <?php echo $rail === 'all' ? 'aria-current="page"' : ''; ?>
            >Everything</a>
            <span class="kcj-rail-sep" aria-hidden="true">|</span>
            <a
                class="<?php echo $rail === 'mirror' ? 'is-active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg('rail', 'mirror', $shop_url)); ?>"
                data-rail="mirror"
                <?php echo $rail === 'mirror' ? 'aria-current="page"' : ''; ?>
            >Mirror</a>
        </nav>

        <?php if ($items) : ?>
            <ul class="kcj-product-wall" data-kcj-product-list>
                <?php foreach ($items as $item) :
                    $href = !empty($item['url']) ? $item['url'] : $shop_url;
                    ?>
                    <li class="kcj-product kcj-product--<?php echo esc_attr($item['rail']); ?>">
                        <a class="kcj-product-link" href="<?php echo esc_url($href); ?>">
                            <?php kcj_render_product_media($item); ?>
                            <span class="kcj-product-body">
                                <span class="kcj-product-rail"><?php echo esc_html($item['rail_label']); ?></span>
                                <span class="kcj-product-title"><?php echo esc_html($item['title']); ?></span>
                                <span class="kcj-product-meta"><?php echo esc_html($item['meta']); ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else : ?>
            <div class="kcj-shop-empty">
                <p>No pieces on this rail yet. Soft and Mirror collections are being printed into existence.</p>
            </div>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
