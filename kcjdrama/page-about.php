<?php
/**
 * About — a letter from the desk (Soft chrome).
 * Template hierarchy: page-about.php for /about/
 */
if (!defined('ABSPATH')) {
    exit;
}

$shop_all = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
$shipping = kcj_page_url('shipping-returns');
$policy = kcj_page_url('editorial-policy');
$privacy = get_privacy_policy_url() ?: home_url('/privacy-policy/');
$contact = kcj_page_url('contact');
$soft = kcj_page_url('soft');
$mirror = kcj_page_url('mirror');
$start = kcj_page_url('start-here');
$tropes = kcj_page_url('tropes');

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-about">
    <div class="kcj-about-inner">
        <header class="kcj-about-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · A letter', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-about-lede">
                <?php esc_html_e('Hi. I’m the person who still pauses on the shared umbrella. I made kcjdrama so you wouldn’t have to explain the feeling.', 'kcjdrama'); ?>
            </p>
        </header>

        <div class="kcj-about-bio">
            <p><?php esc_html_e('I grew up hopping channels between Korean, Chinese, and Japanese romance without a map — chaebol rain, palace heat, office silence — and I never wanted to pick a favorite country so much as keep a seat for all three. The OST still gets me in the grocery store. I rewind the almost-confession more than the kiss.', 'kcjdrama'); ?></p>
            <p><?php esc_html_e('This house has two rooms because one mood was never enough. Soft is the sincere desk: craft notes, comfort, the rewatch light. Mirror is the friend on the floor with snacks who names the syndrome out loud so you can laugh and still care. Same dramas. Different lamp.', 'kcjdrama'); ?></p>
            <p><?php esc_html_e('I write original words on purpose. Patterns, not pirated plots. No stolen stills, no script dumps, no “watch it here” links. If you belong here, you belong without stealing someone else’s work. Merch is for wearing the mood to a rewatch — print-on-demand from the live Soft and Mirror catalog, shipped when you order.', 'kcjdrama'); ?></p>
            <p><?php esc_html_e('I roast devices, not people. Second leads deserve a shrine and a glass of water. You can mix both rails in one cart. You can also just read and go. Either way, I’m glad you found the door.', 'kcjdrama'); ?></p>
        </div>

        <div class="kcj-brand-actions">
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($start); ?>"><?php esc_html_e('Start here', 'kcjdrama'); ?></a>
            <a class="kcj-brand-cross" href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
            <a class="kcj-brand-cross" href="<?php echo esc_url($shop_all); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
        </div>

        <section class="kcj-about-split" aria-label="<?php esc_attr_e('Soft and Mirror', 'kcjdrama'); ?>">
            <a class="kcj-teaser kcj-teaser--soft" href="<?php echo esc_url($soft); ?>">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Soft World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Come sit', 'kcjdrama'); ?></h3>
                <p><?php esc_html_e('Porcelain romance. Craft before the joke.', 'kcjdrama'); ?></p>
            </a>
            <a class="kcj-teaser kcj-teaser--mirror" href="<?php echo esc_url($mirror); ?>">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Mirror World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Come laugh', 'kcjdrama'); ?></h3>
                <p><?php esc_html_e('Violet roast. Same tropes, no plot armor.', 'kcjdrama'); ?></p>
            </a>
        </section>

        <section class="kcj-about-links" aria-labelledby="kcj-about-links-title">
            <h2 id="kcj-about-links-title"><?php esc_html_e('If you need the practical bits', 'kcjdrama'); ?></h2>
            <ul class="kcj-about-link-list">
                <li><a href="<?php echo esc_url($shipping); ?>"><?php esc_html_e('Shipping & returns', 'kcjdrama'); ?></a></li>
                <li><a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a></li>
                <li><a href="<?php echo esc_url($privacy); ?>"><?php esc_html_e('Privacy', 'kcjdrama'); ?></a></li>
                <li><a href="<?php echo esc_url($contact); ?>"><?php esc_html_e('Contact', 'kcjdrama'); ?></a></li>
            </ul>
        </section>
    </div>
</main>
<?php
get_footer();
