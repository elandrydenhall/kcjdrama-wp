<?php
/**
 * Mirror World — Syndrome clinic index.
 * Template hierarchy: page-syndromes.php for /syndromes/
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();
kcj_render_editorial_hub('syndromes');
get_footer();
