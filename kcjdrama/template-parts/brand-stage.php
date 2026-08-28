<?php
/**
 * Soft / Mirror world stage. Structured doors — not the seeded WP dump.
 *
 * @param array $args { tone: soft|mirror }
 */
if (!defined('ABSPATH')) {
    exit;
}

$tone = (isset($args['tone']) && $args['tone'] === 'mirror') ? 'mirror' : 'soft';
$shop_url = function_exists('kcj_catalog_url')
    ? kcj_catalog_url(['rail' => $tone])
    : (function_exists('kcj_rail_url') ? kcj_rail_url($tone, kcj_page_url('shop')) : home_url('/shop/'));
$other = $tone === 'mirror' ? 'soft' : 'mirror';
$other_url = kcj_page_url($other);
$home_url = home_url('/');

$copy = [
    'soft' => [
        'folio'   => '01',
        'kicker'  => 'Soft World',
        'lede'    => 'Porcelain romance for the sincere desk — champagne light, craft before the joke.',
        'body'    => 'This is where the inheritance lands and love stays. Tropes as craft notes. Merch you can wear to a rewatch without explaining yourself.',
        'cta'     => 'Shop Soft merch',
        'cross'   => 'Cross to Mirror',
        'doors'   => [
            ['kicker' => 'On-ramp', 'title' => 'Start here', 'blurb' => 'Global sitting, no spoiler tax.', 'href' => kcj_page_url('start-here')],
            ['kicker' => 'Encyclopedia', 'title' => 'Tropes', 'blurb' => 'Why the device works.', 'href' => kcj_page_url('tropes')],
            ['kicker' => 'Literacy', 'title' => 'Glossary', 'blurb' => 'Chaebol, xianxia, shokuba, danmei.', 'href' => kcj_page_url('glossary')],
            ['kicker' => 'Longform', 'title' => 'Essays', 'blurb' => 'Culture bridges, fandom speech.', 'href' => kcj_page_url('essays')],
            ['kicker' => 'Fiction', 'title' => 'Stories', 'blurb' => 'Patterns, not pirated plots.', 'href' => kcj_page_url('stories')],
            ['kicker' => 'Trust', 'title' => 'Editorial policy', 'blurb' => 'What we publish and refuse.', 'href' => kcj_page_url('editorial-policy')],
        ],
    ],
    'mirror' => [
        'folio'   => '02',
        'kicker'  => 'Mirror World',
        'lede'    => 'Violet circuit roast — orchid heat, no plot armor, syndromes named out loud.',
        'body'    => 'We roast the tropes so you don’t have to. Same drama DNA as Soft — louder, meaner, still wearable.',
        'cta'     => 'Shop Mirror merch',
        'cross'   => 'Cross to Soft',
        'doors'   => [
            ['kicker' => 'Clinic', 'title' => 'Syndromes', 'blurb' => 'Name the affliction.', 'href' => kcj_page_url('syndromes')],
            ['kicker' => 'Casualties', 'title' => 'Victim Log', 'blurb' => 'Tropes that did not survive.', 'href' => kcj_page_url('victim-log')],
            ['kicker' => 'Hall', 'title' => 'Bingo', 'blurb' => 'Checklists for the trope-addicted.', 'href' => kcj_page_url('bingo')],
            ['kicker' => 'Formats', 'title' => 'Memes we own', 'blurb' => 'Written here — not scraped panels.', 'href' => kcj_page_url('memes')],
            ['kicker' => 'Rules', 'title' => 'About the Roast', 'blurb' => 'Engagement, not cruelty.', 'href' => kcj_page_url('about-the-roast')],
        ],
    ],
];

$c = $copy[$tone];
?>
<main id="kcj-main" class="kcj-page kcj-page--<?php echo esc_attr($tone); ?> kcj-brand-stage">
    <div class="kcj-brand-stage-inner">
        <header class="kcj-brand-hero">
            <p class="kcj-brand-folio"><?php echo esc_html($c['folio']); ?></p>
            <p class="kcj-page-kicker"><?php echo esc_html($c['kicker']); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-brand-lede"><?php echo esc_html($c['lede']); ?></p>
            <p class="kcj-brand-body"><?php echo esc_html($c['body']); ?></p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--<?php echo esc_attr($tone); ?>" href="<?php echo esc_url($shop_url); ?>">
                    <?php echo esc_html($c['cta']); ?>
                </a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($other_url); ?>"><?php echo esc_html($c['cross']); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($home_url); ?>"><?php esc_html_e('Back to the split', 'kcjdrama'); ?></a>
            </div>
        </header>

        <nav class="kcj-world-doors" aria-label="<?php echo $tone === 'mirror' ? esc_attr__('Mirror doors', 'kcjdrama') : esc_attr__('Soft doors', 'kcjdrama'); ?>">
            <?php foreach ($c['doors'] as $i => $door) : ?>
                <a class="kcj-world-door" href="<?php echo esc_url($door['href']); ?>">
                    <span class="kcj-world-door-num"><?php echo esc_html(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                    <span class="kcj-world-door-copy">
                        <span class="kcj-world-door-kicker"><?php echo esc_html($door['kicker']); ?></span>
                        <span class="kcj-world-door-title"><?php echo esc_html($door['title']); ?></span>
                        <span class="kcj-world-door-blurb"><?php echo esc_html($door['blurb']); ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php
    if (function_exists('kcj_render_brand_stage_rows')) {
        kcj_render_brand_stage_rows($tone);
    }
    ?>
</main>
