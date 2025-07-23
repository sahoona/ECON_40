<?php
/**
 * Helper functions
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function gp_custom_post_navigation_output(){
    if(!is_single()) return;
    $prev_post=get_previous_post(); $next_post=get_next_post();
    if(!$prev_post&&!$next_post) return;
    $nav_item = function($post,$rel){
        if(!$post){ return ""; }

        $thumb_html = '<div class="no-thumb"></div>';
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $actual_width = '';
            $actual_height = '';
            if ($thumbnail_id) {
                $image_attributes = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
                if ($image_attributes && is_array($image_attributes) && count($image_attributes) >= 3) {
                    $actual_width = $image_attributes[1];
                    $actual_height = $image_attributes[2];
                }
            }
            $thumb_html = get_the_post_thumbnail($post->ID, 'medium_large');
            if ($actual_width && $actual_height) {
                $thumb_html = preg_replace('/ width="[^"]*"/', '', $thumb_html);
                $thumb_html = preg_replace('/ height="[^"]*"/', '', $thumb_html);
                $thumb_html = str_replace('<img ', '<img width="' . esc_attr($actual_width) . '" height="' . esc_attr($actual_height) . '" ', $thumb_html);
            }
        }
        $label=$rel==='prev'?'Prev Post':'Next Post'; $class_name=$rel==='prev'?'previous':'next';
        return "<div class='nav-{$class_name}'><a href='".esc_url(get_permalink($post->ID))."' rel='{$rel}'>".$thumb_html."<div class='nav-title-overlay'><span class='nav-title-label'>".$label."</span><span class='nav-title'>".get_the_title($post->ID)."</span></div></a></div>";
    };
    echo '<div class="gp-custom-post-nav-wrapper"><nav class="navigation post-navigation gp-custom-post-nav"><div class="nav-links">';
    echo $nav_item($prev_post,'prev'); echo $nav_item($next_post,'next');
    echo '</div></nav></div>';
}

function gp_add_footer_elements_and_scripts() {
    ?>
    <noscript>
        <style>
            .site-footer-container .footer-grid {
                display: block;
            }
            .site-footer-container .footer-grid > div {
                display: inline-block;
                vertical-align: top;
                width: 30%; /* Flexible for 3 columns */
                margin: 0 1.5%;
            }
            .site-footer-container .footer-about {
                width: 98%; /* Full width on its own line */
                margin-bottom: 20px;
            }
            @media (max-width: 768px) {
                .site-footer-container .footer-grid > div {
                    display: block;
                    width: 98%;
                    margin: 0 1% 20px;
                }
            }
        </style>
    </noscript>
    <?php
    echo '<div id="mybar" role="progressbar" aria-label="Reading progress" aria-valuemin="0" aria-valuemax="100"></div>';
    echo '<div class="floating-buttons-container" role="toolbar" aria-label="Site control buttons">';
    if (is_single()) {
        echo '<button id="sidebarToggle" class="floating-btn" title="Toggle sidebar" aria-label="Open/close sidebar"><div class="sidebar-toggle-icon"></div></button>';
    }
    echo '<div id="darkModeToggle" class="floating-btn" role="button" tabindex="0" title="Toggle dark mode" aria-label="Switch dark mode"><div class="dark-mode-icon-wrapper"></div></div>';
    echo '<button id="scrollToTopBtn" class="floating-btn" title="Scroll to top" aria-label="Go to page top"></button>';
    echo '</div>';

    if (is_single()) {
        echo '<aside id="gp-sidebar" class="gp-sidebar-hidden" role="complementary" aria-label="Sidebar" style="display: none;"><div class="sidebar-header"><h3>Contents & Tools</h3><button class="sidebar-close" aria-label="Close sidebar">×</button></div><div class="sidebar-content"><div class="sidebar-toc-container"></div><div class="sidebar-tools"><h4>Tools</h4><button class="sidebar-tool" data-action="print">Print</button><button class="sidebar-tool" data-action="bookmark">Bookmark</button><button class="sidebar-tool" data-action="share">Share</button></div></div></aside>';
        echo '<div id="sidebar-overlay" class="sidebar-overlay" style="display: none;"></div>';
    }
}

// Archive page customizations
function gp_archive_customizations() {
    if ( ! is_archive() ) {
        return;
    }

    // Remove default archive title
    remove_action( 'generate_archive_title', 'generate_archive_title' );

    // Add custom archive title
    add_action( 'generate_before_main_content', 'gp_add_custom_archive_title', 15 );

    // Add wrapper for archive posts
    add_action( 'generate_before_loop', 'gp_archive_posts_wrapper_start' );
    add_action( 'generate_after_loop', 'gp_archive_posts_wrapper_end' );
}
add_action( 'wp', 'gp_archive_customizations' );

function gp_add_custom_archive_title() {
    ?>
    <header class="archive-header">
        <?php the_archive_title( '<h1 class="page-title">', '</h1>' ); ?>
        <?php the_archive_description( '<div class="taxonomy-description">', '</div>' ); ?>
    </header>
    <?php
}

function gp_archive_posts_wrapper_start() {
    echo '<div class="archive-posts-grid">';
}

function gp_archive_posts_wrapper_end() {
    echo '</div>';
}

// Pagination styling
add_filter( 'generate_pagination_args', function( $args ) {
    $args['prev_text'] = '<span>«</span> Previous';
    $args['next_text'] = 'Next <span>»</span>';
    return $args;
} );

function gp_custom_hide_entry_footer_archives( $show ) {
    if ( ! is_singular() ) {
        return false;
    }
    return $show;
}
add_filter( 'generate_show_entry_footer', 'gp_custom_hide_entry_footer_archives' );


function gp_add_image_dimensions_to_content($content) {
    if (is_admin() || is_feed() || empty(trim($content))) {
        return $content;
    }
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $images = $dom->getElementsByTagName('img');
    foreach ($images as $image_node) {
        $has_width = $image_node->hasAttribute('width') && !empty($image_node->getAttribute('width'));
        $has_height = $image_node->hasAttribute('height') && !empty($image_node->getAttribute('height'));
        if ($has_width && $has_height) {
            continue;
        }
        $src = $image_node->getAttribute('src');
        if (empty($src)) {
            continue;
        }
        $attachment_id = attachment_url_to_postid($src);
        if ($attachment_id > 0) {
            $image_meta = wp_get_attachment_image_src($attachment_id, 'full');
            if ($image_meta) {
                if (!$has_width) {
                    $image_node->setAttribute('width', $image_meta[1]);
                }
                if (!$has_height) {
                    $image_node->setAttribute('height', $image_meta[2]);
                }
            }
        }
    }
    $container = $dom->getElementsByTagName('div')->item(0);
    $new_content = '';
    if ($container && $container->hasChildNodes()) {
        foreach ($container->childNodes as $child) {
            $new_content .= $dom->saveHTML($child);
        }
    } else {
        return $content;
    }
    return $new_content;
}
add_filter('the_content', 'gp_add_image_dimensions_to_content', 20);

// Query Modifications
function gp_ensure_home_pagination( $query ) {
    if ( is_admin() || ! $query->is_main_query() ) {
        return;
    }
    if ( $query->is_home() || $query->is_front_page() ) {
        $query->set( 'posts_per_page', 5 );
    }
}
add_action( 'pre_get_posts', 'gp_ensure_home_pagination' );

function gp_custom_word_count($content) {
    $content = strip_tags($content);
    // This regex counts words separated by spaces and also counts sequences of non-space characters as words,
    // which is more accurate for languages like Korean.
    // It also correctly handles CJK characters.
    $pattern = '/[\p{L}\p{N}]+/u';
    preg_match_all($pattern, $content, $matches);
    return count($matches[0]);
}

function gp_get_full_category_breadcrumb( $post_id ) {
    $categories = get_the_category( $post_id );
    if ( empty( $categories ) ) {
        return '';
    }

    $child_category = null;
    // Find the deepest category (the one that is a child)
    foreach ($categories as $category) {
        if ($category->parent != 0) {
            // Check if this category is a child of another category in the list.
            // If so, it's not the deepest one.
            $is_child_of_another_in_list = false;
            foreach ($categories as $parent_candidate) {
                if ($category->parent === $parent_candidate->term_id) {
                    $is_child_of_another_in_list = true;
                    break;
                }
            }
            if (!$is_child_of_another_in_list) {
                $child_category = $category;
                break;
            }
        }
    }

    // If no child category is found after the loop, it means we might have a single category or multiple top-level categories.
    // Let's find one that is not a parent to any other in the list.
    if ($child_category === null) {
        foreach($categories as $category) {
            $is_parent = false;
            foreach($categories as $child_candidate) {
                if ($child_candidate->parent === $category->term_id) {
                    $is_parent = true;
                    break;
                }
            }
            if (!$is_parent) {
                $child_category = $category;
                break;
            }
        }
    }

    // As a final fallback, just use the first category.
    if ($child_category === null) {
        $child_category = $categories[0];
    }


    $breadcrumb_parts = [];
    $current_category = $child_category;

    while ( $current_category ) {
        $breadcrumb_parts[] = sprintf(
            '<a href="%s">%s</a>',
            esc_url( get_category_link( $current_category->term_id ) ),
            esc_html( $current_category->name )
        );
        if ( $current_category->parent ) {
            $current_category = get_category( $current_category->parent );
        } else {
            $current_category = null;
        }
    }

    $breadcrumb_parts = array_reverse( $breadcrumb_parts );
    return '<div class="gp-post-category">' . implode( ' » ', $breadcrumb_parts ) . '</div>';
}
