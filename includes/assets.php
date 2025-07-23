<?php
/**
 * Enqueue assets for GP Child Theme (Final Fixed Version)
 *
 * This file handles all CSS and JS loading for the theme.
 * It has been corrected to properly enqueue Google AdSense and set dependencies.
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// ===================================================================
//         ★ 헤더에 로드될 에셋 (CSS - 수정 없음) ★
// ===================================================================
function gp_child_enqueue_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );
	$theme_dir     = get_stylesheet_directory();

	// Base GeneratePress Style.
	wp_enqueue_style( 'generatepress-style', get_template_directory_uri() . '/style.css' );

	// Child theme style.css.
	wp_enqueue_style( 'gp-child-style', get_stylesheet_uri(), array( 'generatepress-style' ), file_exists( $theme_dir . '/style.css' ) ? filemtime( $theme_dir . '/style.css' ) : $theme_version );

	// --- 모든 CSS 파일 순차적 로드 ---.
	$fonts_path = '/assets/css/components/fonts.css';
	if ( file_exists( $theme_dir . $fonts_path ) ) {
		wp_enqueue_style( 'gp-fonts-style', get_stylesheet_directory_uri() . $fonts_path, array( 'gp-child-style' ), filemtime( $theme_dir . $fonts_path ) );
	}

	$css_files = array(
		'variables'       => '/assets/css/components/variables.css',
		'main'            => '/assets/css/main.css',
		'layout'          => '/assets/css/layout.css',
		'header'          => '/assets/css/components/header.css',
		'sidebar'         => '/assets/css/components/sidebar.css',
		'responsive'      => '/assets/css/components/responsive.css',
		'dark_mode'       => '/assets/css/components/dark_mode.css',
		'lang-switcher'   => '/assets/css/components/language-switcher.css',
		'lang-switcher-p' => '/assets/css/components/language-switcher-partial.css',
		'back-to-top'     => '/assets/css/components/back-to-top.css',
		'ads'             => '/components/ads/ads.css',
		'content'         => '/assets/css/components/content.css',
		'post-nav'        => '/assets/css/components/post-navigation.css',
	);

	$last_handle = 'gp-fonts-style';
	foreach ( $css_files as $handle => $path ) {
		if ( file_exists( $theme_dir . $path ) ) {
			$file_handle = 'gp-' . $handle . '-style';
			wp_enqueue_style( $file_handle, get_stylesheet_directory_uri() . $path, array( $last_handle ), filemtime( $theme_dir . $path ) );
			$last_handle = $file_handle;
		}
	}

	// --- 조건부 CSS 로드 ---.
	if ( is_singular( 'post' ) ) {
		$toc_path = '/assets/css/components/table-of-contents.css';
		if ( file_exists( $theme_dir . $toc_path ) ) {
			wp_enqueue_style( 'gp-toc-style', get_stylesheet_directory_uri() . $toc_path, array( $last_handle ), filemtime( $theme_dir . $toc_path ) );
		}
	}
	if ( is_singular( 'post' ) || is_singular( 'series' ) || is_tax( 'series_category' ) ) {
		$series_path = '/assets/css/components/series.css';
		if ( file_exists( $theme_dir . $series_path ) ) {
			wp_enqueue_style( 'gp-series-style', get_stylesheet_directory_uri() . $series_path, array( $last_handle ), filemtime( $theme_dir . $series_path ) );
			$last_handle = 'gp-series-style';
		}
		$yarpp_custom_css_path = '/yarpp-custom.css';
		if ( file_exists( $theme_dir . $yarpp_custom_css_path ) ) {
			wp_enqueue_style( 'gp-yarpp-custom-style', get_stylesheet_directory_uri() . $yarpp_custom_css_path, array( $last_handle ), filemtime( $theme_dir . $yarpp_custom_css_path ) );
		}
	}
	if ( is_singular() && comments_open() ) {
		$comments_path = '/assets/css/components/comments.css';
		if ( file_exists( $theme_dir . $comments_path ) ) {
			wp_enqueue_style( 'gp-comments-style', get_stylesheet_directory_uri() . $comments_path, array( $last_handle ), filemtime( $theme_dir . $comments_path ) );
		}
	}

	// --- 인라인 & 비동기 스크립트 (헤더) ---.
	$custom_css = ':root { --theme-version: "' . esc_attr( $theme_version ) . '"; }';
	wp_add_inline_style( 'gp-child-style', $custom_css );
}
add_action( 'wp_enqueue_scripts', 'gp_child_enqueue_assets' );


// ===================================================================
//         ★ 푸터에 로드될 에셋 (JS 충돌 해결 최종 버전) ★
// ===================================================================
function gp_child_enqueue_footer_scripts() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$ad_client = get_theme_mod( 'econarc_ad_client' );

	// ★★★ [수정 1] 애드센스 스크립트를 '등록'이 아닌 '추가(enqueue)' 하여 페이지에 확실히 로드합니다. ★★★
	if ( ! empty( trim( $ad_client ) ) ) {
		wp_enqueue_script(
			'google-adsense', // 핸들 이름.
			'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-' . esc_attr( trim( $ad_client ) ), // 스크립트 주소.
			array(),          // 의존성 없음.
			null,             // 버전 정보 없음.
			true              // true: 푸터에 로드.
		);
		// 이 스크립트는 비동기로 로드되어야 성능에 좋습니다.
		wp_script_add_data( 'google-adsense', 'async', true );
	}

	// 1. loadMore.js 스크립트를 먼저 '등록'만 합니다.
	$loadmore_js_path = '/assets/js/modules/loadMore.js';
	if ( file_exists( $theme_dir . $loadmore_js_path ) ) {
		wp_register_script(
			'gp-child-loadmore',
			$theme_uri . $loadmore_js_path,
			array( 'jquery' ),
			filemtime( $theme_dir . $loadmore_js_path ),
			true
		);

		// 2. 등록된 스크립트에 Nonce 및 기타 정보를 전달합니다.
		$post_id  = is_singular() ? get_the_ID() : 0;
		$settings = array(
			'ajax_url'                  => admin_url( 'admin-ajax.php' ),
			'_wpnonce_v2'               => wp_create_nonce( 'load_more_posts_nonce_v2' ),
			'load_more_archive_nonce'   => wp_create_nonce( 'load_more_archive_posts_nonce' ), // New nonce for archive.
			'load_more_series_nonce'    => wp_create_nonce( 'load_more_series_nonce' ),
			'gp_reactions_nonce'        => wp_create_nonce( 'gp_reactions_nonce' ),
			'gp_star_rating_nonce'      => wp_create_nonce( 'gp_star_rating_nonce' ),
			'current_post_id'           => $post_id,
		);

		if ( is_archive() ) {
			global $wp_query;
			$settings['query_vars'] = json_encode( $wp_query->query_vars );
		}

		wp_localize_script( 'gp-child-loadmore', 'gp_settings', $settings );
	}

	// 3. 다른 모든 JS 모듈들을 '등록'만 합니다.
	$js_modules = array(
		'toc'                  => '/assets/js/modules/toc.js',
		'darkMode'             => '/assets/js/modules/darkMode.js',
		'floatingButtons'      => '/assets/js/modules/floatingButtons.js',
		'sidebar'              => '/assets/js/modules/sidebar.js',
		'copyUrl'              => '/assets/js/modules/copyUrl.js',
		'progressBar'          => '/assets/js/modules/progressBar.js',
		'starRating'           => '/assets/js/modules/starRating.js',
		'reactions'            => '/assets/js/modules/reactions.js',
		'lazyLoad'             => '/assets/js/modules/lazyLoad.js',
		'series'               => '/assets/js/modules/series.js',
		'archive-load-more'    => '/assets/js/modules/archive-load-more.js', // Add this line.
		'ads'                  => '/components/ads/ads.js',
		'utils'                => '/assets/js/modules/utils.js',
		'customize-dependency' => '/assets/js/modules/customize-dependency.js',
	);

	$deps = array( 'jquery' );
	if ( file_exists( $theme_dir . '/assets/js/vendor/clamp.min.js' ) ) {
		wp_register_script( 'clamp-js', $theme_uri . '/assets/js/vendor/clamp.min.js', array(), '0.5.1', true );
		$deps[] = 'clamp-js';
	}

	foreach ( $js_modules as $handle => $path ) {
		if ( file_exists( $theme_dir . $path ) ) {
			$file_handle = 'gp-module-' . $handle;
			// ★★★ [수정 2] ads.js가 google-adsense 스크립트에 의존하도록 명시적으로 설정합니다. ★★★
			$dependency = ( 'ads' === $handle && wp_script_is( 'google-adsense' ) ) ? array( 'jquery', 'google-adsense' ) : array( 'jquery' );
			wp_register_script( $file_handle, $theme_uri . $path, $dependency, filemtime( $theme_dir . $path ), true );
			$deps[] = $file_handle;
		}
	}

	// 4. 마지막으로 main.js를 로드하면서, 'loadMore'를 포함한 모든 모듈을 의존성으로 지정하여 한 번에 로드합니다.
	if ( file_exists( $theme_dir . '/assets/js/main.js' ) ) {
		$main_deps = array_merge( array( 'jquery', 'gp-child-loadmore' ), $deps );

		// ★★★ [수정 3] 불필요한 중복 체크 로직을 제거하여 코드를 정리합니다. ★★★
		// if (wp_script_is('google-adsense', 'enqueued')) { ... } 부분 삭제.

		wp_enqueue_script( 'gp-main-script', $theme_uri . '/assets/js/main.js', $main_deps, filemtime( $theme_dir . '/assets/js/main.js' ), true );
	} else {
		// main.js가 없다면, 등록된 스크립트들을 개별적으로 로드합니다.
		wp_enqueue_script( 'gp-child-loadmore' );
		foreach ( $deps as $dep_handle ) {
			wp_enqueue_script( $dep_handle );
		}
	}
}
add_action( 'wp_footer', 'gp_child_enqueue_footer_scripts' );


// ===================================================================
//         ★ 나머지 모든 함수들 (수정 없음) ★
// ===================================================================
function gp_child_enqueue_admin_assets() {
	$theme_version    = wp_get_theme()->get( 'Version' );
	$theme_dir        = get_stylesheet_directory();
	$admin_style_path = '/assets/css/admin-style.css';

	if ( file_exists( $theme_dir . $admin_style_path ) ) {
		wp_enqueue_style(
			'gp-child-admin-style',
			get_stylesheet_directory_uri() . $admin_style_path,
			array(),
			filemtime( $theme_dir . $admin_style_path )
		);
	}
}
add_action( 'admin_enqueue_scripts', 'gp_child_enqueue_admin_assets' );

function gp_child_preload_fonts() {
	$font_urls = array(
		get_stylesheet_directory_uri() . '/assets/fonts/Pretendard-Regular.woff2',
		get_stylesheet_directory_uri() . '/assets/fonts/Pretendard-Medium.woff2',
		get_stylesheet_directory_uri() . '/assets/fonts/Pretendard-SemiBold.woff2',
		get_stylesheet_directory_uri() . '/assets/fonts/Pretendard-Bold.woff2',
		get_stylesheet_directory_uri() . '/assets/fonts/Pretendard-ExtraBold.woff2',
		get_stylesheet_directory_uri() . '/assets/fonts/Pretendard-Black.woff2',
	);

	foreach ( $font_urls as $url ) {
		echo '<link rel="preload" href="' . esc_url( $url ) . '" as="font" type="font/woff2" crossorigin>';
	}
}
add_action( 'wp_head', 'gp_child_preload_fonts', 1 );

function gp_child_enqueue_customizer_scripts() {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	wp_enqueue_script(
		'gp-child-customizer-dependency',
		$theme_uri . '/assets/js/modules/customize-dependency.js',
		array( 'customize-controls' ),
		filemtime( $theme_dir . '/assets/js/modules/customize-dependency.js' ),
		true
	);

	wp_enqueue_script(
		'gp-child-customizer-fixes',
		$theme_uri . '/assets/js/admin/customizer-fixes.js',
		array( 'customize-controls', 'jquery' ),
		filemtime( $theme_dir . '/assets/js/admin/customizer-fixes.js' ),
		true
	);
}
add_action( 'customize_controls_enqueue_scripts', 'gp_child_enqueue_customizer_scripts' );

function gp_child_dark_mode_flicker_prevention() {
	?>
	<script>
		(function() {
			const isDarkMode = localStorage.getItem('darkMode') === 'true';
			if (isDarkMode) {
				document.documentElement.classList.add('dark-mode-active');
			}
		})();
	</script>
	<?php
}
add_action( 'wp_head', 'gp_child_dark_mode_flicker_prevention', 0 );