<?php
/**
 * Blog posts index (when a static front page is set).
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$tone = 'soft';
?>
<main class="kcj-page kcj-page--<?php echo esc_attr($tone); ?> kcj-archive">
    <div class="kcj-page-inner kcj-archive-inner">
        <p class="kcj-page-kicker">Soft World · Field notes</p>
        <h1>Stories &amp; deep dives</h1>
        <p class="kcj-lede">Original commentary, trope essays, and starter packs — rewritten for readers everywhere. No stolen stills. No script dumps.</p>
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
            <p>No posts yet. Tropes are growing.</p>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
