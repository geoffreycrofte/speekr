<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get a precise option from Speekr plugin options
 *
 * @param  string  $option  The option name
 * @param  string  $default The fallback value if option doesn't exist
 * @return string           The option value
 */
function speekr_get_option( $option, $default = false) {
	/**
	 * Pre-filter any Speekr option before read
	 *
	 * @since 1.0
	 * @param variant $default The default value
	*/
	$value = apply_filters( 'pre_speekr_get_option_' . $option, NULL, $default );

	if ( NULL !== $value ) {
		return $value;
	}

	$plugins = get_site_option( 'active_sitewide_plugins');
	$options = isset( $plugins[ SPEEKR_SLUG . '/' . SPEEKR_SLUG . '.php' ] ) ? get_site_option( SPEEKR_SETTING_SLUG ) : get_option( SPEEKR_SETTING_SLUG );
	$value 	 = isset( $options[ $option ] ) && $options[ $option ] !== '' ? $options[ $option ] : $default;
	
	/**
	 * Filter any Speekr option after read
	 *
	 * @since 1.0
	 * @param variant $default The default value
	*/
	return apply_filters( 'speekr_get_option_' . $option, $value, $default );
}

/**
 * Get all the Speekr plugin options
 *
 * @return array
 * 
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_get_options() {
	/**
	 * Pre-filter any Speekr option before read
	 *
	 * @since 1.0
	 * @param variant $default The default value
	*/
	$value = apply_filters( 'pre_speekr_get_options', NULL );

	if ( NULL !== $value ) {
		return $value;
	}

	$plugins = get_site_option( 'active_sitewide_plugins' );
	$options = isset( $plugins[ SPEEKR_SLUG . '/' . SPEEKR_SLUG . '.php' ] ) ? get_site_option( SPEEKR_SETTING_SLUG ) : get_option( SPEEKR_SETTING_SLUG );

	/**
	 * Filter any Speekr option after read
	 *
	 * @since 1.0
	 * @param variant $default The default value
	*/
	return apply_filters( 'speekr_get_options', $options );
}

/**
 * Updating options to the right place.
 * Multisite compatibility.
 *
 * @param (array) $options The array of option for Speekr
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_update_option( $options ) {

	if ( ! is_array( $options ) ) {
		die( '$options has to be an array' );
	}

	// When we want to update options in a network activated website.
	if ( true === SPEEKR_NETWORK_ACTIVATED ) {
		$options = update_blog_option( get_current_blog_id(), SPEEKR_SETTING_SLUG, $options );
	}

	// When we want to update options in a simple website.
	else {
		$options = update_option( SPEEKR_SETTING_SLUG, $options );
	}

	return $options;
}
