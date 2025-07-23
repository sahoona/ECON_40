<?php
/**
 * Ad functions
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * 모든 콘텐츠 블록을 기준으로 본문에 광고를 지능적으로 삽입하고, 'Advertisement' 라벨을 제거합니다.
 */
function econarc_insert_manual_ads( $content ) {
    // 단일 글 페이지가 아니거나, 관리자 페이지거나, 피드일 경우 실행하지 않음
    if ( !is_single() || is_admin() || is_feed() ) {
        return $content;
    }

    // 사용자 정의하기에서 설정값 불러오기
    $ads_enabled = get_theme_mod( 'econarc_ads_enabled', false );
    $ad_client   = get_theme_mod( 'econarc_ad_client' );
    $ad_slot     = get_theme_mod( 'econarc_ad_slot' );

    // 광고가 비활성화되었거나, 필수 ID 값이 없으면 실행하지 않음
    if ( ! $ads_enabled || empty( trim( $ad_client ) ) || empty( trim( $ad_slot ) ) ) {
        return $content;
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors( true );
    if ( ! @$dom->loadHTML( '<?xml encoding="UTF-8"><div id="content-wrapper">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ) ) {
        libxml_clear_errors();
        return $content;
    }
    libxml_clear_errors();
    $xpath = new DOMXPath( $dom );

    // p, figure, ul, ol, pre, blockquote 등 주요 콘텐츠 블록을 모두 계산 대상으로 포함
    $content_blocks = $xpath->query( '//*[@id="content-wrapper"]/p | //*[@id="content-wrapper"]/figure | //*[@id="content-wrapper"]/ul | //*[@id="content-wrapper"]/ol | //*[@id="content-wrapper"]/pre | //*[@id="content-wrapper"]/blockquote' );

    $total_blocks = $content_blocks->length;

    // 콘텐츠 블록이 12개 미만이면 광고를 삽입하지 않음
    if ( $total_blocks < 12 ) {
        return $content;
    }

    // 광고를 삽입할 위치 계산 (20%, 45%, 70%, 90% 지점)
    $insertion_points = [
        floor( $total_blocks * 0.20 ),
        floor( $total_blocks * 0.45 ),
        floor( $total_blocks * 0.70 ),
        floor( $total_blocks * 0.90 ),
    ];
    $insertion_points = array_unique( $insertion_points );

    // 'Advertisement' 라벨이 제거된 광고 HTML 코드
    $ad_html_string = <<<HTML
    <div class="manual-ad-container">
        <ins class="adsbygoogle"
             style="display:block"
             data-ad-client="ca-pub-{$ad_client}"
             data-ad-slot="{$ad_slot}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
    </div>
    HTML;

    // 계산된 위치에 광고 삽입 (역순으로)
    foreach ( array_reverse( $insertion_points ) as $index ) {
        if ( $index > 0 && isset( $content_blocks[$index] ) ) {
            $target_node = $content_blocks[$index];

            // 안전장치: 바로 다음 노드가 h2, h3 태그이면 광고를 삽입하지 않음
            if ( $target_node->nextSibling && in_array( strtolower($target_node->nextSibling->nodeName), ['h2', 'h3'] ) ) {
                continue;
            }

            $ad_node = $dom->createDocumentFragment();
            @$ad_node->appendXML( $ad_html_string );

            if ($target_node->nextSibling) {
                $target_node->parentNode->insertBefore($ad_node, $target_node->nextSibling);
            } else {
                $target_node->parentNode->appendChild($ad_node);
            }
        }
    }

    // 임시 컨테이너 내부의 수정된 콘텐츠만 정확히 추출하여 반환
    $wrapper = $xpath->query('//*[@id="content-wrapper"]')->item(0);
    $new_content = '';
    if ($wrapper) {
        foreach ($wrapper->childNodes as $child) {
            $new_content .= $dom->saveHTML($child);
        }
    } else {
        return $content;
    }

    return $new_content;
}
add_filter( 'the_content', 'econarc_insert_manual_ads', 25 );

/**
 * [최종 버전] 목록 상단 광고 (스크립트 태그 제거)
 * JavaScript가 실행할 광고의 '자리'만 만듭니다.
 */
function econarc_homepage_top_ad() {
    // 아카이브, 홈페이지, 블로그 메인 페이지에서만 광고 표시
    if ( is_archive() || is_home() || is_front_page() ) {
        $ads_enabled = get_theme_mod( 'econarc_top_ad_enabled', false );
        $ad_client   = get_theme_mod( 'econarc_ad_client' );
        $ad_slot     = get_theme_mod( 'econarc_top_ad_slot' );

        // 광고가 활성화되었고, 필수 ID 값이 모두 있을 때만 광고 출력
        if ( $ads_enabled && ! empty( trim( $ad_client ) ) && ! empty( trim( $ad_slot ) ) ) {
            // 헤더 바로 아래, 메인 콘텐츠 시작 전에 광고 출력
            echo '<div class="manual-ad-container top-ad-container" style="margin-top: 20px; margin-bottom: 20px; text-align: center;"><ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-' . esc_attr(trim($ad_client)) . '" data-ad-slot="' . esc_attr($ad_slot) . '" data-ad-format="auto" data-full-width-responsive="true"></ins></div>';
        }
    }
}
// 훅을 generate_after_header로 변경하여 헤더 바로 아래에 광고가 위치하도록 함
add_action( 'generate_after_header', 'econarc_homepage_top_ad', 15 );


/**
 * [최종 버전] 카드 사이 인피드 광고 (스크립트 태그 제거)
 * JavaScript가 실행할 광고의 '자리'만 만듭니다.
 */
function econarc_homepage_in_feed_ad() {
    // 홈페이지, 블로그 메인, 아카이브 페이지가 아니면 실행 중지
    if ( ! ( is_front_page() || is_home() || is_archive() ) ) {
        return;
    }

    $ads_enabled = get_theme_mod( 'econarc_infeed_ad_enabled', false );
    if (!$ads_enabled) {
        return;
    }

    static $post_count = 0;
    $ad_frequency = 4; // 4번째 카드 뒤에 광고 삽입

    // 실제 'post' 타입일 때만 카운트를 증가시켜 정확도 높임
    if ( 'post' === get_post_type() ) {
        $post_count++;
    }

    if ( $post_count > 0 && $post_count % $ad_frequency == 0 ) {
        $ad_client = get_theme_mod( 'econarc_ad_client' );
        $ad_slot   = get_theme_mod( 'econarc_infeed_ad_slot' );

        // 필수 ID 값이 모두 있을 때만 광고 출력
        if ( ! empty( trim( $ad_client ) ) && ! empty( trim( $ad_slot ) ) ) {
            echo '<article class="post type-post status-publish format-standard hentry manual-ad-article"><div class="inside-article" style="padding:0; border:none; background:transparent;"><div class="manual-ad-container in-feed-ad"><ins class="adsbygoogle" style="display:block" data-ad-format="fluid" data-ad-layout-key="-fb+5w+4e-db+86" data-ad-client="ca-pub-' . esc_attr(trim($ad_client)) . '" data-ad-slot="' . esc_attr($ad_slot) . '"></ins></div></div></article>';
        }
    }
}
