<?php
/**
 * Shortcodes the theme's pages use. Each renders on the server, so what a viewer
 * may open is decided here and never by script in the browser.
 *
 * [wt_gear_lists]           All gear lists, with Amazon links and the disclosure (moms pages).
 * [wt_kids_corner]          Age picker, then that age's cartoons, printables, games and packs.
 * [wt_plans]                Kids Corner plans from Paid Memberships Pro, behind the grown-up check.
 */

defined( 'ABSPATH' ) || exit;

add_shortcode( 'wt_gear_lists', 'wt_sc_gear_lists' );
add_shortcode( 'wt_kids_corner', 'wt_sc_kids_corner' );
add_shortcode( 'wt_plans', 'wt_sc_plans' );

function wt_sc_gear_lists() {
	$lists = get_posts( array( 'post_type' => 'wt_list', 'numberposts' => 50, 'orderby' => 'menu_order', 'order' => 'ASC' ) );
	$tiers = array( 'must' => __( 'Must have', 'wee-time' ), 'nice' => __( 'Nice to have', 'wee-time' ), 'skip' => __( 'Can skip', 'wee-time' ) );
	ob_start();
	echo '<section class="lists">';
	foreach ( $lists as $list ) {
		echo '<article class="list-card" id="' . esc_attr( $list->post_name ) . '">';
		echo '<p class="eyebrow">' . esc_html( get_post_meta( $list->ID, '_wt_stage', true ) ) . '</p>';
		echo '<h3>' . esc_html( get_the_title( $list ) ) . '</h3>';
		if ( $list->post_excerpt ) {
			echo '<p class="note">' . esc_html( $list->post_excerpt ) . '</p>';
		}
		echo '<ul class="items">';
		foreach ( wt_list_items( $list->ID ) as $item ) {
			$url = wt_amazon_url( $item['asin'] ?? '' );
			echo '<li class="item">';
			echo '<div><b>' . esc_html( $item['name'] ?? '' ) . '</b>';
			if ( ! empty( $item['tier'] ) && isset( $tiers[ $item['tier'] ] ) ) {
				echo ' <span class="badge">' . esc_html( $tiers[ $item['tier'] ] ) . '</span>';
			}
			echo '<p>' . esc_html( $item['why'] ?? '' ) . '</p></div>';
			if ( $url ) {
				echo '<a class="btn mom" href="' . esc_url( $url ) . '" rel="sponsored nofollow noopener" target="_blank">' . esc_html__( 'See it on Amazon', 'wee-time' ) . '</a>';
			}
			echo '</li>';
		}
		echo '</ul></article>';
	}
	echo '</section>';
	echo wt_amazon_disclosure(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the function.
	return ob_get_clean();
}

function wt_age_terms() {
	$terms = get_terms( array( 'taxonomy' => 'wt_age', 'hide_empty' => false, 'orderby' => 'term_order' ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	usort( $terms, function ( $a, $b ) {
		return (int) $a->slug <=> (int) $b->slug;
	} );
	return $terms;
}

function wt_kids_items( $type, $age_slug, $args = array() ) {
	return get_posts( array_merge( array(
		'post_type'   => $type,
		'numberposts' => 100,
		'orderby'     => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		'tax_query'   => array( array( 'taxonomy' => 'wt_age', 'field' => 'slug', 'terms' => $age_slug ) ),
	), $args ) );
}

function wt_sc_kids_corner() {
	$terms = wt_age_terms();
	if ( ! $terms ) {
		return '';
	}
	$slugs = wp_list_pluck( $terms, 'slug' );
	$age   = isset( $_GET['age'] ) ? sanitize_title( wp_unslash( $_GET['age'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only filter.
	$age   = in_array( $age, $slugs, true ) ? $age : $slugs[0];
	$here  = get_permalink();

	ob_start();
	echo '<nav class="ages chips" aria-label="' . esc_attr__( 'Pick an age', 'wee-time' ) . '">';
	foreach ( $terms as $t ) {
		$cur = $t->slug === $age ? ' aria-current="page"' : '';
		echo '<a class="chip" data-age="' . esc_attr( $t->slug ) . '" href="' . esc_url( add_query_arg( 'age', $t->slug, $here ) ) . '"' . $cur . '>' . esc_html( $t->name ) . '</a>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $cur is a fixed string.
	}
	echo '</nav>';

	// Cartoons: The Adventures of Abtuvi first, then the rest.
	$episodes = wt_kids_items( 'wt_episode', $age );
	$abtuvi   = array_filter( $episodes, function ( $e ) {
		return 'abtuvi' === get_post_meta( $e->ID, '_wt_series', true );
	} );
	$others   = array_udiff( $episodes, $abtuvi, function ( $a, $b ) {
		return $a->ID <=> $b->ID;
	} );
	if ( $abtuvi ) {
		echo '<section class="originals abtuvi"><p class="eyebrow">' . esc_html__( 'Our own cartoons', 'wee-time' ) . '</p><h2>' . esc_html__( 'The Adventures of Abtuvi', 'wee-time' ) . '</h2><div class="orig-grid">';
		foreach ( $abtuvi as $e ) {
			echo wt_episode_card( $e ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the function.
		}
		echo '</div></section>';
	}
	if ( $others ) {
		echo '<section class="originals"><h2>' . esc_html__( 'More cartoons', 'wee-time' ) . '</h2><div class="orig-grid">';
		foreach ( $others as $e ) {
			echo wt_episode_card( $e ); // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo '</div></section>';
	}

	foreach ( array( 'wt_printable' => __( 'Coloring and printables', 'wee-time' ), 'wt_game' => __( 'Games', 'wee-time' ), 'wt_pack' => __( "Abtuvi's monthly pack", 'wee-time' ) ) as $type => $heading ) {
		$items = wt_kids_items( $type, $age );
		if ( ! $items ) {
			continue;
		}
		echo '<section class="kid-section ' . esc_attr( $type ) . '"><h2>' . esc_html( $heading ) . '</h2><ul class="tiles">';
		foreach ( $items as $p ) {
			$open = wt_can_view( $p );
			$href = $open ? ( wt_file_url( $p ) ? wt_file_url( $p ) : get_permalink( $p ) ) : wt_join_url();
			echo '<li class="tile' . ( $open ? '' : ' locked' ) . '">';
			echo '<a href="' . esc_url( $href ) . '"' . ( $open ? '' : ' data-wt-grownup' ) . '>';
			echo '<span class="tile-title">' . esc_html( get_the_title( $p ) ) . '</span>';
			if ( wt_is_free( $p ) ) {
				echo ' <span class="badge free">' . esc_html__( 'Free', 'wee-time' ) . '</span>';
			} elseif ( ! $open ) {
				echo ' <span class="badge lock">' . esc_html__( 'Members', 'wee-time' ) . '</span>';
			}
			echo '</a></li>';
		}
		echo '</ul></section>';
	}
	return ob_get_clean();
}

function wt_episode_card( $e ) {
	$soon  = (bool) get_post_meta( $e->ID, '_wt_coming_soon', true );
	$open  = wt_can_view( $e );
	$src   = $open && ! $soon ? wt_file_url( $e ) : '';
	$thumb = get_the_post_thumbnail_url( $e, 'medium_large' );
	$out   = '<article class="orig' . ( $open ? '' : ' locked' ) . '">';
	if ( $src ) {
		$out .= '<video controls preload="none" playsinline src="' . esc_url( $src ) . '"' . ( $thumb ? ' poster="' . esc_url( $thumb ) . '"' : '' ) . '></video>';
	} else {
		$out .= '<div class="slot"><span>' . esc_html( $soon ? __( 'Coming soon', 'wee-time' ) : ( $open ? __( 'Video goes here', 'wee-time' ) : __( 'For members', 'wee-time' ) ) ) . '</span></div>';
	}
	$out .= '<h3>' . esc_html( get_the_title( $e ) ) . '</h3>';
	if ( $e->post_excerpt ) {
		$out .= '<p class="note">' . esc_html( $e->post_excerpt ) . '</p>';
	}
	if ( wt_is_free( $e ) ) {
		$out .= '<span class="badge free">' . esc_html__( 'Free to watch', 'wee-time' ) . '</span>';
	} elseif ( ! $open ) {
		$out .= '<a class="btn kid" href="' . esc_url( wt_join_url() ) . '" data-wt-grownup>' . esc_html__( 'For grown-ups: see plans', 'wee-time' ) . '</a>';
	}
	return $out . '</article>';
}

function wt_sc_plans() {
	if ( ! function_exists( 'pmpro_getAllLevels' ) || ! function_exists( 'pmpro_url' ) ) {
		return '';
	}
	$levels = pmpro_getAllLevels( false, true );
	$ids    = wt_member_level_ids();
	ob_start();
	echo '<div class="plans">';
	foreach ( $levels as $l ) {
		if ( $ids && ! in_array( (int) $l->id, $ids, true ) ) {
			continue;
		}
		$price = function_exists( 'pmpro_formatPrice' ) ? pmpro_formatPrice( $l->billing_amount ) : $l->billing_amount;
		$per   = 'Year' === $l->cycle_period ? __( 'year', 'wee-time' ) : __( 'month', 'wee-time' );
		echo '<div class="plan' . ( 'Year' === $l->cycle_period ? ' best' : '' ) . '">';
		echo '<h3>' . esc_html( $l->name ) . '</h3>';
		echo '<p class="price"><b>' . esc_html( $price ) . '</b> / ' . esc_html( $per ) . '</p>';
		if ( wt_trial_days() ) {
			/* translators: %d: number of days */
			echo '<p class="note">' . esc_html( sprintf( __( '%d days free, then billed every %s. Cancel anytime before then and you pay nothing.', 'wee-time' ), wt_trial_days(), $per ) ) . '</p>';
		}
		echo '<a class="btn kid" data-wt-grownup href="' . esc_url( pmpro_url( 'checkout', '?pmpro_level=' . (int) $l->id ) ) . '">' . esc_html__( 'Start the free trial', 'wee-time' ) . '</a>';
		echo '</div>';
	}
	echo '</div><p class="note">' . esc_html__( 'New cartoons and printables every month.', 'wee-time' ) . '</p>';
	return ob_get_clean();
}
