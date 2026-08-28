<?php
/**
 * Sign in / account — Soft door. Template hierarchy: page-sign-in.php for /sign-in/
 */
if (!defined('ABSPATH')) {
    exit;
}

$stories = kcj_page_url('stories');
$soft = kcj_page_url('soft');
$redirect = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
$crumb = kcj_account_crumb($redirect);
if ($redirect === '') {
    $redirect = $crumb['url'];
}
$in = is_user_logged_in();
$me = $in ? wp_get_current_user() : null;
$name = '';
if ($me) {
    $name = $me->display_name !== '' ? $me->display_name : $me->user_login;
}
$login_note = '';
$login_code = isset($_GET['kcj_login']) ? sanitize_key(wp_unslash($_GET['kcj_login'])) : '';
$login_notes = [
    'failed'    => __('That key did not fit. Check the email and password, or start New? on the desk.', 'kcjdrama'),
    'empty'     => __('The door needs both an email and a password.', 'kcjdrama'),
    'loggedout' => __('Signed out. Come back whenever the house still feels like yours.', 'kcjdrama'),
];
if ($login_code !== '' && isset($login_notes[$login_code])) {
    $login_note = $login_notes[$login_code];
}

get_header();
?>
<main id="kcj-main" class="kcj-page kcj-page--soft kcj-signin<?php echo $in ? ' kcj-signin--in' : ' kcj-signin--out'; ?>">
    <p class="kcj-signin-run" aria-hidden="true"><?php echo $in ? esc_html__('in', 'kcjdrama') : esc_html__('key', 'kcjdrama'); ?></p>
    <div class="kcj-signin-inner">
        <header class="kcj-signin-hero">
            <p class="kcj-brand-folio">00</p>
            <p class="kcj-page-kicker"><?php esc_html_e('Account', 'kcjdrama'); ?></p>
            <h1><?php echo $in ? esc_html($name) : esc_html__('Sign in', 'kcjdrama'); ?></h1>
            <?php if ($in) : ?>
                <p class="kcj-signin-lede">
                    <?php
                    echo esc_html(
                        sprintf(
                            /* translators: %s: display name */
                            __('You’re signed in as: %s.', 'kcjdrama'),
                            $name
                        )
                    );
                    ?>
                </p>
                <p class="kcj-signin-body"><?php esc_html_e('The house knows your name. The desk still asks for original words — patterns, not pirated plots.', 'kcjdrama'); ?></p>
            <?php else : ?>
                <p class="kcj-signin-lede"><?php esc_html_e('The porcelain side of the door. A login is a key, not a plot dump.', 'kcjdrama'); ?></p>
                <p class="kcj-signin-body"><?php esc_html_e('New here? Make an account on the Soft desk, then come back through this door.', 'kcjdrama'); ?></p>
            <?php endif; ?>
            <p class="kcj-signin-back">
                <span><?php esc_html_e('Back to', 'kcjdrama'); ?></span>
                <a href="<?php echo esc_url($crumb['url']); ?>"><?php echo esc_html($crumb['label']); ?></a>
            </p>
        </header>

        <div class="kcj-signin-col">
            <?php if ($in) : ?>
                <div class="kcj-signin-plate">
                    <p class="kcj-signin-plate-kicker"><?php esc_html_e('Session', 'kcjdrama'); ?></p>
                    <p class="kcj-signin-plate-name"><?php echo esc_html($name); ?></p>
                    <p class="kcj-signin-plate-line"><?php esc_html_e('Signed in. The hamburger still shows your name; this is the door out.', 'kcjdrama'); ?></p>
                    <div class="kcj-brand-actions">
                        <a class="kcj-btn kcj-btn--soft" href="<?php echo esc_url(kcj_sign_out_url($crumb['url'])); ?>"><?php esc_html_e('Sign out', 'kcjdrama'); ?></a>
                        <a class="kcj-brand-cross" href="<?php echo esc_url($stories); ?>#kcj-desk"><?php esc_html_e('Stories desk', 'kcjdrama'); ?></a>
                        <a class="kcj-brand-cross" href="<?php echo esc_url($soft); ?>"><?php esc_html_e('Soft World', 'kcjdrama'); ?></a>
                    </div>
                </div>
            <?php else : ?>
                <div class="kcj-signin-plate">
                    <p class="kcj-signin-plate-kicker"><?php esc_html_e('Key', 'kcjdrama'); ?></p>
                    <?php if ($login_note !== '') : ?>
                        <p class="kcj-signin-notice" role="alert"><?php echo esc_html($login_note); ?></p>
                    <?php endif; ?>
                    <?php
                    wp_login_form([
                        'redirect'       => $redirect,
                        'label_username' => __('Email or username', 'kcjdrama'),
                        'label_password' => __('Password', 'kcjdrama'),
                        'label_remember' => __('Keep this key', 'kcjdrama'),
                        'label_log_in'   => __('Open the door', 'kcjdrama'),
                        'remember'       => true,
                    ]);
                    ?>
                </div>
                <a class="kcj-teaser kcj-teaser--soft" href="<?php echo esc_url($stories); ?>#kcj-account">
                    <p class="kcj-teaser-kicker"><?php esc_html_e('New?', 'kcjdrama'); ?></p>
                    <h3><?php esc_html_e('Make a login', 'kcjdrama'); ?></h3>
                    <p><?php esc_html_e('The Soft desk starts an account. We still verify before fiction goes live.', 'kcjdrama'); ?></p>
                </a>
            <?php endif; ?>
        </div>
    </div>
</main>
<?php
get_footer();
