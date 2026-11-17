<?php
/**
 * Amazon product links for the moms pages.
 *
 * One setting turns on both the Associates tag and the disclosure, so neither can
 * go live without the other. Links point to amazon.com/dp/{ASIN}; no Amazon or
 * maker photos are copied or hotlinked.
 */

defined( 'ABSPATH' ) || exit;

function wt_amazon_url( $asin ) {
	$asin = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $asin ) );
	if ( '' === $asin ) {
		return '';
	}
	$url = 'https://www.amazon.com/dp/' . rawurlencode( $asin );
	$tag = wt_amazon_tag();
	return $tag ? add_query_arg( 'tag', rawurlencode( $tag ), $url ) : $url;
}

function wt_amazon_disclosure() {
	if ( ! wt_amazon_tag() ) {
		return '';
	}
	return '<p class="disclosure"><strong>' . esc_html__( 'Heads up:', 'wee-time' ) . '</strong> ' . esc_html__( "As an Amazon Associate I earn from qualifying purchases. We only list things we'd tell a friend about.", 'wee-time' ) . '</p>';
}
