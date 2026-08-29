<?php
/**
 * Soft World — literacy glossary. Template hierarchy: page-glossary.php for /glossary/
 */
if (!defined('ABSPATH')) {
    exit;
}

require_once KCJ_PATH . '/inc/content/pack-data.php';

$desks = [
    'korea' => 'Korea',
    'china' => 'China',
    'japan' => 'Japan',
    'craft' => 'Craft',
];

$entries = kcj_content_glossary();
usort($entries, static function ($a, $b) {
    return strcasecmp((string) ($a['term'] ?? ''), (string) ($b['term'] ?? ''));
});

$counts = ['korea' => 0, 'china' => 0, 'japan' => 0, 'craft' => 0];
foreach ($entries as $row) {
    $d = $row['desk'] ?? '';
    if (isset($counts[$d])) {
        $counts[$d]++;
    }
}

$tropes = kcj_page_url('tropes');
$syndromes = kcj_page_url('syndromes');
$soft = kcj_page_url('soft');
$start = kcj_page_url('start-here');
$policy = kcj_page_url('editorial-policy');

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-glos">
    <div class="kcj-glos-inner">
        <header class="kcj-glos-hero">
            <p class="kcj-brand-folio">06</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · Literacy', 'kcjdrama'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-glos-lede"><?php esc_html_e('Words that unlock both rooms. Korea, China, Japan — then the shared craft.', 'kcjdrama'); ?></p>
            <p class="kcj-glos-body"><?php esc_html_e('Terms are explained so you can watch legally and talk clearly. Not a license to pirate novels or video.', 'kcjdrama'); ?></p>
            <p class="kcj-glos-meta">
                <?php
                printf(
                    esc_html__('%1$d terms · %2$d Korea · %3$d China · %4$d Japan · %5$d craft', 'kcjdrama'),
                    count($entries),
                    (int) $counts['korea'],
                    (int) $counts['china'],
                    (int) $counts['japan'],
                    (int) $counts['craft']
                );
                ?>
            </p>
            <div class="kcj-brand-actions">
                <a class="kcj-brand-cross" href="<?php echo esc_url($start); ?>"><?php esc_html_e('Start here', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($tropes); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($syndromes); ?>"><?php esc_html_e('Syndromes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($soft); ?>"><?php esc_html_e('Soft stage', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
            </div>
                <?php if (function_exists('kcj_the_epigraph')) { kcj_the_epigraph('glossary-i-am-here'); } ?>
        </header>

        <div class="kcj-glos-catalog">
            <div class="kcj-glos-tools">
                <label class="kcj-vh" for="kcj-glos-q"><?php esc_html_e('Find a term', 'kcjdrama'); ?></label>
                <input
                    id="kcj-glos-q"
                    class="kcj-glos-filter"
                    type="search"
                    placeholder="<?php esc_attr_e('Find a word…', 'kcjdrama'); ?>"
                    autocomplete="off"
                    spellcheck="false"
                    aria-controls="kcj-glos-list"
                >
                <div class="kcj-glos-chips" role="group" aria-label="<?php esc_attr_e('Desk', 'kcjdrama'); ?>">
                    <button type="button" class="kcj-glos-chip is-active" data-desk="*" aria-pressed="true"><?php esc_html_e('All', 'kcjdrama'); ?></button>
                    <?php foreach ($desks as $desk => $label) : ?>
                        <button type="button" class="kcj-glos-chip kcj-glos-chip--<?php echo esc_attr($desk); ?>" data-desk="<?php echo esc_attr($desk); ?>" aria-pressed="false"><?php echo esc_html($label); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>

            <dl class="kcj-glos-list" id="kcj-glos-list">
                <?php foreach ($entries as $row) :
                    $term = (string) ($row['term'] ?? '');
                    $def  = (string) ($row['def'] ?? '');
                    $desk = (string) ($row['desk'] ?? 'craft');
                    if ($term === '') {
                        continue;
                    }
                    $label = $desks[$desk] ?? 'Craft';
                    $needle = strtolower($term . ' ' . $def . ' ' . $desk . ' ' . $label);
                    ?>
                    <div
                        class="kcj-glos-item"
                        id="<?php echo esc_attr($term); ?>"
                        data-desk="<?php echo esc_attr($desk); ?>"
                        data-q="<?php echo esc_attr($needle); ?>"
                    >
                        <dt>
                            <span class="kcj-glos-desk"><?php echo esc_html($label); ?></span>
                            <span class="kcj-glos-term"><?php echo esc_html($term); ?></span>
                        </dt>
                        <dd><?php echo esc_html($def); ?></dd>
                    </div>
                <?php endforeach; ?>
            </dl>
            <p class="kcj-glos-none" hidden role="status"><?php esc_html_e('No term matches.', 'kcjdrama'); ?></p>
            <script>
            (function () {
                var q = document.getElementById('kcj-glos-q');
                var list = document.getElementById('kcj-glos-list');
                if (!q || !list) return;
                var none = document.querySelector('.kcj-glos-none');
                var chips = document.querySelectorAll('.kcj-glos-chip');
                var desk = '*';
                function paint() {
                    var needle = q.value.trim().toLowerCase();
                    var shown = 0;
                    list.querySelectorAll('.kcj-glos-item').forEach(function (row) {
                        var deskOk = desk === '*' || row.getAttribute('data-desk') === desk;
                        var textOk = !needle || (row.getAttribute('data-q') || '').indexOf(needle) !== -1;
                        var hit = deskOk && textOk;
                        row.hidden = !hit;
                        if (hit) shown++;
                    });
                    if (none) none.hidden = shown !== 0;
                }
                q.addEventListener('input', paint);
                chips.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        desk = btn.getAttribute('data-desk') || '*';
                        chips.forEach(function (c) {
                            var on = c === btn;
                            c.classList.toggle('is-active', on);
                            c.setAttribute('aria-pressed', on ? 'true' : 'false');
                        });
                        paint();
                    });
                });
            })();
            </script>
        </div>
    </div>
</main>
<?php
get_footer();
