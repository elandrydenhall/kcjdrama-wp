<?php
/**
 * Soft country desk primers — Korea / China / Japan.
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string, mixed>|null
 */
function kcj_country_desk_data($slug) {
    $slug = sanitize_key((string) $slug);
    $tropes = kcj_page_url('tropes');
    $glossary = kcj_page_url('glossary');
    $soft = kcj_page_url('soft');
    $mirror = kcj_page_url('mirror');
    $start = kcj_page_url('start-here');
    $shop = function_exists('kcj_catalog_url') ? kcj_catalog_url(['rail' => 'all']) : kcj_page_url('shop');
    $syndromes = kcj_page_url('syndromes');

    $cat_url = '';
    $term = get_term_by('slug', $slug, 'category');
    if ($term && !is_wp_error($term)) {
        $link = get_category_link($term);
        if ($link && !is_wp_error($link)) {
            $cat_url = $link;
        }
    }

    $desks = [
        'korea' => [
            'kicker' => __('Soft World · Korea desk', 'kcjdrama'),
            'folio'  => '01',
            'lede'   => __('Sixteen episodes, give or take, to invent a private world, break it, and choose it again. That is the ache. That is why you stay up.', 'kcjdrama'),
            'feel'   => [
                __('K-romance runs on compressed time. Not because the writers are in a hurry — because the feeling has to become a whole weather system before the finale takes the house lights up. Status gaps (often chaebol-coded, sometimes just “your world and mine”) turn class into theater. You feel the glass wall. You watch two people learn how to knock.', 'kcjdrama'),
                __('The OST is not decoration. It is emotional architecture. A piano line can hold a confession the dialogue is still too scared to say. Friend-group found family keeps the oxygen in the room. And the second lead — lord, the second lead — gets a shrine in your chest even when you know who the endgame is. That is not confusion. That is literacy.', 'kcjdrama'),
                __('Rain is plot voltage. Umbrellas arrive like mercy. Hospital hallways and midnight convenience-store noodles do more work than a speech ever could. If you have ever paused on a shared look in episode four and felt your week rearrange itself, you already know this desk.', 'kcjdrama'),
            ],
            'devices_title' => __('Signature devices', 'kcjdrama'),
            'devices' => [
                __('Chaebol / status-gap fantasy as class theater — not a shopping list of brands, a wall between rooms', 'kcjdrama'),
                __('OST as emotional architecture — the song that finds you in the grocery store months later', 'kcjdrama'),
                __('Second-lead magnetic pull — heartbreak with good manners and worse timing', 'kcjdrama'),
                __('Friend-group found family — the peanut gallery that keeps soft people alive', 'kcjdrama'),
                __('Weather as voltage — rain, snow, one ridiculous umbrella that somehow saves the hour', 'kcjdrama'),
                __('Car-confession energy — motion as courage when standing still would break you', 'kcjdrama'),
            ],
            'picks_title' => __('Soft patterns to chase next', 'kcjdrama'),
            'picks' => [
                ['label' => __('Chaebol inheritance war', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('OST as emotional architecture', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Second lead magnetic', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Car confession chase', 'kcjdrama'), 'href' => $tropes],
            ],
            'read_soft' => __('On Soft, read for craft: why the wrist-grab works, how silence stores voltage, how a sixteen-episode clock teaches longing without treating you like you need a plot dump. Come for the rewatch light.', 'kcjdrama'),
            'read_mirror' => __('On Mirror, name the syndrome out loud — selective amnesia with perfect hair, umbrella from another dimension, “I resign from feelings” speeches. Laugh, then still care. That is allowed.', 'kcjdrama'),
            'legal' => __('If you want the show, support legal releases and official streams. If you want the feeling explained in original words, you are home.', 'kcjdrama'),
            'epigraph_key' => 'korea-every-moment-shined',
        ],
        'china' => [
            'kicker' => __('Soft World · China desk', 'kcjdrama'),
            'folio'  => '02',
            'lede'   => __('Longer arcs. Destiny metaphors that make love cosmically expensive. Public catharsis beside private softness — and a second chance written into the body of the story.', 'kcjdrama'),
            // Stable shortcode/CPT key — never a numeric id.
            'epigraph_key' => 'china-face-heartbeat',
            'feel'   => [
                __('C-romance often stretches. Web-novel pacing, palace heat, xianxia skies that treat a glance like a vow with interest. The stakes are not only “will they kiss.” The stakes are heaven, face, reincarnation, a contract with fate that someone is trying to renegotiate with trembling hands.', 'kcjdrama'),
                __('Face-slap catharsis exists for a reason — the room needs a release valve when dignity has been cornered. Then the camera finds the soft part anyway: a wrist held too carefully, a bowl of something warm, a promise whispered like it might scare the gods. Transmigration and villainess rewrites are agency machines. You wake up inside the book and refuse the ending that was written for you. That feeling is electric.', 'kcjdrama'),
                __('Red-thread destiny talk can sound mythic until it ruins your commute. Danmei literacy matters for a lot of international readers — we teach terms here, we do not host pirated text. Bring your own legal shelves. Bring your awe. We will meet you at the glossary.', 'kcjdrama'),
            ],
            'devices_title' => __('Signature devices', 'kcjdrama'),
            'devices' => [
                __('Xianxia / cultivation stakes — love that costs immortality-adjacent nonsense in the best way', 'kcjdrama'),
                __('Palace heat and face politics — tenderness under surveillance', 'kcjdrama'),
                __('Transmigration into the book — agency with jet lag of the soul', 'kcjdrama'),
                __('Face-slap court arcs — catharsis with choreography', 'kcjdrama'),
                __('Red-thread destiny — fate as romance grammar, not a spoiler label', 'kcjdrama'),
                __('Villainess / rewrite energy — refusing the assigned tragedy', 'kcjdrama'),
            ],
            'picks_title' => __('Soft patterns to chase next', 'kcjdrama'),
            'picks' => [
                ['label' => __('Transmigration into the book', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Face-slapping court arc', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Red-thread destiny', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Villainess rewrites fate', 'kcjdrama'), 'href' => $tropes],
            ],
            'read_soft' => __('On Soft, we slow down for craft and culture bridges — what “face” is doing in a scene, why destiny talk hits different when the arc is long, how softness survives spectacle.', 'kcjdrama'),
            'read_mirror' => __('On Mirror, we roast the habit: destiny GPS that never loses signal, villainess makeovers at the speed of plot, face-slap as weekly cardio. Affectionate chaos. Devices, not people.', 'kcjdrama'),
            'legal' => __('Official releases only for the shows and novels you love. KCJ is pattern commentary and original satire — never a piracy door.', 'kcjdrama'),
        ],
        'japan' => [
            'kicker' => __('Soft World · Japan desk', 'kcjdrama'),
            'folio'  => '03',
            'lede'   => __('Romance by subtraction. Fewer speeches. More weather. More workplace air you can almost taste. Everyday life as the epic.', 'kcjdrama'),
            'epigraph_key' => 'japan-loneliness-someone',
            'feel'   => [
                __('J-romance often wins by what it refuses to shout. Shokuba hierarchy puts a polite fence between two people who keep choosing the long way around. Slow-burn silence is not emptiness — it is storage. The confession arrives like a last train: late, bright, a little cruel in how necessary it feels.', 'kcjdrama'),
                __('Food as love language. A bent lunch. A shared umbrella that is somehow more intimate than a kiss in another country’s grammar. Seasons do half the writing. You are not waiting for a chaebol helicopter. You are waiting for someone to say the true thing before the doors close.', 'kcjdrama'),
                __('If Soft Korea compresses time and Soft China stretches destiny, Soft Japan teaches patience as voltage. The courage is small and enormous at once. A coat offered without a speech. A glance across fluorescent office light that rearranges your whole evening. That is why it wrecks you quietly.', 'kcjdrama'),
                __('Come to this desk when you want the epic inside ordinary hours — kitchens, train platforms, the soft terror of being understood. We will talk craft. We will keep the awe. We will not rush you into shouting what the story is earning through air.', 'kcjdrama'),
            ],
            'devices_title' => __('Signature devices', 'kcjdrama'),
            'devices' => [
                __('Shokuba / office distance — hierarchy as romantic weather', 'kcjdrama'),
                __('Slow-burn silence — storage, not stalling', 'kcjdrama'),
                __('Last-episode / last-train courage — timing as character', 'kcjdrama'),
                __('Food and gesture as love language — care you can plate', 'kcjdrama'),
                __('Seasonal air — rain, humidity, one coat offered without a speech', 'kcjdrama'),
                __('Everyday epic — the kitchen light as climax machinery', 'kcjdrama'),
            ],
            'picks_title' => __('Soft patterns to chase next', 'kcjdrama'),
            'picks' => [
                ['label' => __('Slow-burn silence', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Office senpai distance', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Last-episode confession', 'kcjdrama'), 'href' => $tropes],
                ['label' => __('Food as love language', 'kcjdrama'), 'href' => $tropes],
            ],
            'read_soft' => __('On Soft, sit with the restraint. Ask why a glance is enough. Ask how workplace air becomes plot. This desk is for rewatch quiet and craft notes that do not rush you.', 'kcjdrama'),
            'read_mirror' => __('On Mirror, we still tease the tropes — senpai forcefields, confession deferral as extreme sport — without punching down at soft people living soft lives.', 'kcjdrama'),
            'legal' => __('Stream and buy through legal doors. We will be here with the feeling explained, original words only.', 'kcjdrama'),
        ],
    ];

    if (!isset($desks[$slug])) {
        return null;
    }

    $row = $desks[$slug];
    $row['slug'] = $slug;
    $row['urls'] = compact('tropes', 'glossary', 'soft', 'mirror', 'start', 'shop', 'syndromes', 'cat_url');
    return $row;
}

/**
 * Render a Soft country desk primer.
 *
 * @param string $slug korea|china|japan
 */
function kcj_render_country_desk($slug) {
    $desk = kcj_country_desk_data($slug);
    if (!$desk) {
        return;
    }
    $u = $desk['urls'];
    ?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-policy kcj-country-desk">
    <div class="kcj-policy-inner">
        <header class="kcj-policy-hero">
            <p class="kcj-brand-folio"><?php echo esc_html((string) $desk['folio']); ?></p>
            <p class="kcj-page-kicker"><?php echo esc_html((string) $desk['kicker']); ?></p>
            <h1><?php the_title(); ?></h1>
            <p class="kcj-policy-lede"><?php echo esc_html((string) $desk['lede']); ?></p>
            <div class="kcj-brand-actions">
                <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($u['soft']); ?>"><?php esc_html_e('Enter Soft', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($u['tropes']); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
                <a class="kcj-brand-cross" href="<?php echo esc_url($u['start']); ?>"><?php esc_html_e('Start here', 'kcjdrama'); ?></a>
            </div>
        </header>

        <?php
        if (!empty($desk['epigraph_key']) && function_exists('kcj_the_epigraph')) {
            kcj_the_epigraph((string) $desk['epigraph_key']);
        }
        ?>

        <section class="kcj-policy-more" aria-labelledby="kcj-desk-feel">
            <article>
                <h2 id="kcj-desk-feel"><?php esc_html_e('What it feels like', 'kcjdrama'); ?></h2>
                <?php foreach ($desk['feel'] as $para) : ?>
                    <p class="kcj-desk-prose"><?php echo esc_html((string) $para); ?></p>
                <?php endforeach; ?>
            </article>
        </section>

        <section class="kcj-policy-grid" aria-label="<?php echo esc_attr((string) $desk['devices_title']); ?>">
            <article class="kcj-policy-card">
                <h2><?php echo esc_html((string) $desk['devices_title']); ?></h2>
                <ul>
                    <?php foreach ($desk['devices'] as $item) : ?>
                        <li><?php echo esc_html((string) $item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </article>
            <article class="kcj-policy-card">
                <h2><?php echo esc_html((string) $desk['picks_title']); ?></h2>
                <ul>
                    <?php foreach ($desk['picks'] as $pick) : ?>
                        <li>
                            <a href="<?php echo esc_url((string) $pick['href']); ?>"><?php echo esc_html((string) $pick['label']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="kcj-policy-inline-links">
                    <a href="<?php echo esc_url($u['glossary']); ?>"><?php esc_html_e('Glossary', 'kcjdrama'); ?></a>
                    <?php if (!empty($u['cat_url'])) : ?>
                        ·
                        <a href="<?php echo esc_url($u['cat_url']); ?>"><?php esc_html_e('Desk-tagged notes', 'kcjdrama'); ?></a>
                    <?php endif; ?>
                </p>
            </article>
        </section>

        <section class="kcj-policy-rails" aria-label="<?php esc_attr_e('How to read here', 'kcjdrama'); ?>">
            <div class="kcj-teaser kcj-teaser--soft">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Soft World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Craft before the joke', 'kcjdrama'); ?></h3>
                <p><?php echo esc_html((string) $desk['read_soft']); ?></p>
                <p class="kcj-policy-inline-links">
                    <a href="<?php echo esc_url($u['soft']); ?>"><?php esc_html_e('Enter Soft', 'kcjdrama'); ?></a>
                </p>
            </div>
            <div class="kcj-teaser kcj-teaser--mirror">
                <p class="kcj-teaser-kicker"><?php esc_html_e('Mirror World', 'kcjdrama'); ?></p>
                <h3><?php esc_html_e('Name it, still care', 'kcjdrama'); ?></h3>
                <p><?php echo esc_html((string) $desk['read_mirror']); ?></p>
                <p class="kcj-policy-inline-links">
                    <a href="<?php echo esc_url($u['mirror']); ?>"><?php esc_html_e('Enter Mirror', 'kcjdrama'); ?></a>
                    ·
                    <a href="<?php echo esc_url($u['syndromes']); ?>"><?php esc_html_e('Syndromes', 'kcjdrama'); ?></a>
                </p>
            </div>
        </section>

        <section class="kcj-policy-more">
            <article>
                <h2><?php esc_html_e('Watch legally', 'kcjdrama'); ?></h2>
                <p><?php echo esc_html((string) $desk['legal']); ?></p>
            </article>
        </section>

        <section class="kcj-policy-footer-links" aria-label="<?php esc_attr_e('Related', 'kcjdrama'); ?>">
            <a href="<?php echo esc_url($u['start']); ?>"><?php esc_html_e('Start here', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url(kcj_page_url('countries')); ?>"><?php esc_html_e('All desks', 'kcjdrama'); ?></a>
            <a href="<?php echo esc_url($u['tropes']); ?>"><?php esc_html_e('Tropes', 'kcjdrama'); ?></a>
            <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url($u['shop']); ?>"><?php esc_html_e('Shop the split', 'kcjdrama'); ?></a>
        </section>
    </div>
</main>
    <?php
}
