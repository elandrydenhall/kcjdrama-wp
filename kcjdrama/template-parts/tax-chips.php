<?php
if (!defined('ABSPATH')) {
    exit;
}
$want = [
    'trope-deep-dive' => ['label' => 'Tropes', 'desk' => 'trope'],
    'syndrome'        => ['label' => 'Syndromes', 'desk' => 'mirror'],
    'essay'           => ['label' => 'Essays', 'desk' => 'essay'],
    'starter-pack'    => ['label' => 'Starter packs', 'desk' => 'craft'],
    'korea'           => ['label' => 'Korea', 'desk' => 'korea'],
    'china'           => ['label' => 'China', 'desk' => 'china'],
    'japan'           => ['label' => 'Japan', 'desk' => 'japan'],
    'craft'           => ['label' => 'Craft', 'desk' => 'craft'],
    'cross-culture'   => ['label' => 'Cross-culture', 'desk' => 'craft'],
];
$current = is_category() ? (string) (get_queried_object()->slug ?? '') : '';
$blog = kcj_field_notes_url();
echo '<nav class="kcj-chips" aria-label="' . esc_attr__('Browse topics', 'kcjdrama') . '"><ul>';
printf(
    '<li><a class="kcj-chip kcj-chip--all%s" href="%s">%s</a></li>',
    $current === '' ? ' is-active' : '',
    esc_url($blog),
    esc_html__('All', 'kcjdrama')
);
foreach ($want as $slug => $row) {
    $term = get_category_by_slug($slug);
    if (!$term) {
        continue;
    }
    printf(
        '<li><a class="kcj-chip kcj-chip--%s%s" href="%s">%s</a></li>',
        esc_attr($row['desk']),
        $current === $slug ? ' is-active' : '',
        esc_url(get_category_link($term)),
        esc_html($row['label'])
    );
}
echo '</ul></nav>';
