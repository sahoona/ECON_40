<?php
/**
 * GP Child Theme Functions (Final Version)
 *
 * @package    GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// -----------------------------------------------------------------------------
// Core Theme Setup
// -----------------------------------------------------------------------------

// 자식 테마의 모든 기능 파일을 불러옵니다.
require_once get_stylesheet_directory() . '/includes/core-setup.php';
require_once get_stylesheet_directory() . '/includes/assets.php';
require_once get_stylesheet_directory() . '/includes/optimization.php';
require_once get_stylesheet_directory() . '/includes/seo.php';
require_once get_stylesheet_directory() . '/includes/layout-hooks.php';
require_once get_stylesheet_directory() . '/includes/template-tags.php';
require_once get_stylesheet_directory() . '/includes/toc.php';
require_once get_stylesheet_directory() . '/includes/post-features.php';
require_once get_stylesheet_directory() . '/includes/related-posts.php';
require_once get_stylesheet_directory() . '/includes/ajax-handlers.php';
require_once get_stylesheet_directory() . '/includes/helpers.php';
require_once get_stylesheet_directory() . '/includes/admin.php';
require_once get_stylesheet_directory() . '/includes/customizer.php';
require_once get_stylesheet_directory() . '/includes/widgets.php';

function gp_child_add_csp_headers( $headers ) {
    $headers['Content-Security-Policy'] = "frame-ancestors 'self' https://www.google.com";
    return $headers;
}
add_filter( 'wp_headers', 'gp_child_add_csp_headers' );

function gp_child_add_google_api_script_to_header() {
    echo '<script src="https://accounts.google.com/gsi/client" async defer></script>';
}
add_action( 'wp_head', 'gp_child_add_google_api_script_to_header', 1 );
require_once get_stylesheet_directory() . '/components/ads/ads.php';
require_once get_stylesheet_directory() . '/includes/comments.php';
require_once get_stylesheet_directory() . '/includes/posts.php';

/**
 * =============================================================================
 * [ARIA 오류 최종 해결책]
 * 워드프레스가 페이지 생성을 마친 후, 최종 HTML을 브라우저로 보내기 직전에
 * 문제가 되는 부분을 직접 찾아 수정하는 글로벌 필터입니다.
 * 이 방법은 테마의 복잡한 실행 순서나 캐시 문제와 상관없이 100% 동작합니다.
 * =============================================================================
 */

// 페이지 로딩 초기에 출력 버퍼링을 시작합니다.
function gp_child_start_html_buffer() {
    ob_start( 'gp_child_final_html_fix' );
}
add_action( 'after_setup_theme', 'gp_child_start_html_buffer' );

// 페이지 로딩 마지막에 버퍼링된 HTML을 받아와 수정합니다.
function gp_child_final_html_fix( $buffer ) {
    // 문제가 되는 HTML 문자열을 찾습니다.
    $find_string = '<footer class="entry-meta" aria-label="항목 메타">';

    // 수정될 올바른 HTML 문자열을 정의합니다. (role="group" 추가)
    $replace_string = '<footer class="entry-meta" role="group" aria-label="항목 메타">';

    // 버퍼에 담긴 전체 HTML 내용에서 문제가 되는 부분을 찾아 교체합니다.
    $buffer = str_replace( $find_string, $replace_string, $buffer );

    // 수정된 최종 HTML을 반환합니다.
    return $buffer;
}


// -----------------------------------------------------------------------------
// 기타 테마 설정 (기존 코드는 그대로 유지)
// -----------------------------------------------------------------------------

if ( ! function_exists( 'gp_child_unique_preload_fonts' ) ) {
    function gp_child_unique_preload_fonts() {
        $font_path = get_stylesheet_directory_uri() . '/assets/fonts/';
        $fonts_to_preload = [ 'Pretendard-Regular.woff2', 'Pretendard-Bold.woff2', 'Pretendard-SemiBold.woff2' ];
        foreach ( $fonts_to_preload as $font ) {
            echo '<link rel="preload" href="' . esc_url( $font_path . $font ) . '" as="font" type="font/woff2" crossorigin="anonymous">';
        }
    }
}
add_action( 'wp_head', 'gp_child_unique_preload_fonts', 1 );

function gp_child_modify_archive_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_archive() ) {
        $query->set( 'posts_per_page', 5 );
    }
}
add_action( 'pre_get_posts', 'gp_child_modify_archive_query' );

function gp_child_add_initial_load_more_button() {
    if ( is_home() || is_front_page() || is_archive() ) {
        global $wp_query;
        if ( $wp_query->max_num_pages > 1 ) {
            echo '<div class="load-more-container gp-posts-loader-container"><button id="gp-posts-loader-btn" class="gp-load-more-btn gp-posts-loader-btn">Load More</button></div>';
        }
    }
}
add_action( 'generate_after_loop', 'gp_child_add_initial_load_more_button' );

function gp_child_disable_comments_on_homepage() {
    if ( is_front_page() || is_home() ) {
        remove_action( 'generate_after_entry_content', 'generate_do_comments_template', 15 );
    }
}
add_action( 'wp', 'gp_child_disable_comments_on_homepage' );