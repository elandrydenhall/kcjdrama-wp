<?php
/**
 * Soft epigraph quotes — CPT + stable keys + shortcode + admin import.
 *
 * Shortcodes use key=… (never SQLite/WP ids). Import skips edited/locked rows.
 */
if (!defined('ABSPATH')) {
    exit;
}

const KCJ_QUOTE_CPT = 'kcj_quote';

/**
 * Theme placements that are not page content shortcodes.
 *
 * @return array<string, list<array{label:string,url:string}>>
 */
function kcj_quote_theme_placements() {
    return [
        'china-face-heartbeat' => [
            ['label' => __('China desk epigraph', 'kcjdrama'), 'url' => '/countries/china/'],
        ],
        'korea-every-moment-shined' => [
            ['label' => __('Korea desk epigraph', 'kcjdrama'), 'url' => '/countries/korea/'],
        ],
        'japan-loneliness-someone' => [
            ['label' => __('Japan desk epigraph', 'kcjdrama'), 'url' => '/countries/japan/'],
        ],
        'start-destiny-together' => [
            ['label' => __('Start here epigraph', 'kcjdrama'), 'url' => '/start-here/'],
        ],
        'soft-already-beautiful' => [
            ['label' => __('Soft stage epigraph', 'kcjdrama'), 'url' => '/soft/'],
        ],
        'mirror-weed-wont-crush' => [
            ['label' => __('Mirror stage epigraph', 'kcjdrama'), 'url' => '/mirror/'],
        ],
        'countries-good-shoes' => [
            ['label' => __('Countries index epigraph', 'kcjdrama'), 'url' => '/countries/'],
        ],
        'about-liking-not-shameful' => [
            ['label' => __('About epigraph', 'kcjdrama'), 'url' => '/about/'],
        ],
        'ship-there-is-a-way' => [
            ['label' => __('Shipping & returns epigraph', 'kcjdrama'), 'url' => '/shipping-returns/'],
        ],
        'support-keep-in-heart' => [
            ['label' => __('Support epigraph', 'kcjdrama'), 'url' => '/support/'],
        ],
        'policy-worth-of-heart' => [
            ['label' => __('Editorial policy epigraph', 'kcjdrama'), 'url' => '/editorial-policy/'],
        ],
        'faq-plan-might-change' => [
            ['label' => __('FAQ epigraph', 'kcjdrama'), 'url' => '/faq/'],
        ],
        'tropes-sadness-or-love' => [
            ['label' => __('Tropes epigraph', 'kcjdrama'), 'url' => '/tropes/'],
        ],
        'syndromes-twisted-rope' => [
            ['label' => __('Syndromes epigraph', 'kcjdrama'), 'url' => '/syndromes/'],
        ],
        'stories-how-not-to-fall' => [
            ['label' => __('Stories epigraph', 'kcjdrama'), 'url' => '/stories/'],
        ],
        'essays-hope-lets-move' => [
            ['label' => __('Essays epigraph', 'kcjdrama'), 'url' => '/essays/'],
        ],
        'glossary-i-am-here' => [
            ['label' => __('Glossary epigraph', 'kcjdrama'), 'url' => '/glossary/'],
        ],
        'victim-swing-own-sword' => [
            ['label' => __('Victim Log epigraph', 'kcjdrama'), 'url' => '/victim-log/'],
        ],
    ];
}

/**
 * Echo Soft/Mirror epigraph shortcode for a stable key.
 */
function kcj_the_epigraph($key) {
    $key = sanitize_title((string) $key);
    if ($key === '') {
        return;
    }
    echo do_shortcode('[kcj_quote key="' . esc_attr($key) . '"]');
}

add_action('init', function () {
    register_post_type(KCJ_QUOTE_CPT, [
        'labels' => [
            'name'               => __('Quotes', 'kcjdrama'),
            'singular_name'      => __('Quote', 'kcjdrama'),
            'add_new_item'       => __('Add Quote', 'kcjdrama'),
            'edit_item'          => __('Edit Quote', 'kcjdrama'),
            'new_item'           => __('New Quote', 'kcjdrama'),
            'view_item'          => __('View Quote', 'kcjdrama'),
            'search_items'       => __('Search Quotes', 'kcjdrama'),
            'not_found'          => __('No quotes found', 'kcjdrama'),
            'not_found_in_trash' => __('No quotes in trash', 'kcjdrama'),
            'menu_name'          => __('Quotes', 'kcjdrama'),
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 26,
        'menu_icon'           => 'dashicons-format-quote',
        'supports'            => ['title', 'editor'],
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'exclude_from_search' => true,
        'has_archive'         => false,
        'rewrite'             => false,
    ]);

    add_shortcode('kcj_quote', 'kcj_quote_shortcode');
});

/**
 * @return array<string, mixed>|null
 */
function kcj_quote_row_from_post($post) {
    $post = get_post($post);
    if (!$post || $post->post_type !== KCJ_QUOTE_CPT) {
        return null;
    }
    $key = (string) get_post_meta($post->ID, '_kcj_quote_key', true);
    if ($key === '') {
        return null;
    }
    return [
        'post_id'    => (int) $post->ID,
        'key'        => $key,
        'text'       => trim(wp_strip_all_tags((string) $post->post_content)),
        'speaker'    => (string) get_post_meta($post->ID, '_kcj_speaker', true),
        'work'       => (string) get_post_meta($post->ID, '_kcj_work', true),
        'country'    => (string) get_post_meta($post->ID, '_kcj_country', true),
        'source_name'=> (string) get_post_meta($post->ID, '_kcj_source_name', true),
        'source_url' => (string) get_post_meta($post->ID, '_kcj_source_url', true),
        'verified'   => (int) get_post_meta($post->ID, '_kcj_verified', true) === 1,
        'tags'       => (string) get_post_meta($post->ID, '_kcj_tags', true),
        'note'       => (string) get_post_meta($post->ID, '_kcj_note', true),
        'locked'     => (int) get_post_meta($post->ID, '_kcj_locked', true) === 1,
        'edited'     => (int) get_post_meta($post->ID, '_kcj_locally_edited', true) === 1,
        'corpus_id'  => (int) get_post_meta($post->ID, '_kcj_corpus_id', true),
    ];
}

/**
 * @return array<string, mixed>|null
 */
function kcj_get_quote_by_key($key, $require_verified = true) {
    $key = sanitize_title((string) $key);
    if ($key === '') {
        return null;
    }
    $q = new WP_Query([
        'post_type'      => KCJ_QUOTE_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_query'     => array_values(array_filter([
            [
                'key'   => '_kcj_quote_key',
                'value' => $key,
            ],
            $require_verified ? [
                'key'   => '_kcj_verified',
                'value' => '1',
            ] : null,
        ])),
        'no_found_rows'  => true,
    ]);
    if (!$q->have_posts()) {
        return null;
    }
    return kcj_quote_row_from_post($q->posts[0]);
}

/**
 * @param array<string, mixed> $args
 * @return array<string, mixed>|null
 */
function kcj_pick_quote(array $args) {
    if (!empty($args['key'])) {
        return kcj_get_quote_by_key((string) $args['key'], empty($args['force']));
    }

    $meta_query = [
        [
            'key'   => '_kcj_verified',
            'value' => '1',
        ],
    ];
    if (!empty($args['country'])) {
        $meta_query[] = [
            'key'   => '_kcj_country',
            'value' => strtoupper(sanitize_text_field((string) $args['country'])),
        ];
    }
    if (!empty($args['tag'])) {
        $meta_query[] = [
            'key'     => '_kcj_tags',
            'value'   => sanitize_text_field((string) $args['tag']),
            'compare' => 'LIKE',
        ];
    }

    $q = new WP_Query([
        'post_type'      => KCJ_QUOTE_CPT,
        'post_status'    => 'publish',
        'posts_per_page' => !empty($args['random']) ? 20 : 1,
        'orderby'        => !empty($args['random']) ? 'rand' : 'date',
        'meta_query'     => $meta_query,
        'no_found_rows'  => true,
    ]);
    if (!$q->have_posts()) {
        return null;
    }
    return kcj_quote_row_from_post($q->posts[0]);
}

/**
 * @param array<string, mixed> $quote
 * @param array<string, mixed> $args
 */
function kcj_render_epigraph(array $quote, array $args = []) {
    $text = trim((string) ($quote['text'] ?? ''));
    if ($text === '') {
        return '';
    }
    $speaker = trim((string) ($quote['speaker'] ?? ''));
    $work = trim((string) ($quote['work'] ?? ''));
    $cite = trim($speaker . ($speaker && $work ? ', ' : '') . $work);
    $note = array_key_exists('note', $args)
        ? (string) $args['note']
        : trim((string) ($quote['note'] ?? ''));
    if (isset($args['show_note']) && !(int) $args['show_note']) {
        $note = '';
    }

    ob_start();
    ?>
    <figure class="kcj-epigraph"<?php echo !empty($quote['key']) ? ' data-kcj-quote-key="' . esc_attr((string) $quote['key']) . '"' : ''; ?>>
        <blockquote class="kcj-epigraph-quote">
            <p><?php echo esc_html($text); ?></p>
        </blockquote>
        <?php if ($cite !== '' || $note !== '') : ?>
            <figcaption class="kcj-epigraph-cite">
                <?php if ($cite !== '') : ?>
                    <span class="kcj-epigraph-attr"><?php echo esc_html($cite); ?></span>
                <?php endif; ?>
                <?php if ($note !== '') : ?>
                    <span class="kcj-epigraph-note"><?php echo esc_html($note); ?></span>
                <?php endif; ?>
            </figcaption>
        <?php endif; ?>
    </figure>
    <?php
    return (string) ob_get_clean();
}

function kcj_render_epigraph_by_key($key, array $args = []) {
    $quote = kcj_get_quote_by_key($key, empty($args['force']));
    if (!$quote) {
        return '';
    }
    return kcj_render_epigraph($quote, $args);
}

function kcj_quote_shortcode($atts) {
    $atts = shortcode_atts([
        'key'     => '',
        'country' => '',
        'tag'     => '',
        'random'  => '0',
        'note'    => '',
        'force'   => '0',
    ], $atts, 'kcj_quote');

    if ($atts['key'] === '' && $atts['country'] === '' && $atts['tag'] === '') {
        return '';
    }

    $quote = kcj_pick_quote([
        'key'     => $atts['key'],
        'country' => $atts['country'],
        'tag'     => $atts['tag'],
        'random'  => (int) $atts['random'] === 1,
        'force'   => (int) $atts['force'] === 1,
    ]);
    if (!$quote) {
        return '';
    }

    $args = [];
    if ($atts['note'] !== '') {
        if ($atts['note'] === '0') {
            $args['show_note'] = 0;
        } else {
            $args['note'] = $atts['note'];
        }
    }
    return kcj_render_epigraph($quote, $args);
}

/** @return list<array{label:string,url:string}> */
function kcj_quote_used_where($key) {
    $key = sanitize_title((string) $key);
    $out = [];
    $seen = [];

    $placements = kcj_quote_theme_placements();
    if (!empty($placements[$key])) {
        foreach ($placements[$key] as $row) {
            $u = (string) ($row['url'] ?? '');
            if ($u === '' || isset($seen[$u])) {
                continue;
            }
            $seen[$u] = true;
            $out[] = [
                'label' => (string) ($row['label'] ?? $u),
                'url'   => $u,
            ];
        }
    }

    $needle = '[kcj_quote';
    $q = new WP_Query([
        'post_type'      => ['page', 'post'],
        'post_status'    => 'publish',
        'posts_per_page' => 100,
        's'              => $needle,
        'no_found_rows'  => true,
    ]);
    // WP search is weak for shortcodes — also scan recent pages with content LIKE.
    $ids = [];
    foreach ($q->posts as $p) {
        $ids[] = (int) $p->ID;
    }
    global $wpdb;
    $like = '%' . $wpdb->esc_like('key="' . $key . '"') . '%';
    $like2 = '%' . $wpdb->esc_like("key='" . $key . "'") . '%';
    $extra = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_status='publish' AND post_type IN ('page','post')
           AND (post_content LIKE %s OR post_content LIKE %s)
         LIMIT 100",
        $like,
        $like2
    ));
    foreach ($extra as $id) {
        $ids[] = (int) $id;
    }
    $ids = array_values(array_unique($ids));

    foreach ($ids as $id) {
        $post = get_post($id);
        if (!$post) {
            continue;
        }
        if (!preg_match('/\[kcj_quote[^\]]*key=["\']' . preg_quote($key, '/') . '["\']/', (string) $post->post_content)) {
            continue;
        }
        $url = get_permalink($post);
        if (!$url || isset($seen[$url])) {
            continue;
        }
        $seen[$url] = true;
        $out[] = [
            'label' => get_the_title($post) ?: ('#' . $id),
            'url'   => $url,
        ];
    }

    return $out;
}

function kcj_quote_propose_key($country, $work, $text, $corpus_id = 0) {
    // Stable special-cases for known Soft placements.
    $special = [
        18 => 'china-face-heartbeat',
    ];
    if ($corpus_id && isset($special[(int) $corpus_id])) {
        return $special[(int) $corpus_id];
    }

    $words = preg_split('/\s+/', strtolower(wp_strip_all_tags((string) $text))) ?: [];
    $words = array_values(array_filter($words, static function ($w) {
        $w = preg_replace('/[^a-z0-9]+/', '', $w);
        return $w !== '' && strlen($w) > 2;
    }));
    $slice = array_slice($words, 0, 4);
    $base = strtolower((string) $country) . '-' . sanitize_title((string) $work);
    if ($slice) {
        $base .= '-' . implode('-', $slice);
    }
    $base = sanitize_title($base);
    if (strlen($base) > 80) {
        $base = substr($base, 0, 80);
    }
    return $base !== '' ? $base : ('quote-' . (int) $corpus_id);
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'kcj_quote_meta',
        __('Quote details', 'kcjdrama'),
        'kcj_quote_meta_box',
        KCJ_QUOTE_CPT,
        'normal',
        'high'
    );
    add_meta_box(
        'kcj_quote_used',
        __('Used where', 'kcjdrama'),
        'kcj_quote_used_box',
        KCJ_QUOTE_CPT,
        'side',
        'default'
    );
});

function kcj_quote_meta_box($post) {
    wp_nonce_field('kcj_quote_meta', 'kcj_quote_meta_nonce');
    $key = (string) get_post_meta($post->ID, '_kcj_quote_key', true);
    $country = (string) get_post_meta($post->ID, '_kcj_country', true);
    $work = (string) get_post_meta($post->ID, '_kcj_work', true);
    $speaker = (string) get_post_meta($post->ID, '_kcj_speaker', true);
    $source_name = (string) get_post_meta($post->ID, '_kcj_source_name', true);
    $source_url = (string) get_post_meta($post->ID, '_kcj_source_url', true);
    $tags = (string) get_post_meta($post->ID, '_kcj_tags', true);
    $note = (string) get_post_meta($post->ID, '_kcj_note', true);
    $verified = (int) get_post_meta($post->ID, '_kcj_verified', true) === 1;
    $locked = (int) get_post_meta($post->ID, '_kcj_locked', true) === 1;
    $edited = (int) get_post_meta($post->ID, '_kcj_locally_edited', true) === 1;
    $corpus = (int) get_post_meta($post->ID, '_kcj_corpus_id', true);
    ?>
    <p><label><strong><?php esc_html_e('Stable key (immutable)', 'kcjdrama'); ?></strong><br>
        <input type="text" class="widefat" name="kcj_quote_key" value="<?php echo esc_attr($key); ?>" <?php disabled($key !== ''); ?> required>
        <?php if ($key !== '') : ?>
            <span class="description"><?php esc_html_e('Shortcodes use this key. It cannot change after create.', 'kcjdrama'); ?></span>
        <?php else : ?>
            <span class="description"><?php esc_html_e('Example: china-face-heartbeat', 'kcjdrama'); ?></span>
        <?php endif; ?>
    </label></p>
    <p><label><strong><?php esc_html_e('Country', 'kcjdrama'); ?></strong><br>
        <select name="kcj_country">
            <?php foreach (['K' => 'Korea', 'C' => 'China', 'J' => 'Japan'] as $code => $label) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($country, $code); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </label></p>
    <p><label><strong><?php esc_html_e('Work', 'kcjdrama'); ?></strong><br>
        <input type="text" class="widefat" name="kcj_work" value="<?php echo esc_attr($work); ?>">
    </label></p>
    <p><label><strong><?php esc_html_e('Speaker', 'kcjdrama'); ?></strong><br>
        <input type="text" class="widefat" name="kcj_speaker" value="<?php echo esc_attr($speaker); ?>">
    </label></p>
    <p><label><strong><?php esc_html_e('Source name', 'kcjdrama'); ?></strong><br>
        <input type="text" class="widefat" name="kcj_source_name" value="<?php echo esc_attr($source_name); ?>">
    </label></p>
    <p><label><strong><?php esc_html_e('Source URL', 'kcjdrama'); ?></strong><br>
        <input type="url" class="widefat" name="kcj_source_url" value="<?php echo esc_attr($source_url); ?>">
    </label></p>
    <p><label><strong><?php esc_html_e('Tags (pipe-separated)', 'kcjdrama'); ?></strong><br>
        <input type="text" class="widefat" name="kcj_tags" value="<?php echo esc_attr($tags); ?>">
    </label></p>
    <p><label><strong><?php esc_html_e('Craft note (optional)', 'kcjdrama'); ?></strong><br>
        <input type="text" class="widefat" name="kcj_note" value="<?php echo esc_attr($note); ?>">
    </label></p>
    <p>
        <label><input type="checkbox" name="kcj_verified" value="1" <?php checked($verified); ?>> <?php esc_html_e('Verified', 'kcjdrama'); ?></label><br>
        <label><input type="checkbox" name="kcj_locked" value="1" <?php checked($locked); ?>> <?php esc_html_e('Locked (import never overwrites)', 'kcjdrama'); ?></label><br>
        <label><input type="checkbox" name="kcj_locally_edited" value="1" <?php checked($edited); ?>> <?php esc_html_e('Locally edited', 'kcjdrama'); ?></label>
    </p>
    <?php if ($corpus) : ?>
        <p class="description"><?php printf(esc_html__('Corpus lineage id: %d', 'kcjdrama'), $corpus); ?></p>
    <?php endif; ?>
    <p class="description"><?php esc_html_e('Shortcode example:', 'kcjdrama'); ?> <code>[kcj_quote key="<?php echo esc_html($key !== '' ? $key : 'your-key'); ?>"]</code></p>
    <?php
}

function kcj_quote_used_box($post) {
    $key = (string) get_post_meta($post->ID, '_kcj_quote_key', true);
    if ($key === '') {
        echo '<p>' . esc_html__('Save with a key to see placements.', 'kcjdrama') . '</p>';
        return;
    }
    $rows = kcj_quote_used_where($key);
    if (!$rows) {
        echo '<p>' . esc_html__('Not detected on published pages or known theme slots.', 'kcjdrama') . '</p>';
        return;
    }
    echo '<ul style="margin:0;padding-left:1.1rem;">';
    foreach ($rows as $row) {
        $url = (string) $row['url'];
        $href = str_starts_with($url, 'http') ? $url : home_url($url);
        printf(
            '<li><a href="%s" target="_blank" rel="noopener">%s</a></li>',
            esc_url($href),
            esc_html((string) $row['label'])
        );
    }
    echo '</ul>';
}

add_action('save_post_' . KCJ_QUOTE_CPT, function ($post_id) {
    if (!isset($_POST['kcj_quote_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_quote_meta_nonce'])), 'kcj_quote_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $existing_key = (string) get_post_meta($post_id, '_kcj_quote_key', true);
    $key = $existing_key !== ''
        ? $existing_key
        : sanitize_title((string) ($_POST['kcj_quote_key'] ?? ''));
    if ($key === '') {
        return;
    }
    // Enforce unique key.
    $clash = new WP_Query([
        'post_type'      => KCJ_QUOTE_CPT,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'post__not_in'   => [$post_id],
        'meta_key'       => '_kcj_quote_key',
        'meta_value'     => $key,
        'fields'         => 'ids',
    ]);
    if ($clash->have_posts()) {
        $key = $key . '-' . $post_id;
    }

    $old_content = (string) get_post_field('post_content', $post_id);
    update_post_meta($post_id, '_kcj_quote_key', $key);
    update_post_meta($post_id, '_kcj_country', strtoupper(sanitize_text_field((string) ($_POST['kcj_country'] ?? 'K'))));
    update_post_meta($post_id, '_kcj_work', sanitize_text_field((string) ($_POST['kcj_work'] ?? '')));
    update_post_meta($post_id, '_kcj_speaker', sanitize_text_field((string) ($_POST['kcj_speaker'] ?? '')));
    update_post_meta($post_id, '_kcj_source_name', sanitize_text_field((string) ($_POST['kcj_source_name'] ?? '')));
    update_post_meta($post_id, '_kcj_source_url', esc_url_raw((string) ($_POST['kcj_source_url'] ?? '')));
    update_post_meta($post_id, '_kcj_tags', sanitize_text_field((string) ($_POST['kcj_tags'] ?? '')));
    update_post_meta($post_id, '_kcj_note', sanitize_text_field((string) ($_POST['kcj_note'] ?? '')));
    update_post_meta($post_id, '_kcj_verified', isset($_POST['kcj_verified']) ? 1 : 0);
    update_post_meta($post_id, '_kcj_locked', isset($_POST['kcj_locked']) ? 1 : 0);

    $edited = isset($_POST['kcj_locally_edited']) ? 1 : 0;
    $new_content = (string) ($_POST['content'] ?? $old_content);
    if ($edited || $new_content !== $old_content) {
        $edited = 1;
    }
    update_post_meta($post_id, '_kcj_locally_edited', $edited);
}, 20);

add_filter('manage_' . KCJ_QUOTE_CPT . '_posts_columns', function ($cols) {
    $new = [];
    foreach ($cols as $k => $v) {
        $new[$k] = $v;
        if ($k === 'title') {
            $new['kcj_key'] = __('Key', 'kcjdrama');
            $new['kcj_country'] = __('Country', 'kcjdrama');
            $new['kcj_work'] = __('Work', 'kcjdrama');
            $new['kcj_flags'] = __('Flags', 'kcjdrama');
        }
    }
    return $new;
});

add_action('manage_' . KCJ_QUOTE_CPT . '_posts_custom_column', function ($col, $post_id) {
    if ($col === 'kcj_key') {
        echo esc_html((string) get_post_meta($post_id, '_kcj_quote_key', true));
    } elseif ($col === 'kcj_country') {
        echo esc_html((string) get_post_meta($post_id, '_kcj_country', true));
    } elseif ($col === 'kcj_work') {
        echo esc_html((string) get_post_meta($post_id, '_kcj_work', true));
    } elseif ($col === 'kcj_flags') {
        $bits = [];
        if ((int) get_post_meta($post_id, '_kcj_verified', true)) {
            $bits[] = 'verified';
        }
        if ((int) get_post_meta($post_id, '_kcj_locked', true)) {
            $bits[] = 'locked';
        }
        if ((int) get_post_meta($post_id, '_kcj_locally_edited', true)) {
            $bits[] = 'edited';
        }
        echo esc_html(implode(', ', $bits));
    }
}, 10, 2);

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=' . KCJ_QUOTE_CPT,
        __('Import corpus', 'kcjdrama'),
        __('Import corpus', 'kcjdrama'),
        'manage_options',
        'kcj-quote-import',
        'kcj_quote_import_page'
    );
});

/**
 * Candidate paths under the site checkout (theme may be a WP junction).
 *
 * @return list<string>
 */
function kcj_quote_corpus_roots() {
    return array_values(array_unique(array_filter([
        dirname(KCJ_PATH, 2) . '/scripts/quotes',
        dirname(KCJ_PATH) . '/scripts/quotes',
        dirname(ABSPATH) . '/scripts/quotes',
        ABSPATH . '../scripts/quotes',
    ])));
}

function kcj_quote_corpus_resolve($filename) {
    foreach (kcj_quote_corpus_roots() as $root) {
        $path = trailingslashit($root) . ltrim((string) $filename, '/\\');
        $real = realpath($path);
        if ($real && is_readable($real)) {
            return $real;
        }
        if (is_readable($path)) {
            return $path;
        }
    }
    return '';
}

function kcj_quote_corpus_db_path() {
    return kcj_quote_corpus_resolve('kcj_quotes.sqlite3');
}

function kcj_quote_corpus_csv_path() {
    return kcj_quote_corpus_resolve('export/quotes.csv');
}

function kcj_quote_import_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $result = null;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kcj_quote_import_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_quote_import_nonce'])), 'kcj_quote_import')) {
        $force = !empty($_POST['kcj_force_unlocked']);
        // CSV works under Apache without the SQLite extension (Hostinger-safe).
        $result = kcj_quote_import_from_csv($force);
    }
    $csv = kcj_quote_corpus_csv_path();
    $db = kcj_quote_corpus_db_path();
    $has_sqlite = class_exists('SQLite3');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Import quote corpus', 'kcjdrama'); ?></h1>
        <p><?php esc_html_e('Imports verified rows into Quotes CPT from the research CSV export. Stable keys never change. Edited or locked quotes are skipped.', 'kcjdrama'); ?></p>
        <p><strong><?php esc_html_e('CSV source', 'kcjdrama'); ?>:</strong>
            <code><?php echo esc_html($csv !== '' ? $csv : __('(quotes.csv not found — run: python scripts/quotes/src/export_csv.py)', 'kcjdrama')); ?></code>
        </p>
        <p class="description">
            <?php esc_html_e('SQLite research DB (optional / CLI):', 'kcjdrama'); ?>
            <code><?php echo esc_html($db !== '' ? $db : __('(not found)', 'kcjdrama')); ?></code>
            —
            <?php
            echo $has_sqlite
                ? esc_html__('SQLite3 extension loaded in this PHP.', 'kcjdrama')
                : esc_html__('SQLite3 extension not loaded in Apache PHP — CSV import is the supported path.', 'kcjdrama');
            ?>
        </p>
        <?php if (is_array($result)) : ?>
            <div class="notice notice-<?php echo ((int) $result['errors'] > 0 && (int) $result['created'] === 0 && (int) $result['skipped'] === 0) ? 'error' : 'success'; ?>"><p>
                <?php
                printf(
                    esc_html__('Created %1$d · skipped %2$d · forced updates %3$d · errors %4$d', 'kcjdrama'),
                    (int) $result['created'],
                    (int) $result['skipped'],
                    (int) $result['updated'],
                    (int) $result['errors']
                );
                ?>
            </p></div>
            <?php if (!empty($result['log'])) : ?>
                <details open><summary><?php esc_html_e('Log', 'kcjdrama'); ?></summary>
                    <pre style="max-height:20rem;overflow:auto;background:#fff;padding:1rem;border:1px solid #ccd0d4;"><?php echo esc_html(implode("\n", $result['log'])); ?></pre>
                </details>
            <?php endif; ?>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field('kcj_quote_import', 'kcj_quote_import_nonce'); ?>
            <p>
                <label>
                    <input type="checkbox" name="kcj_force_unlocked" value="1">
                    <?php esc_html_e('Force-update unlocked quotes that are not marked locally edited', 'kcjdrama'); ?>
                </label>
            </p>
            <p>
                <button type="submit" class="button button-primary" <?php disabled($csv === ''); ?>>
                    <?php esc_html_e('Import verified quotes', 'kcjdrama'); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * @return array{created:int,skipped:int,updated:int,errors:int,log:list<string>}
 */
function kcj_quote_import_empty_result() {
    return ['created' => 0, 'skipped' => 0, 'updated' => 0, 'errors' => 0, 'log' => []];
}

/**
 * Upsert one corpus row into CPT.
 *
 * @param array{corpus_id:int,text:string,country:string,work:string,speaker?:string,source_name?:string,source_url?:string,tags?:string} $row
 * @param array{created:int,skipped:int,updated:int,errors:int,log:list<string>} $out
 */
function kcj_quote_import_upsert_row(array $row, $force_unlocked, array &$out) {
    $corpus_id = (int) ($row['corpus_id'] ?? 0);
    $text = trim((string) ($row['text'] ?? ''));
    $country = strtoupper((string) ($row['country'] ?? ''));
    $work = (string) ($row['work'] ?? '');
    if ($text === '' || !in_array($country, ['K', 'C', 'J'], true)) {
        $out['errors']++;
        $out['log'][] = 'skip invalid row';
        return;
    }
    $key = kcj_quote_propose_key($country, $work, $text, $corpus_id);

    $existing = kcj_get_quote_by_key($key, false);
    if ($existing) {
        $pid = (int) $existing['post_id'];
        $locked = (int) get_post_meta($pid, '_kcj_locked', true) === 1;
        $edited = (int) get_post_meta($pid, '_kcj_locally_edited', true) === 1;
        if ($locked || $edited || !$force_unlocked) {
            $out['skipped']++;
            $out['log'][] = "skip {$key} (locked/edited/exists)";
            return;
        }
        wp_update_post([
            'ID'           => $pid,
            'post_content' => $text,
        ]);
        update_post_meta($pid, '_kcj_speaker', sanitize_text_field((string) ($row['speaker'] ?? '')));
        update_post_meta($pid, '_kcj_work', sanitize_text_field($work));
        update_post_meta($pid, '_kcj_country', $country);
        update_post_meta($pid, '_kcj_source_name', sanitize_text_field((string) ($row['source_name'] ?? '')));
        update_post_meta($pid, '_kcj_source_url', esc_url_raw((string) ($row['source_url'] ?? '')));
        update_post_meta($pid, '_kcj_tags', sanitize_text_field((string) ($row['tags'] ?? '')));
        update_post_meta($pid, '_kcj_verified', 1);
        update_post_meta($pid, '_kcj_corpus_id', $corpus_id);
        $out['updated']++;
        $out['log'][] = "update {$key}";
        return;
    }

    $base = $key;
    $n = 2;
    while (kcj_get_quote_by_key($key, false)) {
        $key = $base . '-' . $n;
        $n++;
    }

    $title = trim($work . ': ' . wp_trim_words($text, 8, '…'));
    $pid = wp_insert_post([
        'post_type'    => KCJ_QUOTE_CPT,
        'post_status'  => 'publish',
        'post_title'   => $title !== '' ? $title : $key,
        'post_content' => $text,
    ], true);
    if (is_wp_error($pid) || !$pid) {
        $out['errors']++;
        $out['log'][] = "error {$key}";
        return;
    }
    update_post_meta($pid, '_kcj_quote_key', $key);
    update_post_meta($pid, '_kcj_country', $country);
    update_post_meta($pid, '_kcj_work', sanitize_text_field($work));
    update_post_meta($pid, '_kcj_speaker', sanitize_text_field((string) ($row['speaker'] ?? '')));
    update_post_meta($pid, '_kcj_source_name', sanitize_text_field((string) ($row['source_name'] ?? '')));
    update_post_meta($pid, '_kcj_source_url', esc_url_raw((string) ($row['source_url'] ?? '')));
    update_post_meta($pid, '_kcj_tags', sanitize_text_field((string) ($row['tags'] ?? '')));
    update_post_meta($pid, '_kcj_verified', 1);
    update_post_meta($pid, '_kcj_corpus_id', $corpus_id);
    update_post_meta($pid, '_kcj_locked', 0);
    update_post_meta($pid, '_kcj_locally_edited', 0);
    if ($key === 'china-face-heartbeat') {
        update_post_meta($pid, '_kcj_note', 'Short line as craft example — Soft face vs private softness.');
    }
    $out['created']++;
    $out['log'][] = "create {$key}";
}

/**
 * Preferred admin import — no SQLite PHP extension required.
 *
 * @return array{created:int,skipped:int,updated:int,errors:int,log:list<string>}
 */
function kcj_quote_import_from_csv($force_unlocked = false) {
    $out = kcj_quote_import_empty_result();
    $path = kcj_quote_corpus_csv_path();
    if ($path === '') {
        $out['errors']++;
        $out['log'][] = 'CSV missing: scripts/quotes/export/quotes.csv (run python src/export_csv.py in scripts/quotes)';
        return $out;
    }

    $fh = fopen($path, 'rb');
    if (!$fh) {
        $out['errors']++;
        $out['log'][] = 'Could not open CSV';
        return $out;
    }
    $header = fgetcsv($fh);
    if (!is_array($header)) {
        fclose($fh);
        $out['errors']++;
        $out['log'][] = 'CSV header missing';
        return $out;
    }
    $header = array_map(static function ($h) {
        return strtolower(trim((string) $h));
    }, $header);

    $verified_col = array_search('verified', $header, true);
    while (($cols = fgetcsv($fh)) !== false) {
        if (!is_array($cols) || count($cols) < 3) {
            continue;
        }
        $row = [];
        foreach ($header as $i => $name) {
            $row[$name] = $cols[$i] ?? '';
        }
        if ($verified_col !== false && (string) ($row['verified'] ?? '') !== '1') {
            continue;
        }
        kcj_quote_import_upsert_row([
            'corpus_id'   => (int) ($row['id'] ?? 0),
            'text'        => (string) ($row['quote_text'] ?? ''),
            'country'     => (string) ($row['country'] ?? ''),
            'work'        => (string) ($row['work_title'] ?? ''),
            'speaker'     => (string) ($row['speaker'] ?? ''),
            'source_name' => (string) ($row['source_name'] ?? ''),
            'source_url'  => (string) ($row['source_url'] ?? ''),
            'tags'        => (string) ($row['tags'] ?? ''),
        ], $force_unlocked, $out);
    }
    fclose($fh);
    $out['log'][] = 'source=csv ' . $path;
    return $out;
}

/**
 * Optional CLI/SQLite path when extension is loaded.
 *
 * @return array{created:int,skipped:int,updated:int,errors:int,log:list<string>}
 */
function kcj_quote_import_from_sqlite($force_unlocked = false) {
    $out = kcj_quote_import_empty_result();
    $path = kcj_quote_corpus_db_path();
    if ($path === '') {
        $out['errors']++;
        $out['log'][] = 'SQLite DB file not found';
        return $out;
    }
    if (!class_exists('SQLite3')) {
        $out['errors']++;
        $out['log'][] = 'SQLite3 extension not loaded in this PHP SAPI (Apache often lacks it). Use CSV import instead.';
        return $out;
    }

    try {
        $db = new SQLite3($path, SQLITE3_OPEN_READONLY);
    } catch (Exception $e) {
        $out['errors']++;
        $out['log'][] = $e->getMessage();
        return $out;
    }

    $sql = "SELECT q.id, q.quote_text, q.speaker, q.source_name, q.source_url,
                   w.title AS work_title, w.country,
                   (SELECT GROUP_CONCAT(t.slug, '|') FROM quote_tags qt JOIN tags t ON t.id=qt.tag_id WHERE qt.quote_id=q.id) AS tags
            FROM quotes q
            JOIN works w ON w.id = q.work_id
            WHERE q.verified = 1
            ORDER BY q.id";
    $res = $db->query($sql);
    if (!$res) {
        $out['errors']++;
        $out['log'][] = 'Query failed';
        $db->close();
        return $out;
    }

    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        kcj_quote_import_upsert_row([
            'corpus_id'   => (int) $row['id'],
            'text'        => (string) $row['quote_text'],
            'country'     => (string) $row['country'],
            'work'        => (string) $row['work_title'],
            'speaker'     => (string) ($row['speaker'] ?? ''),
            'source_name' => (string) ($row['source_name'] ?? ''),
            'source_url'  => (string) ($row['source_url'] ?? ''),
            'tags'        => (string) ($row['tags'] ?? ''),
        ], $force_unlocked, $out);
    }

    $db->close();
    $out['log'][] = 'source=sqlite ' . $path;
    return $out;
}
