<?php
/**
 * Speekr Shortcodes
 *
 * [speekr_profile] - renders the speaker profile block
 * [speekr_talks]   - renders the talks list block
 * [speekr_map]     - renders the conference map block
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * [speekr_profile layout="side-by-side|stacked" show_download="0|1"]
 *
 * Renders the speaker profile block output.
 * Block CSS is enqueued; WordPress deduplicates if the block is also on the page.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML output.
 */
function speekr_shortcode_profile( $atts ) {
	$atts = shortcode_atts(
		array(
			'layout'        => 'side-by-side',
			'show_download' => 0,
		),
		$atts,
		'speekr_profile'
	);

	$attributes = array(
		'layout'        => sanitize_key( $atts['layout'] ),
		'allowDownload' => (bool) $atts['show_download'],
	);

	wp_enqueue_style(
		'speekr-speaker-profile-style',
		SPEEKR_PLUGIN_URL . 'build/blocks/speaker-profile/style-index.css',
		array(),
		SPEEKR_VERSION
	);

	ob_start();
	require SPEEKR_DIRNAME . '/src/blocks/speaker-profile/render.php';
	return ob_get_clean();
}
add_shortcode( 'speekr_profile', 'speekr_shortcode_profile' );

/**
 * [speekr_talks layout="grid|list"]
 *
 * Renders the talks list block output.
 * Block CSS and view JS are enqueued; WordPress deduplicates if the block is also on the page.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML output.
 */
function speekr_shortcode_talks( $atts ) {
	$atts = shortcode_atts(
		array(
			'layout' => 'grid',
		),
		$atts,
		'speekr_talks'
	);

	$attributes = array(
		'layout' => sanitize_key( $atts['layout'] ),
	);

	wp_enqueue_style(
		'speekr-talks-list-style',
		SPEEKR_PLUGIN_URL . 'build/blocks/talks-list/style-index.css',
		array(),
		SPEEKR_VERSION
	);
	wp_enqueue_script(
		'speekr-talks-list-view',
		SPEEKR_PLUGIN_URL . 'build/blocks/talks-list/view.js',
		array(),
		SPEEKR_VERSION,
		true
	);

	ob_start();
	require SPEEKR_DIRNAME . '/src/blocks/talks-list/render.php';
	return ob_get_clean();
}
add_shortcode( 'speekr_talks', 'speekr_shortcode_talks' );

/**
 * [speekr_map height="450"]
 *
 * Renders the conference map block output.
 * Block CSS and view JS (Leaflet) are enqueued; WordPress deduplicates if the block is also on the page.
 *
 * @param array $atts Shortcode attributes.
 * @return string Rendered HTML output.
 */
function speekr_shortcode_map( $atts ) {
	$atts = shortcode_atts(
		array(
			'height' => 450,
		),
		$atts,
		'speekr_map'
	);

	$attributes = array(
		'height' => (int) $atts['height'],
	);

	wp_enqueue_style(
		'speekr-conference-map-style',
		SPEEKR_PLUGIN_URL . 'build/blocks/conference-map/style-index.css',
		array(),
		SPEEKR_VERSION
	);
	wp_enqueue_script(
		'speekr-conference-map-view',
		SPEEKR_PLUGIN_URL . 'build/blocks/conference-map/view.js',
		array(),
		SPEEKR_VERSION,
		true
	);

	ob_start();
	require SPEEKR_DIRNAME . '/src/blocks/conference-map/render.php';
	return ob_get_clean();
}
add_shortcode( 'speekr_map', 'speekr_shortcode_map' );
