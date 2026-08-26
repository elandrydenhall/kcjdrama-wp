<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('add_meta_boxes', function () {
    add_meta_box(
        'kcj_hero_meta',
        'Hero hotspots',
        'kcj_render_hero_metabox',
        'kcj_hero',
        'normal',
        'high'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'kcj_hero') {
        return;
    }
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
        return;
    }
    wp_enqueue_style('kcj-admin-hotspots', KCJ_URI . '/assets/css/admin-hotspots.css', [], KCJ_VERSION);
    wp_enqueue_script('kcj-admin-hotspots', KCJ_URI . '/assets/js/admin-hotspots.js', [], KCJ_VERSION, true);
});

function kcj_render_hero_metabox($post) {
    wp_nonce_field('kcj_save_hero', 'kcj_hero_nonce');
    $has_menu = (bool) get_post_meta($post->ID, '_kcj_has_baked_menu', true);
    $hotspots = get_post_meta($post->ID, '_kcj_hotspots', true);
    if (is_array($hotspots)) {
        $json = wp_json_encode($hotspots);
    } else {
        $json = is_string($hotspots) && $hotspots !== '' ? $hotspots : '[]';
    }
    $thumb = get_the_post_thumbnail_url($post, 'full');
    $soft = kcj_page_url('soft');
    $mirror = kcj_page_url('mirror');
    $home = kcj_local_href(home_url('/'));
    ?>
    <p>
        <a class="submitdelete deletion" href="<?php echo esc_url(get_delete_post_link($post->ID, '', true)); ?>" onclick="return confirm('Delete this hero permanently?');">Delete this hero permanently</a>
    </p>
    <div class="kcj-editor-chrome">
        <p>
            <label>
                <input type="checkbox" name="kcj_has_baked_menu" value="1" <?php checked($has_menu); ?>>
                This image already has a menu painted in (hide the HTML header)
            </label>
        </p>
        <p class="description">Draw boxes on the image. Positions are % of the image. Expand + Fit all gives the most vertical room. Ctrl+wheel zooms on the cursor.</p>
    </div>

    <input type="hidden" id="kcj-hotspots-json" name="kcj_hotspots" value="<?php echo esc_attr($json); ?>">
    <input type="hidden" id="kcj-preset-home" value="<?php echo esc_url($home); ?>">
    <input type="hidden" id="kcj-preset-soft" value="<?php echo esc_url($soft); ?>">
    <input type="hidden" id="kcj-preset-mirror" value="<?php echo esc_url($mirror); ?>">

    <div class="kcj-editor">
        <div class="kcj-editor-main">
            <div class="kcj-editor-toolbar" id="kcj-editor-toolbar">
                <button type="button" class="button" id="kcj-zoom-out" title="Zoom out">−</button>
                <input type="range" id="kcj-zoom" min="25" max="400" step="1" value="100" aria-label="Zoom">
                <button type="button" class="button" id="kcj-zoom-in" title="Zoom in">+</button>
                <span class="kcj-zoom-label" id="kcj-zoom-label">100%</span>
                <button type="button" class="button" id="kcj-zoom-fit">Fit width</button>
                <button type="button" class="button" id="kcj-zoom-all">Fit all</button>
                <button type="button" class="button" id="kcj-jump-top">Show top</button>
                <button type="button" class="button button-primary" id="kcj-expand">Expand editor</button>
                <button type="button" class="button button-primary" id="kcj-save-hero">Update</button>
            </div>
            <div class="kcj-editor-viewport" id="kcj-editor-viewport">
                <div class="kcj-editor-canvas" id="kcj-editor-canvas">
                    <div class="kcj-editor-stage" id="kcj-editor-stage">
                        <?php if ($thumb) : ?>
                            <img src="<?php echo esc_url($thumb); ?>" alt="">
                        <?php else : ?>
                            <div class="kcj-editor-missing">Set a featured image to plot hotspots.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="kcj-editor-side">
            <button type="button" class="button" id="kcj-add-hotspot">Add hotspot</button>
            <ol class="kcj-editor-list" id="kcj-editor-list"></ol>
            <div class="kcj-editor-fields" id="kcj-editor-fields" hidden>
                <label>Label
                    <input type="text" id="kcj-field-label">
                </label>
                <label>Role
                    <select id="kcj-field-role">
                        <option value="logo">Logo → home</option>
                        <option value="soft">Enter Soft</option>
                        <option value="mirror">Enter Mirror</option>
                        <option value="custom">Custom URL</option>
                    </select>
                </label>
                <label>URL
                    <input type="text" id="kcj-field-href">
                </label>
                <div class="kcj-editor-nums">
                    <label>X% <input type="number" step="any" id="kcj-field-x"></label>
                    <label>Y% <input type="number" step="any" id="kcj-field-y"></label>
                    <label>W% <input type="number" step="any" id="kcj-field-w"></label>
                    <label>H% <input type="number" step="any" id="kcj-field-h"></label>
                </div>
                <button type="button" class="button-link-delete" id="kcj-delete-hotspot">Remove hotspot</button>
            </div>
        </div>
    </div>
    <?php
}

add_action('save_post_kcj_hero', function ($post_id) {
    if (!isset($_POST['kcj_hero_nonce']) || !wp_verify_nonce($_POST['kcj_hero_nonce'], 'kcj_save_hero')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $has_menu = !empty($_POST['kcj_has_baked_menu']) ? '1' : '';
    update_post_meta($post_id, '_kcj_has_baked_menu', $has_menu);

    $raw = isset($_POST['kcj_hotspots']) ? wp_unslash($_POST['kcj_hotspots']) : '[]';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = [];
    }
    $clean = [];
    foreach ($data as $spot) {
        if (!is_array($spot)) {
            continue;
        }
        $clean[] = [
            'id'    => sanitize_key($spot['id'] ?? uniqid('s')),
            'x'     => round((float) ($spot['x'] ?? 0), 2),
            'y'     => round((float) ($spot['y'] ?? 0), 2),
            'w'     => round((float) ($spot['w'] ?? 10), 2),
            'h'     => round((float) ($spot['h'] ?? 6), 2),
            'href'  => esc_url_raw($spot['href'] ?? ''),
            'label' => sanitize_text_field($spot['label'] ?? ''),
            'role'  => sanitize_key($spot['role'] ?? 'custom'),
        ];
    }
    update_post_meta($post_id, '_kcj_hotspots', $clean);

    if (!(int) get_option('kcj_current_hero_id', 0)) {
        update_option('kcj_current_hero_id', $post_id, false);
    }
});

add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=kcj_hero',
        'Hero rotation',
        'Rotation',
        'manage_options',
        'kcj-rotation',
        'kcj_render_rotation_page'
    );
});

function kcj_render_rotation_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (isset($_POST['kcj_rotation_nonce']) && wp_verify_nonce($_POST['kcj_rotation_nonce'], 'kcj_save_rotation')) {
        update_option('kcj_rotate_interval', sanitize_key($_POST['kcj_rotate_interval'] ?? 'hourly'));
        update_option('kcj_force_hero_id', (int) ($_POST['kcj_force_hero_id'] ?? 0));
        if (!empty($_POST['kcj_rotate_now'])) {
            delete_option('kcj_force_hero_id');
            update_option('kcj_force_hero_id', 0);
            kcj_advance_hero();
        }
        echo '<div class="updated"><p>Saved.</p></div>';
    }

    $interval = kcj_rotate_interval();
    $forced = (int) get_option('kcj_force_hero_id', 0);
    $current = (int) get_option('kcj_current_hero_id', 0);
    $heroes = get_posts([
        'post_type'      => 'kcj_hero',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'ASC'],
    ]);
    ?>
    <div class="wrap">
        <h1>Hero rotation</h1>
        <form method="post">
            <?php wp_nonce_field('kcj_save_rotation', 'kcj_rotation_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th>Current</th>
                    <td><?php echo $current ? esc_html(get_the_title($current)) . ' (#' . (int) $current . ')' : 'None'; ?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kcj_rotate_interval">Cron interval</label></th>
                    <td>
                        <select name="kcj_rotate_interval" id="kcj_rotate_interval">
                            <option value="hourly" <?php selected($interval, 'hourly'); ?>>Hourly</option>
                            <option value="twicedaily" <?php selected($interval, 'twicedaily'); ?>>Twice daily</option>
                            <option value="daily" <?php selected($interval, 'daily'); ?>>Daily</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kcj_force_hero_id">Pin this hero</label></th>
                    <td>
                        <select name="kcj_force_hero_id" id="kcj_force_hero_id">
                            <option value="0">Follow rotation</option>
                            <?php foreach ($heroes as $hero) : ?>
                                <option value="<?php echo (int) $hero->ID; ?>" <?php selected($forced, $hero->ID); ?>>
                                    <?php echo esc_html($hero->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Pinning stops the cron from advancing.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save'); ?>
            <p>
                <button class="button" type="submit" name="kcj_rotate_now" value="1">Advance to next hero now</button>
            </p>
        </form>
    </div>
    <?php
}
