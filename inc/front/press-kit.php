<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Serve the speaker press-kit ZIP download via template_redirect.
 *
 * URL: /?speekr_kit={post_id}
 * Public — URL is only rendered in render.php when allowDownload is true.
 *
 * Using template_redirect instead of the REST API avoids the JSON pipeline
 * setting Content-Type: application/json before our callback runs, which
 * prevented binary file downloads from completing correctly.
 *
 * @since 4.0
 */
add_action( 'template_redirect', 'speekr_maybe_serve_press_kit' );

function speekr_maybe_serve_press_kit() {
	$post_id = absint( get_query_var( 'speekr_kit', 0 ) );
	if ( ! $post_id ) {
		return;
	}

	if ( 'speekr_speaker' !== get_post_type( $post_id ) ) {
		wp_die( esc_html__( 'Speaker not found.', 'speekr' ), 404 );
	}

	if ( ! class_exists( 'ZipArchive' ) ) {
		wp_die( esc_html__( 'ZIP support not available on this server.', 'speekr' ), 501 );
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
		wp_die( esc_html__( 'Could not create archive.', 'speekr' ), 500 );
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
		wp_die( esc_html__( 'Archive could not be generated.', 'speekr' ), 500 );
	}

	header( 'Content-Type: application/zip' );
	header( 'Content-Disposition: attachment; filename="speaker-kit.zip"' );
	header( 'Content-Length: ' . filesize( $zip_path ) );
	header( 'Cache-Control: no-cache, no-store, must-revalidate' );
	readfile( $zip_path );
	unlink( $zip_path );
	exit;
}

/**
 * Register speekr_kit as a recognised query var so get_query_var() picks it up.
 */
add_filter( 'query_vars', function( $vars ) {
	$vars[] = 'speekr_kit';
	return $vars;
} );
