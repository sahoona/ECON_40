<?php
/**
 * Performance and security optimizations
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Optimize WordPress
function gp_optimize_wordpress() {
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);

    // Remove oEmbed discovery links
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    // Remove oEmbed-specific JavaScript from the front-end and back-end
    remove_action('wp_head', 'wp_oembed_add_host_js');
}
add_action('init', 'gp_optimize_wordpress');

/**
 * Disable Heartbeat API for non-admin users to reduce server load.
 */
function gp_child_optimize_heartbeat_api($settings) {
    if (!current_user_can('manage_options')) {
        $settings['autostart'] = false;
    }
    return $settings;
}
add_filter('heartbeat_settings', 'gp_child_optimize_heartbeat_api');

function gp_child_dequeue_heartbeat_script() {
    if (!current_user_can('manage_options')) {
        wp_dequeue_script('heartbeat');
    }
}
add_action('wp_enqueue_scripts', 'gp_child_dequeue_heartbeat_script', 100);

// Disable XML-RPC
add_filter('xmlrpc_enabled', '__return_false');

// Block REST API for non-logged-in users
add_filter('rest_authentication_errors', function ($result) {
    if (!empty($result)) {
        return $result;
    }
    if (!is_user_logged_in()) {
        return new WP_Error('rest_not_logged_in', 'You are not currently logged in.', array('status' => 401));
    }
    return $result;
});

// Obfuscate login errors
add_filter('login_errors', function () {
    return 'Login information is incorrect.';
});


add_filter('wp_headers', function($headers) {
    unset($headers['X-Pingback']);
    return $headers;
});

// Remove jQuery Migrate
add_action( 'wp_default_scripts', function( $scripts ) {
    if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
        $script = $scripts->registered['jquery'];
        if ( $script->deps ) {
            $script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
        }
    }
});

remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'feed_links_extra', 3 );

// Defer scripts
function prefix_defer_js_scripts( $tag, $handle, $src ) {
    $defer_scripts = array(
        'generate-navigation',
        'generatepress-navigation',
        // 'generate-menu',
        // 'generatepress-menu',
        'generate-modal',
        'generatepress-modal',
    );

    if ( 'gp-main-script' === $handle ) {
        return $tag;
    }

    if ( in_array( $handle, $defer_scripts ) ) {
        if ( !is_admin() && strpos( $tag, 'defer' ) === false ) {
            return str_replace( ' src', ' defer src', $tag );
        }
    }
    return $tag;
}
add_filter( 'script_loader_tag', 'prefix_defer_js_scripts', 99, 3 );

// Add security headers
function gp_add_security_headers() {
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Permissions-Policy: geolocation=(), microphone=(), camera=(), midi=(), magnetometer=(), gyroscope=(), speaker=(), usb=()");
}
add_action('send_headers', 'gp_add_security_headers');

// Add alt text to images
function gp_add_alt_to_images($content) {
    if (is_feed() || is_admin()) {
        return $content;
    }
    $content = preg_replace_callback('/<img[^>]+>/', function($matches) {
        $img = $matches[0];
        if (strpos($img, 'alt=') === false) {
            $img = str_replace('<img', '<img alt=""', $img);
        }
        return $img;
    }, $content);
    return $content;
}
add_filter('the_content', 'gp_add_alt_to_images');

// Add lazy loading to images
function gp_add_lazy_loading($content) {
    if (is_admin() || is_feed() || function_exists('wp_get_loading_attr_default')) {
        return $content;
    }
    $content = preg_replace('/<img([^>]+?)src=/i', '<img$1loading="lazy" src=', $content);
    return $content;
}
//add_filter('the_content', 'gp_add_lazy_loading');

// Dequeue core block styles
function gp_dequeue_core_block_styles() {
    wp_dequeue_style( 'wp-block-file' );
    wp_dequeue_style( 'wp-block-media-text' );
    wp_dequeue_style( 'wp-block-search' );
    wp_dequeue_style( 'wp-block-latest-comments' );
    wp_dequeue_style( 'wp-block-archives' );
    wp_dequeue_style( 'wp-block-tag-cloud' );
    wp_dequeue_style( 'wp-block-calendar' );
    wp_dequeue_style( 'wp-block-rss' );
    wp_dequeue_style( 'wp-block-pullquote' );
    wp_dequeue_style( 'wp-block-verse' );
    wp_dequeue_style( 'wp-block-table' );
}
add_action( 'wp_enqueue_scripts', 'gp_dequeue_core_block_styles', 100 );

// Remove HTML comments
function gp_remove_html_comments_buffer_start() {
    if ( !is_admin() ) {
        ob_start('gp_remove_html_comments_callback');
    }
}
add_action('template_redirect', 'gp_remove_html_comments_buffer_start', 1);

function gp_remove_html_comments_callback($buffer) {
    return preg_replace('/<!--(.|\s)*?-->/', '', $buffer);
}

/*
function gp_remove_html_comments_buffer_end() {
    if ( !is_admin() && ob_get_length() > 0 ) {
        ob_end_flush();
    }
}
add_action('shutdown', 'gp_remove_html_comments_buffer_end');
*/

/**
 * Add DNS prefetch links for external domains to speed up connection.
 */
function gp_child_add_dns_prefetch() {
    echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
    echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
    echo '<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">';
    // Add any other external domains used by the theme here.
}
add_action('wp_head', 'gp_child_add_dns_prefetch', 1);
