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
?>
<main class="kcj-page kcj-page--<?php echo esc_attr($tone); ?> kcj-archive">
    <div class="kcj-page-inner kcj-archive-inner">
        <p class="kcj-page-kicker"><?php echo $tone === 'mirror' ? 'Mirror World' : 'Soft World'; ?> · Archive</p>
        <h1><?php the_archive_title(); ?></h1>
        <?php if (get_the_archive_description()) : ?>
            <div class="kcj-lede"><?php the_archive_description(); ?></div>
        <?php endif; ?>
        <?php get_template_part('template-parts/tax-chips'); ?>
        <?php if (have_posts()) : ?>
            <div class="kcj-card-grid">
                <?php
                while (have_posts()) {
                    the_post();
                    get_template_part('template-parts/card', 'post');
                }
                ?>
            </div>
            <nav class="kcj-pagination"><?php the_posts_pagination(['mid_size' => 1]); ?></nav>
        <?php else : ?>
            <p>Nothing in this shelf yet.</p>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
