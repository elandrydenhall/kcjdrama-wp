<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$mirror_cats = ['syndrome', 'mirror-roast', 'listicle'];
$tone = 'soft';
if (is_category()) {
    $slug = get_queried_object()->slug ?? '';
    if (in_array($slug, $mirror_cats, true)) {
        $tone = 'mirror';
    }
}
$total = (int) $GLOBALS['wp_query']->found_posts;
$heading = get_the_archive_title();
if (is_category()) {
    $pretty = [
        'trope-deep-dive' => 'Tropes',
        'syndrome'        => 'Syndromes',
        'essay'           => 'Essays',
        'starter-pack'    => 'Starter packs',
        'cross-culture'   => 'Cross-culture',
    ];
    $cat_slug = get_queried_object()->slug ?? '';
    if (isset($pretty[$cat_slug])) {
        $heading = $pretty[$cat_slug];
    }
}
?>
<main id="kcj-main" class="kcj-page kcj-page--<?php echo esc_attr($tone); ?> kcj-notes">
    <div class="kcj-notes-inner">
        <header class="kcj-notes-hero">
            <p class="kcj-page-kicker"><?php echo $tone === 'mirror' ? 'Mirror World · Archive' : 'Soft World · Archive'; ?></p>
            <h1><?php echo esc_html($heading); ?></h1>
            <?php if (get_the_archive_description()) : ?>
                <div class="kcj-notes-lede"><?php the_archive_description(); ?></div>
            <?php endif; ?>
            <p class="kcj-notes-meta">
                <?php
                printf(
                    esc_html(_n('%d note in this shelf', '%d notes in this shelf', $total, 'kcjdrama')),
                    $total
                );
                ?>
            </p>
        </header>

        <div class="kcj-notes-catalog">
            <?php get_template_part('template-parts/tax-chips'); ?>
            <?php if (have_posts()) : ?>
                <div class="kcj-note-list">
                    <?php
                    while (have_posts()) {
                        the_post();
                        get_template_part('template-parts/card', 'post');
                    }
                    ?>
                </div>
                <nav class="kcj-pagination"><?php the_posts_pagination(['mid_size' => 1]); ?></nav>
            <?php else : ?>
                <p class="kcj-notes-empty"><?php esc_html_e('Nothing in this shelf yet.', 'kcjdrama'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
get_footer();
