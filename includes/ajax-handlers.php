<?php
/**
 * AJAX handlers
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// ===================================================================
//         ★ 시리즈 게시물 '더 보기' 핸들러 (원본 유지) ★
// ===================================================================
function gp_load_more_series_posts_ajax_handler() {
    check_ajax_referer('load_more_series_nonce', 'nonce');

    $current_post_id = isset($_POST['current_post_id']) ? intval($_POST['current_post_id']) : 0;
    $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
    $posts_per_page = isset($_POST['posts_per_page']) ? intval($_POST['posts_per_page']) : 12;
    // `loaded_post_ids`는 이제 URL의 배열입니다.
    $loaded_urls = isset($_POST['loaded_post_ids']) && is_array($_POST['loaded_post_ids']) ? $_POST['loaded_post_ids'] : [];

    if (!$current_post_id) {
        wp_send_json_error(['message' => 'Current post ID is missing.']);
        return;
    }

    // URL을 게시물 ID로 변환합니다.
    $excluded_ids = [$current_post_id];
    foreach ($loaded_urls as $url) {
        $post_id = url_to_postid(esc_url_raw($url));
        if ($post_id) {
            $excluded_ids[] = $post_id;
        }
    }
    $excluded_ids = array_unique(array_map('intval', $excluded_ids));

    // 이 함수는 `includes/related-posts.php`에 정의되어 있습니다.
    // 첫 번째 인수는 현재 게시물 ID, 두 번째는 가져올 게시물 수, 세 번째는 제외할 ID 배열입니다.
    // `get_custom_related_series_posts` 함수가 세 번째 인수를 받도록 수정해야 할 수도 있습니다.
    // 현재 함수 시그니처: get_custom_related_series_posts( $current_post_id, $num_posts = 4 )
    // 해당 함수를 수정하여 제외 ID를 처리하도록 만들어야 합니다.
    // 우선, 이 핸들러에서 로직을 구현해 보겠습니다.

    // 먼저 모든 관련 게시물을 가져옵니다.
    $all_possible_posts = get_custom_related_series_posts($current_post_id, 100); // 충분히 큰 숫자를 가져옵니다.

    // 그 다음, 제외할 ID들을 필터링합니다.
    $filtered_post_ids = array_diff($all_possible_posts, $excluded_ids);

    // 필요한 만큼만 잘라냅니다.
    $post_ids_to_load = array_slice($filtered_post_ids, 0, $posts_per_page);

    if (empty($post_ids_to_load)) {
        wp_send_json_success(['html' => '', 'has_more' => false, 'message' => 'No more series posts.']);
        return;
    }

    $args = array(
        'post__in' => $post_ids_to_load,
        'posts_per_page' => count($post_ids_to_load),
        'orderby' => 'post__in',
        'ignore_sticky_posts' => 1,
    );

    $query = new WP_Query($args);
    $html_output = '';
    global $placeholder_src;
    $placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) : $query->the_post();
            $post_id = get_the_ID();
            $thumb_html = '';
            if (has_post_thumbnail($post_id)) {
                $thumbnail_id = get_post_thumbnail_id($post_id);
                $image_attributes = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
                $actual_width = $image_attributes ? $image_attributes[1] : '';
                $actual_height = $image_attributes ? $image_attributes[2] : '';
                $image_alt_ajax = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id);

                $image_src_ajax = wp_get_attachment_image_src($thumbnail_id, 'medium_large');
                if ($image_src_ajax) {
                    $image_url_ajax = $image_src_ajax[0];
                    $thumb_html = sprintf(
                        '<img src="%s" data-src="%s" alt="%s" width="%s" height="%s" class="lazy-load">',
                        esc_url($placeholder_src),
                        esc_url($image_url_ajax),
                        esc_attr($image_alt_ajax),
                        esc_attr($actual_width),
                        esc_attr($actual_height)
                    );
                } else {
                    $thumb_html = '<div class="no-thumb-series"></div>';
                }
            } else {
                $thumb_html = '<div class="no-thumb-series"></div>';
            }
            ?>
            <a href="<?php echo esc_url(get_permalink()); ?>" rel="bookmark" class="series-post-item">
                <div class="series-post-thumbnail"><?php echo $thumb_html; ?></div>
                <div class="series-post-content">
                    <h3 class="series-post-title"><?php echo esc_html(get_the_title()); ?></h3>
                </div>
            </a>
            <?php
        endwhile;
        wp_reset_postdata();
        $html_output = ob_get_clean();
    }

    $new_offset = $offset + count($post_ids_to_load);
    $has_more = count($post_ids_to_load) === $posts_per_page;

    wp_send_json_success(['html' => $html_output, 'has_more' => $has_more, 'new_offset' => $new_offset, 'loaded_count' => count($post_ids_to_load)]);
}
add_action('wp_ajax_load_more_series_posts', 'gp_load_more_series_posts_ajax_handler');
add_action('wp_ajax_nopriv_load_more_series_posts', 'gp_load_more_series_posts_ajax_handler');

// ===================================================================
//         ★ 기타 핸들러 (반응, 별점 등 / 원본 유지) ★
// ===================================================================
function gp_handle_reaction_callback() {
	check_ajax_referer('gp_reactions_nonce', 'nonce');
	if (isset($_POST['post_id']) && isset($_POST['reaction'])) {
		$post_id = intval($_POST['post_id']);
		$reaction = sanitize_key($_POST['reaction']);
		$count = get_post_meta($post_id, '_gp_reaction_' . $reaction, true);
		$new_count = ($count ? intval($count) : 0) + 1;
		update_post_meta($post_id, '_gp_reaction_' . $reaction, $new_count);
		wp_send_json_success(['count' => $new_count]);
	}
	wp_send_json_error();
}
add_action('wp_ajax_nopriv_gp_handle_reaction', 'gp_handle_reaction_callback');
add_action('wp_ajax_gp_handle_reaction', 'gp_handle_reaction_callback');

function gp_handle_star_rating_callback() {
	check_ajax_referer('gp_star_rating_nonce', 'nonce');
	if ( !isset($_POST['post_id']) || !isset($_POST['new_rating']) ) {
		wp_send_json_error('Missing parameters.');
	}

	$post_id = intval($_POST['post_id']);
	$new_rating = floatval($_POST['new_rating']);
	$old_rating = isset($_POST['old_rating']) ? floatval($_POST['old_rating']) : 0;

	if ($new_rating < 0.5 || $new_rating > 5) {
		wp_send_json_error('Invalid rating.');
	}

	$total_score = get_post_meta($post_id, '_gp_star_rating_total_score', true) ?: 0;
	$vote_count = get_post_meta($post_id, '_gp_star_rating_vote_count', true) ?: 0;

	if ( $old_rating > 0 ) {
		$new_total_score = $total_score - $old_rating + $new_rating;
		$new_vote_count = $vote_count;
	} else {
		$new_total_score = $total_score + $new_rating;
		$new_vote_count = $vote_count + 1;
	}

	update_post_meta($post_id, '_gp_star_rating_total_score', $new_total_score);
	update_post_meta($post_id, '_gp_star_rating_vote_count', $new_vote_count);
	$new_average = ($new_vote_count > 0) ? round($new_total_score / $new_vote_count, 1) : 0;
	wp_send_json_success([
		'average' => $new_average,
		'votes'   => $new_vote_count
	]);
}
add_action('wp_ajax_nopriv_gp_handle_star_rating', 'gp_handle_star_rating_callback');
add_action('wp_ajax_gp_handle_star_rating', 'gp_handle_star_rating_callback');


// ===================================================================
//         ★ 일반 게시물 '더 보기' 최종 해결 코드 (v2) ★
// ===================================================================
function gp_load_more_posts_ajax_handler_v2() {
    // [최종 수정] Nonce 검증 시, v2 이름표를 확인하고, '_wpnonce_v2'라는 상자 이름으로 찾습니다.
    check_ajax_referer('load_more_posts_nonce_v2', '_wpnonce_v2');

    // Rank Math 함수 사용 준비
    if ( defined( 'RANK_MATH_INCLUDES_PATH' ) && file_exists( RANK_MATH_INCLUDES_PATH . 'helpers/breadcrumbs.php' ) ) {
        require_once RANK_MATH_INCLUDES_PATH . 'helpers/breadcrumbs.php';
    }

    $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
    $args = [
        'post_type'      => 'post',
        'posts_per_page' => 5,
        'paged'          => $page,
        'post_status'    => 'publish',
    ];

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        global $wp_query;
        $original_query = $wp_query;
        $wp_query = $query;

        ob_start();
        while ($query->have_posts()) : $query->the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('ajax-loaded-card'); ?>>
                <div class="inside-article">
                    <header class="entry-header">
                        <?php
                        if (function_exists('gp_get_full_category_breadcrumb')) {
                            echo gp_get_full_category_breadcrumb(get_the_ID());
                        } else {
                            echo '<div class="gp-post-category">' . get_the_category_list(', ') . '</div>';
                        }
                        the_title(sprintf('<h2 class="entry-title" itemprop="headline"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>');
                        ?>
                    </header>
                    <?php
                    // 이미지 출력 로직
                    if (has_post_thumbnail()) {
                        $post_id = get_the_ID();
                        $thumbnail_id = get_post_thumbnail_id($post_id);
                        $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id);
                        $image_src_data = wp_get_attachment_image_src($thumbnail_id, 'medium_large');

                        if ($image_src_data) {
                            $image_url = $image_src_data[0];
                            $width = $image_src_data[1];
                            $height = $image_src_data[2];
                            $placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

                            $image_html = sprintf(
                                '<img src="%s" data-src="%s" alt="%s" width="%d" height="%d" class="lazy-load">',
                                esc_url($placeholder_src),
                                esc_url($image_url),
                                esc_attr($image_alt),
                                esc_attr($width),
                                esc_attr($height)
                            );
                            echo '<div class="post-image"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $image_html . '</a></div>';
                        }
                    }
                    ?>
                    <div class="entry-summary" itemprop="text">
                        <?php echo wp_kses_post(wp_trim_words(get_the_excerpt(), 60, '...')); ?>
                    </div>
                    <?php
                    if (function_exists('gp_read_more_btn_output')) {
                        gp_read_more_btn_output();
                    }
                    if (function_exists('gp_add_tags_to_list')) {
                        gp_add_tags_to_list();
                    }
                    if (function_exists('gp_add_star_rating_to_list')) {
                        gp_add_star_rating_to_list();
                    }
                    ?>
                    <div class="entry-meta">
                    </div>
                </div>
            </article>
            <?php
            if (function_exists('econarc_homepage_in_feed_ad')) {
                econarc_homepage_in_feed_ad();
            }
        endwhile;
        
        $wp_query = $original_query;
        wp_reset_postdata();
        $html_output = ob_get_clean();

        $load_more_button_html = '';
        if ($query->max_num_pages > $page) {
            $load_more_button_html = '<div class="load-more-container gp-posts-loader-container"><button id="gp-posts-loader-btn" class="gp-load-more-btn gp-posts-loader-btn">Load More</button></div>';
        }
        
        wp_send_json_success(['html' => $html_output, 'button_html' => $load_more_button_html]);

    } else {
        wp_send_json_success(['html' => '']);
    }
    wp_die();
}
// v2 액션과 v2 함수를 연결
add_action('wp_ajax_load_more_posts_v2', 'gp_load_more_posts_ajax_handler_v2');
add_action('wp_ajax_nopriv_load_more_posts_v2', 'gp_load_more_posts_ajax_handler_v2');

// ===================================================================
//         ★ 아카이브 '더 보기' 핸들러 ★
// ===================================================================
function gp_load_more_archive_posts_handler() {
    check_ajax_referer('load_more_archive_posts_nonce', 'nonce');

    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $query_vars = isset($_POST['query_vars']) ? json_decode(stripslashes($_POST['query_vars']), true) : [];

    $query_vars['paged'] = $page;
    $query_vars['posts_per_page'] = 10; // Load 10 posts per click

    $query = new WP_Query($query_vars);

    if ($query->have_posts()) {
        ob_start();
        while ($query->have_posts()) : $query->the_post();
            // Using the same card structure as the homepage
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('ajax-loaded-card'); ?>>
                <div class="inside-article">
                    <header class="entry-header">
                        <?php
                        if (function_exists('gp_get_full_category_breadcrumb')) {
                            echo gp_get_full_category_breadcrumb(get_the_ID());
                        } else {
                            echo '<div class="gp-post-category">' . get_the_category_list(', ') . '</div>';
                        }
                        the_title(sprintf('<h2 class="entry-title" itemprop="headline"><a href="%s" rel="bookmark">', esc_url(get_permalink())), '</a></h2>');
                        ?>
                    </header>
                    <?php
                    if (has_post_thumbnail()) {
                        $post_id = get_the_ID();
                        $thumbnail_id = get_post_thumbnail_id($post_id);
                        $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id);
                        $image_src_data = wp_get_attachment_image_src($thumbnail_id, 'medium_large');

                        if ($image_src_data) {
                            $image_url = $image_src_data[0];
                            $width = $image_src_data[1];
                            $height = $image_src_data[2];
                            $placeholder_src = "data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7";

                            $image_html = sprintf(
                                '<img src="%s" data-src="%s" alt="%s" width="%d" height="%d" class="lazy-load">',
                                esc_url($placeholder_src),
                                esc_url($image_url),
                                esc_attr($image_alt),
                                esc_attr($width),
                                esc_attr($height)
                            );
                            echo '<div class="post-image"><a href="' . esc_url(get_permalink()) . '" rel="bookmark">' . $image_html . '</a></div>';
                        }
                    }
                    ?>
                    <div class="entry-summary" itemprop="text">
                        <?php echo wp_kses_post(wp_trim_words(get_the_excerpt(), 60, '...')); ?>
                    </div>
                    <?php
                    if (function_exists('gp_read_more_btn_output')) {
                        gp_read_more_btn_output();
                    }
                    if (function_exists('gp_add_tags_to_list')) {
                        gp_add_tags_to_list();
                    }
                    if (function_exists('gp_add_star_rating_to_list')) {
                        gp_add_star_rating_to_list();
                    }
                    ?>
                    <div class="entry-meta">
                    </div>
                </div>
            </article>
            <?php
        endwhile;
        wp_reset_postdata();
        $html_output = ob_get_clean();

        wp_send_json_success(['html' => $html_output, 'has_more' => $query->max_num_pages > $page]);

    } else {
        wp_send_json_success(['html' => '', 'has_more' => false]);
    }
    wp_die();
}
add_action('wp_ajax_load_more_archive_posts', 'gp_load_more_archive_posts_handler');
add_action('wp_ajax_nopriv_load_more_archive_posts', 'gp_load_more_archive_posts_handler');