<?php
/**
 * Fallback index — same chrome as home/archive.
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
?>
<main class="kcj-page kcj-page--soft kcj-archive">
    <div class="kcj-page-inner kcj-archive-inner">
        <p class="kcj-page-kicker">Soft World</p>
        <h1><?php bloginfo('name'); ?></h1>
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
            <p><a href="<?php echo esc_url(home_url('/')); ?>">Return home</a> and enter Soft or Mirror.</p>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
