<?php
/**
 * Related posts (series)
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

function get_custom_related_series_posts( $current_post_id, $num_posts = 4 ) {
    $found_ids = array();
    $collected_ids = array( $current_post_id );
    $tag_ids = array();

    $tags = wp_get_post_tags( $current_post_id );
    if ( $tags ) {
        $tag_ids = wp_list_pluck( $tags, 'term_id' );
        if ( !empty($tag_ids) ) {
            $args_priority1 = array(
                'tag__in'             => $tag_ids,
                'post__not_in'        => $collected_ids,
                'posts_per_page'      => $num_posts,
                'ignore_sticky_posts' => 1,
                'fields'              => 'ids',
            );
            $query_priority1 = new WP_Query( $args_priority1 );
            if ( $query_priority1->have_posts() ) {
                $newly_found = $query_priority1->posts;
                $found_ids = array_merge( $found_ids, $newly_found );
                $collected_ids = array_merge( $collected_ids, $newly_found );
            }
        }
    }

    if ( count( $found_ids ) < $num_posts ) {
        $source_tags_objects = $tags;
        $all_site_tags_objects = get_tags( array( 'hide_empty' => false ) );
        $similar_tag_ids_to_query = array();

        if ( $source_tags_objects && $all_site_tags_objects ) {
            foreach ( $source_tags_objects as $current_tag_obj ) {
                $current_tag_name = strtolower($current_tag_obj->name);
                $current_tag_words = explode( ' ', $current_tag_name );

                foreach ( $all_site_tags_objects as $site_tag_obj ) {
                    if (in_array($site_tag_obj->term_id, $tag_ids)) {
                        continue;
                    }
                    $site_tag_name = strtolower($site_tag_obj->name);
                    $site_tag_words = explode( ' ', $site_tag_name );

                    if ( count( $current_tag_words ) > 1 ) {
                        foreach ( $current_tag_words as $word ) {
                            if ( count( $site_tag_words ) == 1 && $site_tag_name === $word ) {
                                $similar_tag_ids_to_query[] = $site_tag_obj->term_id;
                                break;
                            }
                        }
                    } else {
                        if ( count( $site_tag_words ) > 1 ) {
                            foreach ( $site_tag_words as $site_word ) {
                                if ( $site_word === $current_tag_name ) {
                                    $similar_tag_ids_to_query[] = $site_tag_obj->term_id;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $similar_tag_ids_to_query = array_unique( $similar_tag_ids_to_query );
        if (!empty($tag_ids)) {
            $similar_tag_ids_to_query = array_diff($similar_tag_ids_to_query, $tag_ids);
        }

        if ( ! empty( $similar_tag_ids_to_query ) ) {
            $needed_posts = $num_posts - count( $found_ids );
            if ( $needed_posts > 0 ) {
                $args_priority2 = array(
                    'tag__in'             => $similar_tag_ids_to_query,
                    'post__not_in'        => $collected_ids,
                    'posts_per_page'      => $needed_posts,
                    'ignore_sticky_posts' => 1,
                    'fields'              => 'ids',
                );
                $query_priority2 = new WP_Query( $args_priority2 );
                if ( $query_priority2->have_posts() ) {
                    $newly_found = $query_priority2->posts;
                    $found_ids = array_merge( $found_ids, $newly_found );
                    $collected_ids = array_merge( $collected_ids, $newly_found );
                }
            }
        }
    }

    if ( count( $found_ids ) < $num_posts ) {
        $category_ids = wp_get_post_categories( $current_post_id, array( 'fields' => 'ids' ) );
        if ( !empty( $category_ids ) ) {
            $needed_posts = $num_posts - count( $found_ids );
            if ( $needed_posts > 0 ) {
                $args_cat = array(
                    'category__in'        => $category_ids,
                    'post__not_in'        => $collected_ids,
                    'posts_per_page'      => $needed_posts,
                    'orderby'             => 'date',
                    'order'               => 'DESC',
                    'ignore_sticky_posts' => 1,
                    'fields'              => 'ids'
                );
                $category_posts_query = new WP_Query( $args_cat );

                if ( $category_posts_query->have_posts() ) {
                    $newly_found_cat = $category_posts_query->posts;
                    $found_ids = array_merge( $found_ids, $newly_found_cat );
                    $collected_ids = array_merge( $collected_ids, $newly_found_cat);
                }
            }
        }
    }

    if ( count( $found_ids ) < $num_posts ) {
        $needed_posts = $num_posts - count( $found_ids );
        if ( $needed_posts > 0 ) {
            $args_fallback = array(
                'post_type'           => 'post',
                'post_status'         => 'publish',
                'post__not_in'        => $collected_ids,
                'posts_per_page'      => $needed_posts,
                'orderby'             => 'date',
                'order'               => 'DESC',
                'ignore_sticky_posts' => 1,
                'fields'              => 'ids',
            );
            $fallback_query = new WP_Query( $args_fallback );
            if ( $fallback_query->have_posts() ) {
                $newly_found_fallback = $fallback_query->posts;
                $found_ids = array_merge( $found_ids, $newly_found_fallback );
            }
        }
    }

    $found_ids = array_unique( $found_ids );
    return array_slice( $found_ids, 0, $num_posts );
}

function gp_series_posts_output() {
    $current_post_id = get_queried_object_id();
    if ( !$current_post_id && isset($GLOBALS['post']) ) {
        $current_post_id = $GLOBALS['post']->ID;
    }

    if ( !is_singular() || !$current_post_id ) {
        return;
    }


    $initial_posts_count = 12;
    $load_more_posts_count = 12;
    $max_clicks = 3;
    $all_related_post_ids = get_custom_related_series_posts($current_post_id, $initial_posts_count + ($load_more_posts_count * $max_clicks));

    if ( empty($all_related_post_ids) ) {
        return;
    }

    $initial_post_ids = array_slice($all_related_post_ids, 0, $initial_posts_count);
	$placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

    if ( !empty($initial_post_ids) ) {
        $args = array(
            'post__in' => $initial_post_ids,
            'posts_per_page' => $initial_posts_count,
            'orderby' => 'post__in',
            'ignore_sticky_posts'=>1
        );
        $series_query = new WP_Query( $args );
        if( $series_query->have_posts() ) {
            echo '<div class="gp-series-posts-container"
                         data-current-post-id="' . esc_attr($current_post_id) . '"
                         data-initial-posts-count="' . esc_attr($initial_posts_count) . '"
                         data-load-more-count="' . esc_attr($load_more_posts_count) . '"
                         data-max-clicks="' . esc_attr($max_clicks) . '"
                         data-total-related-posts="' . count($all_related_post_ids) . '">';
            echo '<h2 class="series-posts-title">Series</h2>';
            echo '<div class="series-posts-grid">';
            while( $series_query->have_posts() ) {
                $series_query->the_post();
                ob_start();
                $post_id = get_the_ID();
                if (has_post_thumbnail($post_id)) {
                    $thumbnail_id = get_post_thumbnail_id($post_id);
                    $image_attributes = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
                    $actual_width = $image_attributes ? $image_attributes[1] : '';
                    $actual_height = $image_attributes ? $image_attributes[2] : '';
                    $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
                    if (empty($image_alt)) {
                        $image_alt = get_the_title($post_id);
                    }
                    $image_src = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
                    $series_thumb_html = '';
                    if ($image_src) {
                        $image_url = $image_src[0];
                        $series_thumb_html = sprintf(
                            '<img src="%s" data-src="%s" alt="%s" width="%s" height="%s" class="lazy-load">',
                            esc_url($placeholder_src),
                            esc_url($image_url),
                            esc_attr($image_alt),
                            esc_attr($actual_width),
                            esc_attr($actual_height)
                        );
                    }

                    if (empty($series_thumb_html)) {
                        echo '<div class="no-thumb-series"></div>';
                    } else {
                        echo $series_thumb_html;
                    }
                } else {
                    echo '<div class="no-thumb-series"></div>';
                }
                $series_thumb_html_output = ob_get_clean();
                ?>
                <a href="<?php the_permalink(); ?>" rel="bookmark" class="series-post-item">
                    <div class="series-post-thumbnail"><?php echo $series_thumb_html_output; ?></div>
                    <div class="series-post-content">
                        <h3 class="series-post-title"><?php the_title(); ?></h3>
                    </div>
                </a>
                <?php
            }
            echo '</div>';

            if (count($all_related_post_ids) > $initial_posts_count) {
                echo '<div class="load-more-series-container"><button id="load-more-series-btn" class="gp-load-more-series-btn">Series More</button></div>';
            }
            echo '</div>';
        }
        wp_reset_postdata();
    }
}
