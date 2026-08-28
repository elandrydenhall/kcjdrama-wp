<?php
if (!defined('ABSPATH')) {
    exit;
}
$kind = function_exists('kcj_post_kind')
    ? kcj_post_kind()
    : ['key' => 'note', 'label' => 'Note', 'tone' => 'soft'];
$lede = function_exists('kcj_hub_post_lede')
    ? kcj_hub_post_lede(get_post())
    : wp_trim_words(get_the_excerpt() ?: wp_strip_all_tags(get_the_content()), 28);
?>
<article <?php post_class('kcj-note kcj-note--' . $kind['tone']); ?>>
    <a class="kcj-note-link" href="<?php the_permalink(); ?>">
        <span class="kcj-note-kicker">
            <?php echo esc_html(get_the_date()); ?>
            ·
            <?php echo esc_html($kind['label']); ?>
        </span>
        <span class="kcj-note-title"><?php the_title(); ?></span>
        <?php
        $byline = function_exists('kcj_post_byline_label') ? kcj_post_byline_label(get_post()) : '';
        if ($byline !== '') :
            ?>
            <span class="kcj-note-byline"><?php echo esc_html($byline); ?></span>
        <?php endif; ?>
        <?php if ($lede !== '') : ?>
            <span class="kcj-note-one"><?php echo esc_html($lede); ?></span>
        <?php endif; ?>
    </a>
</article>
