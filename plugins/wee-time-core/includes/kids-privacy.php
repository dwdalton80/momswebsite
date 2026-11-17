<?php
/**
 * Kids Corner privacy.
 *
 * On any Kids Corner view: no analytics, no embeds from other sites, no emoji CDN,
 * no referrer sent on outbound links, and headers that keep the browser from
 * loading anything third-party. Analytics for the moms pages hook into
 * do_action( 'wt_moms_analytics' ), which never fires on a kids view.
 */

defined( 'ABSPATH' ) || exit;

function wt_kids_page_id() {
	return (int) wt_option( 'kids_page', 0 );
}

/** True on the Kids Corner page, its child pages, every kids item and age archive. */
function wt_is_kids_view() {
	if ( is_admin() ) {
		return false;
	}
	if ( is_singular( wt_kids_post_types() ) || is_post_type_archive( wt_kids_post_types() ) || is_tax( 'wt_age' ) ) {
		return true;
	}
	$kids = wt_kids_page_id();
	if ( $kids && is_page() ) {
		$id = get_queried_object_id();
		return $id === $kids || in_array( $kids, get_post_ancestors( $id ), true );
	}
	return false;
}

add_action( 'template_redirect', function () {
	if ( ! wt_is_kids_view() ) {
		return;
	}
	// Everything the page loads must come from this site.
	header( "Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; media-src 'self' blob:; font-src 'self' data:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-src 'none'; object-src 'none'; base-uri 'self'; form-action 'self'" );
	header( 'Referrer-Policy: no-referrer' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), interest-cohort=()' );

	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'rsd_link' );
	add_filter( 'emoji_svg_url', '__return_false' );
	add_filter( 'embed_oembed_html', '__return_empty_string', 99 );
	add_filter( 'pre_oembed_result', '__return_empty_string', 99 );
	add_filter( 'body_class', function ( $c ) {
		$c[] = 'wt-kids';
		return $c;
	} );
}, 1 );

/** Moms pages only: the theme calls this where an analytics snippet would go. */
add_action( 'wp_head', function () {
	if ( ! wt_is_kids_view() ) {
		do_action( 'wt_moms_analytics' );
	}
}, 99 );

/** Grown-up check before any link that leads to plans, checkout or an account page. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'wt-grownup', WT_URL . 'assets/grownup-gate.js', array(), WT_VERSION, true );
} );
