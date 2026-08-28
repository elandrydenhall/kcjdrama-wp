<?php
/**
 * Homepage search bar + REST suggestions.
 */
if (!defined('ABSPATH')) {
    exit;
}

function kcj_search_post_types() {
    $types = ['post', 'page'];
    if (post_type_exists('product')) {
        $types[] = 'product';
    }
    return $types;
}

add_action('pre_get_posts', function ($query) {
    if (is_admin() || !$query->is_main_query() || !$query->is_search()) {
        return;
    }
    $requested = $query->get('post_type');
    if ($requested === 'product' || (is_array($requested) && $requested === ['product'])) {
        return;
    }
    $query->set('post_type', kcj_search_post_types());
});

/**
 * Shop / site search also matches Woo product_tag names (catalog already did this in PHP).
 */
add_filter('posts_search', 'kcj_search_include_product_tags', 10, 2);
function kcj_search_include_product_tags($search, $query) {
    global $wpdb;
    if (is_admin() || !($query instanceof WP_Query) || $search === '' || !$query->is_search()) {
        return $search;
    }
    $s = (string) $query->get('s');
    if ($s === '') {
        return $search;
    }
    $types = $query->get('post_type');
    if ($types && $types !== 'any') {
        $types = (array) $types;
        if (!in_array('product', $types, true)) {
            return $search;
        }
    }
    $like = '%' . $wpdb->esc_like($s) . '%';
    $tag_match = $wpdb->prepare(
        "{$wpdb->posts}.ID IN (
            SELECT kcj_tr.object_id FROM {$wpdb->term_relationships} kcj_tr
            INNER JOIN {$wpdb->term_taxonomy} kcj_tt ON kcj_tr.term_taxonomy_id = kcj_tt.term_taxonomy_id
            INNER JOIN {$wpdb->terms} kcj_t ON kcj_tt.term_id = kcj_t.term_id
            WHERE kcj_tt.taxonomy = 'product_tag'
              AND (kcj_t.name LIKE %s OR kcj_t.slug LIKE %s)
        )",
        $like,
        $like
    );
    $needle = ' AND (';
    $pos = strpos($search, $needle);
    if ($pos === false) {
        return $search . ' AND (' . $tag_match . ') ';
    }
    return substr($search, 0, $pos + strlen($needle)) . $tag_match . ' OR ' . substr($search, $pos + strlen($needle));
}

add_action('rest_api_init', function () {
    register_rest_route('kcj/v1', '/suggest', [
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => [
            'q' => [
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
            ],
        ],
        'callback'            => 'kcj_rest_suggest',
    ]);
});

function kcj_rest_suggest(WP_REST_Request $request) {
    $q = trim((string) $request->get_param('q'));
    if (strlen($q) < 2) {
        return rest_ensure_response([]);
    }

    $query = new WP_Query([
        's'              => $q,
        'post_type'      => kcj_search_post_types(),
        'post_status'    => 'publish',
        'posts_per_page' => 8,
        'no_found_rows'  => true,
    ]);

    $out = [];
    foreach ($query->posts as $post) {
        $type = $post->post_type;
        $label = 'Page';
        if ($type === 'product') {
            $label = function_exists('kcj_product_rail') && kcj_product_rail($post->ID) === 'mirror'
                ? 'Mirror merch'
                : 'Soft merch';
        } elseif ($type === 'post') {
            $label = 'Note';
        }
        $out[] = [
            'id'    => (int) $post->ID,
            'title' => html_entity_decode(get_the_title($post), ENT_QUOTES, 'UTF-8'),
            'url'   => get_permalink($post),
            'type'  => $label,
        ];
    }
    return rest_ensure_response($out);
}

add_action('wp_enqueue_scripts', function () {
    if (!is_front_page()) {
        return;
    }
    wp_enqueue_script(
        'kcj-home-search',
        KCJ_URI . '/assets/js/home-search.js',
        [],
        KCJ_VERSION,
        true
    );
    wp_localize_script('kcj-home-search', 'kcjSearch', [
        'rest' => esc_url_raw(rest_url('kcj/v1/suggest')),
    ]);
}, 30);
