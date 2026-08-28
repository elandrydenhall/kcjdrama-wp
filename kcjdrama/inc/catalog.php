<?php
/**
 * Shop catalog chrome — SatireSmart /catalog/ structure with Soft|Everything|Mirror.
 */
if (!defined('ABSPATH')) {
    exit;
}

function kcj_catalog_get_slug($key) {
    return isset($_GET[$key]) ? sanitize_title(wp_unslash($_GET[$key])) : '';
}

/**
 * @return array{rail:string,color:string,size:string,orderby:string,s:string}
 */
function kcj_catalog_filters() {
    $rail = isset($_GET['rail']) ? strtolower((string) wp_unslash($_GET['rail'])) : 'all';
    if (!in_array($rail, ['soft', 'mirror', 'all'], true)) {
        $rail = 'all';
    }
    $orderby = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    // Shop search uses post_type=product on /. Only treat s as catalog when we're on shop or product search.
    if ($search !== '' && !(is_page('shop') || is_search() || (isset($_GET['post_type']) && $_GET['post_type'] === 'product'))) {
        // Still allow explicit catalog REST / slice callers to pass s.
    }
    return [
        'rail'    => $rail,
        'color'   => kcj_catalog_get_slug('color'),
        'size'    => kcj_catalog_get_slug('size'),
        'orderby' => $orderby,
        's'       => $search,
    ];
}

/**
 * Build a /shop/ URL keeping other filters.
 *
 * @param array $overrides Keys: rail, color, size, orderby, s, clear (bool).
 */
function kcj_catalog_url(array $overrides = []) {
    $shop = get_page_by_path('shop');
    $base = $shop ? get_permalink($shop) : home_url('/shop/');

    if (!empty($overrides['clear'])) {
        return $base;
    }

    $f = kcj_catalog_filters();
    foreach (['rail', 'color', 'size', 'orderby', 's'] as $key) {
        if (array_key_exists($key, $overrides)) {
            $f[$key] = (string) $overrides[$key];
        }
    }

    if ($f['s'] !== '') {
        $base = home_url('/');
    }

    $args = [];
    if ($f['s'] !== '') {
        $args['s'] = $f['s'];
        $args['post_type'] = 'product';
    }
    if ($f['rail'] !== '' && $f['rail'] !== 'all') {
        $args['rail'] = $f['rail'];
    }
    if ($f['color'] !== '') {
        $args['color'] = $f['color'];
    }
    if ($f['size'] !== '') {
        $args['size'] = $f['size'];
    }
    if ($f['orderby'] !== '' && $f['orderby'] !== 'menu_order') {
        $args['orderby'] = $f['orderby'];
    }

    return $args ? add_query_arg($args, $base) : $base;
}

function kcj_catalog_size_rank($slug) {
    $order = [
        'xs' => 1, 's' => 2, 'm' => 3, 'l' => 4, 'xl' => 5,
        '2xl' => 6, 'xxl' => 6, '3xl' => 7, '4xl' => 8, '5xl' => 9, '6xl' => 10,
    ];
    return $order[$slug] ?? 50;
}

function kcj_catalog_attr_terms($taxonomy) {
    if (!taxonomy_exists($taxonomy)) {
        return [];
    }
    $terms = get_terms([
        'taxonomy'   => $taxonomy,
        'hide_empty' => true,
    ]);
    if (is_wp_error($terms) || empty($terms)) {
        return [];
    }
    if ($taxonomy === 'pa_size') {
        usort($terms, static function ($a, $b) {
            return kcj_catalog_size_rank($a->slug) <=> kcj_catalog_size_rank($b->slug);
        });
    } else {
        usort($terms, static function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
    }
    return $terms;
}

function kcj_catalog_color_hex($slug) {
    $map = [
        'white' => '#f3f3f3', 'bone' => '#e8e0d2', 'natural' => '#efe6d6',
        'ivory' => '#f3ead8', 'sand' => '#d9c9a8', 'khaki' => '#c6b48a',
        'latte' => '#c4a882', 'oatmeal-heather' => '#d8cfc0', 'black' => '#1a1a1a',
        'vintage-black' => '#2a2a2a', 'charcoal' => '#3d3d3d', 'ash' => '#c5c5c5',
        'ice-grey' => '#d6d6d6', 'ice-gray' => '#d6d6d6', 'navy' => '#1b2a4a',
        'sport-grey' => '#b8b8b8', 'sport-gray' => '#b8b8b8',
        'graphite-heather' => '#6a6a6a', 'dark-heather' => '#4d5358',
        'dark-chocolate' => '#4a2c1a', 'red' => '#c62828', 'maroon' => '#6b1d2a',
        'forest' => '#2e5a3c', 'purple' => '#6a3d8f',
    ];
    return $map[$slug] ?? '';
}

/**
 * Soft / Mirror product counts (unfiltered by color/size).
 *
 * @return array{soft:int,mirror:int,all:int}
 */
function kcj_catalog_rail_counts() {
    $by = function_exists('kcj_merch_all_by_rail') ? kcj_merch_all_by_rail() : ['soft' => [], 'mirror' => []];
    $soft = count($by['soft'] ?? []);
    $mirror = count($by['mirror'] ?? []);
    return [
        'soft'   => $soft,
        'mirror' => $mirror,
        'all'    => $soft + $mirror,
    ];
}

/**
 * Does this product match color / size / search filters?
 */
function kcj_catalog_product_matches($product_id, array $filters) {
    $product_id = (int) $product_id;
    if ($filters['s'] !== '') {
        $hay = get_the_title($product_id);
        $tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'names']);
        if (!is_wp_error($tags) && $tags) {
            $hay .= ' ' . implode(' ', $tags);
        }
        if (stripos($hay, $filters['s']) === false) {
            return false;
        }
    }
    if ($filters['color'] !== '' && taxonomy_exists('pa_color')) {
        if (!has_term($filters['color'], 'pa_color', $product_id)) {
            return false;
        }
    }
    if ($filters['size'] !== '' && taxonomy_exists('pa_size')) {
        if (!has_term($filters['size'], 'pa_size', $product_id)) {
            return false;
        }
    }
    return true;
}

/**
 * Sort merch items by catalog orderby.
 *
 * @param array<int,array> $items
 * @return array<int,array>
 */
function kcj_catalog_sort_items(array $items, $orderby) {
    if ($orderby === 'popularity') {
        usort($items, static function ($a, $b) {
            $ida = !empty($a['id']) ? (int) $a['id'] : url_to_postid($a['url'] ?? '');
            $idb = !empty($b['id']) ? (int) $b['id'] : url_to_postid($b['url'] ?? '');
            $sa = (int) get_post_meta($ida, 'total_sales', true);
            $sb = (int) get_post_meta($idb, 'total_sales', true);
            if ($sa === $sb) {
                return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
            }
            return $sb <=> $sa;
        });
        return $items;
    }
    if ($orderby === 'date') {
        usort($items, static function ($a, $b) {
            $ida = !empty($a['id']) ? (int) $a['id'] : url_to_postid($a['url'] ?? '');
            $idb = !empty($b['id']) ? (int) $b['id'] : url_to_postid($b['url'] ?? '');
            return strcmp(get_post_field('post_date', $idb), get_post_field('post_date', $ida));
        });
        return $items;
    }
    return $items;
}

function kcj_render_catalog_toolbar($total = 0) {
    $filters = kcj_catalog_filters();
    $rail = $filters['rail'];
    $color = $filters['color'];
    $size = $filters['size'];
    $orderby = $filters['orderby'];
    $search_q = $filters['s'];
    $counts = kcj_catalog_rail_counts();
    $colors = kcj_catalog_attr_terms('pa_color');
    $sizes = kcj_catalog_attr_terms('pa_size');
    $filter_n = (int) ($color !== '') + (int) ($size !== '') + (int) ($rail !== 'all') + (int) ($search_q !== '');
    $has_extra = $filter_n > 0 || $orderby !== '';
    ?>
    <div class="shop-toolbar" data-shop-toolbar>
        <details class="shop-filters-more" data-shop-filters open>
            <summary class="shop-filters-summary">
                <?php esc_html_e('Filters', 'kcjdrama'); ?>
                <?php if ($filter_n > 0) : ?>
                    <span class="shop-filters-count"><?php echo esc_html((string) $filter_n); ?></span>
                <?php endif; ?>
            </summary>

            <div class="shop-toolbar-row shop-toolbar-cats" role="navigation" aria-label="<?php esc_attr_e('Rail', 'kcjdrama'); ?>">
                <span class="shop-row-label"><?php esc_html_e('Rail', 'kcjdrama'); ?></span>
                <a class="shop-chip<?php echo $rail === 'all' && $search_q === '' ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['rail' => 'all', 's' => ''])); ?>">
                    <span><?php esc_html_e('Everything', 'kcjdrama'); ?></span>
                    <span class="shop-chip-count"><?php echo esc_html((string) $counts['all']); ?></span>
                </a>
                <a class="shop-chip shop-chip--soft<?php echo $rail === 'soft' ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['rail' => $rail === 'soft' ? 'all' : 'soft'])); ?>">
                    <span><?php esc_html_e('Soft', 'kcjdrama'); ?></span>
                    <span class="shop-chip-count"><?php echo esc_html((string) $counts['soft']); ?></span>
                </a>
                <a class="shop-chip shop-chip--mirror<?php echo $rail === 'mirror' ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['rail' => $rail === 'mirror' ? 'all' : 'mirror'])); ?>">
                    <span><?php esc_html_e('Mirror', 'kcjdrama'); ?></span>
                    <span class="shop-chip-count"><?php echo esc_html((string) $counts['mirror']); ?></span>
                </a>
                <a class="shop-chip<?php echo $orderby === 'date' ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['orderby' => $orderby === 'date' ? '' : 'date'])); ?>"><?php esc_html_e('New', 'kcjdrama'); ?></a>
                <a class="shop-chip<?php echo $orderby === 'popularity' ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['orderby' => $orderby === 'popularity' ? '' : 'popularity'])); ?>"><?php esc_html_e('Popular', 'kcjdrama'); ?></a>
                <?php if ($has_extra) : ?>
                    <a class="shop-chip shop-chip--clear" href="<?php echo esc_url(kcj_catalog_url(['clear' => true])); ?>"><?php esc_html_e('Clear', 'kcjdrama'); ?></a>
                <?php endif; ?>
            </div>

            <?php if ($colors) : ?>
            <div class="shop-toolbar-row shop-toolbar-colors" role="navigation" aria-label="<?php esc_attr_e('Color', 'kcjdrama'); ?>">
                <?php foreach ($colors as $term) :
                    $active = $color === $term->slug;
                    $hex = kcj_catalog_color_hex($term->slug);
                    ?>
                    <a class="shop-chip shop-chip--color<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['color' => $active ? '' : $term->slug])); ?>">
                        <?php if ($hex !== '') : ?>
                            <span class="shop-chip-swatch" style="background: <?php echo esc_attr($hex); ?>"></span>
                        <?php endif; ?>
                        <span><?php echo esc_html($term->name); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($sizes) : ?>
            <div class="shop-toolbar-row shop-toolbar-sizes" role="navigation" aria-label="<?php esc_attr_e('Size', 'kcjdrama'); ?>">
                <?php foreach ($sizes as $term) :
                    $active = $size === $term->slug;
                    ?>
                    <a class="shop-chip shop-chip--size<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url(kcj_catalog_url(['size' => $active ? '' : $term->slug])); ?>"><?php echo esc_html($term->name); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </details>

        <div class="shop-toolbar-row shop-toolbar-controls">
            <form class="shop-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                <label class="screen-reader-text" for="shop-product-search"><?php esc_html_e('Search products', 'kcjdrama'); ?></label>
                <input id="shop-product-search" type="search" name="s" value="<?php echo esc_attr($search_q); ?>" placeholder="<?php esc_attr_e('Search designs…', 'kcjdrama'); ?>" autocomplete="off">
                <input type="hidden" name="post_type" value="product">
                <?php if ($rail !== 'all') : ?>
                    <input type="hidden" name="rail" value="<?php echo esc_attr($rail); ?>">
                <?php endif; ?>
                <?php if ($color !== '') : ?>
                    <input type="hidden" name="color" value="<?php echo esc_attr($color); ?>">
                <?php endif; ?>
                <?php if ($size !== '') : ?>
                    <input type="hidden" name="size" value="<?php echo esc_attr($size); ?>">
                <?php endif; ?>
                <?php if ($orderby !== '' && $orderby !== 'menu_order') : ?>
                    <input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>">
                <?php endif; ?>
                <button type="submit" class="shop-search-submit"><?php esc_html_e('Search', 'kcjdrama'); ?></button>
            </form>
            <div class="shop-toolbar-meta">
                <p class="woocommerce-result-count" role="status">
                    <?php
                    printf(
                        /* translators: %d: product count */
                        esc_html(_n('%d design', '%d designs', (int) $total, 'kcjdrama')),
                        (int) $total
                    );
                    ?>
                </p>
            </div>
        </div>
    </div>
    <?php
}
