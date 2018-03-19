<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Create the default Talks page and save it as Speekr option.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_create_default_page() {
	$data = array();

	if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] , 'create_default_page' ) ) {
		if ( ! get_current_user_id() && current_user_can( 'manage_options' ) ) {
			$data['message'] = __( 'You are not allowed to edit this item.' );
			wp_send_json_error( $data  );
			exit;
		}
		
		$title = __( 'My Talks', 'speekr' );
		$inserted = wp_insert_post( speekr_get_my_talks_page_args(), false );

		if ( $inserted ) {
			// Edit Speekr option to set this new page.
			speekr_update_option( 'list_page', (int) $inserted );

			// Compose data to send.
			$data['item_title'] = $title;
			$data['item_id']    = $inserted;
			$data['message']    = sprintf( __( 'Page created as draft and saved as Talks page. %sedit the page%s' ), '(<a href="' . get_edit_post_link( (int) $inserted ) . '" target="_blank">', '</a>)' );

			wp_send_json_success( $data );
		} else {
			$date['message'] = __( 'Error while creating the page :/', 'speekr' );
			wp_send_json_error( $data );	
		}
	} else {
		wp_send_json_error( $data );
	}
	exit;
}
add_action( 'wp_ajax_nopriv_speekr_create_default_page', 'speekr_create_default_page' );
add_action( 'wp_ajax_speekr_create_default_page', 'speekr_create_default_page' );
