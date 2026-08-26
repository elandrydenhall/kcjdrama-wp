<?php
/**
 * Idempotent mega-site content pack for gsolo-kcjdrama.
 * Run: wp eval 'kcj_seed_megaset();'  (via scripts/wp.sh)
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/content/pack-data.php';

function kcj_seed_megaset() {
    if (!get_option('permalink_structure')) {
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules(false);
    }

    kcj_mega_ensure_categories();
    kcj_mega_ensure_pages();
    kcj_mega_ensure_blog_page();
    kcj_mega_seed_tropes();
    kcj_mega_seed_syndromes();
    kcj_mega_seed_essays_and_starters();
    kcj_mega_seed_bingo();
    update_option('kcj_megaset_version', '1.1.0', false);
    flush_rewrite_rules(false);
    return 'kcj megaset seeded';
}

function kcj_mega_ensure_categories() {
    $cats = [
        'korea' => 'Korea',
        'china' => 'China',
        'japan' => 'Japan',
        'cross-culture' => 'Cross-culture',
        'trope-deep-dive' => 'Trope deep dive',
        'syndrome' => 'Syndrome',
        'craft' => 'Craft',
        'starter-pack' => 'Starter pack',
        'listicle' => 'Listicle',
        'essay' => 'Essay',
        'mirror-roast' => 'Mirror roast',
        'soft-feels' => 'Soft feels',
        'glossary' => 'Glossary',
    ];
    foreach ($cats as $slug => $name) {
        if (!get_term_by('slug', $slug, 'category')) {
            wp_insert_term($name, 'category', ['slug' => $slug]);
        }
    }
    // Drop default uncategorized from being sole home if possible — leave it.
}

function kcj_mega_upsert_page($slug, $title, $content, $parent_slug = '') {
    $path = $parent_slug ? trailingslashit($parent_slug) . $slug : $slug;
    $existing = get_page_by_path($path);
    $parent = 0;
    if ($parent_slug) {
        $p = get_page_by_path($parent_slug);
        $parent = $p ? (int) $p->ID : 0;
    }
    $data = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_parent'  => $parent,
    ];
    if ($existing) {
        $data['ID'] = $existing->ID;
        return wp_update_post($data);
    }
    return wp_insert_post($data);
}

function kcj_mega_hub_link($href, $strong, $span) {
    return '<a href="' . esc_url($href) . '"><strong>' . esc_html($strong) . '</strong><span>' . esc_html($span) . '</span></a>';
}

function kcj_mega_ensure_pages() {
    $home = home_url('/');
    $tropes_url = home_url('/tropes/');
    $syn_url = home_url('/syndromes/');
    $blog = home_url('/blog/');

    kcj_mega_upsert_page(
        'start-here',
        'Start here',
        '<p class="kcj-lede">Korean, Chinese, and Japanese romance dramas share a grammar — status gaps, slow recognition, weather as emotion — and then each country plays it in a different key.</p>
        <div class="kcj-callout"><p><strong>How to use this site:</strong> Soft is sincere commentary. Mirror is meme-trope chaos. Everything here is rewritten original text. No stolen stills, no script dumps, no piracy links.</p></div>
        <h2>Three desks, one feeling</h2>
        <div class="kcj-hub-grid">'
        . kcj_mega_hub_link(home_url('/countries/korea/'), 'Korea desk', 'Chaebol fantasy, OST architecture, second-lead culture, 16-episode pressure cookers.')
        . kcj_mega_hub_link(home_url('/countries/china/'), 'China desk', 'Xianxia destiny, face-slap catharsis, transmigration agency, palace heat.')
        . kcj_mega_hub_link(home_url('/countries/japan/'), 'Japan desk', 'Workplace restraint, slow-burn silence, last-episode courage.')
        . kcj_mega_hub_link($tropes_url, 'Trope encyclopedia', 'Shared patterns with Soft craft notes and Mirror jokes.')
        . kcj_mega_hub_link($syn_url, 'Syndrome clinic', 'Name the affliction. You’re among friends.')
        . kcj_mega_hub_link($blog, 'Field notes blog', 'Essays, starter packs, ongoing dispatches.')
        . '</div>
        <h2>Soft or Mirror?</h2>
        <p><a href="' . esc_url(home_url('/soft/')) . '">Enter Soft</a> when you want craft and comfort. <a href="' . esc_url(home_url('/mirror/')) . '">Enter Mirror</a> when you want the roast and the bingo.</p>'
    );

    kcj_mega_upsert_page(
        'soft',
        'Soft',
        '<p class="kcj-page-kicker">Soft World</p>
        <h2>Craft, comfort, courage</h2>
        <p class="kcj-lede">Why a wrist-grab works. How silence stores voltage. K, C, and J romance as sincere desks — not spoiler dumps.</p>
        <p>Soft World is the sincere desk: craft notes for the romance you came for, without treating fans like they need a plot summary dump.</p>
        <div class="kcj-hub-grid">'
        . kcj_mega_hub_link(home_url('/start-here/'), 'Start here', 'Global on-ramp in one sitting.')
        . kcj_mega_hub_link($tropes_url, 'Tropes', 'Deep dives without spoilers-as-default.')
        . kcj_mega_hub_link(home_url('/glossary/'), 'Glossary', 'Chaebol, xianxia, shokuba, danmei literacy, and more.')
        . kcj_mega_hub_link(home_url('/essays/'), 'Essays', 'Longer commentary for the serious rail.')
        . kcj_mega_hub_link(home_url('/stories/'), 'Stories', 'Original fiction inspired by tropes — not IP clones.')
        . kcj_mega_hub_link(home_url('/editorial-policy/'), 'Editorial policy', 'What we publish and what we refuse.')
        . kcj_mega_hub_link(home_url('/shop/?rail=soft'), 'Soft merch', 'Porcelain romance on fabric.')
        . '</div>
        <p>Collections and merch experiments may appear later. The words come first.</p>'
    );

    kcj_mega_upsert_page(
        'mirror',
        'Mirror',
        '<p class="kcj-page-kicker">Mirror World</p>
        <h2>Same tropes. No plot armor.</h2>
        <p class="kcj-lede">Affectionate chaos only. Syndromes, bingo, and roast readings for the meme-fluent half of your brain.</p>
        <p>Mirror World roasts the patterns we love. We mock devices, not people — and we still show up for the next episode.</p>
        <div class="kcj-hub-grid">'
        . kcj_mega_hub_link($syn_url, 'Syndromes', 'SLS, OST tear reflex, slow-burn starvation…')
        . kcj_mega_hub_link(home_url('/bingo/'), 'Bingo hall', 'Checklists for the trope-addicted.')
        . kcj_mega_hub_link(home_url('/memes/'), 'Memes we own', 'Formats written here — not scraped reaction panels of actors.')
        . kcj_mega_hub_link(home_url('/victim-log/'), 'Victim Log', 'Tropes that did not survive contact with us.')
        . kcj_mega_hub_link(home_url('/about-the-roast/'), 'About the Roast', 'Rules of engagement.')
        . kcj_mega_hub_link(home_url('/shop/?rail=mirror'), 'Mirror merch', 'Violet circuit roast on fabric.')
        . '</div>'
    );

    kcj_mega_upsert_page(
        'tropes',
        'Tropes',
        '<p class="kcj-lede">An encyclopedia of romance-drama patterns across Korea, China, and Japan. Each entry has craft notes (Soft) and chaos notes (Mirror). All copy is original to KCJDrama.</p>
        <p><a href="' . esc_url(get_category_link(get_term_by('slug', 'trope-deep-dive', 'category'))) . '">Browse all trope deep dives →</a></p>
        <div class="kcj-callout"><p>We describe patterns, not pirated plots. Title mentions stay light. Bring your own legal streams.</p></div>'
    );

    kcj_mega_upsert_page(
        'syndromes',
        'Syndromes',
        '<p class="kcj-lede">Clinical names for extremely normal brain behavior after too many finales.</p>
        <p><a href="' . esc_url(get_category_link(get_term_by('slug', 'syndrome', 'category'))) . '">Open the clinic →</a></p>'
    );

    $glossary_html = '<p class="kcj-lede">Quick literacy for global readers. Terms are explained; they are not a license to pirate novels or video.</p><dl>';
    foreach (kcj_content_glossary() as $term => $def) {
        $glossary_html .= '<dt id="' . esc_attr($term) . '"><strong>' . esc_html($term) . '</strong></dt><dd>' . esc_html($def) . '</dd>';
    }
    $glossary_html .= '</dl>';
    kcj_mega_upsert_page('glossary', 'Glossary', $glossary_html);

    kcj_mega_upsert_page(
        'essays',
        'Essays',
        '<p class="kcj-lede">Longer Soft-rail commentary: craft, culture bridges, ethics of fandom speech.</p>
        <p><a href="' . esc_url(get_category_link(get_term_by('slug', 'essay', 'category'))) . '">Read essays →</a></p>'
    );

    kcj_mega_upsert_page(
        'memes',
        'Memes we own',
        '<p class="kcj-lede">Text-native meme formats written for this site. No scraped celebrity face panels. Remix the words; don’t steal likenesses.</p>
        <ul>
            <li><strong>Clause 7:</strong> “No feelings.” (Narrator: there were feelings.)</li>
            <li><strong>Umbrella GDP:</strong> national romance economy measured in shared canopies.</li>
            <li><strong>Finale Calendar Fear:</strong> marking the day honesty becomes legal.</li>
            <li><strong>Shrine Maintenance:</strong> unpaid emotional labor for the second lead.</li>
        </ul>
        <p><a href="' . esc_url(home_url('/bingo/')) . '">Take it to the bingo hall →</a></p>'
    );

    kcj_mega_upsert_page(
        'bingo',
        'Bingo hall',
        '<p class="kcj-lede">Print with your eyes. No download required. Check boxes in your soul.</p>
        <p><a href="' . esc_url(home_url('/bingo/romance-starter/')) . '">Romance starter bingo</a> ·
        <a href="' . esc_url(home_url('/bingo/xianxia-chaos/')) . '">Xianxia chaos bingo</a> ·
        <a href="' . esc_url(home_url('/bingo/workplace-soft/')) . '">Workplace soft bingo</a></p>'
    );

    kcj_mega_upsert_page(
        'editorial-policy',
        'Editorial policy',
        '<h2>What we publish</h2>
        <p>Original essays, trope definitions, glossaries, satire, and original fiction inspired by <em>patterns</em>. Global English, ESL-friendly.</p>
        <h2>What we refuse</h2>
        <ul>
            <li>Episode-by-episode plot lifts and long dialogue quotes</li>
            <li>Official posters, stills, OST audio, subtitle files</li>
            <li>Scraped fanart of copyrighted characters</li>
            <li>Piracy / illegal stream directories</li>
            <li>Copy-paste from other recap sites — we read, then rewrite in KCJ voice</li>
        </ul>
        <h2>Fan art</h2>
        <p>On this site, “fan art” means work we create or commission under our license — or clearly CC0 assets. We do not host unlicensed character art uploads.</p>
        <h2>People</h2>
        <p>Mirror roasts tropes and storytelling habits. We do not punch down at actors’ bodies or private lives.</p>'
    );

    kcj_mega_upsert_page(
        'about',
        'About',
        '<p class="kcj-lede">kcjdrama sits between the romance you came for and the satire you needed — Korea, China, and Japan in one lean WordPress.</p>
        <p>Soft is the sincere rail. Mirror is the chaos rail. Both are first-class.</p>
        <p><a href="' . esc_url(home_url('/start-here/')) . '">Start here</a> · <a href="' . esc_url(home_url('/editorial-policy/')) . '">Editorial policy</a></p>'
    );

    kcj_mega_upsert_page(
        'about-the-roast',
        'About the Roast',
        '<p>The Mirror side is the roast. Same tropes, no plot armor. We exaggerate devices — rain confessions, contract clauses, mid-season amnesia — because loving a genre includes laughing at its habits.</p>
        <p>If a joke stops being affectionate, it doesn’t ship.</p>'
    );

    kcj_mega_upsert_page(
        'victim-log',
        'Victim Log',
        '<p class="kcj-lede">A running list of tropes that did not survive contact with Mirror World.</p>
        <ul>
            <li>Selective amnesia with perfect hair continuity</li>
            <li>Umbrella that appears from another dimension</li>
            <li>Glasses as full identity wipe</li>
            <li>“I’ll resign from feelings” speeches</li>
            <li>Childhood friend RNG: critical miss</li>
        </ul>
        <p>File new injuries via the syndromes clinic.</p>'
    );

    kcj_mega_upsert_page(
        'stories',
        'Stories',
        '<p class="kcj-lede">Short original fiction built from trope atoms — not retellings of existing shows. More pieces arrive as the Soft desk grows.</p>
        <p>Meanwhile, wander the <a href="' . esc_url($tropes_url) . '">trope encyclopedia</a> and steal structures (not plots) for your own writing.</p>'
    );

    // Country desks under /countries/
    kcj_mega_upsert_page('countries', 'Countries', '<p>Three desks. One romance language family.</p><ul><li><a href="' . esc_url(home_url('/countries/korea/')) . '">Korea</a></li><li><a href="' . esc_url(home_url('/countries/china/')) . '">China</a></li><li><a href="' . esc_url(home_url('/countries/japan/')) . '">Japan</a></li></ul>');

    kcj_mega_upsert_page(
        'korea',
        'Korea desk',
        '<p class="kcj-lede">K-romance runs on compressed time: roughly sixteen episodes to invent a private world, break it, and choose it again.</p>
        <h2>What it feels like</h2>
        <p>Status gaps (often chaebol-coded), OST as emotional architecture, friend-group found family, and a cultural fluency with second-lead heartbreak.</p>
        <h2>Soft desk picks to read</h2>
        <ul><li>Chaebol inheritance war</li><li>OST as emotional architecture</li><li>Second lead magnetic</li><li>Car confession chase</li></ul>
        <p><a href="' . esc_url($tropes_url) . '">Open tropes</a> · <a href="' . esc_url(get_category_link(get_term_by('slug', 'korea', 'category'))) . '">Korea-tagged posts</a></p>',
        'countries'
    );

    kcj_mega_upsert_page(
        'china',
        'China desk',
        '<p class="kcj-lede">C-romance stretches — web-novel pacing, destiny metaphors, and public catharsis (face-slapping) beside private softness.</p>
        <h2>What it feels like</h2>
        <p>Xianxia and palace stakes make love cosmically expensive. Transmigration grants agency. Danmei literacy matters internationally — we teach terms, we don’t host pirated text.</p>
        <h2>Soft desk picks</h2>
        <ul><li>Transmigration into the book</li><li>Face-slapping court arc</li><li>Red-thread destiny</li><li>Villainess rewrites fate</li></ul>
        <p><a href="' . esc_url(get_category_link(get_term_by('slug', 'china', 'category'))) . '">China-tagged posts</a></p>',
        'countries'
    );

    kcj_mega_upsert_page(
        'japan',
        'Japan desk',
        '<p class="kcj-lede">J-romance often wins by subtraction: fewer speeches, more weather, more workplace air you can almost taste.</p>
        <h2>What it feels like</h2>
        <p>Shokuba hierarchy, slow-burn silence, confessions that arrive like last trains. Everyday life is the epic.</p>
        <h2>Soft desk picks</h2>
        <ul><li>Slow-burn silence</li><li>Office senpai distance</li><li>Last-episode confession</li><li>Food as love language</li></ul>
        <p><a href="' . esc_url(get_category_link(get_term_by('slug', 'japan', 'category'))) . '">Japan-tagged posts</a></p>',
        'countries'
    );
}

function kcj_mega_ensure_blog_page() {
    $blog_id = kcj_mega_upsert_page(
        'blog',
        'Blog',
        '<p>Field notes land here. Use categories to filter Soft essays vs Mirror chaos.</p>'
    );
    if ($blog_id && !is_wp_error($blog_id)) {
        update_option('show_on_front', 'page');
        // Keep front page as the theme front (page "Home" if exists) or create minimal.
        $front = get_page_by_path('home');
        if (!$front) {
            // Use a dedicated front placeholder that theme front-page.php overrides when is_front_page.
            $front_id = wp_insert_post([
                'post_title' => 'Home',
                'post_name' => 'home',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '',
            ]);
        } else {
            $front_id = $front->ID;
            if ($front->post_status !== 'publish') {
                wp_update_post(['ID' => $front_id, 'post_status' => 'publish']);
            }
        }
        if ($front_id && !is_wp_error($front_id)) {
            update_option('page_on_front', (int) $front_id);
        }
        update_option('page_for_posts', (int) $blog_id);
    }
}

function kcj_mega_upsert_post($slug, $title, $content, $categories, $tags = []) {
    $existing = get_page_by_path($slug, OBJECT, 'post');
    // get_page_by_path works for posts too in WP
    if (!$existing) {
        $q = new WP_Query([
            'name' => $slug,
            'post_type' => 'post',
            'post_status' => 'any',
            'posts_per_page' => 1,
        ]);
        $existing = $q->have_posts() ? $q->posts[0] : null;
        wp_reset_postdata();
    }
    $cat_ids = [];
    foreach ($categories as $cslug) {
        $t = get_term_by('slug', $cslug, 'category');
        if ($t) {
            $cat_ids[] = (int) $t->term_id;
        }
    }
    $data = [
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $content,
        'post_status'  => 'publish',
        'post_type'    => 'post',
        'post_category'=> $cat_ids,
    ];
    if ($existing) {
        $data['ID'] = $existing->ID;
        $id = wp_update_post($data);
    } else {
        $id = wp_insert_post($data);
    }
    if ($id && !is_wp_error($id) && $tags) {
        wp_set_post_tags($id, $tags, false);
    }
    return $id;
}

function kcj_mega_trope_html(array $t) {
    return '<p class="kcj-lede">' . esc_html($t['one']) . '</p>'
        . '<h2>Why it works</h2><p>' . esc_html($t['craft']) . '</p>'
        . '<h2>Soft reading</h2><p>' . esc_html($t['soft']) . '</p>'
        . '<h2>Mirror reading</h2><p>' . esc_html($t['mirror']) . '</p>'
        . '<h2>Regional flavor</h2><ul>'
        . '<li><strong>Korea:</strong> ' . esc_html($t['k']) . '</li>'
        . '<li><strong>China:</strong> ' . esc_html($t['c']) . '</li>'
        . '<li><strong>Japan:</strong> ' . esc_html($t['j']) . '</li>'
        . '</ul>'
        . '<div class="kcj-callout"><p>Pattern notes only — not a plot recap of any single title. Stream legally; write your own feelings.</p></div>'
        . '<p><a href="' . esc_url(home_url('/tropes/')) . '">← Trope index</a></p>';
}

function kcj_mega_seed_tropes() {
    foreach (kcj_content_tropes() as $t) {
        kcj_mega_upsert_post(
            'trope-' . $t['slug'],
            $t['title'],
            kcj_mega_trope_html($t),
            ['trope-deep-dive', 'cross-culture', 'soft-feels'],
            [$t['slug'], 'k-drama', 'c-drama', 'j-drama']
        );
    }
}

function kcj_mega_seed_syndromes() {
    foreach (kcj_content_syndromes() as $s) {
        $html = '<p class="kcj-lede">' . esc_html($s['one']) . '</p>'
            . '<h2>Soft clinic</h2><p>' . esc_html($s['soft']) . '</p>'
            . '<h2>Mirror clinic</h2><p>' . esc_html($s['mirror']) . '</p>'
            . '<p><a href="' . esc_url(home_url('/syndromes/')) . '">← All syndromes</a></p>';
        kcj_mega_upsert_post(
            'syndrome-' . $s['slug'],
            $s['title'],
            $html,
            ['syndrome', 'mirror-roast'],
            [$s['slug'], 'meme']
        );
    }
}

function kcj_mega_seed_essays_and_starters() {
    $essays = [
        [
            'slug' => 'essay-three-keys-same-song',
            'title' => 'Three countries, one romance language family',
            'cats' => ['essay', 'cross-culture', 'soft-feels'],
            'body' => '<p class="kcj-lede">If you bounce from a Seoul office rom-com to a cultivation destiny arc to a Tokyo workplace whisper, you are not genre-hopping so much as changing dialects.</p>
            <p>Status, timing, and weather do similar jobs everywhere. Korea often externalizes emotion through music and set-piece motion. China often raises the cosmic invoice — love must survive sects, courts, or rebirth. Japan often subtracts spectacle until a single sentence lands like an earthquake.</p>
            <p>This site exists so global readers can learn the grammar without needing someone to spoiler a specific title for them.</p>',
        ],
        [
            'slug' => 'essay-ost-architecture',
            'title' => 'OST as architecture, not decoration',
            'cats' => ['essay', 'craft', 'korea'],
            'body' => '<p class="kcj-lede">In much K-romance, the soundtrack is load-bearing. Leitmotifs teach your nervous system who is safe before dialogue catches up.</p>
            <p>That is craft, not accident: reunion tracks, confession pads, quiet piano for almost-touching. Soft World listens on purpose. Mirror World admits two notes can ruin your evening productively.</p>',
        ],
        [
            'slug' => 'essay-silence-voltage',
            'title' => 'Silence stores voltage',
            'cats' => ['essay', 'craft', 'japan'],
            'body' => '<p class="kcj-lede">Underplaying is not emptiness. In many J-romance modes, the cutaway is the confession’s rehearsal.</p>
            <p>Hierarchy and politeness delay speech; the delay becomes the plot. When words finally arrive, they feel expensive — because they are.</p>',
        ],
        [
            'slug' => 'essay-agency-transmigration',
            'title' => 'Transmigration and the ethics of knowing too much',
            'cats' => ['essay', 'craft', 'china'],
            'body' => '<p class="kcj-lede">Waking inside a story with spoilers is a fantasy of agency. The Soft question is what you do with power: extract revenge, or grow empathy for the person you once called villain.</p>
            <p>We talk about the pattern. We do not paste novel chapters. Literacy without piracy.</p>',
        ],
        [
            'slug' => 'essay-second-lead-as-structure',
            'title' => 'Second leads are a structural feature',
            'cats' => ['essay', 'cross-culture', 'soft-feels'],
            'body' => '<p class="kcj-lede">A magnetic second lead is not a writing mistake; it is a tension machine. Soft World honors the ache. Mirror World builds the shrine.</p>
            <p>Either way, wanting the other ending means the story successfully made kindness visible.</p>',
        ],
        [
            'slug' => 'essay-what-we-will-not-steal',
            'title' => 'What we will not steal',
            'cats' => ['essay', 'soft-feels'],
            'body' => '<p class="kcj-lede">Fandom thrives on sharing feelings. It dies a little every time a site mirrors scripts, stills, and someone else’s paragraphs.</p>
            <p>KCJDrama publishes rewritten commentary and original satire. If you want the show, support legal releases. If you want the feeling explained, you’re home.</p>',
        ],
    ];
    foreach ($essays as $e) {
        kcj_mega_upsert_post($e['slug'], $e['title'], $e['body'], $e['cats'], ['essay']);
    }

    $starters = [
        [
            'slug' => 'starter-enemies-energy',
            'title' => 'Starter pack: you want enemies-to-lovers energy',
            'body' => '<p class="kcj-lede">You don’t need a pirated list — you need a pattern map.</p><ol><li>Read our <em>Enemies to Lovers</em> trope.</li><li>Add <em>Wrist-Grab Halt</em> and <em>Rival Companies</em>.</li><li>Decide Soft (armor → courage) or Mirror (bickering bingo).</li><li>Pick K workplace, C faction rivalry, or J polite cold-war desk.</li></ol>',
        ],
        [
            'slug' => 'starter-destiny-cosmic',
            'title' => 'Starter pack: destiny on a cosmic invoice',
            'body' => '<p>Begin with <em>Red-Thread Destiny</em>, <em>Transmigration Into the Book</em>, and <em>Palace Scheme Couple</em>. China desk first; then compare Korea sageuk pressure and Japan’s quieter “en.”</p>',
        ],
        [
            'slug' => 'starter-quiet-heat',
            'title' => 'Starter pack: quiet heat only',
            'body' => '<p><em>Slow-Burn Silence</em>, <em>Office Senpai Distance</em>, <em>Food as Love Language</em>, <em>Last-Episode Confession</em>. Japan desk mood; sprinkle Korea OST restraint tracks for contrast.</p>',
        ],
        [
            'slug' => 'starter-chaos-binge',
            'title' => 'Starter pack: chaos binge (Mirror)',
            'body' => '<p>Syndromes first: SLS, Amnesia Fatigue, Just One More. Then bingo hall. Then Victim Log. Hydrate.</p>',
        ],
        [
            'slug' => 'starter-craft-nerd',
            'title' => 'Starter pack: craft nerd',
            'body' => '<p>Essays on OST architecture and silence voltage, then trope entries tagged craft-adjacent: rain confession, umbrella economy, meet-cute collision.</p>',
        ],
        [
            'slug' => 'starter-global-newbie',
            'title' => 'Starter pack: absolute newbie',
            'body' => '<p><a href="' . esc_url(home_url('/start-here/')) . '">Start here</a>, Glossary, Korea/China/Japan desks, then any one Soft trope and one Mirror syndrome. You’re literate in an afternoon.</p>',
        ],
    ];
    foreach ($starters as $s) {
        kcj_mega_upsert_post($s['slug'], $s['title'], $s['body'], ['starter-pack', 'cross-culture'], ['starter-pack']);
    }
}

function kcj_mega_seed_bingo() {
    $boards = [
        'romance-starter' => [
            'title' => 'Romance starter bingo',
            'items' => [
                'Shared umbrella appears from nowhere',
                'Someone grabs a wrist mid-goodbye',
                'Rich person learns grocery store',
                'Rain starts on emotional cue',
                'Second lead is painfully kind',
                'Family dinner is a raid boss',
                'Confession delayed by pride',
                'OST swells; you lose',
                'Fake relationship paperwork',
                'Hospital hallway sprint',
                'Jealousy misunderstood for 2 episodes',
                'Friend group knew first',
            ],
        ],
        'xianxia-chaos' => [
            'title' => 'Xianxia chaos bingo',
            'items' => [
                'Destiny mentioned before breakfast',
                'Sect politics ruin a date',
                'Face-slap in public square',
                'Power scaling as flirting',
                'Rebirth with a grudge shopping list',
                'Villainess chooses kindness DLC',
                'Red thread imagery',
                'Banquet with knives in smiles',
                'Cultivation bottleneck = emotional bottleneck',
                'Loyal subordinate has feelings',
                'Memory curse suspiciously timed',
                'Final tournament of feelings',
            ],
        ],
        'workplace-soft' => [
            'title' => 'Workplace soft bingo',
            'items' => [
                'Overtime as proximity scam',
                'Senpai uses full name once',
                'Elevator silence championship',
                'Coffee order remembered',
                'Hierarchy blocks confession',
                'Transfer rumor panic',
                'Afterwork truth serum',
                'Desk plant witnesses everything',
                'Promotion complicates love',
                'Umbrella at the station',
                'Text message rewritten 12 times',
                'Finale: equality of address',
            ],
        ],
    ];
    foreach ($boards as $slug => $board) {
        $lis = '';
        foreach ($board['items'] as $item) {
            $lis .= '<li>☐ ' . esc_html($item) . '</li>';
        }
        $html = '<p class="kcj-lede">Check with your eyes. Original KCJ checklist — remix freely.</p><ul class="kcj-bingo">' . $lis . '</ul>';
        kcj_mega_upsert_page($slug, $board['title'], $html, 'bingo');
    }
}
