<?php
/**
 * Countries index — three Soft desks. Template: page-countries.php
 */
if (!defined('ABSPATH')) {
    exit;
}

$korea = kcj_page_url('countries/korea');
$china = kcj_page_url('countries/china');
$japan = kcj_page_url('countries/japan');
$start = kcj_page_url('start-here');
$tropes = kcj_page_url('tropes');
$soft = kcj_page_url('soft');

$desks = [
    ['num' => '01', 'kicker' => 'Korea', 'title' => 'Korea desk', 'blurb' => 'Sixteen-episode pressure cookers. OST architecture. Second-lead shrines. Rain as voltage.', 'href' => $korea],
    ['num' => '02', 'kicker' => 'China', 'title' => 'China desk', 'blurb' => 'Destiny with interest. Palace heat. Transmigration agency. Softness under spectacle.', 'href' => $china],
    ['num' => '03', 'kicker' => 'Japan', 'title' => 'Japan desk', 'blurb' => 'Romance by subtraction. Workplace air. Last-train courage. Everyday life as epic.', 'href' => $japan],
];

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-onramp kcj-countries">
    <div class="kcj-brand-stage-inner">
        <header class="kcj-brand-hero">
            <p class="kcj-brand-folio">K C J</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · Three desks', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-brand-lede">
                <?php esc_html_e('Korean, Chinese, and Japanese romance dramas share a grammar — status gaps, slow recognition, weather as emotion — then each country plays it in a different key. Same family of feelings. Different instruments.', 'kcjdrama'); ?>
            </p>
            <p class="kcj-brand-body">
                <?php esc_html_e('Pick a desk when you want the country’s temperature first. Soft will meet you with craft and awe. Mirror can wait until you need the syndrome named out loud. Original words only — no stolen stills, no piracy doors.', 'kcjdrama'); ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($start); ?>"><?php esc_html_e('Start here', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($soft); ?>"><?php esc_html_e('Enter Soft', 'kcjdrama'); ?></a>
            </div>
        </header>

        <?php if (function_exists('kcj_the_epigraph')) { kcj_the_epigraph('countries-good-shoes'); } ?>

        <div class="kcj-onramp-path">
            <p class="kcj-brand-teasers-title"><?php esc_html_e('Choose a key', 'kcjdrama'); ?></p>
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
            <p class="kcj-onramp-then">
                <?php esc_html_e('If you are new to the house, Start here walks the whole map in one sitting.', 'kcjdrama'); ?>
            </p>
        </div>
    </div>
</main>
<?php
get_footer();
