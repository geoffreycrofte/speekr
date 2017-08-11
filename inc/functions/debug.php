<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Alias of speekr_dump().
 *
 * @param  (all)    $value The value to var_dump().
 * @return (string)        The var_dump() returned.
 */
function speekr_log( $value ) {
	speekr_dump( $value );
}

/**
 * Print a formatted a var_dump().
 *
 * @param  (all)    $value The value to var_dump().
 * @return (string)        The var_dump() returned.
 */
function speekr_dump( $value ) {
	echo '<pre style="padding:10px 20px;background:#FFF;color:#555;box-shadow: 0 4px 16px rgba(0,0,0,.15);">';
	var_dump( $value );
	echo '</pre>';
}