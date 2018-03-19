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

/**
 * Get the list of CSS values.
 *
 * @return (array) The list of CSS values.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_get_admin_css_values() {
	return apply_filters( 'speekr_css_values', array() );
}

/**
 * Get the list of Layout values.
 *
 * @return (array) The list of Layout values.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_get_admin_layout_values() {
	return apply_filters( 'speekr_layout_values', array() );
}
