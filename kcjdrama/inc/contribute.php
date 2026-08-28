<?php
/**
 * Soft desk: apply to contribute, then verified users submit original fiction.
 * Stories stay pending until an editor publishes. No product, no stolen stills.
 */
if (!defined('ABSPATH')) {
    exit;
}

function kcj_is_verified_contributor($user_id = 0) {
    $user_id = $user_id ? (int) $user_id : get_current_user_id();
    if ($user_id <= 0) {
        return false;
    }
    if (user_can($user_id, 'publish_posts')) {
        return true;
    }
    return (string) get_user_meta($user_id, 'kcj_verified', true) === '1';
}

function kcj_desk_has_pending_application($email) {
    $email = sanitize_email($email);
    if ($email === '') {
        return false;
    }
    $found = get_posts([
        'post_type'      => 'kcj_contrib_app',
        'post_status'    => 'pending',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_kcj_app_email',
        'meta_value'     => $email,
    ]);
    return !empty($found);
}

add_action('init', function () {
    $contrib = get_role('contributor');
    if ($contrib && !$contrib->has_cap('kcj_submit_story')) {
        $contrib->add_cap('kcj_submit_story');
    }
    foreach (['author', 'editor', 'administrator'] as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap('kcj_submit_story')) {
            $role->add_cap('kcj_submit_story');
        }
    }

    register_post_type('kcj_contrib_app', [
        'labels' => [
            'name'          => 'Desk applications',
            'singular_name' => 'Desk application',
            'menu_name'     => 'Desk applications',
        ],
        'public'              => false,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-id',
        'supports'            => ['title', 'editor'],
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'exclude_from_search' => true,
        'rewrite'             => false,
    ]);

    register_post_type('kcj_story', [
        'labels' => [
            'name'          => 'Soft stories',
            'singular_name' => 'Soft story',
            'add_new_item'  => 'Add Soft story',
            'edit_item'     => 'Edit Soft story',
            'menu_name'     => 'Soft stories',
        ],
        'public'              => true,
        'has_archive'         => false,
        'rewrite'             => ['slug' => 'story'],
        'supports'            => ['title', 'editor', 'author'],
        'capability_type'     => 'post',
        'map_meta_cap'        => true,
        'menu_icon'           => 'dashicons-book-alt',
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'show_in_rest'        => false,
    ]);

    if (get_option('kcj_story_rewrites') !== '1.5.29') {
        flush_rewrite_rules(false);
        update_option('kcj_story_rewrites', '1.5.29', false);
    }
});

function kcj_sign_in_url($redirect = '') {
    $url = kcj_page_url('sign-in');
    if ($redirect === '' && !is_page('sign-in')) {
        $redirect = home_url(add_query_arg([]));
    }
    if ($redirect !== '') {
        $url = add_query_arg('redirect_to', $redirect, $url);
    }
    return $url;
}

function kcj_sign_out_url($redirect = '') {
    if ($redirect === '') {
        $redirect = home_url('/');
    }
    return wp_logout_url($redirect);
}

/**
 * @return array{url:string,label:string}
 */
function kcj_account_crumb($hint = '') {
    $home = home_url('/');
    $candidates = [];
    if ($hint !== '') {
        $candidates[] = $hint;
    }
    if (!empty($_GET['redirect_to'])) {
        $candidates[] = esc_url_raw(wp_unslash($_GET['redirect_to']));
    }
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $candidates[] = esc_url_raw(wp_unslash($_SERVER['HTTP_REFERER']));
    }
    $home_host = wp_parse_url($home, PHP_URL_HOST);
    $picked = '';
    foreach ($candidates as $raw) {
        $parts = wp_parse_url($raw);
        if (!is_array($parts)) {
            continue;
        }
        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        if ($host !== '' && $home_host && strtolower((string) $home_host) !== $host) {
            continue;
        }
        $path = isset($parts['path']) ? (string) $parts['path'] : '/';
        if ($path === '/sign-in' || $path === '/sign-in/' || strpos($path, '/sign-in') === 0) {
            continue;
        }
        $picked = $raw;
        break;
    }
    if ($picked === '') {
        return [
            'url'   => kcj_page_url('soft'),
            'label' => __('Soft World', 'kcjdrama'),
        ];
    }
    $path = (string) (wp_parse_url($picked, PHP_URL_PATH) ?: '/');
    $path = '/' . trim($path, '/') . '/';
    if ($path === '//') {
        $path = '/';
    }
    $map = [
        '/'            => __('Home', 'kcjdrama'),
        '/soft/'       => __('Soft World', 'kcjdrama'),
        '/mirror/'     => __('Mirror World', 'kcjdrama'),
        '/shop/'       => __('Shop', 'kcjdrama'),
        '/stories/'    => __('Stories', 'kcjdrama'),
        '/tropes/'     => __('Tropes', 'kcjdrama'),
        '/syndromes/'  => __('Syndromes', 'kcjdrama'),
        '/essays/'     => __('Essays', 'kcjdrama'),
        '/field-notes/'=> __('Field notes', 'kcjdrama'),
        '/about/'      => __('About', 'kcjdrama'),
        '/start-here/' => __('Start here', 'kcjdrama'),
        '/glossary/'   => __('Glossary', 'kcjdrama'),
    ];
    $label = __('Soft World', 'kcjdrama');
    $url = $picked;
    if ($path === '/' || $path === '') {
        $label = __('Home', 'kcjdrama');
        $url = $home;
    } elseif (isset($map[$path])) {
        $label = $map[$path];
    } elseif (strpos($path, '/shop/') === 0) {
        $label = __('Shop', 'kcjdrama');
    } elseif (strpos($path, '/product/') === 0) {
        $label = __('Shop', 'kcjdrama');
    } elseif (strpos($path, '/story/') === 0) {
        $label = __('Stories', 'kcjdrama');
        $url = kcj_page_url('stories');
    } else {
        $url = kcj_page_url('soft');
    }
    return ['url' => $url, 'label' => $label];
}

function kcj_login_keep_actions() {
    return ['logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp', 'postpass', 'confirmaction', 'checkemail'];
}

function kcj_forgot_password_url($redirect = '') {
    $args = ['action' => 'lostpassword'];
    if ($redirect !== '') {
        $args['redirect_to'] = $redirect;
    }
    return add_query_arg($args, site_url('wp-login.php', 'login'));
}

function kcj_login_door_url($code = '', $redirect = '') {
    $url = kcj_page_url('sign-in');
    if ($code !== '') {
        $url = add_query_arg('kcj_login', $code, $url);
    }
    if ($redirect !== '') {
        $url = add_query_arg('redirect_to', $redirect, $url);
    }
    return $url;
}

add_action('login_init', function () {
    if ((defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (!empty($_REQUEST['interim-login'])) {
        return;
    }
    $action = isset($_REQUEST['action']) ? (string) wp_unslash($_REQUEST['action']) : 'login';
    if ($action === '') {
        $action = 'login';
    }
    $redirect = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : '';

    if ($action === 'login' && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $log = isset($_POST['log']) ? trim((string) wp_unslash($_POST['log'])) : '';
        $pwd = isset($_POST['pwd']) ? (string) $_POST['pwd'] : '';
        if ($log === '' || $pwd === '') {
            wp_safe_redirect(kcj_login_door_url('empty', $redirect));
            exit;
        }
    }

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }
    if (in_array($action, kcj_login_keep_actions(), true)) {
        return;
    }
    $code = isset($_GET['loggedout']) ? 'loggedout' : '';
    wp_safe_redirect(kcj_login_door_url($code, $redirect));
    exit;
});

add_action('wp_login_failed', function () {
    if ((defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }
    if (!empty($_REQUEST['interim-login'])) {
        return;
    }
    $redirect = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : '';
    wp_safe_redirect(kcj_login_door_url('failed', $redirect));
    exit;
});

add_filter('login_headerurl', function () {
    return home_url('/');
});

add_filter('login_headertext', function () {
    return 'kcjdrama';
});

add_filter('login_display_language_dropdown', '__return_false');

add_filter('lostpassword_url', function ($url, $redirect) {
    return kcj_forgot_password_url(is_string($redirect) ? $redirect : '');
}, 10, 2);

/**
 * Forgot-password link under every front-end wp_login_form (Sign in page + Soft desk).
 */
add_filter('login_form_bottom', function ($content, $args = []) {
    if (is_admin()) {
        return $content;
    }
    global $pagenow;
    if (isset($pagenow) && $pagenow === 'wp-login.php') {
        return $content;
    }
    $redirect = '';
    if (is_array($args) && !empty($args['redirect'])) {
        $redirect = (string) $args['redirect'];
    }
    $url = esc_url(kcj_forgot_password_url($redirect));
    $label = esc_html__('Forgot username or password?', 'kcjdrama');
    return $content . '<p class="kcj-forgot-link"><a href="' . $url . '">' . $label . '</a></p>';
}, 10, 2);

add_filter('login_message', function ($message) {
    $action = isset($_REQUEST['action']) ? (string) wp_unslash($_REQUEST['action']) : 'login';
    if ($action === 'lostpassword' || $action === 'retrievepassword') {
        return '<p class="message kcj-login-soft-msg">' . esc_html__(
            'Enter the email or username on your key. We’ll send a reset link if the house knows that address.',
            'kcjdrama'
        ) . '</p>';
    }
    if ($action === 'resetpass' || $action === 'rp') {
        return '<p class="message kcj-login-soft-msg">' . esc_html__(
            'Choose a new password for this key. Twice, so it sticks.',
            'kcjdrama'
        ) . '</p>';
    }
    if ($action === 'checkemail' && isset($_GET['checkemail']) && $_GET['checkemail'] === 'confirm') {
        return '<p class="message kcj-login-soft-msg">' . esc_html__(
            'If that key is in this house, a reset letter is on its way. Check your email.',
            'kcjdrama'
        ) . '</p>';
    }
    return $message;
});

add_filter('gettext', function ($translation, $text, $domain) {
    if ($domain !== 'default') {
        return $translation;
    }
    $on_login = function_exists('is_login') ? is_login() : false;
    if (!$on_login) {
        global $pagenow;
        $on_login = isset($pagenow) && $pagenow === 'wp-login.php';
    }
    if (!$on_login) {
        return $translation;
    }
    $map = [
        'Username or Email Address' => 'Email or username',
        'Get New Password'          => 'Send reset link',
        'Lost your password?'       => 'Forgot username or password?',
        'Remember Me'               => 'Keep this key',
        'Log In'                    => 'Open the door',
        'Register'                  => 'Make a login',
        'Reset Password'            => 'Save new password',
        'Confirm new password'      => 'New password again',
    ];
    return $map[$text] ?? $translation;
}, 10, 3);

add_filter('login_site_html_link', function () {
    return sprintf(
        '<a href="%s">%s</a>',
        esc_url(kcj_page_url('sign-in')),
        esc_html__('← Soft door', 'kcjdrama')
    );
});

add_action('login_enqueue_scripts', function () {
    wp_enqueue_style(
        'kcj-login-fonts',
        'https://fonts.googleapis.com/css2?family=Great+Vibes&family=Montserrat:wght@300;400;500;600&display=swap',
        [],
        null
    );
    $css = '
    body.login {
        background:
            radial-gradient(70% 50% at 8% -10%, rgba(255, 220, 232, 0.9), transparent 58%),
            radial-gradient(50% 40% at 100% 0%, rgba(212, 165, 116, 0.22), transparent 48%),
            linear-gradient(165deg, #fbeff4 0%, #f7e7ee 38%, #e8d0db 100%);
        color: #3d2432;
        font-family: Montserrat, system-ui, sans-serif;
    }
    body.login #login {
        width: min(28rem, 92vw);
        padding: 6vh 0 2rem;
    }
    .login h1 a {
        background-image: none !important;
        text-indent: 0 !important;
        width: auto !important;
        height: auto !important;
        font-family: "Great Vibes", cursive !important;
        font-size: 2.4rem !important;
        font-weight: 400 !important;
        line-height: 1.1 !important;
        color: #3d2432 !important;
        overflow: visible !important;
    }
    .login form {
        background: #fff6fa;
        border: 1px solid rgba(61, 36, 50, 0.12);
        border-left: 3px solid #d4a574;
        border-radius: 14px;
        box-shadow: none;
        padding: 1.25rem 1.2rem 1.35rem;
    }
    .login label {
        color: #7a5a68;
        font-size: 0.68rem;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        font-weight: 600;
    }
    .login form .input,
    .login input[type="text"],
    .login input[type="password"] {
        background: #fff6fa;
        border: 1px solid rgba(61, 36, 50, 0.12);
        border-radius: 8px;
        box-shadow: none;
        color: #3d2432;
        font-size: 1rem;
        padding: 0.55rem 0.7rem;
        margin-top: 0.3rem;
    }
    .login .button-primary {
        background: #fff6fa !important;
        border: 1px solid rgba(61, 36, 50, 0.12) !important;
        border-radius: 999px !important;
        color: #3d2432 !important;
        text-shadow: none !important;
        box-shadow: none !important;
        font-size: 0.78rem !important;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        height: auto !important;
        line-height: 1.2 !important;
        padding: 0.7rem 1.15rem !important;
    }
    .login .button-primary:hover {
        border-color: #d4a574 !important;
        box-shadow: 0 0 0 1px #d4a574 !important;
    }
    .login #login_error,
    .login .message,
    .login .success {
        background: #fff6fa;
        border: 0;
        border-left: 3px solid #c97b9a;
        box-shadow: none;
        color: #3d2432;
        border-radius: 0 8px 8px 0;
    }
    .login #nav,
    .login #backtoblog {
        padding: 0 0.2rem;
    }
    .login #nav a,
    .login #backtoblog a,
    .login .privacy-policy-link {
        color: #c97b9a;
        text-decoration: none;
    }
    .login #nav a:hover,
    .login #backtoblog a:hover {
        color: #3d2432;
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .login #backtoblog a {
        font-size: 0.72rem;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        font-weight: 700;
    }
    .login form .input:focus,
    .login input[type="text"]:focus,
    .login input[type="password"]:focus {
        border-color: #d4a574;
        box-shadow: 0 0 0 1px #d4a574;
        outline: none;
    }
    .login .description {
        color: #7a5a68;
        font-size: 0.88rem;
        line-height: 1.45;
    }
    .login .indicator-hint,
    .login .pw-weak {
        color: #7a5a68;
    }
    .login #pass-strength-result {
        background: #fff6fa;
        border: 1px solid rgba(61, 36, 50, 0.12);
        border-radius: 8px;
        color: #3d2432;
    }
    .login .kcj-login-soft-msg {
        border-left-color: #d4a574 !important;
    }
    ';
    wp_add_inline_style('kcj-login-fonts', $css);
});

add_filter('login_redirect', function ($redirect, $requested, $user) {
    if (!empty($requested)) {
        return $requested;
    }
    if ($user instanceof WP_User && kcj_is_verified_contributor($user->ID)) {
        return kcj_page_url('stories') . '#kcj-desk';
    }
    return $redirect;
}, 10, 3);

add_filter('show_admin_bar', function ($show) {
    if (is_user_logged_in() && kcj_is_verified_contributor() && !current_user_can('edit_others_posts')) {
        return false;
    }
    return $show;
});

add_action('admin_init', function () {
    if (wp_doing_ajax() || current_user_can('edit_others_posts') || current_user_can('manage_woocommerce')) {
        return;
    }
    if (is_user_logged_in() && current_user_can('kcj_submit_story')) {
        wp_safe_redirect(kcj_page_url('stories') . '#kcj-desk');
        exit;
    }
});

function kcj_desk_rate_ok($bucket) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $uid = get_current_user_id();
    $key = 'kcj_desk_' . md5($bucket . '|' . $ip . '|' . $uid);
    $wait = 60;
    if ($bucket === 'ai-check') {
        $wait = 8;
    } elseif ($bucket === 'story') {
        $wait = 12;
    } elseif ($bucket === 'register' || $bucket === 'apply') {
        $wait = 60;
    }
    if (get_transient($key)) {
        return false;
    }
    set_transient($key, 1, $wait);
    return true;
}

function kcj_user_can_edit_story($post_id) {
    if (!is_user_logged_in()) {
        return false;
    }
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'kcj_story') {
        return false;
    }
    if (current_user_can('edit_others_posts')) {
        return true;
    }
    return kcj_is_verified_contributor() && (int) $post->post_author === get_current_user_id();
}

function kcj_user_can_delete_story($post_id) {
    return kcj_user_can_edit_story($post_id);
}

function kcj_allow_force_delete_story($caps, $cap, $user_id, $args) {
    if ($cap !== 'delete_post' || empty($GLOBALS['kcj_force_delete_id'])) {
        return $caps;
    }
    $id = (int) ($args[0] ?? 0);
    if ($id !== (int) $GLOBALS['kcj_force_delete_id']) {
        return $caps;
    }
    if (!kcj_user_can_delete_story($id)) {
        return $caps;
    }
    return ['exist'];
}

function kcj_force_delete_story($post_id) {
    $post_id = (int) $post_id;
    if (!kcj_user_can_delete_story($post_id)) {
        return false;
    }
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'kcj_story') {
        return false;
    }
    $GLOBALS['kcj_force_delete_id'] = $post_id;
    add_filter('map_meta_cap', 'kcj_allow_force_delete_story', 10, 4);
    $deleted = wp_delete_post($post_id, true);
    remove_filter('map_meta_cap', 'kcj_allow_force_delete_story', 10);
    unset($GLOBALS['kcj_force_delete_id']);
    return (bool) $deleted;
}

function kcj_story_edit_url($post_id) {
    return kcj_page_url('stories') . '?kcj_edit=' . (int) $post_id . '#kcj-desk';
}

function kcj_story_byline_name($post) {
    $post = get_post($post);
    if (!$post || $post->post_type !== 'kcj_story') {
        return 'kcjdrama';
    }
    $custom = trim((string) get_post_meta($post->ID, '_kcj_byline', true));
    if ($custom !== '') {
        return $custom;
    }
    $name = trim((string) get_the_author_meta('display_name', (int) $post->post_author));
    if ($name === '') {
        $name = trim((string) get_the_author_meta('user_login', (int) $post->post_author));
    }
    return $name !== '' ? $name : 'kcjdrama';
}

function kcj_story_byline_label($post) {
    return kcj_post_byline_label($post);
}

/**
 * Public byline for any written work. Desk fiction uses the byline field;
 * house Soft/Mirror posts default to kcjdrama. Never blank.
 */
function kcj_post_byline_name($post = null) {
    if ($post === null || $post === 0 || $post === '') {
        return 'kcjdrama';
    }
    $post = get_post($post);
    if (!$post) {
        return 'kcjdrama';
    }
    if ($post->post_type === 'kcj_story') {
        return kcj_story_byline_name($post);
    }
    if (in_array($post->post_type, ['post', 'kcj_story'], true) || is_post_type_viewable($post->post_type)) {
        $custom = trim((string) get_post_meta($post->ID, '_kcj_byline', true));
        if ($custom !== '') {
            return $custom;
        }
        if ($post->post_type === 'post') {
            return 'kcjdrama';
        }
        $name = trim((string) get_the_author_meta('display_name', (int) $post->post_author));
        if ($name === '') {
            $name = trim((string) get_the_author_meta('user_login', (int) $post->post_author));
        }
        if ($name !== '') {
            return $name;
        }
    }
    return 'kcjdrama';
}

function kcj_post_byline_label($post = null) {
    $name = kcj_post_byline_name($post);
    if ($name === '') {
        $name = 'kcjdrama';
    }
    return sprintf(
        /* translators: %s: author byline name */
        __('By %s', 'kcjdrama'),
        $name
    );
}

function kcj_save_story_byline($post_id, $byline) {
    $byline = sanitize_text_field($byline);
    if ($byline === '') {
        return false;
    }
    update_post_meta((int) $post_id, '_kcj_byline', $byline);
    return true;
}

function kcj_desk_edit_story() {
    $id = isset($_GET['kcj_edit']) ? (int) $_GET['kcj_edit'] : 0;
    if ($id <= 0 || !kcj_user_can_edit_story($id)) {
        return null;
    }
    return get_post($id);
}

function kcj_write_story_fields($id, $title, $body) {
    global $wpdb;
    $wpdb->update(
        $wpdb->posts,
        [
            'post_title'         => $title,
            'post_content'       => $body,
            'post_modified'      => current_time('mysql'),
            'post_modified_gmt'  => current_time('mysql', true),
        ],
        ['ID' => (int) $id]
    );
    clean_post_cache($id);
}

function kcj_force_story_status($id, $status) {
    $id = (int) $id;
    $post = get_post($id);
    if (!$post || $post->post_type !== 'kcj_story') {
        return false;
    }
    $old = $post->post_status;
    $row = ['post_status' => $status];
    if ($status === 'publish' && $post->post_name === '') {
        $row['post_name'] = wp_unique_post_slug(sanitize_title($post->post_title), $id, $status, 'kcj_story', 0);
    }
    global $wpdb;
    $wpdb->update($wpdb->posts, $row, ['ID' => $id]);
    clean_post_cache($id);
    $fresh = get_post($id);
    if ($fresh && $old !== $status) {
        wp_transition_post_status($status, $old, $fresh);
    }
    return $fresh && $fresh->post_status === $status;
}

function kcj_goto_live_story($id) {
    if (function_exists('kcj_ai_clear_notes_for_user')) {
        kcj_ai_clear_notes_for_user();
    }
    $url = get_permalink((int) $id);
    if (!$url) {
        kcj_desk_redirect('published');
    }
    wp_safe_redirect(add_query_arg('kcj_live', '1', $url));
    exit;
}

function kcj_desk_redirect($code, $edit_id = 0) {
    $url = kcj_page_url('stories') . '?kcj_desk=' . rawurlencode($code);
    if ((int) $edit_id > 0) {
        $url .= '&kcj_edit=' . (int) $edit_id;
    }
    wp_safe_redirect($url . '#kcj-desk');
    exit;
}

function kcj_desk_notice() {
    $code = isset($_GET['kcj_desk']) ? sanitize_key(wp_unslash($_GET['kcj_desk'])) : '';
    $map = [
        'applied'   => 'Application received. We read every one. If we verify you, email arrives with a door to the desk.',
        'submitted' => 'Story is on the desk as pending. An editor will read it before it goes live.',
        'published' => 'Grok passed it. The story is live on the desk.',
        'held'      => 'Grok held it for a human. Notes are below — edit and send again, or wait for an editor.',
        'nocheck'   => 'Grok is not configured, so this stayed pending for a human.',
        'verified'  => 'You’re verified. Write a Soft short — patterns, not pirated plots.',
        'pending'   => 'We already have an application for that email. Sit tight.',
        'exists'    => 'That email already has a login. Sign in below, then apply if you still need verification.',
        'needlogin' => 'Sign in to submit a story.',
        'denied'    => 'This desk is for verified contributors. Apply first — or sign in if you already are.',
        'rate'      => 'Give it a few seconds, then try again.',
        'invalid'   => 'That form needed a title, a byline, and a story — not a blank page.',
        'spam'      => 'That submission did not look like a person. Try again slowly.',
        'loggedout' => 'Signed out. The desk will remember you when you come back.',
        'declined'  => 'Application marked declined.',
        'accounted' => 'Account is yours. You’re signed in. Apply to the desk next — we still verify before fiction goes live.',
        'taken'     => 'That email already has a login. Sign in.',
        'mismatch'  => 'Those two passwords did not match.',
        'weak'      => 'Password needs at least eight characters.',
        'deleted'   => 'That short is gone from the desk.',
        'error'     => 'Something on the desk jammed. Try once more.',
    ];
    if ($code === '' || !isset($map[$code])) {
        return '';
    }
    return $map[$code];
}

add_action('admin_post_nopriv_kcj_register', 'kcj_handle_register');
function kcj_handle_register() {
    if (!isset($_POST['kcj_register_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_register_nonce'])), 'kcj_register')) {
        kcj_desk_redirect('error');
    }
    $hp = isset($_POST['kcj_hp']) ? trim((string) wp_unslash($_POST['kcj_hp'])) : '';
    $ts = isset($_POST['kcj_ts']) ? (int) $_POST['kcj_ts'] : 0;
    if ($hp !== '' || ($ts > 0 && (time() - $ts) < 3)) {
        kcj_desk_redirect('spam');
    }
    if (!kcj_desk_rate_ok('register')) {
        kcj_desk_redirect('rate');
    }
    if (is_user_logged_in()) {
        kcj_desk_redirect('accounted');
    }

    $name = sanitize_text_field(wp_unslash($_POST['kcj_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['kcj_email'] ?? ''));
    $pass = (string) ($_POST['kcj_pass'] ?? '');
    $pass2 = (string) ($_POST['kcj_pass2'] ?? '');
    if ($name === '' || !is_email($email)) {
        kcj_desk_redirect('invalid');
    }
    if (strlen($pass) < 8) {
        kcj_desk_redirect('weak');
    }
    if (!hash_equals($pass, $pass2)) {
        kcj_desk_redirect('mismatch');
    }
    if (email_exists($email)) {
        kcj_desk_redirect('taken');
    }

    $login = sanitize_user(current(explode('@', $email, 2)), true);
    if ($login === '') {
        $login = 'desk' . wp_rand(1000, 9999);
    }
    $base = $login;
    $i = 1;
    while (username_exists($login)) {
        $login = $base . $i;
        $i++;
    }

    $uid = wp_insert_user([
        'user_login'   => $login,
        'user_email'   => $email,
        'user_pass'    => $pass,
        'display_name' => $name,
        'role'         => 'subscriber',
    ]);
    if (is_wp_error($uid) || !$uid) {
        kcj_desk_redirect('error');
    }

    wp_set_current_user($uid);
    wp_set_auth_cookie($uid, true);
    kcj_desk_redirect('accounted');
}

add_action('admin_post_nopriv_kcj_apply', 'kcj_handle_apply');
add_action('admin_post_kcj_apply', 'kcj_handle_apply');
function kcj_handle_apply() {
    if (!isset($_POST['kcj_apply_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_apply_nonce'])), 'kcj_apply')) {
        kcj_desk_redirect('error');
    }
    $hp = isset($_POST['kcj_hp']) ? trim((string) wp_unslash($_POST['kcj_hp'])) : '';
    $ts = isset($_POST['kcj_ts']) ? (int) $_POST['kcj_ts'] : 0;
    if ($hp !== '' || ($ts > 0 && (time() - $ts) < 3)) {
        kcj_desk_redirect('spam');
    }
    if (!kcj_desk_rate_ok('apply')) {
        kcj_desk_redirect('rate');
    }

    $name = sanitize_text_field(wp_unslash($_POST['kcj_name'] ?? ''));
    $email = sanitize_email(wp_unslash($_POST['kcj_email'] ?? ''));
    $note = sanitize_textarea_field(wp_unslash($_POST['kcj_note'] ?? ''));
    $sample = wp_kses_post(wp_unslash($_POST['kcj_sample'] ?? ''));
    if ($name === '' || !is_email($email) || strlen($note) < 20) {
        kcj_desk_redirect('invalid');
    }
    if (strlen($name) > 80 || strlen($note) > 2000 || strlen($sample) > 8000) {
        kcj_desk_redirect('invalid');
    }

    $existing = get_user_by('email', $email);
    if ($existing && kcj_is_verified_contributor($existing->ID)) {
        kcj_desk_redirect('exists');
    }
    if (kcj_desk_has_pending_application($email)) {
        kcj_desk_redirect('pending');
    }

    $id = wp_insert_post([
        'post_type'    => 'kcj_contrib_app',
        'post_status'  => 'pending',
        'post_title'   => $name,
        'post_content' => $note . ($sample !== '' ? "\n\n--- sample ---\n\n" . wp_strip_all_tags($sample) : ''),
        'post_author'  => get_current_user_id() ?: 0,
    ], true);
    if (is_wp_error($id) || !$id) {
        kcj_desk_redirect('error');
    }
    update_post_meta($id, '_kcj_app_email', $email);
    update_post_meta($id, '_kcj_app_name', $name);
    update_post_meta($id, '_kcj_app_note', $note);
    if ($sample !== '') {
        update_post_meta($id, '_kcj_app_sample', $sample);
    }
    kcj_desk_redirect('applied');
}

add_action('admin_post_kcj_story', 'kcj_handle_story');
function kcj_handle_story() {
    if (!is_user_logged_in()) {
        kcj_desk_redirect('needlogin');
    }
    if (!isset($_POST['kcj_story_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_story_nonce'])), 'kcj_story')) {
        kcj_desk_redirect('error');
    }
    if (!kcj_is_verified_contributor()) {
        kcj_desk_redirect('denied');
    }
    if (!kcj_desk_rate_ok('story')) {
        kcj_desk_redirect('rate');
    }
    $title = sanitize_text_field(wp_unslash($_POST['kcj_title'] ?? ''));
    $byline = sanitize_text_field(wp_unslash($_POST['kcj_byline'] ?? ''));
    $body = wp_kses_post(wp_unslash($_POST['kcj_body'] ?? ''));
    if ($title === '' || $byline === '' || wp_strip_all_tags($body) === '' || strlen($title) > 160 || strlen($byline) > 80) {
        kcj_desk_redirect('invalid');
    }
    $edit_id = isset($_POST['kcj_story_id']) ? (int) $_POST['kcj_story_id'] : 0;
    $existing = null;
    if ($edit_id) {
        if (!kcj_user_can_edit_story($edit_id)) {
            kcj_desk_redirect('denied');
        }
        $existing = get_post($edit_id);
        if (!$existing) {
            kcj_desk_redirect('error');
        }
    }
    $review = function_exists('kcj_ai_review_story')
        ? kcj_ai_review_story($title, $body)
        : ['ok' => false, 'pass' => false, 'reasons' => ['Grok is not configured, so a human has to read this.'], 'error' => 'missing'];
    $intent = sanitize_key(wp_unslash($_POST['kcj_intent'] ?? ''));
    $want_live = ($intent === 'publish' && !empty($review['ok']) && !empty($review['pass']));

    if ($existing && $existing->post_status === 'publish' && $intent === 'publish' && !$want_live) {
        if (function_exists('kcj_ai_store_review')) {
            kcj_ai_store_review($edit_id, $review);
        }
        kcj_desk_redirect('held', $edit_id);
    }

    if ($existing) {
        kcj_write_story_fields($edit_id, $title, $body);
        $id = $edit_id;
    } else {
        $id = wp_insert_post([
            'post_type'    => 'kcj_story',
            'post_status'  => 'pending',
            'post_title'   => $title,
            'post_content' => $body,
            'post_author'  => get_current_user_id(),
        ], true);
        if (is_wp_error($id) || !$id) {
            kcj_desk_redirect('error');
        }
    }
    kcj_save_story_byline((int) $id, $byline);
    if (function_exists('kcj_ai_store_review')) {
        kcj_ai_store_review((int) $id, $review);
    }
    if ($want_live) {
        kcj_force_story_status((int) $id, 'publish');
        kcj_goto_live_story((int) $id);
    }
    if ($intent === 'publish' && empty($review['pass'])) {
        kcj_desk_redirect('held', $edit_id);
    }
    if (!empty($review['error']) && $review['error'] === 'missing_key') {
        kcj_desk_redirect('nocheck', $edit_id);
    }
    if ($existing && $existing->post_status === 'publish') {
        kcj_goto_live_story((int) $id);
    }
    kcj_desk_redirect('submitted', $edit_id);
}

add_action('admin_post_kcj_story_delete', 'kcj_handle_story_delete');
function kcj_handle_story_delete() {
    if (!is_user_logged_in()) {
        kcj_desk_redirect('needlogin');
    }
    if (!isset($_POST['kcj_story_delete_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['kcj_story_delete_nonce'])), 'kcj_story_delete')) {
        kcj_desk_redirect('error');
    }
    $id = isset($_POST['kcj_story_id']) ? (int) $_POST['kcj_story_id'] : 0;
    if ($id <= 0 || !kcj_user_can_delete_story($id)) {
        kcj_desk_redirect('denied');
    }
    $expect = sanitize_key(wp_unslash($_POST['kcj_delete_color'] ?? 'black'));
    if ($expect === '') {
        $expect = 'black';
    }
    $typed = strtolower(trim((string) wp_unslash($_POST['kcj_delete_confirm'] ?? '')));
    if ($typed !== $expect) {
        kcj_desk_redirect('error', $id);
    }
    if (!kcj_force_delete_story($id)) {
        kcj_desk_redirect('error');
    }
    if (function_exists('kcj_ai_clear_notes_for_user')) {
        kcj_ai_clear_notes_for_user();
    }
    kcj_desk_redirect('deleted');
}

add_action('admin_post_kcj_verify_contributor', 'kcj_verify_contributor');
function kcj_verify_contributor() {
    if (!current_user_can('promote_users')) {
        wp_die(esc_html__('Not allowed.', 'kcjdrama'));
    }
    check_admin_referer('kcj_verify_contributor');
    $app_id = isset($_GET['app']) ? (int) $_GET['app'] : 0;
    $app = $app_id ? get_post($app_id) : null;
    if (!$app || $app->post_type !== 'kcj_contrib_app') {
        wp_safe_redirect(admin_url('edit.php?post_type=kcj_contrib_app'));
        exit;
    }
    $email = sanitize_email((string) get_post_meta($app_id, '_kcj_app_email', true));
    $name = sanitize_text_field((string) get_post_meta($app_id, '_kcj_app_name', true));
    if (!is_email($email)) {
        wp_safe_redirect(admin_url('edit.php?post_type=kcj_contrib_app'));
        exit;
    }

    $user = get_user_by('email', $email);
    $created = false;
    if (!$user) {
        $login = sanitize_user(current(explode('@', $email, 2)), true);
        if ($login === '') {
            $login = 'desk' . $app_id;
        }
        $base = $login;
        $i = 1;
        while (username_exists($login)) {
            $login = $base . $i;
            $i++;
        }
        $uid = wp_insert_user([
            'user_login'   => $login,
            'user_email'   => $email,
            'display_name' => $name !== '' ? $name : $login,
            'role'         => 'contributor',
            'user_pass'    => wp_generate_password(24, true, true),
        ]);
        if (is_wp_error($uid)) {
            wp_safe_redirect(admin_url('edit.php?post_type=kcj_contrib_app&kcj_desk=error'));
            exit;
        }
        $user = get_user_by('id', $uid);
        $created = true;
    } else {
        $user->add_role('contributor');
    }
    update_user_meta($user->ID, 'kcj_verified', '1');
    update_post_meta($app_id, '_kcj_app_user_id', $user->ID);
    wp_update_post(['ID' => $app_id, 'post_status' => 'publish']);

    $key = get_password_reset_key($user);
    $set = '';
    if (!is_wp_error($key)) {
        $set = network_site_url(
            'wp-login.php?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user->user_login),
            'login'
        );
    }
    $stories = kcj_page_url('stories');
    $body = "You're verified for the Soft desk at kcjdrama.\n\n";
    if ($created && $set !== '') {
        $body .= "Set a password:\n{$set}\n\n";
    } else {
        $body .= "Sign in with this email, then open Stories.\n\n";
    }
    $body .= "Submit original fiction from {$stories}\n\nPatterns, not pirated plots. No stills, no scripts, no stolen recaps.\n";
    wp_mail($email, 'kcjdrama — Soft desk verified', $body);

    wp_safe_redirect(admin_url('edit.php?post_type=kcj_contrib_app&kcj_desk=verified'));
    exit;
}

add_action('admin_post_kcj_decline_contributor', function () {
    if (!current_user_can('promote_users')) {
        wp_die(esc_html__('Not allowed.', 'kcjdrama'));
    }
    check_admin_referer('kcj_decline_contributor');
    $app_id = isset($_GET['app']) ? (int) $_GET['app'] : 0;
    if ($app_id && get_post_type($app_id) === 'kcj_contrib_app') {
        wp_update_post(['ID' => $app_id, 'post_status' => 'draft']);
    }
    wp_safe_redirect(admin_url('edit.php?post_type=kcj_contrib_app&kcj_desk=declined'));
    exit;
});

add_filter('post_row_actions', function ($actions, $post) {
    if ($post->post_type !== 'kcj_contrib_app' || !current_user_can('promote_users')) {
        return $actions;
    }
    if ($post->post_status === 'pending') {
        $verify = wp_nonce_url(
            admin_url('admin-post.php?action=kcj_verify_contributor&app=' . $post->ID),
            'kcj_verify_contributor'
        );
        $decline = wp_nonce_url(
            admin_url('admin-post.php?action=kcj_decline_contributor&app=' . $post->ID),
            'kcj_decline_contributor'
        );
        $actions['kcj_verify'] = '<a href="' . esc_url($verify) . '">Verify</a>';
        $actions['kcj_decline'] = '<a href="' . esc_url($decline) . '" class="submitdelete">Decline</a>';
    }
    return $actions;
}, 10, 2);

add_filter('manage_kcj_contrib_app_posts_columns', function ($cols) {
    $cols['kcj_email'] = 'Email';
    $cols['kcj_state'] = 'State';
    return $cols;
});

add_action('manage_kcj_contrib_app_posts_custom_column', function ($col, $post_id) {
    if ($col === 'kcj_email') {
        echo esc_html((string) get_post_meta($post_id, '_kcj_app_email', true));
    }
    if ($col === 'kcj_state') {
        $st = get_post_status($post_id);
        $map = ['pending' => 'Waiting', 'publish' => 'Verified', 'draft' => 'Declined'];
        echo esc_html($map[$st] ?? $st);
    }
}, 10, 2);

function kcj_published_stories($limit = 40) {
    return get_posts([
        'post_type'      => 'kcj_story',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $limit,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
}

/**
 * Top Soft desk stories for brand stages: Grok-preapproved, newest first.
 *
 * @return array<int,WP_Post>
 */
function kcj_top_desk_stories($limit = 6) {
    return get_posts([
        'post_type'      => 'kcj_story',
        'post_status'    => 'publish',
        'posts_per_page' => max(1, (int) $limit),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'meta_key'       => '_kcj_ai_pass',
        'meta_value'     => '1',
    ]);
}

function kcj_my_pending_stories() {
    if (!is_user_logged_in()) {
        return [];
    }
    return get_posts([
        'post_type'      => 'kcj_story',
        'post_status'    => 'pending',
        'author'         => get_current_user_id(),
        'posts_per_page' => 12,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
}

add_action('wp_enqueue_scripts', function () {
    if (!is_page('stories') || !kcj_is_verified_contributor()) {
        return;
    }
    wp_enqueue_editor();
}, 40);

function kcj_render_stories_desk() {
    $notice = kcj_desk_notice();
    $user = wp_get_current_user();
    $verified = kcj_is_verified_contributor();
    $pending_app = ($user && $user->exists()) ? kcj_desk_has_pending_application($user->user_email) : false;
    $apply_url = admin_url('admin-post.php');
    $logout = wp_logout_url(kcj_page_url('stories') . '?kcj_desk=loggedout#kcj-desk');
    $policy = kcj_page_url('editorial-policy');
    $edit = $verified ? kcj_desk_edit_story() : null;
    ?>
    <section class="kcj-desk" id="kcj-desk">
        <span id="kcj-account"></span>
        <header class="kcj-desk-head">
            <p class="kcj-page-kicker"><?php esc_html_e('Soft World · The desk', 'kcjdrama'); ?></p>
            <h2><?php esc_html_e('Write for the desk', 'kcjdrama'); ?></h2>
            <p class="kcj-desk-lede"><?php esc_html_e('Original Soft fiction — patterns, not pirated plots. Verified contributors submit. Everyone else can ask to become one.', 'kcjdrama'); ?></p>
            <p class="kcj-desk-rule">
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: 1: pass/fail FAQ hash, 2: editorial policy URL */
                        __('No stills, no scripts, no stolen recaps. <a href="%1$s">Pass OR Fail</a> is the Grok bar. The <a href="%2$s">editorial policy</a> is the house rule.', 'kcjdrama'),
                        '#kcj-desk-faq',
                        esc_url($policy)
                    ),
                    ['a' => ['href' => []]]
                );
                ?>
            </p>
        </header>

        <?php if ($notice !== '') : ?>
            <p class="kcj-desk-notice" role="status"><?php echo esc_html($notice); ?></p>
        <?php endif; ?>

        <div class="kcj-desk-grid<?php echo $verified ? ' kcj-desk-grid--editor' : ''; ?>">
            <?php if ($verified) : ?>
                <div class="kcj-desk-card kcj-desk-card--editor">
                    <h3><?php echo $edit ? esc_html__('Edit this story', 'kcjdrama') : esc_html__('Submit a story', 'kcjdrama'); ?></h3>
                    <p class="kcj-desk-who">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: display name */
                                __('Signed in as %s — verified.', 'kcjdrama'),
                                $user->display_name
                            )
                        );
                        ?>
                        <a href="<?php echo esc_url($logout); ?>"><?php esc_html_e('Sign out', 'kcjdrama'); ?></a>
                        <?php if ($edit) : ?>
                            <a href="<?php echo esc_url(kcj_page_url('stories') . '#kcj-desk'); ?>"><?php esc_html_e('Write a new short', 'kcjdrama'); ?></a>
                        <?php endif; ?>
                    </p>
                    <?php
                    if ($edit && $edit->post_status === 'publish') :
                        ?>
                        <p class="kcj-desk-pending"><?php esc_html_e('This short is already live. Publish now replaces the page. Keep working stays here.', 'kcjdrama'); ?></p>
                    <?php endif; ?>
                    <?php
                    $mine = kcj_my_pending_stories();
                    if ($mine) :
                        ?>
                        <p class="kcj-desk-pending"><?php esc_html_e('Waiting on the editor:', 'kcjdrama'); ?>
                            <?php echo esc_html(implode(', ', wp_list_pluck($mine, 'post_title'))); ?>
                        </p>
                    <?php endif; ?>
                    <form class="kcj-desk-form kcj-desk-form--editor" method="post" action="<?php echo esc_url($apply_url); ?>">
                        <input type="hidden" name="action" value="kcj_story">
                        <input type="hidden" name="kcj_intent" id="kcj-intent" value="">
                        <?php if ($edit) : ?>
                            <input type="hidden" name="kcj_story_id" value="<?php echo (int) $edit->ID; ?>">
                        <?php endif; ?>
                        <?php wp_nonce_field('kcj_story', 'kcj_story_nonce'); ?>
                        <?php
                        $byline_value = $edit
                            ? kcj_story_byline_name($edit)
                            : (string) $user->display_name;
                        if ($byline_value === '') {
                            $byline_value = (string) $user->user_login;
                        }
                        ?>
                        <label>
                            <span><?php esc_html_e('Title', 'kcjdrama'); ?></span>
                            <input type="text" name="kcj_title" required maxlength="160" value="<?php echo $edit ? esc_attr($edit->post_title) : ''; ?>">
                        </label>
                        <label>
                            <span><?php esc_html_e('Byline', 'kcjdrama'); ?></span>
                            <input type="text" name="kcj_byline" id="kcj-byline-input" required maxlength="80" value="<?php echo esc_attr($byline_value); ?>" autocomplete="nickname">
                        </label>
                        <p class="kcj-story-byline kcj-desk-byline-preview" id="kcj-byline-preview"><?php echo esc_html(sprintf(/* translators: %s: author byline name */ __('By %s', 'kcjdrama'), $byline_value !== '' ? $byline_value : 'kcjdrama')); ?></p>
                        <p class="kcj-desk-byline-hint"><?php esc_html_e('Same line readers see under the title on the live short.', 'kcjdrama'); ?></p>
                        <div class="kcj-desk-field" id="kcj-story-field">
                            <span><?php esc_html_e('Story', 'kcjdrama'); ?></span>
                            <?php
                            wp_editor($edit ? $edit->post_content : '', 'kcj_body', [
                                'textarea_name'    => 'kcj_body',
                                'textarea_rows'    => 5,
                                'media_buttons'    => false,
                                'drag_drop_upload' => false,
                                'teeny'            => false,
                                'quicktags'        => false,
                                'tinymce'          => [
                                    'toolbar1'             => 'formatselect,bold,italic,underline,blockquote,bullist,numlist,link,unlink,undo,redo,removeformat',
                                    'toolbar2'             => '',
                                    'block_formats'        => 'Paragraph=p;Heading 2=h2;Heading 3=h3',
                                    'wordpress_adv_hidden' => true,
                                    'branding'             => false,
                                    'resize'               => true,
                                    'wp_autoresize_on'     => false,
                                    'height'               => 120,
                                    'content_css'          => false,
                                    'content_style'        => 'html,body{height:auto!important;min-height:0!important;}body{margin:0;padding:0.6rem 0.75rem;font-size:1rem;line-height:1.55;box-sizing:border-box;}',
                                ],
                            ]);
                            ?>
                        </div>
                        <div class="kcj-desk-ai" id="kcj-desk-ai">
                            <button type="button" class="kcj-btn kcj-btn--soft" id="kcj-ai-check"
                                data-rest="<?php echo esc_url(rest_url('kcj/v1/story-check')); ?>"
                                data-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"
                                title="<?php echo esc_attr__('Runs an AI Pass OR Fail on your draft. A pass offers Publish now; a hold explains what to fix.', 'kcjdrama'); ?>"
                                aria-describedby="kcj-ai-check-tip"
                            ><?php esc_html_e('AI Review My Draft & Publish', 'kcjdrama'); ?></button>
                            <button type="submit" class="kcj-btn kcj-btn--soft"
                                title="<?php echo esc_attr__('Sends the short to a human editor. It stays pending until someone publishes — Grok does not auto-publish this path.', 'kcjdrama'); ?>"
                                aria-describedby="kcj-human-submit-tip"
                            ><?php esc_html_e('Human Review & Publish', 'kcjdrama'); ?></button>
                            <a class="kcj-brand-cross" href="#kcj-desk-faq" title="<?php echo esc_attr__('What passes and what fails before a Soft short can go live.', 'kcjdrama'); ?>"><?php esc_html_e('Pass OR Fail', 'kcjdrama'); ?></a>
                            <p class="kcj-desk-ai-tips" id="kcj-desk-ai-tips">
                                <span id="kcj-ai-check-tip"><?php esc_html_e('AI Review: get a Pass or Hold first. Pass can publish right away.', 'kcjdrama'); ?></span>
                                <span id="kcj-human-submit-tip"><?php esc_html_e('Human Review: an editor reads it before it goes live.', 'kcjdrama'); ?></span>
                            </p>
                        </div>
                        <?php
                        $last = ($edit && function_exists('kcj_ai_last_notes_for_user')) ? kcj_ai_last_notes_for_user() : null;
                        $restore_hold = is_array($last) && empty($last['pass']);
                        $ai_class = 'kcj-desk-ai-notes';
                        $ai_text = '';
                        if ($restore_hold) {
                            $ai_class .= ' is-hold';
                            $ai_text = implode(' ', array_map('strval', $last['reasons'] ?? []));
                        }
                        ?>
                        <div class="kcj-desk-field kcj-desk-result" id="kcj-desk-result" <?php echo $restore_hold ? '' : 'hidden'; ?>>
                            <span id="kcj-ai-result-label"><?php esc_html_e('Result', 'kcjdrama'); ?></span>
                            <div class="<?php echo esc_attr($ai_class); ?>" id="kcj-ai-notes" role="status" aria-labelledby="kcj-ai-result-label" aria-live="polite">
                                <p class="kcj-desk-ai-copy" id="kcj-ai-copy"><?php echo esc_html($ai_text); ?></p>
                                <p class="kcj-desk-ai-choice" id="kcj-ai-choice" hidden>
                                    <button type="button" class="kcj-btn kcj-btn--soft" id="kcj-ai-publish" disabled><?php esc_html_e('Publish now', 'kcjdrama'); ?></button>
                                    <a class="kcj-brand-cross" href="#kcj-story-field" id="kcj-ai-keep"><?php esc_html_e('Keep working', 'kcjdrama'); ?></a>
                                </p>
                            </div>
                        </div>
                    </form>
                    <?php if ($edit && kcj_user_can_delete_story($edit->ID)) : ?>
                        <div class="kcj-desk-delete">
                            <button type="button" class="kcj-desk-delete-btn" id="kcj-delete-open" aria-haspopup="dialog" aria-controls="kcj-rose-delete"><?php esc_html_e('Delete this short', 'kcjdrama'); ?></button>
                            <span class="kcj-desk-delete-hint"><?php esc_html_e('Removes it from the desk and the live page. Yours to delete because you wrote it.', 'kcjdrama'); ?></span>
                        </div>
                        <div class="kcj-rose-delete" id="kcj-rose-delete" hidden role="dialog" aria-modal="true" aria-labelledby="kcj-rose-delete-title">
                            <div class="kcj-rose-delete-scrim" data-kcj-rose-close></div>
                            <div class="kcj-rose-delete-panel">
                                <div class="kcj-rose-delete-bloom" aria-hidden="true">
                                    <picture>
                                        <source srcset="<?php echo esc_url(KCJ_URI . '/assets/img/black-rose.webp'); ?>" type="image/webp">
                                        <img
                                            class="kcj-rose-photo"
                                            src="<?php echo esc_url(KCJ_URI . '/assets/img/black-rose.png'); ?>"
                                            width="256"
                                            height="360"
                                            sizes="(max-width: 600px) 9rem, 9rem"
                                            alt=""
                                            decoding="async"
                                        >
                                    </picture>
                                </div>
                                <p class="kcj-rose-delete-kicker"><?php esc_html_e('Black rose', 'kcjdrama'); ?></p>
                                <h3 id="kcj-rose-delete-title"><?php esc_html_e('Delete this short', 'kcjdrama'); ?></h3>
                                <p class="kcj-rose-delete-copy"><?php esc_html_e('This cannot be undone. Type the color of the rose to confirm.', 'kcjdrama'); ?></p>
                                <form class="kcj-rose-delete-form" id="kcj-rose-delete-form" method="post" action="<?php echo esc_url($apply_url); ?>">
                                    <input type="hidden" name="action" value="kcj_story_delete">
                                    <input type="hidden" name="kcj_story_id" value="<?php echo (int) $edit->ID; ?>">
                                    <input type="hidden" name="kcj_delete_color" value="black">
                                    <?php wp_nonce_field('kcj_story_delete', 'kcj_story_delete_nonce'); ?>
                                    <label class="kcj-rose-delete-label">
                                        <span><?php esc_html_e('Rose color', 'kcjdrama'); ?></span>
                                        <input type="text" name="kcj_delete_confirm" id="kcj-rose-confirm" autocomplete="off" spellcheck="false" placeholder="<?php esc_attr_e('type the color', 'kcjdrama'); ?>" aria-describedby="kcj-rose-delete-title">
                                    </label>
                                    <div class="kcj-rose-delete-actions">
                                        <button type="button" class="kcj-rose-delete-cancel" data-kcj-rose-close><?php esc_html_e('Keep it', 'kcjdrama'); ?></button>
                                        <button type="submit" class="kcj-rose-delete-go" id="kcj-rose-go" disabled><?php esc_html_e('Delete forever', 'kcjdrama'); ?></button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    <script>
                    (function () {
                      var rose = document.getElementById("kcj-rose-delete");
                      var openBtn = document.getElementById("kcj-delete-open");
                      if (rose && openBtn) {
                        var confirmIn = document.getElementById("kcj-rose-confirm");
                        var go = document.getElementById("kcj-rose-go");
                        var form = document.getElementById("kcj-rose-delete-form");
                        var color = ((form && form.querySelector('[name="kcj_delete_color"]')) || {}).value || "black";
                        color = String(color).toLowerCase();
                        var lastFocus = null;
                        function setOpen(on) {
                          rose.hidden = !on;
                          document.documentElement.classList.toggle("kcj-rose-open", on);
                          if (on) {
                            lastFocus = document.activeElement;
                            if (confirmIn) {
                              confirmIn.value = "";
                              if (go) go.disabled = true;
                              setTimeout(function () { confirmIn.focus(); }, 20);
                            }
                          } else if (lastFocus && lastFocus.focus) {
                            lastFocus.focus();
                          }
                        }
                        openBtn.addEventListener("click", function () { setOpen(true); });
                        rose.querySelectorAll("[data-kcj-rose-close]").forEach(function (el) {
                          el.addEventListener("click", function () { setOpen(false); });
                        });
                        document.addEventListener("keydown", function (e) {
                          if (e.key === "Escape" && !rose.hidden) {
                            e.preventDefault();
                            setOpen(false);
                          }
                        });
                        if (confirmIn && go) {
                          confirmIn.addEventListener("input", function () {
                            go.disabled = confirmIn.value.trim().toLowerCase() !== color;
                          });
                        }
                        if (form) {
                          form.addEventListener("submit", function (e) {
                            if (!confirmIn || confirmIn.value.trim().toLowerCase() !== color) {
                              e.preventDefault();
                              go.disabled = true;
                              confirmIn.focus();
                            }
                          });
                        }
                      }
                      var f = document.querySelector(".kcj-desk-form--editor");
                      if (!f) return;
                      f.addEventListener("submit", function () {
                        if (window.tinyMCE) window.tinyMCE.triggerSave();
                      });
                      var btn = document.getElementById("kcj-ai-check");
                      var result = document.getElementById("kcj-desk-result");
                      var notes = document.getElementById("kcj-ai-notes");
                      var copy = document.getElementById("kcj-ai-copy");
                      var choice = document.getElementById("kcj-ai-choice");
                      var publish = document.getElementById("kcj-ai-publish");
                      var keep = document.getElementById("kcj-ai-keep");
                      var intent = document.getElementById("kcj-intent");
                      if (!btn || !result || !notes || !copy || !choice) return;
                      var passedSig = "";
                      var ignoreDirty = false;
                      function readDraft() {
                        ignoreDirty = true;
                        if (window.tinyMCE) window.tinyMCE.triggerSave();
                        var title = (f.querySelector('[name="kcj_title"]') || {}).value || "";
                        var bodyEl = f.querySelector('[name="kcj_body"]');
                        var body = bodyEl ? bodyEl.value : "";
                        if (window.tinyMCE && window.tinyMCE.get("kcj_body")) {
                          body = window.tinyMCE.get("kcj_body").getContent() || body;
                        }
                        ignoreDirty = false;
                        return { title: title, body: body, sig: title + "\x1e" + body };
                      }
                      function showResult(kind, text, offer) {
                        result.hidden = false;
                        notes.className = "kcj-desk-ai-notes" + (kind ? " " + kind : "");
                        copy.textContent = text;
                        choice.hidden = !offer;
                        choice.classList.toggle("is-offer", !!offer);
                        if (publish) publish.disabled = !offer;
                        if (!offer) {
                          passedSig = "";
                          if (intent) intent.value = "";
                        }
                      }
                      function onDirty() {
                        if (ignoreDirty || !choice.classList.contains("is-offer")) return;
                        if (readDraft().sig === passedSig) return;
                        showResult("", "The story changed. Run AI Review again.", false);
                      }
                      btn.addEventListener("click", function () {
                        if (intent) intent.value = "";
                        var draft = readDraft();
                        showResult("", "Checking with Grok…", false);
                        fetch(btn.getAttribute("data-rest"), {
                          method: "POST",
                          credentials: "same-origin",
                          headers: {
                            "Content-Type": "application/json",
                            "X-WP-Nonce": btn.getAttribute("data-nonce") || ""
                          },
                          body: JSON.stringify({ title: draft.title, body: draft.body })
                        }).then(function (r) { return r.json().then(function (data) { return { r: r, data: data }; }); }).then(function (pack) {
                          var data = pack.data || {};
                          var reasons = Array.isArray(data.reasons) ? data.reasons : [];
                          var msg = data.message || data.error || "";
                          if (data.pass === true || data.pass === 1 || data.pass === "1") {
                            passedSig = draft.sig;
                            showResult("is-pass", "Grok passed.", true);
                            return;
                          }
                          var hold = "Held. Edit, then run AI Review again.";
                          if (reasons.length) hold = reasons.join(" ");
                          else if (msg) hold = msg;
                          else if (!pack.r.ok) hold = "Check failed (" + pack.r.status + "). Sign in again, then retry.";
                          showResult("is-hold", hold, false);
                        }).catch(function () {
                          showResult("is-hold", "Grok could not be reached. You can still send; it will wait for a human.", false);
                        });
                      });
                      if (publish) {
                        publish.addEventListener("click", function () {
                          if (readDraft().sig !== passedSig) {
                            showResult("", "The story changed. Run AI Review again.", false);
                            return;
                          }
                          if (intent) intent.value = "publish";
                          if (typeof f.requestSubmit === "function") {
                            f.requestSubmit();
                          } else {
                            f.submit();
                          }
                        });
                      }
                      if (keep) {
                        keep.addEventListener("click", function () {
                          showResult("", "Keep working. Edit, then run AI Review again.", false);
                          if (window.tinyMCE && window.tinyMCE.get("kcj_body")) {
                            window.tinyMCE.get("kcj_body").focus();
                          } else {
                            var t = f.querySelector('[name="kcj_title"]');
                            if (t) t.focus();
                          }
                        });
                      }
                      var titleEl = f.querySelector('[name="kcj_title"]');
                      if (titleEl) titleEl.addEventListener("input", onDirty);
                      var bodyTa = f.querySelector('[name="kcj_body"]');
                      if (bodyTa) bodyTa.addEventListener("input", onDirty);
                      var bylineIn = document.getElementById("kcj-byline-input");
                      var bylinePrev = document.getElementById("kcj-byline-preview");
                      if (bylineIn && bylinePrev) {
                        var syncByline = function () {
                          var name = (bylineIn.value || "").trim() || "kcjdrama";
                          bylinePrev.textContent = "By " + name;
                        };
                        bylineIn.addEventListener("input", syncByline);
                        syncByline();
                      }
                      function clampMobileEditor(ed) {
                        if (!ed || ed.id !== "kcj_body") return;
                        if (!window.matchMedia || !window.matchMedia("(max-width: 699px)").matches) return;
                        try {
                          ed.theme && ed.theme.resizeTo && ed.theme.resizeTo(null, 120);
                        } catch (err) {}
                        var ifr = document.getElementById("kcj_body_ifr");
                        if (ifr) {
                          ifr.style.height = "7.5rem";
                          ifr.style.minHeight = "7.5rem";
                          ifr.style.maxHeight = "12rem";
                        }
                        var body = ed.getBody && ed.getBody();
                        if (body) {
                          body.style.minHeight = "0";
                          body.style.height = "auto";
                        }
                      }
                      function bindEditor(ed) {
                        if (!ed || ed.id !== "kcj_body") return;
                        ed.on("change", onDirty);
                        ed.on("keyup", onDirty);
                        ed.on("undo", onDirty);
                        ed.on("redo", onDirty);
                        ed.on("paste", onDirty);
                        ed.on("init", function () { clampMobileEditor(ed); });
                        clampMobileEditor(ed);
                      }
                      if (window.tinyMCE) {
                        bindEditor(window.tinyMCE.get("kcj_body"));
                        if (window.tinyMCE.on) {
                          window.tinyMCE.on("AddEditor", function (e) { bindEditor(e.editor); });
                        }
                      }
                      window.addEventListener("orientationchange", function () {
                        if (window.tinyMCE && window.tinyMCE.get("kcj_body")) {
                          clampMobileEditor(window.tinyMCE.get("kcj_body"));
                        }
                      });
                    })();
                    </script>
                </div>
            <?php elseif ($user && $user->exists()) : ?>
                <div class="kcj-desk-card">
                    <h3><?php esc_html_e('Become a contributor', 'kcjdrama'); ?></h3>
                    <p class="kcj-desk-who">
                        <?php
                        echo esc_html(
                            sprintf(
                                /* translators: %s: display name */
                                __('Signed in as %s. Apply below — we verify before you can submit fiction.', 'kcjdrama'),
                                $user->display_name
                            )
                        );
                        ?>
                        <a href="<?php echo esc_url($logout); ?>"><?php esc_html_e('Sign out', 'kcjdrama'); ?></a>
                    </p>
                    <?php if ($pending_app) : ?>
                        <p class="kcj-desk-pending"><?php esc_html_e('Your application is already waiting.', 'kcjdrama'); ?></p>
                    <?php endif; ?>
                    <form class="kcj-desk-form" method="post" action="<?php echo esc_url($apply_url); ?>">
                        <input type="hidden" name="action" value="kcj_apply">
                        <?php wp_nonce_field('kcj_apply', 'kcj_apply_nonce'); ?>
                        <input type="hidden" name="kcj_ts" value="<?php echo esc_attr((string) time()); ?>">
                        <p class="kcj-hp" aria-hidden="true">
                            <label><?php esc_html_e('Website', 'kcjdrama'); ?>
                                <input type="text" name="kcj_hp" tabindex="-1" autocomplete="off">
                            </label>
                        </p>
                        <label>
                            <span><?php esc_html_e('Name', 'kcjdrama'); ?></span>
                            <input type="text" name="kcj_name" required maxlength="80" value="<?php echo esc_attr($user->display_name); ?>">
                        </label>
                        <label>
                            <span><?php esc_html_e('Email', 'kcjdrama'); ?></span>
                            <input type="email" name="kcj_email" required value="<?php echo esc_attr($user->user_email); ?>">
                        </label>
                        <label>
                            <span><?php esc_html_e('Why this desk', 'kcjdrama'); ?></span>
                            <textarea name="kcj_note" rows="5" required minlength="20" maxlength="2000" placeholder="<?php esc_attr_e('A few sentences. No plot dumps.', 'kcjdrama'); ?>"></textarea>
                        </label>
                        <label>
                            <span><?php esc_html_e('Optional sample', 'kcjdrama'); ?></span>
                            <textarea name="kcj_sample" rows="8" maxlength="8000" placeholder="<?php esc_attr_e('A paragraph of original fiction is enough.', 'kcjdrama'); ?>"></textarea>
                        </label>
                        <button type="submit" class="kcj-btn kcj-btn--soft"><?php esc_html_e('Apply to contribute', 'kcjdrama'); ?></button>
                    </form>
                </div>
            <?php else : ?>
                <div class="kcj-desk-card">
                    <h3><?php esc_html_e('New?', 'kcjdrama'); ?></h3>
                    <p><?php esc_html_e('Make a login. Then you can ask to join the Soft desk. We still verify before fiction goes live.', 'kcjdrama'); ?></p>
                    <form class="kcj-desk-form" method="post" action="<?php echo esc_url($apply_url); ?>">
                        <input type="hidden" name="action" value="kcj_register">
                        <?php wp_nonce_field('kcj_register', 'kcj_register_nonce'); ?>
                        <input type="hidden" name="kcj_ts" value="<?php echo esc_attr((string) time()); ?>">
                        <p class="kcj-hp" aria-hidden="true">
                            <label><?php esc_html_e('Website', 'kcjdrama'); ?>
                                <input type="text" name="kcj_hp" tabindex="-1" autocomplete="off">
                            </label>
                        </p>
                        <label>
                            <span><?php esc_html_e('Name', 'kcjdrama'); ?></span>
                            <input type="text" name="kcj_name" required maxlength="80" autocomplete="name">
                        </label>
                        <label>
                            <span><?php esc_html_e('Email', 'kcjdrama'); ?></span>
                            <input type="email" name="kcj_email" required autocomplete="email">
                        </label>
                        <label>
                            <span><?php esc_html_e('Password', 'kcjdrama'); ?></span>
                            <input type="password" name="kcj_pass" required minlength="8" autocomplete="new-password">
                        </label>
                        <label>
                            <span><?php esc_html_e('Password again', 'kcjdrama'); ?></span>
                            <input type="password" name="kcj_pass2" required minlength="8" autocomplete="new-password">
                        </label>
                        <button type="submit" class="kcj-btn kcj-btn--soft"><?php esc_html_e('Create my account', 'kcjdrama'); ?></button>
                    </form>
                </div>
                <div class="kcj-desk-card kcj-desk-card--login">
                    <h3><?php esc_html_e('Already have a login?', 'kcjdrama'); ?></h3>
                    <p><?php esc_html_e('Sign in. Verified people get the story form. Everyone else can apply to the desk.', 'kcjdrama'); ?></p>
                    <?php
                    wp_login_form([
                        'redirect'       => kcj_page_url('stories') . '#kcj-desk',
                        'label_username' => __('Email or username', 'kcjdrama'),
                        'label_log_in'   => __('Sign in', 'kcjdrama'),
                        'remember'       => true,
                    ]);
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php kcj_render_desk_faq('h3'); ?>
    </section>
    <?php
}

function kcj_render_desk_faq($heading_tag = 'h3') {
    $heading_tag = $heading_tag === 'h2' ? 'h2' : 'h3';
    $faq = kcj_page_url('faq');
    $policy = kcj_page_url('editorial-policy');
    $on_faq = function_exists('is_page') && is_page('faq');
    ?>
    <div class="kcj-desk-faq" id="kcj-desk-faq">
        <<?php echo $heading_tag; ?>><?php esc_html_e('Pass OR Fail', 'kcjdrama'); ?></<?php echo $heading_tag; ?>>
        <p class="kcj-faq-lede"><?php esc_html_e('AI Review My Draft & Publish uses this bar. The Result field names a pass or a hold. A pass offers Publish now or Keep working. Publish now opens the live short. Keep working stays in the editor. Human Review & Publish waits for an editor.', 'kcjdrama'); ?></p>

        <section class="kcj-policy-grid kcj-faq-pair" aria-label="<?php esc_attr_e('The bar', 'kcjdrama'); ?>">
            <article class="kcj-policy-card">
                <h2><?php esc_html_e('Pass', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Original prose: a short, a scene, weather and cloth and silence.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Kissing, massage, holding, a door closing — fade-to-black.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Tropes as craft: the umbrella, the rain, the almost-confession. Not someone else’s plot.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('A title and a story. Not a blank box.', 'kcjdrama'); ?></li>
                </ul>
            </article>
            <article class="kcj-policy-card kcj-policy-card--refuse">
                <h2><?php esc_html_e('Fail', 'kcjdrama'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Shooting-script format: EPISODE, EXT./INT., CHARACTER:, OST swell.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Episode-by-episode recaps and long quoted dialogue from a real show.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Stills, screenshots, OST files, subtitle files, piracy links.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Punching down at a real actor’s body or private life.', 'kcjdrama'); ?></li>
                    <li><?php esc_html_e('Oral sex or coitus on the page — even one small line or euphemism.', 'kcjdrama'); ?></li>
                </ul>
            </article>
        </section>

        <section class="kcj-faq-steps" aria-label="<?php esc_attr_e('Fail to pass', 'kcjdrama'); ?>">
            <article>
                <h2><?php esc_html_e('Script → prose', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Same rain, same umbrella — as paragraphs. No sluglines, no (CONT’D), no camera push.', 'kcjdrama'); ?></p>
                <p class="kcj-faq-ex is-fail"><?php esc_html_e('Fail: EXT. GANGNAM SIDEWALK – NIGHT. JI-WOO (CONT’D). OST swell.', 'kcjdrama'); ?></p>
                <p class="kcj-faq-ex is-pass"><?php esc_html_e('Pass: Heavy rain blurred the neon. A black umbrella opened over her. He did not explain.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('Recap → pattern', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Keep the device. Lose their sixteen episodes. We publish original feelings, not a stolen rundown.', 'kcjdrama'); ?></p>
                <p class="kcj-faq-ex is-fail"><?php esc_html_e('Fail: Episode 1 coffee spill. Episode 2 contract. Episode 3 umbrella. Watch it here.', 'kcjdrama'); ?></p>
                <p class="kcj-faq-ex is-pass"><?php esc_html_e('Pass: She left the office with a soggy handkerchief. He covered her like the rain was the only witness.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('Still → cloth', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Do not attach an official frame. Write the wet sleeve. Write the neon. Leave the screenshot off the page.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('Gossip → trope', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('Roast the rain confession. Leave the actress’s face, weight, marriage, and surgery out of it.', 'kcjdrama'); ?></p>
            </article>
            <article>
                <h2><?php esc_html_e('Heat → fade', 'kcjdrama'); ?></h2>
                <p><?php esc_html_e('A kiss can stay. Massage can stay. A door can close. A sex act — oral or coitus, graphic or small — has to come out.', 'kcjdrama'); ?></p>
            </article>
        </section>

        <?php if (!$on_faq) : ?>
            <p class="kcj-faq-more">
                <a href="<?php echo esc_url($faq); ?>"><?php esc_html_e('Full FAQ', 'kcjdrama'); ?></a>
                ·
                <a href="<?php echo esc_url($policy); ?>"><?php esc_html_e('Editorial policy', 'kcjdrama'); ?></a>
            </p>
        <?php endif; ?>
    </div>
    <?php
}
