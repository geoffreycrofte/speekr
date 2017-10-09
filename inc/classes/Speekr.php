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
		require_once( SPEEKR_DIRNAME . '/inc/functions/options.php' );
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

		require_once( SPEEKR_DIRNAME . '/inc/admin/enqueues.php' );
		require_once( SPEEKR_DIRNAME . '/inc/admin/settings.php' );
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
		require_once( SPEEKR_DIRNAME . '/inc/functions/markup.php' );
		require_once( SPEEKR_DIRNAME . '/inc/front/lists.php' );

		do_action( 'speekr_after_includes_front' );
	}

}