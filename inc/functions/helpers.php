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

/**
 * Get the metas for a Talk CPT
 *
 * @param  (int)   $post_id The post ID to retrieve the metas from.
 * @return (array)          An array with all the Speekr Metas.
 */
function speekr_get_talk_metas( $post_id ) {
	
	$metas = array();

	if ( get_post_type( $post_id ) !== speekr_get_cpt_slug() ) {
		$metas['error'] = true;
	} else {
		$post_metas = get_post_meta( $post_id );
		$metas['media_links'] = unserialize( $post_metas['speekr-media-links'][0] ); // array
		$metas['conf_infos'] = unserialize( $post_metas['speekr-conf'][0] ); // array
		$metas['summary'] = $post_metas['speekr-summary'][0]; // string
		$metas['as_article'] = $post_metas['speekr-as-article'][0]; // "on"
	}

	return $metas;
}

/**
 * Get the current_user_can() WP Function return value, appropriate for this plugin.
 * @return (bool)
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_current_user_can_do() {
	return current_user_can( 'edit_users' );
}

/**
 * Restrict the CSS, JS and some other PHP functions to specific pages.
 * @return (bool)
 *
 * @author Geoffrey Crofte
 * @since 1.0
 * 
 */
function is_speekr_plugin_allowed_pages() {
	return ( isset( $_GET['page'] ) && $_GET['page'] === SPEEKR_SLUG )
		||
		( isset( $_GET['post_type'] ) && $_GET['post_type'] === speekr_get_cpt_slug() )
		||
		( isset( $_GET['post'] ) && get_post_type( (int) $_GET['post'] ) === speekr_get_cpt_slug() )
		||
		( 'plugins.php' === $pagenow );
}

/**
 * Returns the ID of a given page if it matches an existing setting ID for this page.
 * 
 * @param  (string)    $page   The setting slug of the page found in the plugin settings.
 * 
 * @return (int|bool)          The ID of the page, if found, or false if it doesn't match a setting info.
 */
function speekr_get_pages_id( $page ) {
	$options = speekr_get_options();

	if ( isset( $options[ $page ] ) ) {
		return (int) $options[ $page ];
	}

	return false;
}
