<?php
/**
 * Local test fixtures. Run with: wp eval-file tests/setup-fixtures.php <pdf> <mp4>
 * Creates a member parent and a non-member parent, attaches files, and prints
 * the ids and login cookies the HTTP checks need as JSON.
 */

$pdf = $args[0];
$mp4 = $args[1];

function wt_test_user( $login ) {
	$u = get_user_by( 'login', $login );
	return $u ? $u->ID : wp_insert_user( array( 'user_login' => $login, 'user_pass' => wp_generate_password(), 'user_email' => $login . '@example.test', 'role' => 'subscriber' ) );
}

$member    = wt_test_user( 'parent_member' );
$nonmember = wt_test_user( 'parent_free' );
pmpro_changeMembershipLevel( 1, $member );
pmpro_changeMembershipLevel( 0, $nonmember );

$locked = get_posts( array( 'post_type' => 'wt_printable', 'numberposts' => 1, 'meta_query' => array( array( 'key' => '_wt_free', 'value' => '1', 'compare' => '!=' ) ), 'tax_query' => array( array( 'taxonomy' => 'wt_age', 'field' => 'slug', 'terms' => '0-2' ) ) ) );
$free   = get_posts( array( 'post_type' => 'wt_printable', 'numberposts' => 1, 'meta_key' => '_wt_free', 'meta_value' => '1', 'tax_query' => array( array( 'taxonomy' => 'wt_age', 'field' => 'slug', 'terms' => '0-2' ) ) ) );
$ep     = get_posts( array( 'post_type' => 'wt_episode', 'numberposts' => 1, 'name' => 'sharing-the-berries-0-2' ) );

foreach ( array( $locked[0], $free[0] ) as $p ) {
	$name = 'test-' . $p->ID . '.pdf';
	copy( $pdf, wt_protected_dir() . '/' . $name );
	update_post_meta( $p->ID, '_wt_file', $name );
}
copy( $mp4, wt_protected_dir() . '/abtuvi-preview-2.mp4' );

$exp = time() + DAY_IN_SECONDS;
echo wp_json_encode( array(
	'member'         => $member,
	'nonmember'      => $nonmember,
	'locked'         => $locked[0]->ID,
	'free'           => $free[0]->ID,
	'episode'        => $ep[0]->ID,
	'cookie_name'    => LOGGED_IN_COOKIE,
	'member_cookie'  => wp_generate_auth_cookie( $member, $exp, 'logged_in' ),
	'free_cookie'    => wp_generate_auth_cookie( $nonmember, $exp, 'logged_in' ),
	'is_member'      => wt_is_member( $member ),
	'is_member_free' => wt_is_member( $nonmember ),
) );
