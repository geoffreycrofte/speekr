<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Plugin Name: Speekr
 * Plugin URI: http://wordpress.org/extend/plugins/speekr
 * Description: Create an archive with the talks you gave around the world.
 * Author: <a href="https://geoffrey.crofte.fr">Geoffrey Crofte</a>, <a href="https://stephaniewalter.fr/">Stephanie Walter</a>
 * Version: 1.0
 * Author URI: https://crofte.fr
 * License: GPLv2 or later
 * Text Domain: speekr
 */

define( 'SPEEKR_PLUGIN_NAME',   'Speekr' );
define( 'SPEEKR_VERSION',       '1.0' );
define( 'SPEEKR_FILE',          __FILE__ );
define( 'SPEEKR_DIRNAME',       dirname( SPEEKR_FILE ) );
define( 'SPEEKR_CLASSES_DIR',   SPEEKR_DIRNAME . '/inc/classes/' );
define( 'SPEEKR_DIRBASENAME',   basename( dirname( SPEEKR_FILE ) ) );
define( 'SPEEKR_PLUGIN_URL',    plugin_dir_url( SPEEKR_FILE ));
define( 'SPEEKR_SLUG',          'speekr' );
define( 'SPEEKR_SETTING_SLUG', 'speekr_settings' );

require_once( SPEEKR_CLASSES_DIR . 'Speekr.php' );

$speekr = new Speekr();
