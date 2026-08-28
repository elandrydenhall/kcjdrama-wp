<?php
/**
 * Soft | Everything | Mirror merch wall (chunked + infinite scroll).
 */
if (!defined('ABSPATH')) {
    exit;
}

function kcj_wall_chunk_size($context = 'home') {
    // Home paints the current catalog in one response so the wall does not
    // depend on REST+IntersectionObserver (50 SKUs today; scroll still exists past this).
    return $context === 'shop' ? 24 : 120;
}

/**
 * @return string URL for a rail on a given base (home or shop).
 */
function kcj_rail_url($rail, $base = null) {
    $base = $base ? (string) $base : home_url('/');
    $base = remove_query_arg('rail', $base);
    // Drop any existing fragment, then pin Soft/Mirror toggles to the wall.
    $hash = '';
    if (strpos($base, '#') !== false) {
        list($base, $hash) = explode('#', $base, 2);
    }
    if ($rail !== 'all' && $rail !== '') {
        $base = add_query_arg('rail', $rail, $base);
    }
    return $base . '#kcj-shop-split';
}

/**
 * Build one wall tile payload from a WC product.
 *
 * @return array{rail:string,rail_label:string,title:string,meta:string,url:string,image:string}
 */
function kcj_merch_item_from_product($product) {
    $id = $product->get_id();
    $item_rail = kcj_product_rail($id);
    $img = get_the_post_thumbnail_url($id, 'medium_large') ?: '';
    $price = '';
    if (method_exists($product, 'get_price_html')) {
        $price = wp_strip_all_tags($product->get_price_html());
    }
    return [
        'id'         => $id,
        'rail'       => $item_rail,
        'rail_label' => $item_rail === 'mirror' ? 'Mirror' : 'Soft',
        'title'      => $product->get_name(),
        'meta'       => $price !== '' ? $price : 'In stock',
        'url'        => get_permalink($id),
        'image'      => $img,
    ];
}

/**
 * All Soft/Mirror items for this request (cached).
 *
 * @return array{soft:array<int,array>,mirror:array<int,array>}
 */
function kcj_merch_all_by_rail() {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $soft = [];
    $mirror = [];
    if (!function_exists('wc_get_products')) {
        return $cache = ['soft' => [], 'mirror' => []];
    }
    $products = wc_get_products([
        'status'  => 'publish',
        'limit'   => -1,
        'orderby' => 'date',
        'order'   => 'DESC',
        'return'  => 'objects',
    ]);
    foreach ($products as $product) {
        $item = kcj_merch_item_from_product($product);
        $rails = function_exists('kcj_product_rails')
            ? kcj_product_rails($product->get_id())
            : [$item['rail']];
        if (in_array('soft', $rails, true)) {
            $soft[] = $item;
        }
        if (in_array('mirror', $rails, true)) {
            $mirror[] = $item;
        }
    }
    return $cache = ['soft' => $soft, 'mirror' => $mirror];
}

/**
 * Soft, Mirror, Soft, Mirror… so Everything is a true visible mix.
 *
 * @param array<int,array> $soft
 * @param array<int,array> $mirror
 * @return array<int,array>
 */
function kcj_merch_interleave(array $soft, array $mirror) {
    $out = [];
    $seen = [];
    $i = 0;
    $j = 0;
    $ns = count($soft);
    $nm = count($mirror);
    $take = static function ($item) use (&$out, &$seen) {
        $id = isset($item['id']) ? (int) $item['id'] : 0;
        if ($id && isset($seen[$id])) {
            return;
        }
        if ($id) {
            $seen[$id] = true;
        }
        $out[] = $item;
    };
    while ($i < $ns || $j < $nm) {
        if ($i < $ns) {
            $take($soft[$i++]);
        }
        if ($j < $nm) {
            $take($mirror[$j++]);
        }
    }
    return $out;
}

/**
 * Slice of the wall for a rail (+ optional catalog color/size/search/sort).
 *
 * @param array|null $filters Optional overrides: rail, color, size, orderby, s
 * @return array{items:array,total:int,offset:int,limit:int,has_more:bool,rail:string}
 */
function kcj_merch_slice($rail = null, $offset = 0, $limit = 12, $filters = null) {
    $filters = is_array($filters) ? $filters : (function_exists('kcj_catalog_filters') ? kcj_catalog_filters() : []);
    $rail = $rail ?: ($filters['rail'] ?? kcj_shop_rail());
    if (!in_array($rail, ['soft', 'mirror', 'all'], true)) {
        $rail = 'all';
    }
    $offset = max(0, (int) $offset);
    $limit = max(1, (int) $limit);
    $by = kcj_merch_all_by_rail();

    if ($rail === 'soft') {
        $all = $by['soft'];
    } elseif ($rail === 'mirror') {
        $all = $by['mirror'];
    } else {
        $all = kcj_merch_interleave($by['soft'], $by['mirror']);
    }

    if (!$all) {
        $fallback = kcj_featured_merch_placeholders_fallback();
        if ($rail !== 'all') {
            $fallback = array_values(array_filter(
                $fallback,
                static function ($item) use ($rail) {
                    return $item['rail'] === $rail;
                }
            ));
        }
        $all = $fallback;
    }

    $color = (string) ($filters['color'] ?? '');
    $size = (string) ($filters['size'] ?? '');
    $search = (string) ($filters['s'] ?? '');
    $orderby = (string) ($filters['orderby'] ?? '');
    if ($color !== '' || $size !== '' || $search !== '') {
        $match = [
            'color' => $color,
            'size'  => $size,
            's'     => $search,
        ];
        $all = array_values(array_filter($all, static function ($item) use ($match) {
            $id = !empty($item['id']) ? (int) $item['id'] : 0;
            if ($id <= 0) {
                return $match['color'] === '' && $match['size'] === '' && $match['s'] === '';
            }
            return kcj_catalog_product_matches($id, $match);
        }));
    }
    if ($orderby !== '' && function_exists('kcj_catalog_sort_items')) {
        $all = kcj_catalog_sort_items($all, $orderby);
    }

    $total = count($all);
    $items = array_slice($all, $offset, $limit);
    return [
        'items'    => $items,
        'total'    => $total,
        'offset'   => $offset,
        'limit'    => $limit,
        'has_more' => ($offset + count($items)) < $total,
        'rail'     => $rail,
    ];
}

/**
 * Back-compat wrapper used by older callers.
 *
 * @return array<int,array>
 */
function kcj_featured_merch($limit = 8) {
    $slice = kcj_merch_slice(kcj_shop_rail(), 0, (int) $limit);
    return $slice['items'];
}

function kcj_wall_product_label($title) {
    $label = trim(wp_strip_all_tags((string) $title));
    $label = rtrim($label, ".…");
    if ($label === '') {
        return '';
    }
    // Satire-style wall label: short, ends with ellipsis.
    if (function_exists('mb_strimwidth')) {
        $label = mb_strimwidth($label, 0, 42, '', 'UTF-8');
    } elseif (strlen($label) > 42) {
        $label = substr($label, 0, 42);
    }
    return $label . '…';
}

/**
 * @param array  $item
 * @param string $fallback_url
 * @param string $mode home|catalog — catalog hides Soft/Mirror badge (Satire tile language)
 */
function kcj_render_product_tile(array $item, $fallback_url = '', $mode = 'home') {
    // Prefer real product permalink (/product/slug/), same pattern as SatireSmart.
    $href = !empty($item['url']) ? $item['url'] : ($fallback_url ?: kcj_page_url('shop'));
    $label = kcj_wall_product_label($item['title'] ?? '');
    $mode = $mode === 'catalog' ? 'catalog' : 'home';
    ?>
    <li class="kcj-product kcj-product--<?php echo esc_attr($item['rail']); ?><?php echo $mode === 'catalog' ? ' kcj-product--catalog' : ''; ?>">
        <a class="kcj-product-link" href="<?php echo esc_url($href); ?>">
            <?php kcj_render_product_media($item); ?>
            <span class="kcj-product-body">
                <?php if ($mode !== 'catalog') : ?>
                    <span class="kcj-product-rail"><?php echo esc_html($item['rail_label']); ?></span>
                <?php endif; ?>
                <span class="kcj-product-title"><?php echo esc_html($label); ?></span>
            </span>
        </a>
    </li>
    <?php
}

/**
 * Render Soft|Everything|Mirror toggle for home or shop.
 */
function kcj_render_rail_toggle($base_url, $rail = null) {
    $rail = $rail ?: kcj_shop_rail();
    $soft = kcj_rail_url('soft', $base_url);
    $all = kcj_rail_url('all', $base_url);
    $mirror = kcj_rail_url('mirror', $base_url);
    ?>
    <nav class="kcj-rail-toggle" aria-label="<?php esc_attr_e('Shop rail', 'gsolo-kcjdrama'); ?>">
        <a
            class="<?php echo $rail === 'soft' ? 'is-active' : ''; ?>"
            href="<?php echo esc_url($soft); ?>"
            data-rail="soft"
            <?php echo $rail === 'soft' ? 'aria-current="page"' : ''; ?>
        >Soft</a>
        <span class="kcj-rail-sep" aria-hidden="true">|</span>
        <a
            class="<?php echo $rail === 'all' ? 'is-active' : ''; ?>"
            href="<?php echo esc_url($all); ?>"
            data-rail="all"
            <?php echo $rail === 'all' ? 'aria-current="page"' : ''; ?>
        >Everything</a>
        <span class="kcj-rail-sep" aria-hidden="true">|</span>
        <a
            class="<?php echo $rail === 'mirror' ? 'is-active' : ''; ?>"
            href="<?php echo esc_url($mirror); ?>"
            data-rail="mirror"
            <?php echo $rail === 'mirror' ? 'aria-current="page"' : ''; ?>
        >Mirror</a>
    </nav>
    <?php
}

/**
 * Product wall + infinite-scroll sentinel.
 *
 * @param string $context home|shop
 */
function kcj_render_merch_wall($context = 'home') {
    $filters = function_exists('kcj_catalog_filters') ? kcj_catalog_filters() : ['rail' => kcj_shop_rail()];
    $rail = $filters['rail'] ?? kcj_shop_rail();
    $per = kcj_wall_chunk_size($context);
    $slice = kcj_merch_slice($rail, 0, $per, $filters);
    $fallback = kcj_page_url('shop');
    $mode = $context === 'shop' ? 'catalog' : 'home';
    $color = (string) ($filters['color'] ?? '');
    $size = (string) ($filters['size'] ?? '');
    $orderby = (string) ($filters['orderby'] ?? '');
    $search = (string) ($filters['s'] ?? '');
    ?>
    <ul
        class="kcj-product-wall<?php echo $mode === 'catalog' ? ' kcj-product-wall--catalog' : ''; ?>"
        data-kcj-wall
        data-mode="<?php echo esc_attr($mode); ?>"
        data-rail="<?php echo esc_attr($rail); ?>"
        data-color="<?php echo esc_attr($color); ?>"
        data-size="<?php echo esc_attr($size); ?>"
        data-orderby="<?php echo esc_attr($orderby); ?>"
        data-s="<?php echo esc_attr($search); ?>"
        data-page="1"
        data-per="<?php echo esc_attr((string) $per); ?>"
        data-has-more="<?php echo $slice['has_more'] ? '1' : '0'; ?>"
        data-total="<?php echo esc_attr((string) $slice['total']); ?>"
    >
        <?php
        if ($slice['items']) {
            foreach ($slice['items'] as $item) {
                kcj_render_product_tile($item, $fallback, $mode);
            }
        }
        ?>
    </ul>
    <?php if (!$slice['items']) : ?>
        <p class="kcj-wall-empty">No pieces match these filters yet.</p>
    <?php endif; ?>
    <div
        class="kcj-wall-sentinel"
        data-kcj-wall-sentinel
        <?php echo $slice['has_more'] ? '' : 'hidden'; ?>
        aria-hidden="true"
    ></div>
    <?php
}

/**
 * REST sanitize_callback receives ($value, WP_REST_Request, $param).
 * sanitize_title() treats arg 2 as a fallback string — empty color/size 500s the wall.
 */
function kcj_rest_sanitize_slug($value) {
    return sanitize_title(is_scalar($value) ? (string) $value : '');
}

add_action('rest_api_init', function () {
    register_rest_route('kcj/v1', '/wall', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => [
            'rail' => [
                'type'              => 'string',
                'default'           => 'all',
                'sanitize_callback' => 'sanitize_key',
            ],
            'page' => [
                'type'    => 'integer',
                'default' => 1,
            ],
            'per'  => [
                'type'    => 'integer',
                'default' => 12,
            ],
            'color' => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'kcj_rest_sanitize_slug',
            ],
            'size' => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'kcj_rest_sanitize_slug',
            ],
            'orderby' => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_key',
            ],
            's' => [
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ],
            'mode' => [
                'type'              => 'string',
                'default'           => 'home',
                'sanitize_callback' => 'sanitize_key',
            ],
        ],
        'callback'            => 'kcj_rest_wall',
    ]);
});

function kcj_rest_wall(WP_REST_Request $request) {
    $rail = strtolower((string) $request->get_param('rail'));
    if (!in_array($rail, ['soft', 'mirror', 'all'], true)) {
        $rail = 'all';
    }
    $page = max(1, (int) $request->get_param('page'));
    $per = max(1, min(48, (int) $request->get_param('per')));
    $offset = ($page - 1) * $per;
    $mode = $request->get_param('mode') === 'catalog' ? 'catalog' : 'home';
    $filters = [
        'rail'    => $rail,
        'color'   => (string) $request->get_param('color'),
        'size'    => (string) $request->get_param('size'),
        'orderby' => (string) $request->get_param('orderby'),
        's'       => (string) $request->get_param('s'),
    ];
    $slice = kcj_merch_slice($rail, $offset, $per, $filters);

    ob_start();
    foreach ($slice['items'] as $item) {
        kcj_render_product_tile($item, '', $mode);
    }
    $html = ob_get_clean();

    return rest_ensure_response([
        'html'     => $html,
        'has_more' => (bool) $slice['has_more'],
        'page'     => $page,
        'per'      => $per,
        'total'    => (int) $slice['total'],
        'rail'     => $rail,
    ]);
}

add_action('wp_enqueue_scripts', function () {
    $on_home = is_front_page();
    $on_shop = is_page('shop')
        || is_page_template('page-shop.php')
        || (function_exists('is_shop') && is_shop());
    if (!$on_home && !$on_shop) {
        return;
    }
    wp_enqueue_script(
        'kcj-wall',
        KCJ_URI . '/assets/js/wall.js',
        [],
        KCJ_VERSION,
        true
    );
    wp_localize_script('kcj-wall', 'kcjWall', [
        'rest'  => esc_url_raw(rest_url('kcj/v1/wall')),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
}, 31);

/**
 * Newest Mirror editorial posts (syndromes / roast / listicles).
 *
 * @return array<int,WP_Post>
 */
function kcj_mirror_editorial_posts($limit = 6) {
    return get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => max(1, (int) $limit),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => [
            [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => ['syndrome', 'mirror-roast', 'listicle'],
            ],
        ],
    ]);
}

/**
 * Soft/Mirror brand-stage rows: merch strip + related writing.
 */
function kcj_render_brand_stage_rows($tone = 'soft') {
    $tone = $tone === 'mirror' ? 'mirror' : 'soft';
    $shop = function_exists('kcj_catalog_url')
        ? kcj_catalog_url(['rail' => $tone])
        : kcj_page_url('shop');
    $slice = kcj_merch_slice($tone, 0, 8);

    if ($tone === 'mirror') {
        $posts_url = kcj_page_url('syndromes');
        $posts = kcj_mirror_editorial_posts(6);
        $aria = __('Mirror merch and editorial', 'kcjdrama');
        $merch_title = __('Mirror merch', 'kcjdrama');
        $merch_cta = __('Shop Mirror', 'kcjdrama');
        $merch_empty = __('Mirror merch will land here as the catalog fills.', 'kcjdrama');
        $posts_title = __('Mirror posts', 'kcjdrama');
        $posts_cta = __('Syndrome clinic', 'kcjdrama');
        $posts_empty = __('Mirror syndromes and roast notes will land here.', 'kcjdrama');
    } else {
        $posts_url = kcj_page_url('stories');
        $posts = function_exists('kcj_top_desk_stories') ? kcj_top_desk_stories(6) : [];
        $aria = __('Soft merch and desk stories', 'kcjdrama');
        $merch_title = __('Soft merch', 'kcjdrama');
        $merch_cta = __('Shop Soft', 'kcjdrama');
        $merch_empty = __('Soft merch will land here as the catalog fills.', 'kcjdrama');
        $posts_title = __('From the desk', 'kcjdrama');
        $posts_cta = __('All stories', 'kcjdrama');
        $posts_empty = __('Grok-preapproved Soft shorts will land here.', 'kcjdrama');
    }
    ?>
    <section class="kcj-stage-rows" aria-label="<?php echo esc_attr($aria); ?>">
        <div class="kcj-stage-row">
            <header class="kcj-stage-row-head">
                <h2><?php echo esc_html($merch_title); ?></h2>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shop); ?>"><?php echo esc_html($merch_cta); ?></a>
            </header>
            <?php if (!empty($slice['items'])) : ?>
                <ul class="kcj-stage-row-wall">
                    <?php
                    foreach ($slice['items'] as $item) {
                        kcj_render_product_tile($item, $shop, 'catalog');
                    }
                    ?>
                </ul>
            <?php else : ?>
                <p class="kcj-stage-row-empty"><?php echo esc_html($merch_empty); ?></p>
            <?php endif; ?>
        </div>

        <div class="kcj-stage-row">
            <header class="kcj-stage-row-head">
                <h2><?php echo esc_html($posts_title); ?></h2>
                <a class="kcj-brand-cross" href="<?php echo esc_url($posts_url); ?>"><?php echo esc_html($posts_cta); ?></a>
            </header>
            <?php if ($posts) : ?>
                <ul class="kcj-stage-story-row">
                    <?php foreach ($posts as $post) :
                        $byline = function_exists('kcj_post_byline_label')
                            ? kcj_post_byline_label($post)
                            : '';
                        ?>
                        <li class="kcj-stage-story">
                            <a class="kcj-stage-story-link" href="<?php echo esc_url(get_permalink($post)); ?>">
                                <span class="kcj-stage-story-title"><?php echo esc_html(get_the_title($post)); ?></span>
                                <?php if ($byline !== '') : ?>
                                    <span class="kcj-stage-story-byline"><?php echo esc_html($byline); ?></span>
                                <?php endif; ?>
                                <span class="kcj-stage-story-one"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($post->post_content), 22)); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="kcj-stage-row-empty">
                    <?php echo esc_html($posts_empty); ?>
                    <?php if ($tone === 'soft') : ?>
                        <a href="<?php echo esc_url($posts_url); ?>#kcj-desk"><?php esc_html_e('Write for the desk', 'kcjdrama'); ?></a>
                    <?php else : ?>
                        <a href="<?php echo esc_url($posts_url); ?>"><?php echo esc_html($posts_cta); ?></a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>
    </section>
    <?php
}
