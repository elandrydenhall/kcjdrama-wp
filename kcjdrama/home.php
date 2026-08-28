<?php
/**
 * Field notes index (when a static front page is set). /field-notes/
 */
if (!defined('ABSPATH')) {
    exit;
}

$tropes = kcj_page_url('tropes');
$essays = kcj_page_url('essays');
$syndromes = kcj_page_url('syndromes');
$stories = kcj_page_url('stories');
$total = (int) $GLOBALS['wp_query']->found_posts;

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-notes">
    <div class="kcj-notes-inner">
        <header class="kcj-notes-hero">
            <p class="kcj-brand-folio">07</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · Field notes', 'kcjdrama'); ?></p>
            <h1><?php esc_html_e('Field notes', 'kcjdrama'); ?></h1>
            <p class="kcj-notes-lede"><?php esc_html_e('Dated tropes, essays, syndromes, and starter packs as they land. Original words — no stolen stills, no script dumps.', 'kcjdrama'); ?></p>
            <p class="kcj-notes-meta">
                <?php
                printf(
                    esc_html(_n('%d note in the feed', '%d notes in the feed', $total, 'kcjdrama')),
                    $total
                );
                ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-brand-cross" href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($essays); ?>"><?php esc_html_e('Essays', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($syndromes); ?>"><?php esc_html_e('Syndromes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($stories); ?>"><?php esc_html_e('Stories', 'kcjdrama'); ?></a>
            </div>
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
                <p class="kcj-notes-empty"><?php esc_html_e('No posts yet. Tropes are growing.', 'kcjdrama'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
get_footer();
