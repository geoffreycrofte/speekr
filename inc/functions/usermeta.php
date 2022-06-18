<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get a precise meta from Speekr user metadata.
 *
 * @param  (string)       $option  A precise meta.
 * @return (string|array)          String or array depending on the option format.
 *
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_get_user_meta( $option = null ) {
	
	$meta = get_user_meta( get_current_user_id(), 'speekr', true );

	if ( null !== $option && isset( $meta[ $option ] ) ) {
		$meta = $meta[ $option ];
	}

	// Be sure to send back an array.
	$meta = is_array( $meta ) ? $meta : array();
	
	/**
	 * Filter any Speekr User Metadata after read.
	 *
	 * @since 1.0
	 * @param (string|array) $meta   The read value.
	 * @param (null|string)  $option The requested option if exists.
	*/
	return apply_filters( 'speekr_get_user_meta', $meta, $option );
}

/**
 * Updating User Metadata, all of them.
 *
 * @param (array)   $metas  The array of user meta for Speekr
 * @return (string)         update_user_meta() function return.
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_update_user_metas( $options ) {

	if ( ! is_array( $options ) ) {
		die( '$options has to be an array' );
	}

	// When we want to update options in a network activated website.
	$user_meta = update_user_meta( get_current_user_id(), 'speekr', $options );

	return $user_meta;
}

/**
 * Updating a specific user meta.
 *
 * @param (string) $the_meta  The usermeta to update.
 * @param (string) $the_value The value of the usermeta.
 * @return (string)           update_user_meta() function return.
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_update_user_meta( $the_meta, $the_value ) {

	$metas = speekr_get_user_meta();

	$metas[ $the_meta ] = $the_value;

	return speekr_update_user_metas( $metas );

}
