<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get youtube video ID from an URL.
 * @param  (string) $url The URL of the video.
 * @return (string)      The id of the video.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_youtube_id( $url ) {
	$url = explode( '?v=', $url );
	return $url[1];
}

/**
 * Get vimeo video ID from an URL.
 * @param  (string) $url The URL of the video.
 * @return (string)      The id of the video.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_vimeo_id( $url ) {
	var_dump($url);
	$url = explode( '/', $url ); // https:($0)/($1)/vimeo.com($2)/([id])$3
	return $url[3];
}

/**
 * Get speakerdeck iframe from an URL.
 * @param  (string) $url The speakerdeck URL.
 * @return (string)      The iframe for embed.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_speakerdeck_iframe( $url ) {
	$json_link = 'https://speakerdeck.com/oembed.json?url=' . $url;

	$response = wp_remote_get( $json_link );

	if ( is_wp_error( $response ) ) {
		return false;
	} else {
		$json = isset( $response['body'] ) ? json_decode( $response['body'] ) : '';
		
		return isset( $json->html ) ? $json->html : false;
	}
}

/**
 * Get slideshare iframe from an URL.
 * @param  (string) $url The slideshare URL.
 * @return (array)       The `html` with iframe for embed, and `thumbnail` image.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_slideshare_iframe( $url ) {
	$json_link = 'http://www.slideshare.net/api/oembed/2?url=' . $url . '&format=json';

	$response = wp_remote_get( $json_link );

	if ( is_wp_error( $response ) ) {
		return false;
	} else {
		$json = isset( $response['body'] ) ? json_decode( $response['body'] ) : '';
		
		$slideshare = array(
			'html'      => isset( $json->html ) ? $json->html : '',
			'thumbnail' => isset( $json->slide_image_baseurl ) ? $json->slide_image_baseurl . '1' . $json->slide_image_baseurl_suffix : '',
		);
		
		return isset( $json->html ) ? $slideshare : false;
	}
}

/**
 * Get Slides iframe from an URL.
 * @param  (string) $url The Slides URL.
 * @return (string)      The URL to put in an iframe.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_slides_iframe_url( $url ) {
	return '//' . trailingslashit( preg_replace( '(^https?://)', '', $url ) ) . 'embed?style=dark';
}
