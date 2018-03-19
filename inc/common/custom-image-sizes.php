<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * The default media width.
 *
 * @return (int) Width in pixels.
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_medias_width() {
	return apply_filters( 'speekr_get_medias_width', 1600 );
}

/**
 * The default media height.
 *
 * @return (int) height in pixels.
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_medias_height() {
	return apply_filters( 'speekr_get_medias_height', 900 );
}

/**
 * Get the name of media sizes.
 *
 * @param  (string) $type The size you want to get.
 * @return (string)       The size name.
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_media_name( $type = 'list' ) {
	if ( 'list' === $type ) {
		return apply_filters( 'speekr_get_media_name_list', 'speekr-list' );
	} else {
		return apply_filters( 'speekr_get_media_name_big', 'speekr-big' );
	}
}

$width  = speekr_get_medias_width();
$height = speekr_get_medias_height();

// Add image sizes.
add_image_size( speekr_get_media_name( 'list' ), $width/2, $height/2, true );
add_image_size( speekr_get_media_name( 'big' ), $width, $height, true );
