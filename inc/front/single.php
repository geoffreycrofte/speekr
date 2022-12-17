<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

function speekr_maybe_replace_post_thumbnail_html( $html ) {
	global $post;

	if ( get_post_type() === 'talks' ) {
		$meta = get_post_meta( $post->ID, 'speekr-media-links', true );

		if ( isset( $meta['embeded'] ) && $meta['embeded'] ) {
			$html = speekr_get_media_header( $post->ID, $meta );
		}
	}

	return $html;
}
add_filter( 'post_thumbnail_html', 'speekr_maybe_replace_post_thumbnail_html' );