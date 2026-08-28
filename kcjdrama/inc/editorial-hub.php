<?php
/**
 * Soft Tropes / Soft Essays / Mirror Syndromes index hubs.
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once KCJ_PATH . '/inc/content/pack-data.php';

function kcj_ensure_editorial_hub_pages() {
    $pages = [
        'tropes'    => 'Tropes',
        'syndromes' => 'Syndromes',
        'essays'    => 'Essays',
        'stories'    => 'Stories',
        'victim-log' => 'Victim Log',
        'sign-in'    => 'Sign in',
    ];
    foreach ($pages as $slug => $title) {
        if (get_page_by_path($slug)) {
            continue;
        }
        wp_insert_post([
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
add_action('init', 'kcj_ensure_editorial_hub_pages', 25);

/**
 * First readable lede from post HTML (prefers .kcj-lede).
 */
function kcj_hub_post_lede($post) {
    $html = (string) $post->post_content;
    if (preg_match('/<p[^>]*class="[^"]*kcj-lede[^"]*"[^>]*>(.*?)<\/p>/is', $html, $m)) {
        return trim(wp_strip_all_tags($m[1]));
    }
    $plain = trim(wp_strip_all_tags($html));
    if ($plain === '') {
        return '';
    }
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($plain, 0, 160, '…', 'UTF-8');
    }
    return strlen($plain) > 160 ? substr($plain, 0, 160) . '…' : $plain;
}

/**
 * Hub entries from a Soft editorial category (essay, starter-pack, …).
 *
 * @return array<int,array{slug:string,title:string,one:string,href:string,has_post:bool,soft:string,mirror:string,craft:string}>
 */
function kcj_hub_category_entries($category_name) {
    $posts = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 50,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'category_name'  => $category_name,
    ]);
    $out = [];
    foreach ($posts as $post) {
        $out[] = [
            'slug'     => (string) $post->post_name,
            'title'    => get_the_title($post),
            'one'      => kcj_hub_post_lede($post),
            'byline'   => function_exists('kcj_post_byline_label') ? kcj_post_byline_label($post) : 'By kcjdrama',
            'soft'     => '',
            'mirror'   => '',
            'craft'    => '',
            'href'     => get_permalink($post),
            'has_post' => true,
        ];
    }
    return $out;
}

/**
 * Kind + room for a post in the field-notes feed.
 *
 * @return array{key:string,label:string,tone:string}
 */
function kcj_post_kind($post = null) {
    $cats = get_the_category($post);
    $slugs = wp_list_pluck($cats, 'slug');
    if (array_intersect($slugs, ['syndrome', 'mirror-roast', 'listicle'])) {
        return ['key' => 'syndrome', 'label' => 'Syndrome', 'tone' => 'mirror'];
    }
    if (in_array('essay', $slugs, true)) {
        return ['key' => 'essay', 'label' => 'Essay', 'tone' => 'soft'];
    }
    if (in_array('starter-pack', $slugs, true)) {
        return ['key' => 'starter', 'label' => 'Starter', 'tone' => 'soft'];
    }
    if (in_array('trope-deep-dive', $slugs, true)) {
        return ['key' => 'trope', 'label' => 'Trope', 'tone' => 'soft'];
    }
    return ['key' => 'note', 'label' => 'Note', 'tone' => 'soft'];
}

function kcj_hub_essay_entries() {
    return kcj_hub_category_entries('essay');
}

function kcj_hub_stories_entries() {
    // Starter packs are reading paths built from trope atoms (not IP retellings).
    return kcj_hub_category_entries('starter-pack');
}

/**
 * @param array  $entries Pack rows with slug, title, one, …
 * @param string $prefix  trope- | syndrome-
 * @return array<int,array{slug:string,title:string,one:string,href:string,has_post:bool,soft?:string,mirror?:string,craft?:string}>
 */
function kcj_hub_enrich_entries(array $entries, $prefix) {
    $names = [];
    foreach ($entries as $row) {
        if (!empty($row['slug'])) {
            $names[] = $prefix . $row['slug'];
        }
    }
    $by_name = [];
    if ($names) {
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => count($names),
            'post_name__in'  => $names,
        ]);
        foreach ($posts as $post) {
            $by_name[(string) $post->post_name] = $post;
        }
    }

    $out = [];
    foreach ($entries as $row) {
        if (empty($row['slug']) || empty($row['title'])) {
            continue;
        }
        $slug = (string) $row['slug'];
        $post = $by_name[$prefix . $slug] ?? null;
        $out[] = [
            'slug'     => $slug,
            'title'    => (string) $row['title'],
            'one'      => (string) ($row['one'] ?? ''),
            'byline'   => function_exists('kcj_post_byline_label')
                ? kcj_post_byline_label($post)
                : 'By kcjdrama',
            'soft'     => (string) ($row['soft'] ?? ''),
            'mirror'   => (string) ($row['mirror'] ?? ''),
            'craft'    => (string) ($row['craft'] ?? ''),
            'href'     => $post ? get_permalink($post) : '',
            'has_post' => (bool) $post,
        ];
    }
    usort($out, static function ($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });
    return $out;
}

/**
 * Render Soft tropes / essays / stories / Mirror syndromes hub.
 *
 * @param string $kind tropes|syndromes|essays|stories
 */
function kcj_render_editorial_hub($kind) {
    $allowed = ['tropes', 'syndromes', 'essays', 'stories'];
    if (!in_array($kind, $allowed, true)) {
        $kind = 'tropes';
    }
    $tone = $kind === 'syndromes' ? 'mirror' : 'soft';
    $meta_mode = 'indexed';
    $filter_ph = __('Find a trope…', 'kcjdrama');
    $hub_body = '';
    $show_dx = false;
    $none_msg = __('Nothing matches that filter.', 'kcjdrama');
    $hub_mod = '';

    if ($kind === 'tropes') {
        $entries = kcj_hub_enrich_entries(kcj_content_tropes(), 'trope-');
        $kicker = 'Soft World · Encyclopedia';
        $lede = 'Romance-drama patterns across Korea, China, and Japan. Craft notes first — Mirror jokes optional.';
        $empty = 'Trope deep dives are being typeset. Seed cards below are the working index.';
        $cross_label = 'Stories';
        $cross_url = kcj_page_url('stories');
        $cross2_label = 'Essays';
        $cross2_url = kcj_page_url('essays');
        $shop_url = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'soft']) : kcj_page_url('shop');
        $shop_label = 'Shop Soft merch';
        $world_url = kcj_page_url('soft');
        $world_label = 'Soft stage';
    } elseif ($kind === 'essays') {
        $entries = kcj_hub_essay_entries();
        $kicker = 'Soft World · Longform';
        $lede = 'Craft, culture bridges, and ethics of fandom speech — Soft-rail commentary you can read without a spoiler tax.';
        $empty = 'Essays are warming up. Soft longform will land here as posts in the essay category.';
        $cross_label = 'Stories';
        $cross_url = kcj_page_url('stories');
        $cross2_label = 'Trope encyclopedia';
        $cross2_url = kcj_page_url('tropes');
        $shop_url = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'soft']) : kcj_page_url('shop');
        $shop_label = 'Shop Soft merch';
        $world_url = kcj_page_url('soft');
        $world_label = 'Soft stage';
        $meta_mode = 'essays';
        $filter_ph = __('Find an essay…', 'kcjdrama');
    } elseif ($kind === 'stories') {
        $entries = kcj_hub_stories_entries();
        $kicker = 'Soft World · Stories desk';
        $lede = 'Original Soft fiction will live here — patterns, not pirated plots. Today the desk opens with starter packs: reading paths built from trope atoms.';
        $empty = 'Starter packs and Soft shorts will land here. Until then, wander Tropes and steal structures (not plots).';
        $cross_label = 'Trope encyclopedia';
        $cross_url = kcj_page_url('tropes');
        $cross2_label = 'Essays';
        $cross2_url = kcj_page_url('essays');
        $shop_url = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'soft']) : kcj_page_url('shop');
        $shop_label = 'Shop Soft merch';
        $world_url = kcj_page_url('soft');
        $world_label = 'Soft stage';
        $meta_mode = 'stories';
        $filter_ph = __('Find a starter pack…', 'kcjdrama');
    } else {
        $entries = kcj_hub_enrich_entries(kcj_content_syndromes(), 'syndrome-');
        $kicker = 'Mirror World · Intake';
        $lede = 'A syndrome is a cluster of symptoms with a nickname. After enough finales, yours has one.';
        $hub_body = 'We name the affliction so you can stop calling it a personality. Roast of devices — not actors. Soft keeps the craft notes; this room files the damage.';
        $empty = 'The clinic is open for naming. Case files land here as posts when seeded.';
        $cross_label = 'Trope encyclopedia';
        $cross_url = kcj_page_url('tropes');
        $cross2_label = 'Victim Log';
        $cross2_url = kcj_page_url('victim-log');
        $shop_url = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'mirror']) : kcj_page_url('shop');
        $shop_label = 'Shop Mirror merch';
        $world_url = kcj_page_url('mirror');
        $world_label = 'Mirror stage';
        $filter_ph = __('Name the affliction…', 'kcjdrama');
        $none_msg = __('No file under that name.', 'kcjdrama');
        $show_dx = true;
        $hub_mod = ' kcj-hub--clinic';
    }

    $posted = count(array_filter($entries, static function ($e) {
        return !empty($e['has_post']);
    }));
    $total = count($entries);
    $use_tools = $kind === 'syndromes' || $total >= 8;
    $use_az = $kind === 'syndromes' || $total >= 12;
    $groups = $use_az ? kcj_hub_letter_groups($entries) : ['*' => $entries];
    $n = 0;
    ?>
    <main id="kcj-main" class="kcj-page kcj-page--<?php echo esc_attr($tone); ?> kcj-hub<?php echo esc_attr($hub_mod); ?>">
        <div class="kcj-hub-inner">
            <header class="kcj-hub-hero">
                <p class="kcj-brand-folio"><?php echo $tone === 'mirror' ? '02' : '01'; ?></p>
                <p class="kcj-page-kicker"><?php echo esc_html($kicker); ?></p>
                <h1><?php the_title(); ?></h1>
                <p class="kcj-hub-lede"><?php echo esc_html($lede); ?></p>
                <?php if ($hub_body !== '') : ?>
                    <p class="kcj-hub-body"><?php echo esc_html($hub_body); ?></p>
                <?php endif; ?>
                <p class="kcj-hub-meta">
                    <?php
                    if ($meta_mode === 'essays') {
                        printf(
                            esc_html(_n('%d essay on the Soft rail', '%d essays on the Soft rail', $total, 'kcjdrama')),
                            $total
                        );
                    } elseif ($meta_mode === 'stories') {
                        printf(
                            esc_html(_n('%d starter pack on the Stories desk', '%d starter packs on the Stories desk', $total, 'kcjdrama')),
                            $total
                        );
                    } elseif ($kind === 'syndromes') {
                        printf(
                            esc_html(_n('%d named syndrome', '%d named syndromes', $total, 'kcjdrama')),
                            $total
                        );
                    } else {
                        printf(
                            esc_html(_n('%d trope in the encyclopedia', '%d tropes in the encyclopedia', $total, 'kcjdrama')),
                            $total
                        );
                    }
                    if ($use_az) {
                        echo ' · ';
                        esc_html_e('A–Z', 'kcjdrama');
                    }
                    if ($posted && $posted < $total) {
                        echo ' · ';
                        printf(
                            esc_html(_n('%d deep dive', '%d deep dives', $posted, 'kcjdrama')),
                            $posted
                        );
                    }
                    ?>
                </p>
                <div class="kcj-brand-actions">
                    <a class="kcj-btn kcj-btn--<?php echo esc_attr($tone); ?>" href="<?php echo esc_url($shop_url); ?>"><?php echo esc_html($shop_label); ?></a>
                    <a class="kcj-brand-cross" href="<?php echo esc_url($world_url); ?>"><?php echo esc_html($world_label); ?></a>
                    <a class="kcj-brand-cross" href="<?php echo esc_url($cross_url); ?>"><?php echo esc_html($cross_label); ?></a>
                    <?php if (!empty($cross2_url) && !empty($cross2_label)) : ?>
                        <a class="kcj-brand-cross" href="<?php echo esc_url($cross2_url); ?>"><?php echo esc_html($cross2_label); ?></a>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!$entries) : ?>
                <p class="kcj-hub-empty"><?php echo esc_html($empty); ?></p>
            <?php else : ?>
                <div class="kcj-hub-catalog">
                <?php if ($use_tools) : ?>
                    <div class="kcj-hub-tools">
                        <label class="kcj-vh" for="kcj-hub-q"><?php esc_html_e('Filter entries', 'kcjdrama'); ?></label>
                        <input
                            id="kcj-hub-q"
                            class="kcj-hub-filter"
                            type="search"
                            placeholder="<?php echo esc_attr($filter_ph); ?>"
                            autocomplete="off"
                            spellcheck="false"
                            aria-controls="kcj-hub-index"
                        >
                        <?php if ($use_az) : ?>
                            <nav class="kcj-hub-az" aria-label="<?php esc_attr_e('Letters', 'kcjdrama'); ?>">
                                <?php
                                $az = range('A', 'Z');
                                if (isset($groups['#'])) {
                                    $az[] = '#';
                                }
                                foreach ($az as $letter) :
                                    $lid = $letter === '#' ? 'other' : $letter;
                                    if (isset($groups[$letter])) :
                                        ?>
                                        <a href="#hub-<?php echo esc_attr($lid); ?>" data-az="<?php echo esc_attr($letter); ?>"><?php echo esc_html($letter); ?></a>
                                    <?php else : ?>
                                        <span data-az="<?php echo esc_attr($letter); ?>" aria-hidden="true"><?php echo esc_html($letter); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="kcj-hub-index" id="kcj-hub-index" data-kcj-hub-index>
                    <?php foreach ($groups as $letter => $rows) :
                        $lid = $letter === '#' ? 'other' : $letter;
                        ?>
                        <section class="kcj-hub-letter"<?php echo $letter !== '*' ? ' id="hub-' . esc_attr($lid) . '" data-letter="' . esc_attr($letter) . '"' : ''; ?>>
                            <?php if ($use_az) : ?>
                                <h2 class="kcj-hub-letter-label"><?php echo esc_html($letter); ?></h2>
                            <?php endif; ?>
                            <ol class="kcj-hub-grid">
                                <?php foreach ($rows as $entry) :
                                    $n++;
                                    $dx = $show_dx ? kcj_hub_strip_rail_prefix($entry['mirror'] ?? '') : '';
                                    $needle = strtolower($entry['title'] . ' ' . $entry['one'] . ' ' . $entry['slug'] . ' ' . $dx);
                                    $open = !empty($entry['has_post']) && $entry['href'] !== '';
                                    ?>
                                    <li id="entry-<?php echo esc_attr($entry['slug']); ?>">
                                        <?php if ($open) : ?>
                                        <a class="kcj-hub-card kcj-hub-card--<?php echo esc_attr($tone); ?>" href="<?php echo esc_url($entry['href']); ?>" data-hub-title="<?php echo esc_attr($needle); ?>">
                                        <?php else : ?>
                                        <div class="kcj-hub-card kcj-hub-card--<?php echo esc_attr($tone); ?> kcj-hub-card--seed" data-hub-title="<?php echo esc_attr($needle); ?>">
                                        <?php endif; ?>
                                            <span class="kcj-hub-card-kicker"><?php echo esc_html(str_pad((string) $n, 2, '0', STR_PAD_LEFT)); ?></span>
                                            <span class="kcj-hub-card-copy">
                                                <span class="kcj-hub-card-title"><?php echo esc_html($entry['title']); ?></span>
                                                <span class="kcj-hub-card-byline"><?php echo esc_html(!empty($entry['byline']) ? $entry['byline'] : (function_exists('kcj_post_byline_label') ? kcj_post_byline_label(null) : 'By kcjdrama')); ?></span>
                                                <?php if ($entry['one'] !== '') : ?>
                                                    <span class="kcj-hub-card-one"><?php echo esc_html($entry['one']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($dx !== '') : ?>
                                                    <span class="kcj-hub-card-dx"><?php echo esc_html($dx); ?></span>
                                                <?php endif; ?>
                                            </span>
                                        <?php echo $open ? '</a>' : '</div>'; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </section>
                    <?php endforeach; ?>
                    <p class="kcj-hub-empty kcj-hub-none" hidden role="status"><?php echo esc_html($none_msg); ?></p>
                </div>
                <?php if ($use_tools) : ?>
                    <script>
                    (function () {
                        var q = document.getElementById('kcj-hub-q');
                        var catalog = document.querySelector('.kcj-hub-catalog');
                        if (!q || !catalog) return;
                        var root = catalog.querySelector('[data-kcj-hub-index]');
                        if (!root) return;
                        var none = root.querySelector('.kcj-hub-none');
                        var az = catalog.querySelectorAll('.kcj-hub-az [data-az]');
                        q.addEventListener('input', function () {
                            var needle = q.value.trim().toLowerCase();
                            var shown = 0;
                            root.querySelectorAll('.kcj-hub-letter').forEach(function (sec) {
                                var n = 0;
                                sec.querySelectorAll('.kcj-hub-card').forEach(function (card) {
                                    var hit = !needle || (card.getAttribute('data-hub-title') || '').indexOf(needle) !== -1;
                                    card.parentElement.hidden = !hit;
                                    if (hit) n++;
                                });
                                sec.hidden = n === 0;
                                shown += n;
                            });
                            if (none) none.hidden = shown !== 0;
                            az.forEach(function (el) {
                                var letter = el.getAttribute('data-az') || '';
                                var sec = root.querySelector('.kcj-hub-letter[data-letter="' + letter + '"]');
                                var on = !!(sec && !sec.hidden);
                                el.classList.toggle('is-empty', !on);
                                if (el.tagName === 'A') {
                                    if (on) el.removeAttribute('aria-disabled');
                                    else el.setAttribute('aria-disabled', 'true');
                                }
                            });
                        });
                    })();
                    </script>
                <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php
}

/**
 * Drop a leading "Soft:" / "Mirror:" rail tag from pack copy.
 */
function kcj_hub_strip_rail_prefix($text) {
    $text = trim((string) $text);
    if ($text !== '' && preg_match('/^(Soft|Mirror)\s*:\s*/i', $text, $m)) {
        $text = trim(substr($text, strlen($m[0])));
    }
    if ($text === '') {
        return '';
    }
    if (function_exists('mb_substr')) {
        return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');
    }
    return strtoupper($text[0]) . substr($text, 1);
}

/**
 * Group hub entries by first letter of title.
 *
 * @param array<int,array{title:string}> $entries
 * @return array<string,array<int,array>>
 */
function kcj_hub_letter_groups(array $entries) {
    $groups = [];
    foreach ($entries as $row) {
        $title = ltrim((string) ($row['title'] ?? ''));
        $ch = strtoupper(substr($title, 0, 1));
        if ($ch < 'A' || $ch > 'Z') {
            $ch = '#';
        }
        $groups[$ch][] = $row;
    }
    uksort($groups, static function ($a, $b) {
        if ($a === '#') {
            return 1;
        }
        if ($b === '#') {
            return -1;
        }
        return strcmp($a, $b);
    });
    return $groups;
}
