<?php
/**
 * Who can see what.
 *
 * A kids item is open when it is marked free (the free sample) or the signed-in
 * grown-up holds an active Kids Corner level in Paid Memberships Pro. Every check
 * runs on the server: the page, the REST API and the file endpoint all ask here.
 */

defined( 'ABSPATH' ) || exit;

function wt_is_member( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id || ! function_exists( 'pmpro_hasMembershipLevel' ) ) {
		return false;
	}
	$levels = wt_member_level_ids();
	return (bool) pmpro_hasMembershipLevel( $levels ? $levels : null, $user_id );
}

function wt_is_free( $post ) {
	$post = get_post( $post );
	return $post && (bool) get_post_meta( $post->ID, '_wt_free', true );
}

/** True when this viewer may open this kids item. Anything that isn't kids content is left alone. */
function wt_can_view( $post, $user_id = 0 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	if ( ! in_array( $post->post_type, wt_kids_post_types(), true ) ) {
		return true;
	}
	if ( 'publish' !== $post->post_status ) {
		return current_user_can( 'edit_post', $post->ID );
	}
	if ( wt_is_free( $post ) || user_can( $user_id ? $user_id : get_current_user_id(), 'edit_post', $post->ID ) ) {
		return true;
	}
	return wt_is_member( $user_id );
}

/** URL of the page where a grown-up picks a plan. */
function wt_join_url() {
	if ( function_exists( 'pmpro_url' ) ) {
		$url = pmpro_url( 'levels' );
		if ( $url ) {
			return $url;
		}
	}
	return home_url( '/membership-levels/' );
}

/** The locked card shown in place of members-only content. */
function wt_locked_card( $post = null ) {
	$post  = get_post( $post );
	$title = $post ? get_the_title( $post ) : '';
	ob_start();
	?>
	<div class="wt-locked" role="note">
		<p class="wt-locked__title"><?php echo esc_html( $title ); ?></p>
		<p><?php esc_html_e( 'This one is for Kids Corner members. A grown-up can start a 3-day free trial.', 'wee-time' ); ?></p>
		<a class="btn kid" href="<?php echo esc_url( wt_join_url() ); ?>" data-wt-grownup><?php esc_html_e( 'For grown-ups: see plans', 'wee-time' ); ?></a>
	</div>
	<?php
	return ob_get_clean();
}

/** Single pages: swap members-only content for the locked card. */
add_filter( 'the_content', function ( $content ) {
	$post = get_post();
	if ( ! $post || ! in_array( $post->post_type, wt_kids_post_types(), true ) ) {
		return $content;
	}
	return wt_can_view( $post ) ? $content : wt_locked_card( $post );
}, 5 );

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	if ( $post && in_array( $post->post_type, wt_kids_post_types(), true ) && ! wt_can_view( $post ) ) {
		return wp_strip_all_tags( $post->post_excerpt );
	}
	return $excerpt;
}, 5, 2 );

/** REST API and feeds never hand out locked content or file names. */
foreach ( array( 'wt_printable', 'wt_episode', 'wt_game', 'wt_pack' ) as $wt_type ) {
	add_filter( "rest_prepare_{$wt_type}", function ( $response, $post ) {
		$data = $response->get_data();
		if ( ! current_user_can( 'edit_post', $post->ID ) && isset( $data['meta'] ) ) {
			unset( $data['meta']['_wt_file'], $data['meta']['_wt_video'] );
		}
		if ( ! wt_can_view( $post ) ) {
			if ( isset( $data['content'] ) ) {
				$data['content'] = array( 'rendered' => '', 'protected' => true );
			}
		}
		$data['wt_locked'] = ! wt_can_view( $post );
		$response->set_data( $data );
		return $response;
	}, 10, 2 );
}

add_filter( 'the_content_feed', function ( $content ) {
	$post = get_post();
	return ( $post && in_array( $post->post_type, wt_kids_post_types(), true ) && ! wt_can_view( $post ) ) ? '' : $content;
} );
