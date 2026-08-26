<?php
/**
 * PHP built-in server router for local WordPress (Laragon PHP, no Docker).
 * php -S 127.0.0.1:8080 -t wordpress scripts/php-router.php
 */
$root = $_SERVER['DOCUMENT_ROOT'];
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (!is_string($path) || $path === '') {
    $path = '/';
}
$file = $root . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require $root . '/index.php';
