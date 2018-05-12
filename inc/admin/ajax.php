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

/**
 * Import classical posts to Speekr talks type.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_import_posts() {
	$data = array( 'message' => 'Security check issue…' );

	if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'] , 'speekr_import_posts' ) ) {
		
		if ( ! get_current_user_id() && current_user_can( 'edit_users' ) ) {
			$data['message'] = __( 'You are not allowed to import posts.' );
			wp_send_json_error( $data  );
			exit;
		}

		// Set $posts array to be used later.
		$posts = array(
			'max'     => 0,
			'current' => 0,
			'list'    => array(),
		);

		// If JS sent CURRENT, it's because we are inside our increment loop, else get posts.
		if ( ! isset( $_POST[ 'current' ] ) ) {
			
			// Get posts from Tags,
			$tags = $_POST['posts']['tags'];

			$tags_query = new WP_Query( array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'tag__in'		 => $tags,
				'fields'         => 'ids',
			) );
			
			// Get posts from Cats,
			$cats = $_POST['posts']['cats'];

			$cats_query = new WP_Query( array(
				'post_type'      => 'post',
				'posts_per_page' => -1,
				'category__in'   => $cats,
				'fields'         => 'ids',
			) );

			$posts_list = array_merge( $cats_query, $tags_query );

			// Get our posts list.
			$posts_list = $_POST['posts']['posts'];
			
			// Get posts from item selection.
			$posts['max']     = count( $posts_list );
			$posts['current'] = 0;
			$posts['list']    = $posts_list;
		} else {
			$posts['current'] = (int) $_POST['current'] + 1;
		}

		// Set the new post type.
		$set = set_post_type( $posts['list'][ (int) $posts['current'] ], speekr_get_cpt_slug() );
		$data['post_id'] = $post_id;

		if ( $set ) {
			$data['post_id'] = $post_id;
			$data['message'] = $post_id . ' is now a Speekr talk';
			$data['datas']   = $posts;
			wp_send_json_success( $data );
		} else {
			$data['message'] = __( 'Cannot edit that post.', 'speekr' );
			$data['datas']   = $posts;
			wp_send_json_error( $data );
		}
	} else {
		wp_send_json_error( $data );
	}
	exit;
}
add_action( 'wp_ajax_nopriv_speekr_import_posts', 'speekr_import_posts' );
add_action( 'wp_ajax_speekr_import_posts', 'speekr_import_posts' );
