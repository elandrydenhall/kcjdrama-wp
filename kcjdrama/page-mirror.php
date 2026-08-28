<?php
/**
 * Mirror brand stage — hero right-plate landing.
 * Template hierarchy: page-mirror.php for /mirror/
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('template-parts/brand-stage', null, ['tone' => 'mirror']);
get_footer();
