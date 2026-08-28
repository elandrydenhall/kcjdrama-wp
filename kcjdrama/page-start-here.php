<?php
/**
 * Start here — Soft on-ramp. Template hierarchy: page-start-here.php
 */
if (!defined('ABSPATH')) {
    exit;
}

$korea = kcj_page_url('countries/korea');
$china = kcj_page_url('countries/china');
$japan = kcj_page_url('countries/japan');
$tropes = kcj_page_url('tropes');
$syndromes = kcj_page_url('syndromes');
$glossary = kcj_page_url('glossary');
$soft = kcj_page_url('soft');
$mirror = kcj_page_url('mirror');
$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
$policy = kcj_page_url('editorial-policy');

$desks = [
    ['num' => '01', 'kicker' => 'Korea', 'title' => 'Korea desk', 'blurb' => 'Chaebol fantasy, OST architecture, second-lead culture, 16-episode pressure cookers.', 'href' => $korea],
    ['num' => '02', 'kicker' => 'China', 'title' => 'China desk', 'blurb' => 'Xianxia destiny, face-slap catharsis, transmigration agency, palace heat.', 'href' => $china],
    ['num' => '03', 'kicker' => 'Japan', 'title' => 'Japan desk', 'blurb' => 'Workplace restraint, slow-burn silence, last-episode courage.', 'href' => $japan],
];

$next = [
    ['num' => '04', 'kicker' => 'Soft', 'title' => 'Trope encyclopedia', 'blurb' => 'Shared patterns. Craft notes first — Mirror jokes optional.', 'href' => $tropes],
    ['num' => '05', 'kicker' => 'Mirror', 'title' => 'Syndrome clinic', 'blurb' => 'Name the affliction. You’re among friends.', 'href' => $syndromes],
    ['num' => '06', 'kicker' => 'Literacy', 'title' => 'Glossary', 'blurb' => 'Chaebol, xianxia, shokuba, danmei — words that unlock the rooms.', 'href' => $glossary],
];

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-onramp">
    <div class="kcj-brand-stage-inner">
        <header class="kcj-brand-hero">
            <p class="kcj-brand-folio">00</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · On-ramp', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-brand-lede"><?php esc_html_e('Korean, Chinese, and Japanese romance dramas share a grammar — status gaps, slow recognition, weather as emotion — then each country plays it in a different key.', 'kcjdrama'); ?></p>
            <p class="kcj-brand-body"><?php esc_html_e('Soft is sincere commentary. Mirror is meme-trope chaos. Everything here is rewritten original text. No stolen stills, no script dumps, no piracy links.', 'kcjdrama'); ?></p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($soft); ?>"><?php esc_html_e('Enter Soft', 'kcjdrama'); ?></a>
                <a class="kcj-btn kcj-btn--mirror" href="<?php echo esc_url($mirror); ?>"><?php esc_html_e('Enter Mirror', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
            </div>
            <p class="kcj-onramp-rule">
                <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
                <?php esc_html_e('if you want the rules before the rooms.', 'kcjdrama'); ?>
            </p>
        </header>

        <div class="kcj-onramp-path">
            <p class="kcj-brand-teasers-title"><?php esc_html_e('One sitting', 'kcjdrama'); ?></p>
            <nav class="kcj-world-doors" aria-label="<?php esc_attr_e('Country desks', 'kcjdrama'); ?>">
                <?php foreach ($desks as $door) : ?>
                    <a class="kcj-world-door" href="<?php echo esc_url($door['href']); ?>">
                        <span class="kcj-world-door-num"><?php echo esc_html($door['num']); ?></span>
                        <span class="kcj-world-door-copy">
                            <span class="kcj-world-door-kicker"><?php echo esc_html($door['kicker']); ?></span>
                            <span class="kcj-world-door-title"><?php echo esc_html($door['title']); ?></span>
                            <span class="kcj-world-door-blurb"><?php echo esc_html($door['blurb']); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <p class="kcj-onramp-then"><?php esc_html_e('Then pick one Soft pattern and one Mirror affliction.', 'kcjdrama'); ?></p>
            <nav class="kcj-world-doors" aria-label="<?php esc_attr_e('Next rooms', 'kcjdrama'); ?>">
                <?php foreach ($next as $door) : ?>
                    <a class="kcj-world-door" href="<?php echo esc_url($door['href']); ?>">
                        <span class="kcj-world-door-num"><?php echo esc_html($door['num']); ?></span>
                        <span class="kcj-world-door-copy">
                            <span class="kcj-world-door-kicker"><?php echo esc_html($door['kicker']); ?></span>
                            <span class="kcj-world-door-title"><?php echo esc_html($door['title']); ?></span>
                            <span class="kcj-world-door-blurb"><?php echo esc_html($door['blurb']); ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</main>
<?php
get_footer();
