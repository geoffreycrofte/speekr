<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Enqueue styles and scripts files
 *
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_enqueues() {

	if ( 
		( isset( $_GET['page'] ) && $_GET['page'] === 'speekr' )
		||
		( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'talks' )
		||
		( isset( $_GET['post'] ) && get_post_type( (int) $_GET['post'] ) === 'talks' )
	) {
		wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/admin.css', array(), false, 'all' );
	}
}
add_action( 'admin_enqueue_scripts', 'speekr_enqueues' );
