<?php
/**
 * Customizer functions
 *
 * @package GP_Child_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Site additional info and SEO
if ( ! function_exists( 'gp_customize_register_additional_meta' ) ) {
    function gp_customize_register_additional_meta( $wp_customize ) {
        $wp_customize->add_section( 'gp_additional_meta_section', array(
            'title'    => __( '사이트 추가 정보 및 SEO', 'gp_child_theme' ),
            'priority' => 160,
        ) );
        $wp_customize->add_setting( 'website_schema_name', array(
            'type'              => 'theme_mod',
            'capability'        => 'edit_theme_options',
            'default'           => get_bloginfo( 'name' ),
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        $wp_customize->add_control( 'website_schema_name_control', array(
            'label'    => __( '웹사이트 스키마 - 이름', 'gp_child_theme' ),
            'section'  => 'gp_additional_meta_section',
            'settings' => 'website_schema_name',
            'type'     => 'text',
        ) );
        $wp_customize->add_setting( 'website_schema_url', array(
            'type'              => 'theme_mod',
            'capability'        => 'edit_theme_options',
            'default'           => home_url( '/' ),
            'sanitize_callback' => 'esc_url_raw',
        ) );
        $wp_customize->add_control( 'website_schema_url_control', array(
            'label'    => __( '웹사이트 스키마 - URL', 'gp_child_theme' ),
            'section'  => 'gp_additional_meta_section',
            'settings' => 'website_schema_url',
            'type'     => 'url',
        ) );
    }
    add_action( 'customize_register', 'gp_customize_register_additional_meta' );
}

// Footer color settings
if ( ! function_exists( 'gp_customize_register_footer_colors' ) ) {
    function gp_customize_register_footer_colors( $wp_customize ) {
        $wp_customize->add_section( 'gp_footer_colors_section', array(
            'title'    => __( '푸터 색상 설정', 'gp_child_theme' ),
            'priority' => 170,
        ) );

        // Footer background color
        $wp_customize->add_setting( 'footer_background_color', array(
            'default'           => '#121212',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_background_color_control', array(
            'label'    => __( '푸터 배경색', 'gp_child_theme' ),
            'section'  => 'gp_footer_colors_section',
            'settings' => 'footer_background_color',
        ) ) );

        // Footer text color
        $wp_customize->add_setting( 'footer_text_color', array(
            'default'           => '#999999',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_text_color_control', array(
            'label'    => __( '푸터 텍스트 색상', 'gp_child_theme' ),
            'section'  => 'gp_footer_colors_section',
            'settings' => 'footer_text_color',
        ) ) );

        // Footer link color
        $wp_customize->add_setting( 'footer_link_color', array(
            'default'           => '#ffffff',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_link_color_control', array(
            'label'    => __( '푸터 링크 색상', 'gp_child_theme' ),
            'section'  => 'gp_footer_colors_section',
            'settings' => 'footer_link_color',
        ) ) );

        // Footer border color
        $wp_customize->add_setting( 'footer_border_color', array(
            'default'           => '#FFC107',
            'sanitize_callback' => 'sanitize_hex_color',
            'transport'         => 'refresh',
        ) );
        $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'footer_border_color_control', array(
            'label'    => __( '푸터 상단 테두리 색상', 'gp_child_theme' ),
            'section'  => 'gp_footer_colors_section',
            'settings' => 'footer_border_color',
        ) ) );
    }
    add_action( 'customize_register', 'gp_customize_register_footer_colors' );
}

// Ad settings
function gp_customize_register_ad_settings( $wp_customize ) {
    $wp_customize->add_section( 'gp_ad_settings_section', array(
        'title'    => __( '광고 설정', 'gp_child_theme' ),
        'priority' => 180,
    ) );

    // Ad Client ID
    $wp_customize->add_setting( 'econarc_ad_client', array(
        'type'              => 'theme_mod',
        'capability'        => 'edit_theme_options',
        'default'           => '',
        'sanitize_callback' => 'gp_sanitize_ad_client',
    ) );
    $wp_customize->add_control( 'econarc_ad_client_control', array(
        'label'       => __( '애드센스 클라이언트 ID', 'gp_child_theme' ),
        'description' => __( "'ca-pub-' 접두사를 제외한 숫자 ID만 입력하세요.", 'gp_child_theme' ),
        'section'     => 'gp_ad_settings_section',
        'settings'    => 'econarc_ad_client',
        'type'        => 'text',
    ) );

    // --- 본문 내 광고 ---
    $wp_customize->add_setting( 'econarc_ads_enabled', [
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'econarc_ads_enabled_control', [
        'label'    => '본문 내 광고 활성화',
        'section'  => 'gp_ad_settings_section',
        'settings' => 'econarc_ads_enabled',
        'type'     => 'checkbox',
        'priority' => 20,
    ] );
    $wp_customize->add_setting( 'econarc_ad_slot', [
        'type'              => 'theme_mod',
        'capability'        => 'edit_theme_options',
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'econarc_ad_slot_control', [
        'label'           => '본문 내 광고 슬롯 ID',
        'section'         => 'gp_ad_settings_section',
        'settings'        => 'econarc_ad_slot',
        'type'            => 'text',
        'priority'        => 21,
        'customize_dependency' => [
            'controls' => ['econarc_ads_enabled_control'],
            'value'    => [true],
        ],
    ] );

    // --- 상단 광고 ---
    $wp_customize->add_setting( 'econarc_top_ad_enabled', [
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'econarc_top_ad_enabled_control', [
        'label'    => '상단 광고 활성화',
        'section'  => 'gp_ad_settings_section',
        'settings' => 'econarc_top_ad_enabled',
        'type'     => 'checkbox',
        'priority' => 30,
    ] );
    $wp_customize->add_setting( 'econarc_top_ad_slot', [
        'type'              => 'theme_mod',
        'capability'        => 'edit_theme_options',
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'econarc_top_ad_slot_control', [
        'label'           => '상단 광고 슬롯 ID',
        'section'         => 'gp_ad_settings_section',
        'settings'        => 'econarc_top_ad_slot',
        'type'            => 'text',
        'priority'        => 31,
        'customize_dependency' => [
            'controls' => ['econarc_top_ad_enabled_control'],
            'value'    => [true],
        ],
    ] );

    // --- 인피드 광고 ---
    $wp_customize->add_setting( 'econarc_infeed_ad_enabled', [
        'default'           => false,
        'sanitize_callback' => 'wp_validate_boolean',
        'transport'         => 'postMessage',
    ] );
    $wp_customize->add_control( 'econarc_infeed_ad_enabled_control', [
        'label'    => '인피드 광고 활성화',
        'section'  => 'gp_ad_settings_section',
        'settings' => 'econarc_infeed_ad_enabled',
        'type'     => 'checkbox',
        'priority' => 40,
    ] );
    $wp_customize->add_setting( 'econarc_infeed_ad_slot', [
        'type'              => 'theme_mod',
        'capability'        => 'edit_theme_options',
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    $wp_customize->add_control( 'econarc_infeed_ad_slot_control', [
        'label'           => '인피드 광고 슬롯 ID',
        'section'         => 'gp_ad_settings_section',
        'settings'        => 'econarc_infeed_ad_slot',
        'type'            => 'text',
        'priority'        => 41,
        'customize_dependency' => [
            'controls' => ['econarc_infeed_ad_enabled_control'],
            'value'    => [true],
        ],
    ] );
}
add_action( 'customize_register', 'gp_customize_register_ad_settings' );

/**
 * Sanitize Ad Client ID.
 *
 * @param string $input The input string.
 * @return string Sanitized string.
 */
function gp_sanitize_ad_client( $input ) {
    // Remove 'ca-pub-' prefix if present.
    $input = preg_replace( '/^ca-pub-/', '', $input );
    // Sanitize the remaining string as a text field.
    return sanitize_text_field( $input );
}

if ( ! function_exists( 'gp_output_customizer_header_meta' ) ) {
    function gp_output_customizer_header_meta() {
        $website_schema_name = get_theme_mod( 'website_schema_name', get_bloginfo( 'name' ) );
        $website_schema_url = get_theme_mod( 'website_schema_url', home_url( '/' ) );
        if ( ! empty( $website_schema_name ) && ! empty( $website_schema_url ) ) {
            $website_schema = array(
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => esc_html( $website_schema_name ),
                'url'      => esc_url( $website_schema_url ),
            );
            echo '<script type="application/ld+json" class="gp-website-schema-customizer">' . wp_json_encode( $website_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        }
    }
    add_action( 'wp_head', 'gp_output_customizer_header_meta', 6 );
}

/**
 * Force refresh transport for menu-related customizer settings.
 *
 * This ensures that the preview pane reloads automatically when a menu or its
 * items are modified. This is a more robust solution than relying on JavaScript
 * event binding, which can be unreliable.
 *
 * @param WP_Customize_Manager $wp_customize The Customizer manager object.
 */
function gp_child_force_menu_refresh_transport( $wp_customize ) {
    // Force refresh for menu locations
    $nav_menu_locations_setting = $wp_customize->get_setting( 'nav_menu_locations' );
    if ( $nav_menu_locations_setting ) {
        $nav_menu_locations_setting->transport = 'refresh';
    }

    // Force refresh for all individual menu settings
    $settings = $wp_customize->settings();
    foreach ( $settings as $setting ) {
        if ( 'nav_menu' === $setting->type || 'nav_menu_item' === $setting->type ) {
            $setting->transport = 'refresh';
        }
    }
}
add_action( 'customize_register', 'gp_child_force_menu_refresh_transport', 99 );

if ( ! function_exists( 'gp_apply_footer_color_css' ) ) {
    function gp_apply_footer_color_css() {
        $footer_background_color = get_theme_mod( 'footer_background_color', '#121212' );
        $footer_text_color = get_theme_mod( 'footer_text_color', '#999999' );
        $footer_link_color = get_theme_mod( 'footer_link_color', '#ffffff' );
        $footer_border_color = get_theme_mod( 'footer_border_color', '#FFC107' );

        $custom_css = "
            :root {
                --footer-background-color: {$footer_background_color};
                --footer-text-color: {$footer_text_color};
                --footer-link-color: {$footer_link_color};
                --footer-border-color: {$footer_border_color};
            }
            .site-footer-container .footer-grid { font-size: 0 !important; }
            .site-footer-container .footer-grid > div { display: inline-block !important; vertical-align: top !important; font-size: 1rem !important; box-sizing: border-box !important; padding: 0 20px !important; }
            .site-footer-container .footer-about { width: 40% !important; }
            .site-footer-container .footer-links { width: 20% !important; }
        ";
        wp_add_inline_style( 'gp-child-style', $custom_css );
    }
    add_action( 'wp_enqueue_scripts', 'gp_apply_footer_color_css' );
}

if ( ! function_exists( 'gp_output_customizer_header_meta' ) ) {
    function gp_output_customizer_header_meta() {
        $website_schema_name = get_theme_mod( 'website_schema_name', get_bloginfo( 'name' ) );
        $website_schema_url = get_theme_mod( 'website_schema_url', home_url( '/' ) );
        if ( ! empty( $website_schema_name ) && ! empty( $website_schema_url ) ) {
            $website_schema = array(
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => esc_html( $website_schema_name ),
                'url'      => esc_url( $website_schema_url ),
            );
            echo '<script type="application/ld+json" class="gp-website-schema-customizer">' . wp_json_encode( $website_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        }
    }
    add_action( 'wp_head', 'gp_output_customizer_header_meta', 6 );
}
