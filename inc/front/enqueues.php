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
	// If the current object is a listed page for Speekr, or if it's the current CPT page.
	if ( 
		( isset( $options['list_page'] ) && $options['list_page'] == $current->ID )
		||
		( $current->post_type === speekr_get_cpt_slug() )
	) {
		wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/speekr.min.css', array(), SPEEKR_VERSION, 'all' );
	}
}
add_action( 'wp_enqueue_scripts', 'speekr_enqueues_infront' );
