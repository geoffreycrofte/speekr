<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get header markup for a precise talk.
 *
 * @param  (int)    $post_id   The post ID.
 * @param  (array)  $meta      An array of post options.
 * @param  (bool)   $is_linked True if talk is also a blog post.
 * @return (string)            The header markup.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_media_header( $post_id, $meta = null, $is_linked = false ) {
	if ( $meta === null ) {
		$meta = get_post_meta( $post_id, 'speekr-media-links', true );
	}

	$output = '';

	if ( isset( $meta['embeded'] ) && 'on' === $meta['embeded'] ) {
		// do something with embeded thing.
	} else {
		$thumbnail = get_the_post_thumbnail( $post_id );
		$thumbnail = $is_linked ? '<a href="' . get_permalink( $post_id ) . '">' . $thumbnail . '</a>' : $thumbnail;
		$output .= $thumbnail;
	}

	return apply_filters( 'speekr_get_media_header', $output, $post_id, $meta );
}

/**
 * Get links markup for a precise talk.
 *
 * @param  (int)    $post_id  The post ID.
 * @param  (array)  $meta    An array of post options.
 * @return (string)           The links markup.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_talk_links( $post_id, $meta = null ) {
	if ( $meta === null ) {
		$meta = get_post_meta( $post_id, 'speekr-media-links', true );
	}

	$auth_links = speekr_get_content_media_links();
	$output     = '';
	speekr_log($meta);

	foreach ( $auth_links as $slug => $datas ) {
		if ( 'other' === $slug ) {
			continue;
		}
		if ( isset( $meta[ $slug . '-link' ] ) && ! empty( $meta[ $slug . '-link' ] ) ) {
			$title  = sprintf( __( 'See this talk on %s', 'speekr' ), $datas['name'] );
			$target = apply_filters( 'speekr_talk_links_target', '', $slug );
			$class  = apply_filters( 'speekr-talk_links_classes', 'talk-link talk-link-' . $slug, $slug ); 

			$output .= '<a href="' . esc_url( $meta[ $slug . '-link' ] ) . '" title="' . esc_attr( $title ) . '"' . $target . ' class="' . esc_attr( $class ) . '">' . esc_html( $datas['name'] ) . '</a>';
		}
	}

	return apply_filters( 'speekr_get_talk_links', $output, $post_id, $meta );
}