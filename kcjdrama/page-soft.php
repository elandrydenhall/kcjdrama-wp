<?php
/**
 * Soft brand stage — hero left-plate landing.
 * Template hierarchy: page-soft.php for /soft/
 */
if (!defined('ABSPATH')) {
    exit;
}

get_header();
get_template_part('template-parts/brand-stage', null, ['tone' => 'soft']);
get_footer();
