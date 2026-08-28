<?php
/**
 * Compact merch product_tag vocabulary from _inbox/05 KCJ-Drama.docx
 * (drama tropes only) + pack-data house tropes. Skips sports/city/NFL/tech/golf-club dumps.
 *
 * Re-run safe. Replaces leftover category-as-tag terms (Classic Tropes / Romantic Chaos).
 * Assigns tags only where a product title matches — does not dump every tag on every SKU.
 */
if (!defined('ABSPATH')) {
    exit;
}

$vocab = [
    ['name' => 'Evil MIL', 'slug' => 'evil-mil', 'desc' => 'Cold welcome, family dinner massacre, golf-club flavor folded in.'],
    ['name' => 'Evil FIL', 'slug' => 'evil-fil', 'desc' => 'Ice-cold first meeting, legacy speech, loyalty-before-love.'],
    ['name' => 'Evil SIL', 'slug' => 'evil-sil', 'desc' => 'Fake-sweet sister, hallway knife, alliance with MIL.'],
    ['name' => 'In-Laws', 'slug' => 'in-laws', 'desc' => 'MIL/FIL/SIL household as one search bucket.'],
    ['name' => 'Hidden Heir', 'slug' => 'hidden-heir', 'desc' => 'DNA reveal, secret child, seating-chart rearrangement.'],
    ['name' => 'Cinderella DIL', 'slug' => 'cinderella-dil', 'desc' => 'Outsider wife, unpaid household labor, place-setting accidents.'],
    ['name' => 'Fake Illness', 'slug' => 'fake-illness', 'desc' => 'Hospital power play / guilt-collapse trap.'],
    ['name' => 'Enemies to Lovers', 'slug' => 'enemies-to-lovers', 'desc' => 'Friction first, gravity later. Insults-as-foreplay included.'],
    ['name' => 'Contract Marriage', 'slug' => 'contract-marriage', 'desc' => 'Paperwork relationship that refuses to stay paperwork.'],
    ['name' => 'Fake Dating', 'slug' => 'fake-dating', 'desc' => 'Practice couple that forgets it was practice.'],
    ['name' => 'Second Lead Syndrome', 'slug' => 'second-lead-syndrome', 'desc' => 'The other one had the better OST.'],
    ['name' => 'Rain Confession', 'slug' => 'rain-confession', 'desc' => 'Rain scene = instant confession.'],
    ['name' => 'Chaebol', 'slug' => 'chaebol', 'desc' => 'Inheritance war, ramyon-to-penthouse, frightened of cockroaches.'],
    ['name' => 'Slow Burn', 'slug' => 'slow-burn', 'desc' => 'More like slow torture. Nine episodes of almost.'],
    ['name' => 'Banmal', 'slug' => 'banmal', 'desc' => 'He called her “ya”… marriage confirmed. Speech-register intimacy.'],
    ['name' => 'Makjang', 'slug' => 'makjang', 'desc' => 'Volume, secrets, 3am twists. Coma-revenge adjacent.'],
    ['name' => 'Airport Run', 'slug' => 'airport-run', 'desc' => 'Every gate is Judgment Day.'],
    ['name' => 'Fell First, Fell Harder', 'slug' => 'fell-first-fell-harder', 'desc' => 'Asymmetric timing as romance math.'],
    ['name' => 'Ice Prince', 'slug' => 'ice-prince', 'desc' => 'Tsundere / cold ML thaw. He hates me then kisses me.'],
    ['name' => 'Amnesia', 'slug' => 'amnesia', 'desc' => 'Selective memory wipe with suspiciously good hair.'],
    ['name' => 'Wrist-Grab', 'slug' => 'wrist-grab', 'desc' => 'Halt industrial complex.'],
    ['name' => 'Childhood Friend', 'slug' => 'childhood-friend', 'desc' => 'Stall, rage, friend-zoned in 4K.'],
    ['name' => 'Workplace Romance', 'slug' => 'workplace-romance', 'desc' => 'Hierarchy, senpai distance, office almosts.'],
    ['name' => 'Face-Slap', 'slug' => 'face-slap', 'desc' => 'Court arc / public humiliation payoff.'],
    ['name' => 'Class Gap', 'slug' => 'class-gap', 'desc' => 'Street food to private jet. Chaebol fell for poor girl.'],
];

foreach ($vocab as $t) {
    if (!term_exists($t['slug'], 'product_tag')) {
        $r = wp_insert_term($t['name'], 'product_tag', [
            'slug'        => $t['slug'],
            'description' => $t['desc'],
        ]);
        if (is_wp_error($r)) {
            WP_CLI::warning($t['slug'] . ': ' . $r->get_error_message());
        }
    }
}

/**
 * Title fragment → tag slugs. First match wins per slug (unique).
 *
 * @return list<string>
 */
function kcj_merch_tag_slugs_for_title($title) {
    $h = strtolower(html_entity_decode((string) $title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    $h = preg_replace('/\s+/', ' ', $h);
    $out = [];

    $has = static function ($needle) use ($h) {
        return strpos($h, strtolower($needle)) !== false;
    };

    if ($has('evil mil') || $has('cold welcome') || $has('accidental') && $has('humiliation')) {
        $out[] = 'evil-mil';
        $out[] = 'in-laws';
    }
    if ($has('evil fil') || $has('cold fil') || $has('patriarch')) {
        $out[] = 'evil-fil';
        $out[] = 'in-laws';
    }
    if ($has('sister-in-law') || $has('evil sil') || $has('fake-sweet')) {
        $out[] = 'evil-sil';
        $out[] = 'in-laws';
    }
    if ($has('hidden heir') || $has('dna')) {
        $out[] = 'hidden-heir';
    }
    if ($has('cinderella')) {
        $out[] = 'cinderella-dil';
        $out[] = 'in-laws';
        $out[] = 'class-gap';
    }
    if ($has('fake illness') || $has('guilt-collapse') || $has('guilt collapse')) {
        $out[] = 'fake-illness';
    }
    if ($has('enemies to lovers') || $has('hates me') && $has('kisses') || $has('insults equals foreplay')) {
        $out[] = 'enemies-to-lovers';
    }
    if ($has('contract marriage')) {
        $out[] = 'contract-marriage';
    }
    if ($has('fake dating') || $has('friends with benefits')) {
        $out[] = 'fake-dating';
    }
    if ($has('second lead') || preg_match('/\bsls\b/', $h)) {
        $out[] = 'second-lead-syndrome';
    }
    if ($has('rain')) {
        $out[] = 'rain-confession';
    }
    if ($has('chaebol') || $has('chae bol')) {
        $out[] = 'chaebol';
    }
    if ($has('slow burn')) {
        $out[] = 'slow-burn';
    }
    if ($has('called her ya') || $has('called her via') || $has('banmal') || $has('marriage confirmed')) {
        $out[] = 'banmal';
    }
    if ($has('makjang') || $has('coma revenge')) {
        $out[] = 'makjang';
    }
    if ($has('airport')) {
        $out[] = 'airport-run';
    }
    if ($has('fell first') || $has('fell harder')) {
        $out[] = 'fell-first-fell-harder';
    }
    if ($has('ice prince') || $has('tsundere') || ($has('hates me') && $has('kisses')) || $has('whatever attitude')) {
        $out[] = 'ice-prince';
    }
    if ($has('amnesia')) {
        $out[] = 'amnesia';
    }
    if ($has('coma revenge')) {
        $out[] = 'amnesia';
    }
    if ($has('wrist-grab') || $has('wrist grab')) {
        $out[] = 'wrist-grab';
    }
    if ($has('childhood friend') || $has('friend-zoned') || $has('friend zoned')) {
        $out[] = 'childhood-friend';
    }
    if ($has('workplace') || $has('senpai') || $has('office romance')) {
        $out[] = 'workplace-romance';
    }
    if ($has('face-slap') || $has('face slap')) {
        $out[] = 'face-slap';
    }
    if ($has('street food to private jet') || $has('ramyon')) {
        $out[] = 'class-gap';
        $out[] = 'chaebol';
    }

    return array_values(array_unique($out));
}

$ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'no_found_rows'  => true,
]);

$tagged = 0;
$cleared = 0;
foreach ($ids as $id) {
    $id = (int) $id;
    $slugs = kcj_merch_tag_slugs_for_title(get_the_title($id));
    wp_set_object_terms($id, $slugs, 'product_tag', false);
    if ($slugs) {
        $tagged++;
        WP_CLI::log($id . ' ' . get_the_title($id) . ' → ' . implode(', ', $slugs));
    } else {
        $cleared++;
    }
}

foreach (['classic-tropes', 'romantic-chaos'] as $drop) {
    $term = get_term_by('slug', $drop, 'product_tag');
    if ($term && !is_wp_error($term)) {
        wp_delete_term((int) $term->term_id, 'product_tag');
        WP_CLI::log('deleted leftover tag ' . $drop);
    }
}

WP_CLI::success(sprintf(
    'vocab %d tags; %d products tagged from title; %d left untagged (no drama match).',
    count($vocab),
    $tagged,
    $cleared
));
