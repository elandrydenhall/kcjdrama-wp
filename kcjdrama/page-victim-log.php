<?php
/**
 * Mirror Victim Log — casualties of roast contact.
 * Template hierarchy: page-victim-log.php for /victim-log/
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once KCJ_PATH . '/inc/content/pack-data.php';

$entries = kcj_content_victim_log();
$counts = ['legendary' => 0, 'critical' => 0, 'toasted' => 0];
$related_names = [];
foreach ($entries as $e) {
    $s = $e['status'] ?? 'toasted';
    if (!isset($counts[$s])) {
        $counts[$s] = 0;
    }
    $counts[$s]++;
    if (!empty($e['related']) && !empty($e['related_kind'])) {
        $prefix = $e['related_kind'] === 'syndrome' ? 'syndrome-' : 'trope-';
        $related_names[] = $prefix . $e['related'];
    }
}
$related_names = array_values(array_unique($related_names));
$related_posts = [];
if ($related_names) {
    foreach (get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => count($related_names),
        'post_name__in'  => $related_names,
    ]) as $rp) {
        $related_posts[(string) $rp->post_name] = $rp;
    }
}

$syndromes = kcj_page_url('syndromes');
$tropes = kcj_page_url('tropes');
$roast = kcj_page_url('about-the-roast');
$mirror = kcj_page_url('mirror');
$shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'mirror']) : kcj_page_url('shop');

$status_label = [
    'legendary' => 'Legendary',
    'critical'  => 'Critical',
    'toasted'   => 'Toasted',
];

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--mirror kcj-victim">
    <div class="kcj-victim-inner">
        <header class="kcj-victim-hero">
            <p class="kcj-page-kicker"><?php esc_html_e('Mirror World · Casualty desk', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-victim-lede">
                <?php esc_html_e('A running list of tropes and devices that did not survive contact with Mirror World. We roast habits — not actors. File new injuries at the syndrome clinic.', 'kcjdrama'); ?>
            </p>
            <p class="kcj-victim-meta">
                <?php
                printf(
                    /* translators: 1: total 2: legendary 3: critical 4: toasted */
                    esc_html__('%1$d on the log · %2$d legendary · %3$d critical · %4$d toasted', 'kcjdrama'),
                    count($entries),
                    (int) $counts['legendary'],
                    (int) $counts['critical'],
                    (int) $counts['toasted']
                );
                ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--mirror" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Shop Mirror merch', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($syndromes); ?>"><?php esc_html_e('Syndrome clinic', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($roast); ?>"><?php esc_html_e('About the roast', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($mirror); ?>"><?php esc_html_e('Mirror stage', 'kcjdrama'); ?></a>
            </div>
        </header>

        <nav class="kcj-hub-chips kcj-victim-chips" aria-label="<?php esc_attr_e('Jump to casualty', 'kcjdrama'); ?>">
            <?php foreach ($entries as $entry) : ?>
                <a class="kcj-hub-chip" href="#casualty-<?php echo esc_attr($entry['slug']); ?>"><?php echo esc_html($entry['title']); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="kcj-victim-grid" role="list">
            <?php foreach ($entries as $i => $entry) :
                $status = $entry['status'] ?? 'toasted';
                $label = $status_label[$status] ?? 'Toasted';
                $related_href = '';
                $related_text = '';
                if (!empty($entry['related']) && !empty($entry['related_kind'])) {
                    $prefix = $entry['related_kind'] === 'syndrome' ? 'syndrome-' : 'trope-';
                    $rname = $prefix . $entry['related'];
                    if (!empty($related_posts[$rname])) {
                        $related_href = get_permalink($related_posts[$rname]);
                        $related_text = $entry['related_kind'] === 'syndrome'
                            ? __('Open syndrome →', 'kcjdrama')
                            : __('Open trope →', 'kcjdrama');
                    } elseif ($entry['related_kind'] === 'syndrome') {
                        $related_href = $syndromes . '#entry-' . $entry['related'];
                        $related_text = __('See clinic →', 'kcjdrama');
                    } else {
                        $related_href = $tropes . '#entry-' . $entry['related'];
                        $related_text = __('See trope index →', 'kcjdrama');
                    }
                }
                ?>
                <article
                    class="kcj-victim-card kcj-victim-card--<?php echo esc_attr($status); ?>"
                    role="listitem"
                    id="casualty-<?php echo esc_attr($entry['slug']); ?>"
                >
                    <div class="kcj-victim-card-top">
                        <span class="kcj-victim-case"><?php printf(esc_html__('Case %02d', 'kcjdrama'), $i + 1); ?></span>
                        <span class="kcj-victim-status"><?php echo esc_html($label); ?></span>
                    </div>
                    <h2><?php echo esc_html($entry['title']); ?></h2>
                    <p class="kcj-victim-charge"><?php echo esc_html($entry['charge']); ?></p>
                    <?php if ($related_href !== '') : ?>
                        <p class="kcj-victim-related">
                            <a href="<?php echo esc_url($related_href); ?>"><?php echo esc_html($related_text); ?></a>
                        </p>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <section class="kcj-victim-foot">
            <p><?php esc_html_e('New injuries welcome — name the pattern, keep the roast affectionate, then hydrate.', 'kcjdrama'); ?></p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--mirror" href="<?php echo esc_url($syndromes); ?>"><?php esc_html_e('File via syndromes', 'kcjdrama'); ?></a>
                <a class="kcj-btn kcj-btn--gold" href="<?php echo esc_url($shop); ?>"><?php esc_html_e('Wear the roast', 'kcjdrama'); ?></a>
            </div>
        </section>
    </div>
</main>
<?php
get_footer();
