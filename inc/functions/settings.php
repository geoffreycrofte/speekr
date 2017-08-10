<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get the list of content media links.
 *
 * @return (array) The list of content media links.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_get_content_media_links() {
	return apply_filters( 'speekr_media_links', array() );
}
