<?php
/**
 * Start here — Soft on-ramp / guided map. Template: page-start-here.php
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
$stories = kcj_page_url('stories');
$notes = kcj_field_notes_url();
$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
$policy = kcj_page_url('editorial-policy');
$shipping = kcj_page_url('shipping-returns');
$support = kcj_page_url('support');

$desks = [
    ['num' => '01', 'kicker' => 'Korea', 'title' => 'Korea desk', 'blurb' => 'Sixteen-episode pressure cookers. OST that finds you in the grocery store. Second-lead heartbreak with a shrine attached.', 'href' => $korea],
    ['num' => '02', 'kicker' => 'China', 'title' => 'China desk', 'blurb' => 'Destiny with interest. Palace heat. Transmigration as agency. Softness that survives spectacle.', 'href' => $china],
    ['num' => '03', 'kicker' => 'Japan', 'title' => 'Japan desk', 'blurb' => 'Romance by subtraction. Workplace air you can taste. Last-train courage. Everyday life as the epic.', 'href' => $japan],
];

$next = [
    ['num' => '04', 'kicker' => 'Soft', 'title' => 'Trope encyclopedia', 'blurb' => 'Shared patterns. Craft notes first — Mirror jokes optional, never required.', 'href' => $tropes],
    ['num' => '05', 'kicker' => 'Mirror', 'title' => 'Syndrome clinic', 'blurb' => 'Name the affliction out loud. You’re among friends. Devices, not people.', 'href' => $syndromes],
    ['num' => '06', 'kicker' => 'Literacy', 'title' => 'Glossary', 'blurb' => 'Chaebol, xianxia, shokuba, danmei — words that unlock the rooms without gatekeeping.', 'href' => $glossary],
];

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-onramp">
    <div class="kcj-brand-stage-inner">
        <header class="kcj-brand-hero">
            <p class="kcj-brand-folio">00</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · On-ramp', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-brand-lede">
                <?php esc_html_e('You are in a house with two lamps. Soft is the sincere desk — craft, comfort, the rewatch light. Mirror is the friend on the floor with snacks who names the syndrome so you can laugh and still care. Same dramas. Different lamp.', 'kcjdrama'); ?>
            </p>
            <p class="kcj-brand-body">
                <?php esc_html_e('Korean, Chinese, and Japanese romance share a grammar — status gaps, slow recognition, weather as emotion — then each country plays it in a different key. Everything here is rewritten original text. No stolen stills, no script dumps, no “watch it free” doors. If you belong here, you belong without stealing someone else’s work.', 'kcjdrama'); ?>
            </p>
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

        <?php if (function_exists('kcj_the_epigraph')) { kcj_the_epigraph('start-destiny-together'); } ?>

        <section class="kcj-onramp-guide" aria-labelledby="kcj-onramp-how">
            <h2 id="kcj-onramp-how" class="kcj-brand-teasers-title"><?php esc_html_e('How to use this sitting', 'kcjdrama'); ?></h2>
            <ol class="kcj-onramp-steps">
                <li>
                    <strong><?php esc_html_e('Pick a country desk.', 'kcjdrama'); ?></strong>
                    <?php esc_html_e('Korea’s compressed clock, China’s long destiny stretch, Japan’s quiet subtraction — start with the temperature you already crave, or the one you have been avoiding because it wrecks you nicer.', 'kcjdrama'); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Open one Soft pattern.', 'kcjdrama'); ?></strong>
                    <?php esc_html_e('The trope encyclopedia is not a spoiler machine. It is craft notes for the wrist-grab, the silence, the umbrella that somehow saves the hour. Read one entry like a rewatch companion.', 'kcjdrama'); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Optionally name a syndrome.', 'kcjdrama'); ?></strong>
                    <?php esc_html_e('If the feeling needs a joke so it can breathe, Mirror’s clinic is down the hall. Afflictions of habit. Never punching down at soft people living soft lives.', 'kcjdrama'); ?>
                </li>
                <li>
                    <strong><?php esc_html_e('Shop only if the mood sticks.', 'kcjdrama'); ?></strong>
                    <?php esc_html_e('Merch is print-on-demand Soft/Mirror moodwear — made after you order. Reading is enough. Staying is enough. The catalog is there when a feeling wants fabric.', 'kcjdrama'); ?>
                </li>
            </ol>
            <p class="kcj-onramp-aside">
                <?php esc_html_e('You do not have to do all four tonight. One desk and one pattern is a whole visit. We built the house so you could stop explaining the almost-confession to people who do not pause on shared umbrellas.', 'kcjdrama'); ?>
            </p>
        </section>

        <div class="kcj-onramp-path">
            <p class="kcj-brand-teasers-title"><?php esc_html_e('Three desks, one feeling', 'kcjdrama'); ?></p>
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
                <?php esc_html_e('Shared grammar. Different keys. The desks are primers with awe baked in — not thin labels pretending to be rooms.', 'kcjdrama'); ?>
            </p>
        </div>

        <section class="kcj-policy-rails kcj-onramp-rails" aria-label="<?php esc_attr_e('Soft and Mirror', 'kcjdrama'); ?>">
            <a class="kcj-teaser kcj-teaser--soft" href="<?php echo esc_url($soft); ?>">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Soft World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Come sit', 'kcjdrama'); ?></h3>
                <p><?php esc_html_e('Porcelain romance. Why a device works. Comfort without treating you like you need a plot summary dump. Craft before the joke.', 'kcjdrama'); ?></p>
            </a>
            <a class="kcj-teaser kcj-teaser--mirror" href="<?php echo esc_url($mirror); ?>">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Mirror World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Come laugh', 'kcjdrama'); ?></h3>
                <p><?php esc_html_e('Violet roast. Same tropes, no plot armor. We exaggerate storytelling habits so you can exhale — then go back to caring harder.', 'kcjdrama'); ?></p>
            </a>
        </section>

        <div class="kcj-onramp-path">
            <p class="kcj-onramp-then"><?php esc_html_e('Then pick one Soft pattern and one Mirror affliction — or just wander.', 'kcjdrama'); ?></p>
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
            <p class="kcj-onramp-secondary">
                <a href="<?php echo esc_url($stories); ?>"><?php esc_html_e('Stories desk', 'kcjdrama'); ?></a>
                <span aria-hidden="true"> · </span>
                <a href="<?php echo esc_url($notes); ?>"><?php esc_html_e('Field notes', 'kcjdrama'); ?></a>
                <span aria-hidden="true"> · </span>
                <a href="<?php echo esc_url(kcj_page_url('countries')); ?>"><?php esc_html_e('All country desks', 'kcjdrama'); ?></a>
            </p>
        </div>

        <section class="kcj-onramp-buy" aria-labelledby="kcj-onramp-buy-title">
            <h2 id="kcj-onramp-buy-title" class="kcj-brand-teasers-title"><?php esc_html_e('Before you buy', 'kcjdrama'); ?></h2>
            <p class="kcj-brand-body">
                <?php esc_html_e('Soft and Mirror merch is printed after you order — not pulled off a warehouse shelf. If a feeling wants fabric, fine. If you only came to understand why rain wrecks you, also fine.', 'kcjdrama'); ?>
            </p>
            <p class="kcj-onramp-secondary">
                <a href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a>
                <span aria-hidden="true"> · </span>
                <a href="<?php echo esc_url($support); ?>"><?php esc_html_e('Support', 'kcjdrama'); ?></a>
                <span aria-hidden="true"> · </span>
                <a href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
            </p>
        </section>
    </div>
</main>
<?php
get_footer();
