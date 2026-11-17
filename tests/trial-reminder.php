<?php
/**
 * Trial reminder check. Run with: wp eval-file tests/trial-reminder.php
 * Captures mail instead of sending it.
 */

$mail = array();
add_filter( 'pre_wp_mail', function ( $r, $atts ) use ( &$mail ) {
	$mail[] = $atts;
	return true;
}, 10, 2 );

$u = get_user_by( 'login', 'parent_member' );
delete_user_meta( $u->ID, 'wt_had_trial' );
delete_user_meta( $u->ID, 'wt_trial_reminded' );
// What a monthly checkout leaves behind: nothing charged today, $4.99 a month after.
pmpro_changeMembershipLevel( array( 'membership_id' => 1, 'user_id' => $u->ID, 'initial_payment' => 0, 'billing_amount' => 4.99, 'cycle_number' => 1, 'cycle_period' => 'Month', 'billing_limit' => 0, 'trial_amount' => 0, 'trial_limit' => 0, 'code_id' => 0, 'startdate' => current_time( 'mysql' ), 'enddate' => '0000-00-00 00:00:00' ), $u->ID );
do_action( 'pmpro_after_checkout', $u->ID, null );
$ends = (int) get_user_meta( $u->ID, 'wt_trial_ends', true );
$ok   = array();

$ok['trial end set 3 days out'] = abs( $ends - ( time() + 3 * DAY_IN_SECONDS ) ) < 120;
$ok['no reminder on day 1']     = 0 === wt_send_trial_reminders( time() );
$ok['reminder about a day before'] = 1 === wt_send_trial_reminders( $ends - DAY_IN_SECONDS );
$ok['only one reminder']        = 0 === wt_send_trial_reminders( $ends - DAY_IN_SECONDS + 60 );
$ok['sent to the parent']       = isset( $mail[0] ) && $mail[0]['to'] === $u->user_email;
$ok['email names price and how to cancel'] = isset( $mail[0] ) && false !== strpos( $mail[0]['message'], '4.99' ) && false !== stripos( $mail[0]['message'], 'cancel' );

// A cancel during the trial clears it.
pmpro_changeMembershipLevel( 0, $u->ID );
$ok['cancel clears trial'] = '' === get_user_meta( $u->ID, 'wt_trial_ends', true );
pmpro_changeMembershipLevel( 1, $u->ID );

$fail = 0;
foreach ( $ok as $k => $v ) {
	echo ( $v ? 'PASS ' : 'FAIL ' ) . $k . "\n";
	$fail += $v ? 0 : 1;
}
echo $fail ? "$fail FAILED\n" : "ALL PASSED\n";
