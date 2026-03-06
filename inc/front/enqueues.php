<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Enqueue styles and scripts files in front.
 *
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_enqueues_infront() {
	$current = get_queried_object();
	$options = speekr_get_options();

	if ( ! is_object( $current ) ) {
		return;
	}
	// If the current object is a listed page for Speekr, or if it's the current CPT page/archive.
	// get_queried_object() returns WP_Post on singular/page, WP_Post_Type on CPT archives.
	$cpt = speekr_get_cpt_slug();
	$is_speekr = ( $current instanceof WP_Post && isset( $options['list_page'] ) && $options['list_page'] == $current->ID )
		|| ( $current instanceof WP_Post && $current->post_type === $cpt )
		|| ( $current instanceof WP_Post_Type && $current->name === $cpt );

	if ( $is_speekr ) {
		wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'build/frontend/style-style.css', array(), SPEEKR_VERSION, 'all' );
	}

	// Enqueue block CSS for classic theme CPT pages (bypassed when render.php is
	// included directly in a template rather than via render_block()).
	if ( is_singular( 'speekr_speaker' ) ) {
		wp_enqueue_style( 'speekr-speaker-profile-style', SPEEKR_PLUGIN_URL . 'build/blocks/speaker-profile/style-index.css', array(), SPEEKR_VERSION );
	}
	if ( is_post_type_archive( 'talks' ) || is_singular( 'talks' ) ) {
		wp_enqueue_style( 'speekr-talks-list-style', SPEEKR_PLUGIN_URL . 'build/blocks/talks-list/style-index.css', array(), SPEEKR_VERSION );
	}
	if ( is_singular( 'talks' ) ) {
		wp_enqueue_style( 'speekr-single-talk-style', SPEEKR_PLUGIN_URL . 'build/blocks/single-talk/style-index.css', array(), SPEEKR_VERSION );
	}
	if ( is_post_type_archive( 'speekr_conference' ) || is_singular( 'speekr_conference' ) ) {
		wp_enqueue_style( 'speekr-conference-archive-style', SPEEKR_PLUGIN_URL . 'build/blocks/conference-archive/style-index.css', array(), SPEEKR_VERSION );
	}
}
add_action( 'wp_enqueue_scripts', 'speekr_enqueues_infront' );
