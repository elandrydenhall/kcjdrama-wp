<?php
/**
 * Editorial policy — Soft trust / literacy page.
 * Template hierarchy: page-editorial-policy.php for /editorial-policy/
 */
if (!defined('ABSPATH')) {
    exit;
}

$about = kcj_page_url('about');
$shipping = kcj_page_url('shipping-returns');
$support = kcj_page_url('support');
$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
$tropes = kcj_page_url('tropes');
$essays = kcj_page_url('essays');
$roast = kcj_page_url('about-the-roast');

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-policy">
    <div class="kcj-policy-inner">
        <header class="kcj-policy-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Trust & literacy', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-policy-lede">
                <?php esc_html_e('We publish original Soft commentary and Mirror satire about romance-drama patterns — not pirated plots, stills, or someone else’s paragraphs. If you’re here before checkout: this is what we stand on.', 'kcjdrama'); ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--gold" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($about); ?>"><?php esc_html_e('About', 'kcjdrama'); ?></a>
            </div>
        </header>

        <?php if (function_exists('kcj_the_epigraph')) { kcj_the_epigraph('policy-worth-of-heart'); } ?>

        <section class="kcj-policy-grid" aria-label="<?php esc_attr_e('Policy pillars', 'kcjdrama'); ?>">
            <article class="kcj-policy-card">
                <h2><?php esc_html_e('What we publish', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Original trope definitions, essays, glossaries, and Soft fiction inspired by patterns', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Mirror roast and syndrome naming — satire of devices, not people', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Original merch designs sold as Soft / Mirror catalog', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Global English, ESL-friendly — literacy without gatekeeping', 'kcjdrama'); ?></li>
                </ul>
            </article>

            <article class="kcj-policy-card kcj-policy-card--refuse">
                <h2><?php esc_html_e('What we refuse', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Episode-by-episode plot lifts and long dialogue quotes', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Official posters, stills, OST audio, subtitle files', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Scraped fanart of copyrighted characters', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Piracy / illegal stream directories', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Copy-paste from other recap sites — we read, then rewrite in KCJ voice', 'kcjdrama'); ?></li>
                </ul>
            </article>
        </section>

        <section class="kcj-policy-rails" aria-label="<?php esc_attr_e('Soft and Mirror rules', 'kcjdrama'); ?>">
            <div class="kcj-teaser kcj-teaser--soft">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Soft World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Craft before the joke', 'kcjdrama'); ?></h3>
                <p><?php esc_html_e('Soft explains why a device works — rewatch notes, culture bridges, care without spoiling whole arcs as default.', 'kcjdrama'); ?></p>
                <p class="kcj-policy-inline-links">
                    <a href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                    ·
                    <a href="<?php echo esc_url($essays); ?>"><?php esc_html_e('Essays', 'kcjdrama'); ?></a>
                </p>
            </div>
            <div class="kcj-teaser kcj-teaser--mirror">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Mirror World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Roast the habit, not the human', 'kcjdrama'); ?></h3>
                <p><?php esc_html_e('Mirror exaggerates storytelling habits — rain confessions, contract clauses, mid-season amnesia. If a joke stops being affectionate, it doesn’t ship.', 'kcjdrama'); ?></p>
                <p class="kcj-policy-inline-links">
                    <a href="<?php echo esc_url($roast); ?>"><?php esc_html_e('About the roast', 'kcjdrama'); ?></a>
                </p>
            </div>
        </section>

        <section class="kcj-policy-more">
            <article>
                <h2><?php esc_html_e('Fan art & assets', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('On this site, “fan art” means work we create or commission under our license — or clearly CC0 assets. We do not host unlicensed character art uploads.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('People', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Mirror roasts tropes and storytelling habits. We do not punch down at actors’ bodies or private lives.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('Merch', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Catalog designs are original Soft/Mirror work printed on demand. Support legal streams for the shows you love; wear the pattern commentary here.', 'kcjdrama'); ?></p>
            </article>
        </section>

        <section class="kcj-policy-footer-links" aria-label="<?php esc_attr_e('Related', 'kcjdrama'); ?>">
            <a href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url(kcj_page_url('faq')); ?>"><?php esc_html_e('Desk FAQ', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($support); ?>"><?php esc_html_e('Support', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($about); ?>"><?php esc_html_e('About', 'kcjdrama'); ?></a>
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Open the catalog', 'kcjdrama'); ?></a>
        </section>
    </div>
</main>
<?php
get_footer();
