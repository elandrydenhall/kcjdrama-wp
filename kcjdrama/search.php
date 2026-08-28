<?php
/**
 * Search results.
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$q = get_search_query();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-archive">
    <div class="kcj-page-inner kcj-archive-inner">
        <p class="kcj-page-kicker">Search</p>
        <h1><?php echo $q !== '' ? esc_html(sprintf('Results for “%s”', $q)) : 'Search'; ?></h1>
        <?php if (have_posts()) : ?>
            <div class="kcj-card-grid">
                <?php
                while (have_posts()) {
                    the_post();
                    if (get_post_type() === 'product') {
                        $rail = function_exists('kcj_product_rail') ? kcj_product_rail(get_the_ID()) : 'soft';
                        ?>
                        <article class="kcj-card<?php echo $rail === 'mirror' ? ' kcj-card--mirror' : ''; ?>">
                            <a class="kcj-card-link" href="<?php the_permalink(); ?>">
                                <p class="kcj-card-meta"><?php echo $rail === 'mirror' ? 'Mirror merch' : 'Soft merch'; ?></p>
                                <h2 class="kcj-card-title"><?php the_title(); ?></h2>
                            </a>
                        </article>
                        <?php
                    } else {
                        get_template_part('template-parts/card', 'post');
                    }
                }
                ?>
            </div>
            <nav class="kcj-pagination"><?php the_posts_pagination(['mid_size' => 1]); ?></nav>
        <?php else : ?>
            <p>Nothing matched. Try a trope, a syndrome, or a merch word — or <a href="<?php echo esc_url(home_url('/')); ?>">return home</a>.</p>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
