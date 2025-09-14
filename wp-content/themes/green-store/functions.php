<?php
  // Exit if accessed directly
  if ( !defined( 'ABSPATH' ) )exit;


  function green_store_settings( $values ) {

    $values[ 'primary_color' ] = '#2ab60d';
    $values[ 'secondary_color' ] = '#ffdc00';
    $values[ 'heading_font' ] = 'Jost';
    $values[ 'body_font' ] = 'Poppins';

    $values[ 'woo_bar_color' ] = '#000';
    $values[ 'woo_bar_bg_color' ] = '#fafafa';
    $values[ 'woo_category_title' ] = esc_html__( 'Top Categories', "green-store" );

    $values[ 'preloader_enabled' ] = false;

    $values[ 'logo_width' ] = 130;
    $values[ 'layout_width' ] = 1280;

    $values[ 'header_layout' ] = 'woocommerce-bar';
    $values[ 'menu_layout' ] = 'default';
    $values[ 'enable_search' ] = true;
    $values[ 'ed_social_links' ] = true;

    $values[ 'subscription_shortcode' ] = '';

    $values[ 'enable_top_bar' ] = true;
    $values[ 'top_bar_left_content' ] = 'menu';
    $values[ 'top_bar_left_text' ] = esc_html__( 'edit top bar text', "green-store" );
    $values[ 'top_bar_right_content' ] = 'menu_social';
    $values[ 'enable_top_bar' ] = true;
    $values[ 'topbar_bg_color' ] = '#0abe1e';
    $values[ 'topbar_text_color' ] = '#e7e7e7';


    $values[ 'footer_text_color' ] = '#000000';
    $values[ 'footer_color' ] = '#F4F4F4';
    $values[ 'footer_link' ] = 'https://gradientthemes.com/';
    $values[ 'footer_copyright' ] = esc_html__( 'A theme by GradientThemes', "green-store" );

    $values[ 'page_sidebar_layout' ] = 'right-sidebar';
    $values[ 'post_sidebar_layout' ] = 'right-sidebar';
    $values[ 'layout_style' ] = 'right-sidebar';
    $values[ 'woo_sidebar_layout' ] = 'left-sidebar';

    return $values;

  }


  add_filter( 'best_shop_settings', 'green_store_settings' );


  /*
   * Add default header image
   */

  function green_store_header_style() {
    add_theme_support(
      'custom-header',
      apply_filters(
        'green_store_custom_header_args',
        array(
          'default-text-color' => '#000000',
          'width' => 1920,
          'height' => 760,
          'flex-height' => true,
          'video' => true,
          'wp-head-callback' => 'green_store_header_style',
        )
      )
    );
    add_theme_support( 'automatic-feed-links' );
  }

  add_action( 'after_setup_theme', 'green_store_header_style' );


  //  PARENT ACTION

  if ( !function_exists( 'green_store_cfg_locale_css' ) ):
    function green_store_cfg_locale_css( $uri ) {
      if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
        $uri = get_template_directory_uri() . '/rtl.css';
      return $uri;
    }
  endif;

  add_filter( 'locale_stylesheet_uri', 'green_store_cfg_locale_css' );

  if ( !function_exists( 'green_store_cfg_parent_css' ) ):
    function green_store_cfg_parent_css() {
      wp_enqueue_style( 'green_store_cfg_parent', trailingslashit( get_template_directory_uri() ) . 'style.css', array() );
    }
  endif;

  add_action( 'wp_enqueue_scripts', 'green_store_cfg_parent_css', 10 );

  // Add prealoder js
  function green_store_custom_scripts() {
    wp_enqueue_script( "green-store", get_stylesheet_directory_uri() . '/assests/preloader.js', array( 'jquery' ), '', true );
  }

  add_action( 'wp_enqueue_scripts', 'green_store_custom_scripts' );

  // END ENQUEUE PARENT ACTION

  if ( !function_exists( 'green_store_customize_register' ) ):
    /**
     * Add postMessage support for site title and description for the Theme Customizer.
     *
     * @param WP_Customize_Manager $wp_customize Theme Customizer object.
     */
    function green_store_customize_register( $wp_customize ) {

      $wp_customize->add_section(
        'subscription_settings',
        array(
          'title' => esc_html__( 'Email Subscription', "green-store" ),
          'priority' => 199,
          'capability' => 'edit_theme_options',
          'panel' => 'theme_options',
          'description' => __( 'Add email subscription plugin shortcode.', "green-store" ),

        )
      );

      /** Footer Copyright */
      $wp_customize->add_setting(
        'subscription_shortcode',
        array(
          'default' => best_shop_default_settings( 'subscription_shortcode' ),
          'sanitize_callback' => 'sanitize_text_field',
          'transport' => 'postMessage'
        )
      );

      $wp_customize->add_control(
        'subscription_shortcode',
        array(
          'label' => esc_html__( 'Subscription Plugin Shortcode', "green-store" ),
          'section' => 'subscription_settings',
          'type' => 'text',
        )
      );

      //preloader
      $wp_customize->add_section(
        'preloader_settings',
        array(
          'title' => esc_html__( 'Preloader', "green-store" ),
          'priority' => 200,
          'capability' => 'edit_theme_options',
          'panel' => 'theme_options',

        )
      );

      $wp_customize->add_setting(
        'preloader_enabled',
        array(
          'default' => best_shop_default_settings( 'preloader_enabled' ),
          'sanitize_callback' => 'best_shop_sanitize_checkbox',
          'transport' => 'refresh'
        )
      );

      $wp_customize->add_control(
        'preloader_enabled',
        array(
          'label' => esc_html__( 'Enable Preloader', "green-store" ),
          'section' => 'preloader_settings',
          'type' => 'checkbox',
        )
      );


    }
  endif;
  add_action( 'customize_register', 'green_store_customize_register' );