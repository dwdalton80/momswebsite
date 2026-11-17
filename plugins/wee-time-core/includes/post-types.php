<?php
/**
 * Content types.
 *
 * wt_list      Gear lists for moms (Pregnancy, Newborn, ...). Items live in post meta.
 * wt_printable Coloring pages and learning printables. The PDF is stored outside public uploads.
 * wt_episode   The Adventures of Abtuvi and other cartoons. Can be "coming soon" with no video.
 * wt_game      Kids Corner games (the game itself is a small script in the theme).
 * wt_pack      Abtuvi's monthly pack: a set of printables for one month.
 * wt_age       Age group taxonomy shared by kids content: 0-2, 3-5, 6-8, 9-12.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'wt_register_content_types' );

function wt_kids_post_types() {
	return array( 'wt_printable', 'wt_episode', 'wt_game', 'wt_pack' );
}

function wt_register_content_types() {
	$common = array(
		'public'       => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-heart',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
	);

	register_post_type( 'wt_list', $common + array(
		'labels'      => array( 'name' => 'Gear lists', 'singular_name' => 'Gear list' ),
		'rewrite'     => array( 'slug' => 'lists' ),
		'has_archive' => 'lists',
	) );

	$kids = array(
		'wt_printable' => array( 'Printables', 'Printable', 'kids-corner/printables' ),
		'wt_episode'   => array( 'Cartoons', 'Cartoon', 'kids-corner/cartoons' ),
		'wt_game'      => array( 'Games', 'Game', 'kids-corner/games' ),
		'wt_pack'      => array( 'Monthly packs', 'Monthly pack', 'kids-corner/packs' ),
	);
	foreach ( $kids as $type => $l ) {
		register_post_type( $type, array_merge( $common, array(
			'labels'      => array( 'name' => $l[0], 'singular_name' => $l[1] ),
			'rewrite'     => array( 'slug' => $l[2] ),
			'has_archive' => $l[2],
			'menu_icon'   => 'dashicons-smiley',
			'supports'    => array( 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields', 'page-attributes' ),
		) ) );
	}

	register_taxonomy( 'wt_age', wt_kids_post_types(), array(
		'labels'            => array( 'name' => 'Age groups', 'singular_name' => 'Age group' ),
		'public'            => true,
		'hierarchical'      => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array( 'slug' => 'kids-corner/age' ),
	) );

	$meta = array(
		'_wt_free'        => array( 'boolean', wt_kids_post_types() ),
		'_wt_coming_soon' => array( 'boolean', array( 'wt_episode' ) ),
		'_wt_file'        => array( 'string', array( 'wt_printable', 'wt_pack' ) ),
		'_wt_video'       => array( 'string', array( 'wt_episode' ) ),
		'_wt_series'      => array( 'string', array( 'wt_episode' ) ),
		'_wt_month'       => array( 'string', array( 'wt_pack' ) ),
		'_wt_stage'       => array( 'string', array( 'wt_list' ) ),
		'_wt_items'       => array( 'string', array( 'wt_list' ) ),
	);
	foreach ( $meta as $key => $def ) {
		foreach ( $def[1] as $type ) {
			register_post_meta( $type, $key, array(
				'type'          => $def[0],
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			) );
		}
	}
}

/** Kids content never takes comments, so nothing can be typed in Kids Corner. */
add_filter( 'comments_open', function ( $open, $post_id ) {
	return in_array( get_post_type( $post_id ), wt_kids_post_types(), true ) ? false : $open;
}, 10, 2 );
add_filter( 'pings_open', function ( $open, $post_id ) {
	return in_array( get_post_type( $post_id ), wt_kids_post_types(), true ) ? false : $open;
}, 10, 2 );

/** Gear list items: array of [name, why, category, tier, asin, price]. */
function wt_list_items( $post_id ) {
	$raw   = get_post_meta( $post_id, '_wt_items', true );
	$items = is_string( $raw ) ? json_decode( $raw, true ) : $raw;
	return is_array( $items ) ? $items : array();
}
