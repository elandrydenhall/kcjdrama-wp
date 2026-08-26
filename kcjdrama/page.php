<?php
if (!defined('ABSPATH')) {
    exit;
}
get_header();
$slug = get_post_field('post_name', get_queried_object_id());
$parent = wp_get_post_parent_id(get_queried_object_id());
$parent_slug = $parent ? get_post_field('post_name', $parent) : '';
$mirror_slugs = [
    'mirror',
    'about-the-roast',
    'victim-log',
    'syndromes',
    'memes',
    'bingo',
];
$tone = (in_array($slug, $mirror_slugs, true) || in_array($parent_slug, ['mirror'], true)) ? 'mirror' : 'soft';
?>
<main class="kcj-page kcj-page--<?php echo esc_attr($tone); ?>">
    <article class="kcj-page-inner">
        <p class="kcj-page-kicker"><?php echo $tone === 'mirror' ? 'Mirror World' : 'Soft World'; ?></p>
        <h1><?php the_title(); ?></h1>
        <div class="kcj-page-body">
            <?php
            while (have_posts()) {
                the_post();
                the_content();
            }
            ?>
        </div>
    </article>
</main>
<?php
get_footer();
