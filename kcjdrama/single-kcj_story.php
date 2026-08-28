<?php
/**
 * Single original Soft story.
 */
if (!defined('ABSPATH')) {
    exit;
}
get_header();

while (have_posts()) :
    the_post();
    $story_id = get_the_ID();
    $can_edit = function_exists('kcj_user_can_edit_story') && kcj_user_can_edit_story($story_id);
    $just_live = $can_edit && isset($_GET['kcj_live']) && (string) wp_unslash($_GET['kcj_live']) === '1';
    ?>
    <main id="kcj-main" class="kcj-page kcj-page--soft">
        <article id="post-<?php the_ID(); ?>" <?php post_class('kcj-page-inner kcj-entry'); ?>>
            <p class="kcj-page-kicker"><?php echo $just_live ? esc_html__('Soft World · Live', 'kcjdrama') : esc_html__('Soft World · Desk', 'kcjdrama'); ?> · <?php echo esc_html(get_the_date()); ?></p>
            <h1><?php the_title(); ?></h1>
            <?php
            $byline = function_exists('kcj_post_byline_label')
                ? kcj_post_byline_label($story_id)
                : (function_exists('kcj_story_byline_label') ? kcj_story_byline_label($story_id) : '');
            if ($byline !== '') :
                ?>
                <p class="kcj-story-byline"><?php echo esc_html($byline); ?></p>
            <?php endif; ?>
            <?php if ($just_live) : ?>
                <p class="kcj-story-live" role="status"><?php esc_html_e('It’s live. Soft World can read it.', 'kcjdrama'); ?></p>
            <?php endif; ?>
            <div class="kcj-page-body kcj-entry-body">
                <?php the_content(); ?>
            </div>
            <nav class="kcj-entry-footer">
                <a href="<?php echo esc_url(kcj_page_url('stories')); ?>"><?php esc_html_e('All stories', 'kcjdrama'); ?></a>
                <?php if ($can_edit) : ?>
                    ·
                    <a href="<?php echo esc_url(kcj_story_edit_url($story_id)); ?>"><?php esc_html_e('Edit', 'kcjdrama'); ?></a>
                    ·
                    <a href="<?php echo esc_url(kcj_page_url('stories') . '#kcj-desk'); ?>"><?php esc_html_e('Write another', 'kcjdrama'); ?></a>
                <?php elseif (is_user_logged_in()) : ?>
                    ·
                    <a href="<?php echo esc_url(kcj_page_url('stories') . '#kcj-desk'); ?>"><?php esc_html_e('The desk', 'kcjdrama'); ?></a>
                <?php endif; ?>
                ·
                <a href="<?php echo esc_url(kcj_page_url('tropes')); ?>"><?php esc_html_e('Trope index', 'kcjdrama'); ?></a>
                ·
                <a href="<?php echo esc_url(kcj_page_url('editorial-policy')); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
            </nav>
        </article>
    </main>
    <?php
endwhile;

get_footer();
