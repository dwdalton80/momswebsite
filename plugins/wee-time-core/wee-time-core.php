<?php
/**
 * Plugin Name: Wee Time, Me Time Core
 * Description: Content types, Kids Corner membership rules, protected printables, Amazon links and the trial reminder for Wee Time, Me Time. Works with Paid Memberships Pro.
 * Version: 0.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Text Domain: wee-time
 *
 * Kids Corner rules this plugin enforces:
 * - Only a grown-up account can join or pay. Kids never sign in or type anything.
 * - Members-only content is checked on the server, never only in the browser.
 * - Kids Corner pages load no analytics, embeds or third-party requests.
 */

defined( 'ABSPATH' ) || exit;

define( 'WT_VERSION', '0.1.0' );
define( 'WT_FILE', __FILE__ );
define( 'WT_DIR', plugin_dir_path( __FILE__ ) );
define( 'WT_URL', plugin_dir_url( __FILE__ ) );

require_once WT_DIR . 'includes/settings.php';
require_once WT_DIR . 'includes/post-types.php';
require_once WT_DIR . 'includes/access.php';
require_once WT_DIR . 'includes/downloads.php';
require_once WT_DIR . 'includes/amazon.php';
require_once WT_DIR . 'includes/kids-privacy.php';
require_once WT_DIR . 'includes/trial-reminder.php';
require_once WT_DIR . 'includes/shortcodes.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once WT_DIR . 'includes/cli.php';
}

register_activation_hook( __FILE__, 'wt_activate' );
register_deactivation_hook( __FILE__, 'wt_deactivate' );

function wt_activate() {
	wt_register_content_types();
	wt_register_download_rewrite();
	wt_protect_upload_dir();
	flush_rewrite_rules();
	if ( ! wp_next_scheduled( 'wt_trial_reminder_check' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'wt_trial_reminder_check' );
	}
}

function wt_deactivate() {
	wp_clear_scheduled_hook( 'wt_trial_reminder_check' );
	flush_rewrite_rules();
}

/** Paid Memberships Pro must be active for anything members-only to unlock. */
add_action( 'admin_notices', function () {
	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p>' . esc_html__( 'Wee Time, Me Time needs Paid Memberships Pro. Until it is active, Kids Corner shows only the free samples.', 'wee-time' ) . '</p></div>';
	}
} );
