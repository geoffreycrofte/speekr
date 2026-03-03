<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register the press-kit download REST endpoint.
 *
 * Endpoint: GET /wp-json/speekr/v1/press-kit/{id}
 * Public (no auth required) — URL is only rendered in render.php when
 * the block's allowDownload attribute is true.
 *
 * @since 4.0
 */
add_action( 'rest_api_init', function() {
	register_rest_route( 'speekr/v1', '/press-kit/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'callback'            => 'speekr_press_kit_download',
		'permission_callback' => '__return_true',
		'args'                => array(
			'id'    => array( 'sanitize_callback' => 'absint' ),
			'token' => array( 'sanitize_callback' => 'sanitize_text_field' ),
		),
	) );
} );

/**
 * Generate and stream the speaker press-kit ZIP.
 *
 * @param WP_REST_Request $request
 * @return WP_Error|void
 * @since 4.0
 */
function speekr_press_kit_download( WP_REST_Request $request ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return new WP_Error(
			'zip_unavailable',
			__( 'ZIP support not available on this server.', 'speekr' ),
			array( 'status' => 501 )
		);
	}

	$post_id = $request->get_param( 'id' );

	$token    = (string) $request->get_param( 'token' );
	$expected = hash_hmac( 'sha256', 'speekr-kit|' . $post_id, AUTH_KEY );
	if ( ! hash_equals( $expected, $token ) ) {
		return new WP_Error( 'forbidden', __( 'Invalid or missing token.', 'speekr' ), array( 'status' => 403 ) );
	}

	if ( 'speekr_speaker' !== get_post_type( $post_id ) ) {
		return new WP_Error( 'not_found', __( 'Speaker not found.', 'speekr' ), array( 'status' => 404 ) );
	}

	$headshots    = get_post_meta( $post_id, '_speekr_headshots', true ) ?: array();
	$bio_short    = get_post_meta( $post_id, '_speekr_bio_short', true ) ?: '';
	$social_links = get_post_meta( $post_id, '_speekr_social_links', true ) ?: array();
	$rider        = get_post_meta( $post_id, '_speekr_rider', true ) ?: array();
	$post_content = get_post_field( 'post_content', $post_id );
	$speaker_name = get_the_title( $post_id );

	// Build combined speaker-kit.md
	$md  = '# Speaker Kit: ' . $speaker_name . "\n\n";
	$md .= '## Short Bio' . "\n\n" . $bio_short . "\n\n";
	$md .= '## Full Bio' . "\n\n" . wp_strip_all_tags( $post_content ) . "\n\n";

	if ( ! empty( $social_links ) ) {
		$md .= '## Social Links' . "\n\n";
		foreach ( $social_links as $link ) {
			$platform = isset( $link['platform'] ) ? sanitize_text_field( $link['platform'] ) : '';
			$url      = isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '';
			$label    = ! empty( $link['label'] ) ? sanitize_text_field( $link['label'] ) : $platform;
			if ( $platform && $url ) {
				$md .= '- **' . $platform . '**: [' . $label . '](' . $url . ')' . "\n";
			}
		}
		$md .= "\n";
	}

	if ( ! empty( $rider ) ) {
		$md .= '## Speaker Preferences' . "\n\n";
		$fields = array( 'av' => 'A/V Requirements', 'travel' => 'Travel', 'dietary' => 'Dietary', 'accessibility' => 'Accessibility' );
		foreach ( $fields as $key => $label ) {
			if ( ! empty( $rider[ $key ] ) ) {
				$md .= '### ' . $label . "\n\n" . sanitize_textarea_field( $rider[ $key ] ) . "\n\n";
			}
		}
	}

	$zip_path = sys_get_temp_dir() . '/speekr-press-kit-' . $post_id . '-' . time() . '.zip';
	$zip      = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		return new WP_Error( 'zip_failed', __( 'Could not create archive.', 'speekr' ), array( 'status' => 500 ) );
	}

	foreach ( $headshots as $hs ) {
		$img_path = isset( $hs['id'] ) ? get_attached_file( (int) $hs['id'] ) : false;
		if ( $img_path && file_exists( $img_path ) ) {
			$zip->addFile( $img_path, 'headshots/' . basename( $img_path ) );
		}
	}

	$zip->addFromString( 'speaker-kit.md', $md );
	$zip->close();

	if ( ! file_exists( $zip_path ) ) {
		return new WP_Error( 'zip_missing', __( 'Archive could not be generated.', 'speekr' ), array( 'status' => 500 ) );
	}

	// Drain all WordPress/PHP output buffers so readfile() writes directly to the socket.
	while ( ob_get_level() ) {
		ob_end_clean();
	}

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="speaker-kit.zip"' );
	header( 'Content-Length: ' . filesize( $zip_path ) );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );
	readfile( $zip_path );
	unlink( $zip_path );
	exit;
}
