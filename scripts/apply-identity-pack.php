<?php
/**
 * Set site identity: title, support email, Mountain Time, Utah store address.
 *
 * Local:  .\scripts\wp.ps1 eval-file scripts/apply-identity-pack.php
 * Remote: php apply-identity-pack.php /path/to/public_html
 */
if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$wp_root = '';
if (isset($argv[1]) && $argv[1] !== '') {
    $wp_root = rtrim($argv[1], "/\\");
    if (!is_file($wp_root . '/wp-load.php')) {
        fwrite(STDERR, "Bad WP root: {$wp_root}\n");
        exit(1);
    }
    define('WP_USE_THEMES', false);
    require $wp_root . '/wp-load.php';
} elseif (!defined('ABSPATH')) {
    fwrite(STDERR, "Load via wp eval-file or pass WP root\n");
    exit(1);
}

$title   = 'KCJDRAMA.com';
$email   = 'support@kcjdrama.com';
$tz      = 'America/Denver';
$line1   = '1873 N Concord PL';
$city    = 'Saratoga Springs';
$country = 'US:UT';
$postcode = '84045';

$updates = [
    'blogname'                          => $title,
    'admin_email'                       => $email,
    'new_admin_email'                   => $email,
    'timezone_string'                   => $tz,
    'gmt_offset'                        => '',
    'woocommerce_store_address'         => $line1,
    'woocommerce_store_address_2'       => '',
    'woocommerce_store_city'            => $city,
    'woocommerce_default_country'       => $country,
    'woocommerce_store_postcode'        => $postcode,
    'woocommerce_email_from_name'       => $title,
    'woocommerce_email_from_address'    => $email,
    'woocommerce_stock_email_recipient' => $email,
    'woocommerce_pos_store_email'       => $email,
    'woocommerce_email_reply_to_enabled'=> 'yes',
    'woocommerce_email_reply_to_name'   => $title,
    'woocommerce_email_reply_to_address'=> $email,
    'woocommerce_pos_store_address'     => $line1,
    'woocommerce_pos_store_city'        => $city,
    'woocommerce_pos_store_postcode'    => $postcode,
    'woocommerce_pos_store_country'     => $country,
];

foreach ($updates as $key => $value) {
    update_option($key, $value);
    echo "set {$key}=" . (is_string($value) ? $value : wp_json_encode($value)) . "\n";
}

// Drop pending admin-email confirmation so admin_email sticks immediately.
delete_option('adminhash');
delete_transient('settings_errors');

$check = [
    'blogname'                    => get_option('blogname'),
    'admin_email'                 => get_option('admin_email'),
    'timezone_string'             => get_option('timezone_string'),
    'wp_timezone_string'          => wp_timezone_string(),
    'now_local'                   => wp_date('Y-m-d H:i:s T'),
    'woocommerce_store_address'   => get_option('woocommerce_store_address'),
    'woocommerce_store_city'      => get_option('woocommerce_store_city'),
    'woocommerce_default_country' => get_option('woocommerce_default_country'),
    'woocommerce_store_postcode'  => get_option('woocommerce_store_postcode'),
    'woocommerce_email_from_address' => get_option('woocommerce_email_from_address'),
];

echo "VERIFY " . wp_json_encode($check, JSON_UNESCAPED_SLASHES) . "\n";
echo "DONE identity pack\n";
