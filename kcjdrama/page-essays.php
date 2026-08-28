<?php
/**
 * Soft World — Essay folio. Template hierarchy: page-essays.php for /essays/
 */
if (!defined('ABSPATH')) {
    exit;
}

$entries = kcj_hub_essay_entries();
$tropes = kcj_page_url('tropes');
$stories = kcj_page_url('stories');
$soft = kcj_page_url('soft');
$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'soft']) : kcj_page_url('shop');
$n = 0;

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-folio">
    <p class="kcj-folio-run" aria-hidden="true"><?php esc_html_e('longform', 'kcjdrama'); ?></p>
    <div class="kcj-folio-inner">
        <header class="kcj-folio-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <div class="kcj-folio-intro">
                <p class="kcj-folio-lede"><?php esc_html_e('Craft, culture bridges, and the ethics of fandom speech — commentary you can read without a spoiler tax.', 'kcjdrama'); ?></p>
                <p class="kcj-folio-meta">
                    <?php
                    printf(
                        esc_html(_n('%d essay on the Soft rail', '%d essays on the Soft rail', count($entries), 'kcjdrama')),
                        count($entries)
                    );
                    ?>
                </p>
                <div class="kcj-brand-actions">
                    <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop Soft merch', 'kcjdrama'); ?></a>
                    <a class="kcj-brand-cross" href="<?php echo esc_url($soft); ?>"><?php esc_html_e('Soft stage', 'kcjdrama'); ?></a>
                    <a class="kcj-brand-cross" href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                    <a class="kcj-brand-cross" href="<?php echo esc_url($stories); ?>"><?php esc_html_e('Stories', 'kcjdrama'); ?></a>
                </div>
            </div>
        </header>

        <?php if (!$entries) : ?>
            <p class="kcj-folio-empty"><?php esc_html_e('Essays are warming up. Soft longform will land here as posts in the essay category.', 'kcjdrama'); ?></p>
        <?php else : ?>
            <ol class="kcj-folio-list">
                <?php foreach ($entries as $entry) :
                    $n++;
                    $href = !empty($entry['href']) ? $entry['href'] : '';
                    if ($href === '') {
                        continue;
                    }
                    ?>
                    <li>
                        <a class="kcj-folio-piece" href="<?php echo esc_url($href); ?>">
                            <span class="kcj-folio-num"><?php echo esc_html(str_pad((string) $n, 2, '0', STR_PAD_LEFT)); ?></span>
                            <span class="kcj-folio-copy">
                                <span class="kcj-folio-title"><?php echo esc_html($entry['title']); ?></span>
                                <?php if ($entry['one'] !== '') : ?>
                                    <span class="kcj-folio-one"><?php echo esc_html($entry['one']); ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</main>
<?php
get_footer();
