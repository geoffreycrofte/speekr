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
}
add_action( 'wp_enqueue_scripts', 'speekr_enqueues_infront' );
