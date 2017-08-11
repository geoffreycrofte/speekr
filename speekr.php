<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Plugin Name: Speekr
 * Plugin URI: http://wordpress.org/extend/plugins/speekr
 * Description: Create an archive with the talks you give around the world.
 * Author: Geoffrey Crofte, Stephanie Walter
 * Version: 1.0
 * Author URI: https://crofte.fr
 * License: GPLv2 or later
 * Text Domain: speekr
 */

define( 'SPEEKR_PLUGIN_NAME',	 'Speekr' );
define( 'SPEEKR_VERSION',      '1.0' );
define( 'SPEEKR_FILE',		 __FILE__ );
define( 'SPEEKR_DIRNAME',		 dirname( SPEEKR_FILE ) );
define( 'SPEEKR_DIRBASENAME',	 basename( dirname( SPEEKR_FILE ) ) );
define( 'SPEEKR_PLUGIN_URL',	 plugin_dir_url( SPEEKR_FILE ));
define( 'SPEEKR_SLUG',		 'speekr' );
define( 'SPEEKR_SETTING_SLUG', 'speekr_settings' );

// Checking network activation
$is_nw_activated = function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( SPEEKR_SLUG . '/' . SPEEKR_SLUG . '.php' ) ? true : false;

define( 'SPEEKR_NETWORK_ACTIVATED', $is_nw_activated );

/**
 * Make plugin translatable
 *
 * @return void
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_textdomain() {
	load_plugin_textdomain( 'speekr', false, SPEEKR_DIRBASENAME . '/languages' );
}
add_action( 'init', 'speekr_textdomain' );

/**
 * Includes all the thing
 */

// Admin and Front cases
require_once( SPEEKR_DIRNAME . '/inc/functions/debug.php' );
require_once( SPEEKR_DIRNAME . '/inc/common/custom-posts.php' );
require_once( SPEEKR_DIRNAME . '/inc/functions/options.php' );
require_once( SPEEKR_DIRNAME . '/inc/functions/settings.php' );
require_once( SPEEKR_DIRNAME . '/inc/common/default-types.php' );

// Only Admin case.
if ( is_admin() ) {
	require_once( SPEEKR_DIRNAME . '/inc/admin/enqueues.php' );
	require_once( SPEEKR_DIRNAME . '/inc/admin/settings.php' );
	require_once( SPEEKR_DIRNAME . '/inc/admin/menus.php' );
	require_once( SPEEKR_DIRNAME . '/inc/admin/custom-meta-boxes.php' );
}
// Only Front case
else {
	require_once( SPEEKR_DIRNAME . '/inc/front/enqueues.php' );
	require_once( SPEEKR_DIRNAME . '/inc/functions/markup.php' );
	require_once( SPEEKR_DIRNAME . '/inc/front/lists.php' );
}
