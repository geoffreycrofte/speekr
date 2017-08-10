<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Set the default list of content media links.
 *
 * @return (array) The list of content media links.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_add_default_media_links( $links ) {
	$default = array(
		'youtube'     => array(
			'name' => __( 'Youtube', 'speekr' ),
		),
		'vimeo'       => array(
			'name' => __( 'Vimeo', 'speekr' ),
		),
		'speakerdeck' => array(
			'name' => __( 'SpeakerDeck', 'speekr' ),
		),
		'slideshare'  => array(
			'name' => __( 'Slideshare', 'speekr' ),
		),
		'other'       => array(
			'name' => __( 'Other', 'speekr' ),
		),
	);
	
	$links = array_merge( $links, $default );

	return $links;
}
add_filter( 'speekr_media_links', 'speekr_add_default_media_links' );
