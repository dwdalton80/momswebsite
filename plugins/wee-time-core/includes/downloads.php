<?php
/**
 * Protected files: printables (PDF), pack PDFs and members' cartoons (MP4).
 *
 * Files live in wp-content/uploads/wt-protected/, which the web server must not serve
 * directly. On Apache the .htaccess written here blocks it. On nginx add:
 *     location ^~ /wp-content/uploads/wt-protected/ { deny all; }
 * Every file goes out through /wt-file/{post_id}/, which checks wt_can_view() first.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'wt_register_download_rewrite' );

function wt_register_download_rewrite() {
	add_rewrite_rule( '^wt-file/([0-9]+)/?$', 'index.php?wt_file=$matches[1]', 'top' );
}

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'wt_file';
	return $vars;
} );

function wt_protected_dir() {
	$up = wp_upload_dir( null, false );
	return trailingslashit( $up['basedir'] ) . 'wt-protected';
}

function wt_protect_upload_dir() {
	$dir = wt_protected_dir();
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}
	if ( ! file_exists( $dir . '/.htaccess' ) ) {
		file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
	}
	if ( ! file_exists( $dir . '/index.php' ) ) {
		file_put_contents( $dir . '/index.php', "<?php\n// Silence.\n" );
	}
}

/** Public URL for an item's file. It works only for viewers allowed to open the item. */
function wt_file_url( $post ) {
	$post = get_post( $post );
	if ( ! $post || ! wt_file_name( $post ) ) {
		return '';
	}
	return get_option( 'permalink_structure' ) ? home_url( '/wt-file/' . $post->ID . '/' ) : add_query_arg( 'wt_file', $post->ID, home_url( '/' ) );
}

function wt_file_name( $post ) {
	$post = get_post( $post );
	$key  = 'wt_episode' === $post->post_type ? '_wt_video' : '_wt_file';
	$name = (string) get_post_meta( $post->ID, $key, true );
	return '' !== $name ? sanitize_file_name( wp_basename( $name ) ) : '';
}

function wt_file_path( $post ) {
	$name = wt_file_name( $post );
	if ( '' === $name ) {
		return '';
	}
	$path = wt_protected_dir() . '/' . $name;
	$real = realpath( $path );
	$base = realpath( wt_protected_dir() );
	return ( $real && $base && 0 === strpos( $real, $base . DIRECTORY_SEPARATOR ) && is_file( $real ) ) ? $real : '';
}

add_action( 'template_redirect', function () {
	$id = absint( get_query_var( 'wt_file' ) );
	if ( ! $id ) {
		return;
	}
	$post = get_post( $id );
	if ( ! $post || ! in_array( $post->post_type, wt_kids_post_types(), true ) ) {
		status_header( 404 );
		exit;
	}
	if ( ! wt_can_view( $post ) ) {
		// Locked: send the grown-up to the plans page instead of the file.
		wp_safe_redirect( wt_join_url(), 302 );
		exit;
	}
	$path = wt_file_path( $post );
	if ( ! $path ) {
		status_header( 404 );
		exit;
	}
	wt_send_file( $path, wt_is_free( $post ) );
}, 0 );

/** Streams a file, with byte ranges so videos can seek. */
function wt_send_file( $path, $is_free ) {
	$size = filesize( $path );
	$type = wp_check_filetype( $path );
	$mime = $type['type'] ? $type['type'] : 'application/octet-stream';
	$start = 0;
	$end   = $size - 1;

	nocache_headers();
	header( 'Content-Type: ' . $mime );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Accept-Ranges: bytes' );
	header( 'Cache-Control: ' . ( $is_free ? 'public, max-age=3600' : 'private, no-store' ) );
	if ( 'application/pdf' === $mime ) {
		header( 'Content-Disposition: inline; filename="' . rawurlencode( basename( $path ) ) . '"' );
	}

	if ( isset( $_SERVER['HTTP_RANGE'] ) && preg_match( '/bytes=(\d*)-(\d*)/', (string) $_SERVER['HTTP_RANGE'], $m ) ) {
		if ( '' === $m[1] && '' !== $m[2] ) {
			$start = max( 0, $size - (int) $m[2] );
		} else {
			$start = (int) $m[1];
			$end   = '' !== $m[2] ? min( (int) $m[2], $size - 1 ) : $end;
		}
		if ( $start > $end || $start >= $size ) {
			status_header( 416 );
			header( 'Content-Range: bytes */' . $size );
			exit;
		}
		status_header( 206 );
		header( sprintf( 'Content-Range: bytes %d-%d/%d', $start, $end, $size ) );
	} else {
		status_header( 200 );
	}
	header( 'Content-Length: ' . ( $end - $start + 1 ) );

	if ( 'HEAD' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
		exit;
	}
	while ( ob_get_level() ) {
		ob_end_clean();
	}
	$fh = fopen( $path, 'rb' );
	fseek( $fh, $start );
	$left = $end - $start + 1;
	while ( $left > 0 && ! feof( $fh ) ) {
		$chunk = fread( $fh, min( 1048576, $left ) );
		echo $chunk; // phpcs:ignore WordPress.Security.EscapeOutput -- binary file body.
		$left -= strlen( $chunk );
		flush();
	}
	fclose( $fh );
	exit;
}

/* ---------- Admin: upload the protected file on the item's edit screen ---------- */

add_action( 'add_meta_boxes', function () {
	foreach ( wt_kids_post_types() as $type ) {
		if ( 'wt_game' === $type ) {
			continue;
		}
		add_meta_box( 'wt-file', 'Kids Corner file', 'wt_file_meta_box', $type, 'side' );
	}
} );

add_action( 'post_edit_form_tag', function () {
	echo ' enctype="multipart/form-data"';
} );

function wt_file_meta_box( $post ) {
	wp_nonce_field( 'wt_file_save', 'wt_file_nonce' );
	$name = wt_file_name( $post );
	$free = (bool) get_post_meta( $post->ID, '_wt_free', true );
	echo '<p><label><input type="checkbox" name="wt_free" value="1" ' . checked( $free, true, false ) . '> Free sample (anyone can open it)</label></p>';
	if ( 'wt_episode' === $post->post_type ) {
		$soon = (bool) get_post_meta( $post->ID, '_wt_coming_soon', true );
		echo '<p><label><input type="checkbox" name="wt_coming_soon" value="1" ' . checked( $soon, true, false ) . '> Coming soon (no video yet)</label></p>';
	}
	echo '<p>' . ( $name ? 'Current file: <code>' . esc_html( $name ) . '</code>' : 'No file yet.' ) . '</p>';
	$accept = 'wt_episode' === $post->post_type ? 'video/mp4' : 'application/pdf';
	echo '<p><input type="file" name="wt_file_upload" accept="' . esc_attr( $accept ) . '"></p>';
	echo '<p class="description">Stored outside the public uploads folder and served only to members, or to anyone if marked free.</p>';
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! in_array( $post->post_type, wt_kids_post_types(), true ) || wp_is_post_revision( $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['wt_file_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wt_file_nonce'] ) ), 'wt_file_save' ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_wt_free', ! empty( $_POST['wt_free'] ) );
	if ( 'wt_episode' === $post->post_type ) {
		update_post_meta( $post_id, '_wt_coming_soon', ! empty( $_POST['wt_coming_soon'] ) );
	}
	if ( empty( $_FILES['wt_file_upload']['name'] ) ) {
		return;
	}
	$allowed = 'wt_episode' === $post->post_type ? array( 'mp4' => 'video/mp4' ) : array( 'pdf' => 'application/pdf' );
	$saved   = wt_store_protected_file( $_FILES['wt_file_upload'], $allowed ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- validated by wp_handle_upload.
	if ( $saved ) {
		update_post_meta( $post_id, 'wt_episode' === $post->post_type ? '_wt_video' : '_wt_file', $saved );
	}
}, 10, 2 );

/** Moves an uploaded file into the protected folder. Returns the stored file name or ''. */
function wt_store_protected_file( $file, $allowed ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
	wt_protect_upload_dir();
	$dir_filter = function ( $dirs ) {
		$dirs['path']   = wt_protected_dir();
		$dirs['url']    = '';
		$dirs['subdir'] = '';
		return $dirs;
	};
	add_filter( 'upload_dir', $dir_filter );
	$result = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $allowed ) );
	remove_filter( 'upload_dir', $dir_filter );
	return empty( $result['file'] ) ? '' : wp_basename( $result['file'] );
}
