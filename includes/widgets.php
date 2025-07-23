<?php
/**
 * Widget areas.
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register "After Entry Content" widget area
function gp_child_register_widget_areas() {
    register_sidebar( array(
        'name'          => __( 'After Entry Content', 'gp-child-theme' ),
        'id'            => 'after_entry_content_widget_area',
        'description'   => __( 'Appears at the end of single posts, before the tags.', 'gp-child-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h2 class="widget-title">',
        'after_title'   => '</h2>',
    ) );
}
add_action( 'widgets_init', 'gp_child_register_widget_areas' );

// Display the widget area after the entry content
function gp_child_display_after_content_widget_area() {
    if ( is_singular( 'post' ) && is_active_sidebar( 'after_entry_content_widget_area' ) ) {
        echo '<div class="after-entry-content-widget-area">';
        dynamic_sidebar( 'after_entry_content_widget_area' );
        echo '</div>';
    }
}
