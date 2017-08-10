<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get header markup for a precise talk.
 *
 * @param  (int)    $post_id  The post ID.
 * @param  (array)  $$meta    An array of post options.
 * @return (string)           The header markup.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_media_header( $post_id, $meta = null ) {
	if ( $meta === null ) {
		get_post_meta( $post_id, 'speekr-media-links', true );
	}
}