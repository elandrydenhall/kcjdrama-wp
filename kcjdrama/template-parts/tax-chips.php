<?php
if (!defined('ABSPATH')) {
    exit;
}
$want = [
    'trope-deep-dive' => 'Tropes',
    'syndrome'        => 'Syndromes',
    'essay'           => 'Essays',
    'starter-pack'    => 'Starter packs',
    'korea'           => 'Korea',
    'china'           => 'China',
    'japan'           => 'Japan',
    'craft'           => 'Craft',
    'cross-culture'   => 'Cross-culture',
];
echo '<nav class="kcj-chips" aria-label="Browse topics"><ul>';
foreach ($want as $slug => $label) {
    $term = get_category_by_slug($slug);
    if (!$term) {
        continue;
    }
    printf(
        '<li><a href="%s">%s</a></li>',
        esc_url(get_category_link($term)),
        esc_html($label)
    );
}
echo '</ul></nav>';
