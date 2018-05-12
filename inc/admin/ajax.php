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

/**
 * Remove notice from being displayed for a specific user.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_remove_notice() {
	$data = array( 'message' => 'Security check issue…' );

	if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] , 'speekr_notice' ) ) {
		
		if ( ! get_current_user_id() && current_user_can( 'edit_users' ) ) {
			$data['message'] = __( 'You are not allowed to remove this notice.' );
			wp_send_json_error( $data  );
			exit;
		}
		
		// Edit Speekr usermeta for the user.
		$notices = speekr_get_user_meta( 'notice' );
		
		$notices[ $_POST['notice'] ] = 'off';

		$updated = speekr_update_user_meta( 'notice', $notices );

		if ( $updated ) {

			// Compose data to send.
			$data['user_id']    = get_current_user_id();
			$data['message']    = $updated;

			wp_send_json_success( $data );
		} else {
			$data['message'] = __( 'Error while removing the notice :/', 'speekr' );
			wp_send_json_error( $data );
		}
	} else {
		wp_send_json_error( $data );
	}
	exit;
}
add_action( 'wp_ajax_nopriv_speekr_remove_notice', 'speekr_remove_notice' );
add_action( 'wp_ajax_speekr_remove_notice', 'speekr_remove_notice' );
