<?php
if (!defined('ABSPATH')) {
    exit;
}
$cats = get_the_category();
$mirror = false;
foreach ($cats as $c) {
    if (in_array($c->slug, ['syndrome', 'mirror-roast', 'listicle'], true)) {
        $mirror = true;
        break;
    }
}
?>
<article <?php post_class('kcj-card' . ($mirror ? ' kcj-card--mirror' : '')); ?>>
    <a class="kcj-card-link" href="<?php the_permalink(); ?>">
        <p class="kcj-card-meta">
            <?php echo esc_html(get_the_date()); ?>
            <?php if ($cats) : ?>
                · <?php echo esc_html($cats[0]->name); ?>
            <?php endif; ?>
        </p>
        <h2 class="kcj-card-title"><?php the_title(); ?></h2>
        <p class="kcj-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 28)); ?></p>
    </a>
</article>
