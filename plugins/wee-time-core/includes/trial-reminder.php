<?php
/**
 * Trial reminder.
 *
 * Stripe's own "trial ending" notice fires 3 days before a trial ends, which with a
 * 3-day trial is the moment it starts. So the site sends its own email about a day
 * before the first charge: what plan, how much, when, and how to cancel.
 */

defined( 'ABSPATH' ) || exit;

/** At checkout, note when the trial ends if this level has one. */
add_action( 'pmpro_after_checkout', function ( $user_id, $order = null ) {
	$days = wt_trial_days();
	if ( ! $days ) {
		return;
	}
	$level = function_exists( 'pmpro_getMembershipLevelForUser' ) ? pmpro_getMembershipLevelForUser( $user_id ) : null;
	if ( ! $level || (float) $level->billing_amount <= 0 ) {
		return;
	}
	// Only a first-time member gets a trial; the level's setup decides that, we only remind.
	if ( get_user_meta( $user_id, 'wt_had_trial', true ) ) {
		return;
	}
	update_user_meta( $user_id, 'wt_had_trial', 1 );
	update_user_meta( $user_id, 'wt_trial_ends', time() + $days * DAY_IN_SECONDS );
	update_user_meta( $user_id, 'wt_trial_level', (int) $level->id );
	delete_user_meta( $user_id, 'wt_trial_reminded' );
}, 10, 2 );

/** A cancel during the trial means no reminder and no charge. */
add_action( 'pmpro_after_change_membership_level', function ( $level_id, $user_id ) {
	if ( empty( $level_id ) ) {
		delete_user_meta( $user_id, 'wt_trial_ends' );
	}
}, 10, 2 );

add_action( 'wt_trial_reminder_check', 'wt_send_trial_reminders' );

/** Sends one reminder to each trial that ends in the next 12 to 36 hours. Returns how many went out. */
function wt_send_trial_reminders( $now = null ) {
	$now   = $now ? (int) $now : time();
	$users = get_users( array(
		'meta_query' => array(
			'relation' => 'AND',
			array( 'key' => 'wt_trial_ends', 'value' => array( $now + 12 * HOUR_IN_SECONDS, $now + 36 * HOUR_IN_SECONDS ), 'compare' => 'BETWEEN', 'type' => 'NUMERIC' ),
			array( 'key' => 'wt_trial_reminded', 'compare' => 'NOT EXISTS' ),
		),
		'fields'     => array( 'ID', 'user_email', 'display_name' ),
		'number'     => 200,
	) );
	$sent = 0;
	foreach ( $users as $u ) {
		if ( ! wt_is_member( $u->ID ) ) {
			delete_user_meta( $u->ID, 'wt_trial_ends' );
			continue;
		}
		if ( wp_mail( $u->user_email, wt_trial_email_subject(), wt_trial_email_body( $u->ID ) ) ) {
			update_user_meta( $u->ID, 'wt_trial_reminded', $now );
			$sent++;
		}
	}
	return $sent;
}

function wt_trial_email_subject() {
	return __( 'Your Kids Corner free trial ends tomorrow', 'wee-time' );
}

function wt_trial_email_body( $user_id ) {
	$level = function_exists( 'pmpro_getMembershipLevelForUser' ) ? pmpro_getMembershipLevelForUser( $user_id ) : null;
	$ends  = (int) get_user_meta( $user_id, 'wt_trial_ends', true );
	$price = $level && function_exists( 'pmpro_formatPrice' ) ? pmpro_formatPrice( $level->billing_amount ) : '';
	$per   = $level ? ( 'Year' === $level->cycle_period ? __( 'year', 'wee-time' ) : __( 'month', 'wee-time' ) ) : '';
	$when  = $ends ? wp_date( get_option( 'date_format' ), $ends ) : '';
	$acct  = function_exists( 'pmpro_url' ) ? pmpro_url( 'account' ) : home_url( '/' );

	$lines   = array();
	$lines[] = __( 'Hi,', 'wee-time' );
	$lines[] = '';
	/* translators: 1: plan name, 2: date, 3: price, 4: month or year */
	$lines[] = sprintf( __( 'Your Kids Corner free trial ends on %2$s. Unless you cancel before then, your %1$s plan starts and your card is charged %3$s a %4$s.', 'wee-time' ), $level ? $level->name : '', $when, $price, $per );
	$lines[] = '';
	$lines[] = __( 'To keep going, you do not need to do anything. To cancel, sign in to your account and choose Cancel:', 'wee-time' );
	$lines[] = $acct;
	$lines[] = '';
	$lines[] = get_bloginfo( 'name' );
	return implode( "\n", $lines );
}
