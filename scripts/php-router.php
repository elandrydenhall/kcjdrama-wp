<?php
/**
 * PHP built-in server router for local WordPress (Laragon PHP, no Docker).
 * php -S 127.0.0.1:8080 -t wordpress scripts/php-router.php
 *
 * Must hand real files AND directory indexes (wp-admin/) to the engine.
 * Otherwise /wp-admin/ falls through to front-end index.php.
 */
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}
$path = rawurldecode($path);
$file = $root . $path;

if ($path !== '/') {
    if (is_file($file)) {
        return false; // static / PHP file under docroot
    }
    if (is_dir($file)) {
        $index = rtrim($file, '/\\') . DIRECTORY_SEPARATOR . 'index.php';
        if (is_file($index)) {
            require $index;
            return true;
        }
        return false;
    }
}

require $root . '/index.php';
