<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

class Speekr {

	public function __construct() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		
		$this->is_network();

		$this->includes();

		if ( is_admin() ) {
			$this->includes_admin();
		} else {
			$this->includes_front();
		}

		register_activation_hook( SPEEKR_FILE, array( $this, 'install' ) );
	}


	/**
	 * Set a define for network activation.
	 *
	 * @return void
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	public function is_network() {
		$is_nw_activated = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( SPEEKR_SLUG . '/' . SPEEKR_SLUG . '.php' ) ? true : false;

		define( 'SPEEKR_NETWORK_ACTIVATED', $is_nw_activated );
	}

	/**
	 * Make plugin translatable
	 *
	 * @return void
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'speekr', false, SPEEKR_DIRBASENAME . '/languages' );
	}

	/**
	 * Generate needed datas.
	 *
	 * @return void
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	public function install() {
		$speekr_options = speekr_get_options();

		if (
			! isset( $speekr_options['list_page'] )
			||
			( isset( $speekr_options['list_page'] ) && ! get_post( $speekr_options['list_page'] ) )
		) {
			$talks_page = wp_insert_post( speekr_get_my_talks_page_args(), true );

			if ( ! is_wp_error( $talks_page ) ) {
				speekr_update_option( 'list_page', (int) $talks_page );
			}
		}
	}

	/**
	 * Includes front and admin components.
	 *
	 * @return void
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	public function includes() {
		do_action( 'speekr_before_includes' );

		require_once( SPEEKR_DIRNAME . '/inc/functions/debug.php' );
		require_once( SPEEKR_DIRNAME . '/inc/common/custom-posts.php' );
		require_once( SPEEKR_DIRNAME . '/inc/common/custom-image-sizes.php' );
		require_once( SPEEKR_DIRNAME . '/inc/functions/helpers.php' );
		require_once( SPEEKR_DIRNAME . '/inc/functions/markup.php' );
		require_once( SPEEKR_DIRNAME . '/inc/functions/options.php' );
		require_once( SPEEKR_DIRNAME . '/inc/functions/usermeta.php' );
		require_once( SPEEKR_DIRNAME . '/inc/functions/settings.php' );
		require_once( SPEEKR_DIRNAME . '/inc/common/default-types.php' );

		do_action( 'speekr_after_includes' );
	}

	/**
	 * Includes admin components.
	 *
	 * @return void
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	public function includes_admin() {
		do_action( 'speekr_before_includes_admin' );

		require_once( SPEEKR_DIRNAME . '/inc/functions/urls.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/notices.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/ajax.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/enqueues.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/settings.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/importer.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/menus.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/custom-meta-boxes.php' );

		do_action( 'speekr_after_includes_admin' );
	}

	/**
	 * Includes front components.
	 *
	 * @return void
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	public function includes_front() {
		do_action( 'speekr_before_includes_front' );

		require_once( SPEEKR_DIRNAME . '/inc/front/enqueues.php' );
		require_once( SPEEKR_DIRNAME . '/inc/front/lists.php' );

		do_action( 'speekr_after_includes_front' );
	}

}