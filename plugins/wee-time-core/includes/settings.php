<?php
/**
 * One settings screen: Settings > Wee Time.
 *
 * Amazon tag: left empty until Amazon issues the Associates ID (it needs a live site first).
 * While it is empty, product links carry no tag and the affiliate disclosure stays hidden.
 */

defined( 'ABSPATH' ) || exit;

function wt_option( $key, $default = '' ) {
	$opts = get_option( 'wt_settings', array() );
	return isset( $opts[ $key ] ) && '' !== $opts[ $key ] ? $opts[ $key ] : $default;
}

function wt_amazon_tag() {
	$tag = defined( 'WT_AMAZON_TAG' ) ? WT_AMAZON_TAG : wt_option( 'amazon_tag' );
	return preg_match( '/^[A-Za-z0-9-]{3,40}$/', (string) $tag ) ? $tag : '';
}

/** Membership level ids that unlock Kids Corner. Empty means any active level. */
function wt_member_level_ids() {
	$ids = wt_option( 'member_levels', '' );
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $ids ) ) );
	return array_values( $ids );
}

function wt_trial_days() {
	return max( 0, (int) wt_option( 'trial_days', 3 ) );
}

add_action( 'admin_menu', function () {
	add_options_page( 'Wee Time', 'Wee Time', 'manage_options', 'wee-time', 'wt_render_settings' );
} );

add_action( 'admin_init', function () {
	register_setting( 'wt_settings', 'wt_settings', array(
		'type'              => 'array',
		'sanitize_callback' => 'wt_sanitize_settings',
		'default'           => array(),
	) );
} );

function wt_sanitize_settings( $in ) {
	$in  = is_array( $in ) ? $in : array();
	$out = array();
	$tag = isset( $in['amazon_tag'] ) ? trim( sanitize_text_field( $in['amazon_tag'] ) ) : '';
	$out['amazon_tag']    = preg_match( '/^[A-Za-z0-9-]{3,40}$/', $tag ) ? $tag : '';
	$out['member_levels'] = isset( $in['member_levels'] ) ? implode( ',', array_filter( array_map( 'absint', explode( ',', $in['member_levels'] ) ) ) ) : '';
	$out['trial_days']    = isset( $in['trial_days'] ) ? max( 0, min( 30, absint( $in['trial_days'] ) ) ) : 3;
	$out['kids_page']     = isset( $in['kids_page'] ) ? absint( $in['kids_page'] ) : 0;
	return $out;
}

function wt_render_settings() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o = get_option( 'wt_settings', array() );
	?>
	<div class="wrap">
		<h1>Wee Time, Me Time</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'wt_settings' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="wt-amazon">Amazon Associates tag</label></th>
					<td><input id="wt-amazon" name="wt_settings[amazon_tag]" class="regular-text" value="<?php echo esc_attr( $o['amazon_tag'] ?? '' ); ?>">
					<p class="description">Leave empty until Amazon gives you the tag. Adding it turns on the tag in every product link and shows the affiliate disclosure.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="wt-levels">Kids Corner levels</label></th>
					<td><input id="wt-levels" name="wt_settings[member_levels]" class="regular-text" value="<?php echo esc_attr( $o['member_levels'] ?? '' ); ?>">
					<p class="description">Membership level ids that unlock Kids Corner, separated by commas. Empty means any level.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="wt-trial">Free trial days</label></th>
					<td><input id="wt-trial" type="number" min="0" max="30" name="wt_settings[trial_days]" value="<?php echo esc_attr( $o['trial_days'] ?? 3 ); ?>">
					<p class="description">Used for the reminder email sent about a day before the first charge. The trial itself is set on the membership level.</p></td>
				</tr>
				<tr>
					<th scope="row"><label for="wt-kids">Kids Corner page</label></th>
					<td><?php wp_dropdown_pages( array( 'name' => 'wt_settings[kids_page]', 'id' => 'wt-kids', 'selected' => (int) ( $o['kids_page'] ?? 0 ), 'show_option_none' => '— Select —' ) ); ?>
					<p class="description">This page and every page under it get the Kids Corner privacy rules.</p></td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
