<?php
/**
 * WP-CLI commands for setting up a fresh staging site.
 *
 *   wp weetime seed                  Age groups, pages, gear lists, Abtuvi episodes, printable slots.
 *   wp weetime seed --levels         Also adds the Monthly and Yearly levels in Paid Memberships Pro
 *                                    (local data only: no payment gateway is touched).
 *   wp weetime attach <post> <file>  Copies a PDF or MP4 into the protected folder for a kids item.
 *   wp weetime remind [--now=<ts>]   Runs the trial reminder check once.
 */

defined( 'ABSPATH' ) || exit;

class WT_CLI {

	/**
	 * Seed a fresh site with the content structure from the preview.
	 *
	 * [--levels]
	 * : Also create the Monthly and Yearly membership levels in Paid Memberships Pro.
	 */
	public function seed( $args, $assoc ) {
		$data = WT_DIR . 'data/';

		// Age groups.
		$ages = json_decode( file_get_contents( $data . 'ages.json' ), true );
		foreach ( $ages as $slug => $a ) {
			if ( ! term_exists( $slug, 'wt_age' ) ) {
				wp_insert_term( $a['label'] . ' ' . $a['name'], 'wt_age', array( 'slug' => $slug ) );
			}
		}

		// Pages.
		$pages = array(
			'home'        => array( 'Home', '' ),
			'moms'        => array( 'For Moms', "<!-- wp:shortcode -->\n[wt_gear_lists]\n<!-- /wp:shortcode -->" ),
			'kids-corner' => array( 'Kids Corner', "<!-- wp:shortcode -->\n[wt_kids_corner]\n<!-- /wp:shortcode -->\n\n<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">For grown-ups</h2>\n<!-- /wp:heading -->\n\n<!-- wp:shortcode -->\n[wt_plans]\n<!-- /wp:shortcode -->" ),
		);
		$ids = array();
		foreach ( $pages as $slug => $p ) {
			$found = get_page_by_path( $slug );
			$ids[ $slug ] = $found ? $found->ID : wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $p[0], 'post_content' => $p[1] ) );
		}
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
		$opts              = get_option( 'wt_settings', array() );
		$opts['kids_page'] = $ids['kids-corner'];
		$opts['trial_days'] = $opts['trial_days'] ?? 3;
		update_option( 'wt_settings', $opts );

		// Gear lists.
		$lists = json_decode( file_get_contents( $data . 'gear-lists.json' ), true );
		foreach ( $lists as $i => $l ) {
			if ( get_page_by_path( $l['id'], OBJECT, 'wt_list' ) ) {
				continue;
			}
			$id = wp_insert_post( array( 'post_type' => 'wt_list', 'post_status' => 'publish', 'post_name' => $l['id'], 'post_title' => $l['title'], 'post_excerpt' => $l['blurb'], 'menu_order' => $i ) );
			update_post_meta( $id, '_wt_stage', $l['stage'] );
			update_post_meta( $id, '_wt_items', wp_slash( wp_json_encode( $l['items'] ) ) );
		}

		// The Adventures of Abtuvi. The first episode is free to watch; the rest are free previews too, as on the preview.
		$abt = json_decode( file_get_contents( $data . 'abtuvi-episodes.json' ), true );
		foreach ( $abt['ages'] as $age ) {
			foreach ( $abt['episodes'] as $n => $e ) {
				$slug = sanitize_title( $e['title'] . '-' . $age );
				if ( get_page_by_path( $slug, OBJECT, 'wt_episode' ) ) {
					continue;
				}
				$id = wp_insert_post( array( 'post_type' => 'wt_episode', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $e['title'], 'post_excerpt' => $e['note'], 'menu_order' => $n ) );
				wp_set_object_terms( $id, $age, 'wt_age' );
				update_post_meta( $id, '_wt_series', 'abtuvi' );
				update_post_meta( $id, '_wt_free', true );
				update_post_meta( $id, '_wt_coming_soon', ! empty( $e['coming_soon'] ) );
				if ( ! empty( $e['video'] ) ) {
					update_post_meta( $id, '_wt_video', $e['video'] );
				}
			}
		}

		// Printable slots: one free page per age, the rest for members. PDFs are attached later.
		foreach ( $ages as $slug => $a ) {
			$titles = array_merge( $a['coloring'], $a['learning'] );
			foreach ( $titles as $n => $title ) {
				$pslug = sanitize_title( $title . '-' . $slug );
				if ( get_page_by_path( $pslug, OBJECT, 'wt_printable' ) ) {
					continue;
				}
				$id = wp_insert_post( array( 'post_type' => 'wt_printable', 'post_status' => 'publish', 'post_name' => $pslug, 'post_title' => $title, 'menu_order' => $n ) );
				wp_set_object_terms( $id, $slug, 'wt_age' );
				update_post_meta( $id, '_wt_free', 0 === $n );
			}
		}

		if ( ! empty( $assoc['levels'] ) ) {
			$this->levels();
		}
		wt_protect_upload_dir();
		flush_rewrite_rules();
		WP_CLI::success( 'Seeded age groups, pages, gear lists, Abtuvi episodes and printable slots.' );
	}

	private function levels() {
		global $wpdb;
		if ( empty( $wpdb->pmpro_membership_levels ) ) {
			WP_CLI::warning( 'Paid Memberships Pro is not active, so no levels were added.' );
			return;
		}
		$have = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->pmpro_membership_levels}" ); // phpcs:ignore WordPress.DB
		if ( $have ) {
			WP_CLI::log( 'Membership levels already exist; left as they are.' );
			return;
		}
		$plans = array(
			array( 'Monthly', 4.99, 'Month' ),
			array( 'Yearly', 47.00, 'Year' ),
		);
		$ids = array();
		foreach ( $plans as $p ) {
			$wpdb->insert( $wpdb->pmpro_membership_levels, array( // phpcs:ignore WordPress.DB
				'name'              => $p[0],
				'description'       => 'Kids Corner: every cartoon, printable and game for all ages. New content every month.',
				'confirmation'      => '',
				'initial_payment'   => 0,
				'billing_amount'    => $p[1],
				'cycle_number'      => 1,
				'cycle_period'      => $p[2],
				'billing_limit'     => 0,
				'trial_amount'      => 0,
				'trial_limit'       => 0,
				'allow_signups'     => 1,
				'expiration_number' => 0,
				'expiration_period' => '',
			) );
			$ids[] = (int) $wpdb->insert_id;
		}
		$opts                  = get_option( 'wt_settings', array() );
		$opts['member_levels'] = implode( ',', $ids );
		update_option( 'wt_settings', $opts );
		WP_CLI::log( 'Added levels ' . implode( ', ', $ids ) . '. The 3-day delay before the first charge is set in the trial add-on; see the README.' );
	}

	/**
	 * Copy a PDF or MP4 into the protected folder for a kids item.
	 *
	 * <post>
	 * : The kids item's id.
	 *
	 * <file>
	 * : Path to the PDF or MP4.
	 */
	public function attach( $args ) {
		list( $post_id, $file ) = $args;
		$post = get_post( (int) $post_id );
		if ( ! $post || ! in_array( $post->post_type, wt_kids_post_types(), true ) || ! is_file( $file ) ) {
			WP_CLI::error( 'Need a kids item id and a file that exists.' );
		}
		$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		$want = 'wt_episode' === $post->post_type ? 'mp4' : 'pdf';
		if ( $ext !== $want ) {
			WP_CLI::error( "This item takes a .$want file." );
		}
		wt_protect_upload_dir();
		$name = wp_unique_filename( wt_protected_dir(), sanitize_file_name( wp_basename( $file ) ) );
		copy( $file, wt_protected_dir() . '/' . $name );
		update_post_meta( $post->ID, 'wt_episode' === $post->post_type ? '_wt_video' : '_wt_file', $name );
		WP_CLI::success( "Attached $name to {$post->post_title}." );
	}

	/**
	 * Run the trial reminder check once.
	 *
	 * [--now=<timestamp>]
	 * : Pretend the current time is this Unix timestamp.
	 */
	public function remind( $args, $assoc ) {
		$sent = wt_send_trial_reminders( isset( $assoc['now'] ) ? (int) $assoc['now'] : null );
		WP_CLI::success( "Sent $sent reminder(s)." );
	}
}

WP_CLI::add_command( 'weetime', 'WT_CLI' );
