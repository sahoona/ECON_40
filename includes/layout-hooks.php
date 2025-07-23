<?php
/**
 * Layout hooks and filters (Final Cleaned Up Version)
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// -----------------------------------------------------------------------------
// 테마 레이아웃 설정
// -----------------------------------------------------------------------------

function gp_layout_setup() {
    // 자식 테마에서 새로운 기능을 완벽하게 제어하기 위해, GeneratePress의 기본 기능들을 비활성화합니다.
    remove_action( 'generate_after_entry_title', 'generate_post_meta' );
    remove_action( 'generate_after_entry_header', 'generate_post_meta' );
    remove_action( 'generate_after_entry_header', 'generate_post_image' );
    add_filter( 'generate_show_post_navigation', '__return_false' );

    // 자식 테마의 새로운 함수들을 올바른 위치(훅)에 추가합니다.
    add_action( 'generate_before_entry_title', 'gp_breadcrumb_output', 5 );
    add_action( 'generate_after_entry_title', 'gp_meta_after_title', 10 );
    add_action( 'generate_after_entry_header', 'gp_featured_image_output', 15 );
    add_action( 'generate_after_entry_header', 'gp_insert_toc', 20 );
    add_action( 'generate_after_entry_content', 'gp_child_display_after_content_widget_area', 8 );
    add_action( 'generate_after_entry_content', 'gppress_tags_before_related', 9);
    add_action( 'generate_after_entry_content', 'gp_render_post_footer_sections', 11 );
    add_action( 'generate_after_entry_content', 'gp_series_posts_output', 15 );
    add_action( 'generate_after_entry_content', 'gp_custom_post_navigation_output', 20 );
    add_action( 'generate_before_entry_content', 'gp_featured_image_output', 5 );
    add_action( 'wp_footer', 'gp_add_footer_elements_and_scripts' );
}
add_action( 'wp', 'gp_layout_setup' );


// -----------------------------------------------------------------------------
// 레이아웃 수정을 위한 각종 필터
// -----------------------------------------------------------------------------

// 기본 카테고리 링크는 자식 테마에서 직접 제어하므로 비활성화합니다.
add_filter( 'generate_show_categories', '__return_false' );

// 게시물 하단 푸터에 기본적으로 표시되는 항목들을 제거합니다. (이것이 텅 빈 footer의 원인이 됨)
add_filter( 'generate_footer_entry_meta_items', function($items) {
    return array_diff($items, ['categories', 'tags', 'comments']);
} );

// 요약글 관련 필터들
add_filter( 'excerpt_length', function($length) { return 55; }, 999 );
add_filter( 'generate_excerpt_more_output', function() { return ' …'; } );

function gp_add_elements_to_excerpt( $excerpt ) {
    if ( is_singular() || ! in_the_loop() ) {
        return $excerpt;
    }
    ob_start();
    gp_read_more_btn_output();
    gp_add_tags_to_list();
    gp_add_star_rating_to_list();
    $additional_elements = ob_get_clean();
    return $excerpt . $additional_elements;
}
add_filter( 'the_excerpt', 'gp_add_elements_to_excerpt' );

/**
 * 포스트 레이아웃 조정을 위한 CSS 주입
 */
function gp_child_inject_single_post_layout_css() {
    if ( is_singular() ) {
        $css = '
            <style>
/*
 * [최종 레이아웃 수정]
 * 싱글 포스트의 모든 콘텐츠 컨테이너를 제어하여
 * 디자인 통일성과 가독성을 확보합니다.
 */

/* 1. 헤더, 대표 이미지, 본문 영역의 내부 컨테이너를 모두 선택합니다. */
.single-post .entry-header > .grid-container,
.single-post .featured-image > .grid-container,
.single-post .entry-content {
    /* 2. 최대 너비를 지정하여 너무 넓어지지 않게 합니다. */
    max-width: var(--container-max-width, 840px);

    /* 3. 좌우 마진을 auto로 주어 가운데 정렬합니다. */
    margin-left: auto;
    margin-right: auto;

    /* 4. 좌우에 일관된 여백을 적용합니다. */
    padding-left: 20px;
    padding-right: 20px;
}

/*
 * 일부 요소에 !important가 필요할 경우를 대비한 추가 규칙.
 * 위의 코드가 동작하지 않을 경우 이 규칙을 사용합니다.
 */
.single-post .inside-article {
    max-width: var(--container-max-width, 840px) !important;
    margin-left: auto !important;
    margin-right: auto !important;
}

/*
 * 모바일 화면(768px 이하)에서는 패딩을 약간 줄여줍니다.
 */
@media (max-width: 768px) {
    .single-post .entry-header > .grid-container,
    .single-post .featured-image > .grid-container,
    .single-post .entry-content {
        padding-left: 15px;
        padding-right: 15px;
    }

    .featured-image img,
    .entry-content .wp-block-image img {
        border-radius: 12px;
    }
}
            </style>
        ';
        echo $css;
    }
}
add_action('wp_head', 'gp_child_inject_single_post_layout_css', 999);