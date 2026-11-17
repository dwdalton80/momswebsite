<?php
/**
 * Wee Time, Me Time theme.
 *
 * The look comes from the approved preview: preview.css holds its components,
 * site.css adds what WordPress pages need (locked cards, the grown-up check, tiles).
 * Fonts are bundled in the theme so no page calls a font service.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/preview.css', 'assets/css/site.css' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$v = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'wee-time-preview', get_theme_file_uri( 'assets/css/preview.css' ), array(), $v );
	wp_enqueue_style( 'wee-time-site', get_theme_file_uri( 'assets/css/site.css' ), array( 'wee-time-preview' ), $v );
} );
