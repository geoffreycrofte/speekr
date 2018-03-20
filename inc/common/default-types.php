<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Set the default list of content media links.
 *
 * @param (array)  $links Array of existing links.
 * @return (array)        The list of content media links.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_add_default_media_links( $links ) {
	$default = array(
		'youtube'     => array(
			'name' => __( 'Youtube', 'speekr' ),
			'type' => 'video',
		),
		'vimeo'       => array(
			'name' => __( 'Vimeo', 'speekr' ),
			'type' => 'video',
		),
		'dailymotion' => array(
			'name' => __( 'Dailymotion', 'speekr' ),
			'type' => 'video',
		),
		'slides' => array(
			'name' => __( 'Slides', 'speekr' ),
			'type' => 'presentation',
		),
		'speakerdeck' => array(
			'name' => __( 'SpeakerDeck', 'speekr' ),
			'type' => 'presentation',
		),
		'slideshare'  => array(
			'name' => __( 'Slideshare', 'speekr' ),
			'type' => 'presentation',
		),
		'other'       => array(
			'name' => __( 'Other', 'speekr' ),
			'type' => 'misc',
		),
	);
	
	$links = array_merge( $links, $default );

	return $links;
}
add_filter( 'speekr_media_links', 'speekr_add_default_media_links' );

/**
 * Set the default list of CSS values.
 *
 * @param (array)  $css   Array of existing values.
 * @return (array)        The list of CSS values.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_add_default_css_values( $css ) {
	$default = array(
		'both'   => _x( 'Yes, layout and painting.', 'admin option', 'speekr' ),
		'layout' => _x( 'Yes, layout only.', 'admin option', 'speekr' ),
		'nope'   => _x( 'Nope. Remove it.', 'admin option', 'speekr' ),
	);

	$css = array_merge( $css, $default );

	return $css;
}
add_filter( 'speekr_css_values', 'speekr_add_default_css_values' );

/**
 * Set the default list of Layouts.
 *
 * @param (array)  $layout Array of existing values.
 * @return (array)         The list of CSS values.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_add_default_layout_values( $layout ) {
	$default = array(
		'grid'  => __( 'Grid', 'speekr' ),
		'list'  => __( 'List', 'speekr' ),
	);

	$layout = array_merge( $layout, $default );

	return $layout;
}
add_filter( 'speekr_layout_values', 'speekr_add_default_layout_values' );