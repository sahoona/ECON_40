<?php
/**
 * Custom template tags for this theme
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function gp_entry_header_start_wrap() { echo '<div class="entry-header-wrapper">'; }
function gp_entry_header_end_wrap() { echo '</div>'; }

function gp_get_tags_list() {
    $tags = get_the_tags();
    if ( $tags ) {
        $tags_list = '';
        foreach ( $tags as $tag ) {
            $tag_link = get_tag_link( $tag->term_id );
            $tags_list .= sprintf( '<a href="%s" rel="tag">%s</a>, ', esc_url( $tag_link ), esc_html( $tag->name ) );
        }
        $tags_list = rtrim( $tags_list, ', ' );
        return '<footer class="entry-meta tags-footer" role="group"><div class="tags-links">' . $tags_list . '</div></footer>';
    }
    return '';
}
