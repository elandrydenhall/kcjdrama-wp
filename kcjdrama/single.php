<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();

$mirror = false;
foreach (get_the_category() as $c) {
    if (in_array($c->slug, ['syndrome', 'mirror-roast', 'listicle'], true)) {
        $mirror = true;
        break;
    }
}
$tone = $mirror ? 'mirror' : 'soft';

while (have_posts()) :
    the_post();
    ?>
    <main id="kcj-main" class="kcj-page kcj-page--<?php echo esc_attr($tone); ?>">
        <article id="post-<?php the_ID(); ?>" <?php post_class('kcj-page-inner kcj-entry'); ?>>
            <p class="kcj-page-kicker"><?php echo $mirror ? 'Mirror World' : 'Soft World'; ?> · <?php echo esc_html(get_the_date()); ?></p>
            <h1><?php the_title(); ?></h1>
            <?php
            $byline = function_exists('kcj_post_byline_label') ? kcj_post_byline_label(get_post()) : '';
            if ($byline !== '') :
                ?>
                <p class="kcj-story-byline"><?php echo esc_html($byline); ?></p>
            <?php endif; ?>
            <p class="kcj-entry-tax">
                <?php
                the_category(' · ');
                if (has_tag()) {
                    echo ' · ';
                    the_tags('', ' · ');
                }
                ?>
            </p>
            <div class="kcj-page-body kcj-entry-body">
                <?php the_content(); ?>
            </div>
            <nav class="kcj-entry-footer">
                <a href="<?php echo esc_url(kcj_field_notes_url()); ?>">All field notes</a>
                ·
                <a href="<?php echo esc_url(kcj_page_url('tropes')); ?>">Trope index</a>
                ·
                <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
            </nav>
        </article>
    </main>
    <?php
endwhile;

get_footer();
