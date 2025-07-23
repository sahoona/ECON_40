<?php
/**
 * Theme Core Setup
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Limit post revisions to 3
if ( ! defined( 'WP_POST_REVISIONS' ) ) {
    define( 'WP_POST_REVISIONS', 3 );
}

// Theme setup
function gp_child_theme_setup() {
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array(
        'search-form', 'comment-list', 'gallery', 'caption', 'script', 'style'
    ));
    add_theme_support('title-tag');
    add_theme_support('custom-logo');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
}
add_action('after_setup_theme', 'gp_child_theme_setup');

// Add image sizes
function gp_add_image_sizes() {
    add_image_size('og-image', 1200, 630, true);
    add_image_size('twitter-card', 1200, 600, true);
    add_image_size('large-thumb', 800, 450, true);
}
add_action('after_setup_theme', 'gp_add_image_sizes');
