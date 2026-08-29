<?php
/**
 * Soft World — Reading paths. Template hierarchy: page-stories.php for /stories/
 */
if (!defined('ABSPATH')) {
    exit;
}

$entries = kcj_hub_stories_entries();
$lanes = [
    'starter-global-newbie'  => ['lane' => 'On-ramp', 'desk' => 'craft'],
    'starter-chaos-binge'    => ['lane' => 'Mirror', 'desk' => 'mirror'],
    'starter-craft-nerd'     => ['lane' => 'Craft', 'desk' => 'craft'],
    'starter-destiny-cosmic' => ['lane' => 'Destiny', 'desk' => 'china'],
    'starter-quiet-heat'     => ['lane' => 'Quiet heat', 'desk' => 'japan'],
    'starter-enemies-energy' => ['lane' => 'Friction', 'desk' => 'korea'],
];

$tropes = kcj_page_url('tropes');
$essays = kcj_page_url('essays');
$soft = kcj_page_url('soft');
$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'soft']) : kcj_page_url('shop');
$n = 0;

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-paths">
    <div class="kcj-paths-inner">
        <header class="kcj-paths-hero">
            <p class="kcj-brand-folio">04</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · Reading paths', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-paths-lede"><?php esc_html_e('Original Soft fiction will live here — patterns, not pirated plots.', 'kcjdrama'); ?></p>
            <p class="kcj-paths-body"><?php esc_html_e('Today the desk holds starter packs: six reading paths built from trope atoms. Pick a mood. Steal structure. Write your own feelings.', 'kcjdrama'); ?></p>
            <p class="kcj-paths-meta">
                <?php
                printf(
                    esc_html(_n('%d path on the desk', '%d paths on the desk', count($entries), 'kcjdrama')),
                    count($entries)
                );
                ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="#kcj-desk"><?php esc_html_e('Write for the desk', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="#kcj-desk-faq"><?php esc_html_e('Pass OR Fail', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="#kcj-account"><?php esc_html_e('New?', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($essays); ?>"><?php esc_html_e('Essays', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($soft); ?>"><?php esc_html_e('Soft stage', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop Soft merch', 'kcjdrama'); ?></a>
            </div>
                <?php if (function_exists('kcj_the_epigraph')) { kcj_the_epigraph('stories-how-not-to-fall'); } ?>
        </header>

        <div class="kcj-paths-col">
        <?php if (!$entries) : ?>
            <p class="kcj-paths-empty"><?php esc_html_e('Starter packs and Soft shorts will land here. Until then, wander Tropes and steal structures (not plots).', 'kcjdrama'); ?></p>
        <?php else : ?>
            <ol class="kcj-path-list">
                <?php foreach ($entries as $entry) :
                    $n++;
                    $href = !empty($entry['href']) ? $entry['href'] : '';
                    if ($href === '') {
                        continue;
                    }
                    $meta = $lanes[$entry['slug']] ?? ['lane' => 'Path', 'desk' => 'craft'];
                    $title = preg_replace('/^Starter pack:\s*/i', '', (string) $entry['title']);
                    $title = $title === '' ? (string) $entry['title'] : ucfirst($title);
                    $byline = !empty($entry['byline'])
                        ? (string) $entry['byline']
                        : (function_exists('kcj_post_byline_label') ? kcj_post_byline_label(null) : 'By kcjdrama');
                    ?>
                    <li>
                        <a class="kcj-path kcj-path--<?php echo esc_attr($meta['desk']); ?>" href="<?php echo esc_url($href); ?>">
                            <span class="kcj-path-num"><?php echo esc_html(str_pad((string) $n, 2, '0', STR_PAD_LEFT)); ?></span>
                            <span class="kcj-path-copy">
                                <span class="kcj-path-lane"><?php echo esc_html($meta['lane']); ?></span>
                                <span class="kcj-path-title"><?php echo esc_html($title); ?></span>
                                <span class="kcj-path-byline"><?php echo esc_html($byline); ?></span>
                                <?php if ($entry['one'] !== '') : ?>
                                    <span class="kcj-path-one"><?php echo esc_html($entry['one']); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>

        <?php
        $shorts = function_exists('kcj_published_stories') ? kcj_published_stories() : [];
        if ($shorts) :
            ?>
            <ol class="kcj-path-list kcj-path-list--desk" aria-label="<?php esc_attr_e('Original Soft fiction', 'kcjdrama'); ?>">
                <?php
                foreach ($shorts as $story) :
                    $n++;
                    $byline = function_exists('kcj_post_byline_label')
                        ? kcj_post_byline_label($story)
                        : (function_exists('kcj_story_byline_label') ? kcj_story_byline_label($story) : 'By kcjdrama');
                    ?>
                    <li>
                        <a class="kcj-path kcj-path--craft" href="<?php echo esc_url(get_permalink($story)); ?>">
                            <span class="kcj-path-num"><?php echo esc_html(str_pad((string) $n, 2, '0', STR_PAD_LEFT)); ?></span>
                            <span class="kcj-path-copy">
                                <span class="kcj-path-lane"><?php esc_html_e('Desk', 'kcjdrama'); ?></span>
                                <span class="kcj-path-title"><?php echo esc_html(get_the_title($story)); ?></span>
                                <span class="kcj-path-byline"><?php echo esc_html($byline); ?></span>
                                <span class="kcj-path-one"><?php echo esc_html(wp_trim_words(wp_strip_all_tags($story->post_content), 28)); ?></span>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
        </div>
    </div>

    <?php
    if (function_exists('kcj_render_stories_desk')) {
        kcj_render_stories_desk();
    }
    ?>
</main>
<?php
get_footer();
