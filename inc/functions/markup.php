<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

if ( ! function_exists( 'speekr_get_media_header' ) ) {
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
}

if ( ! function_exists( 'speekr_get_talk_links' ) ) {
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

		foreach ( $auth_links as $slug => $datas ) {

			$target = apply_filters( 'speekr_talk_links_target', '', $slug );
			$title  = sprintf( __( 'See this talk on %s', 'speekr' ), $datas['name'] );
			$class  = apply_filters( 'speekr_talk_links_classes', 'talk-link talk-link-' . $slug, $slug ); 

			if ( 'other' === $slug && is_array( $meta['other-link'] ) && ! empty( $meta['other-link'] ) ) {
				foreach ( $meta['other-link'] as $link ) {
					
					$more_class = apply_filters( 'speekr_talk_links_other_classes', 'talk-link-' . sanitize_key( $link['label'] ), $link );

					$output .= '<a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $title ) . '"' . $target . ' class="' . esc_attr( $class ) . ' ' . esc_attr( $more_class ) . '">' . esc_html( $link['label'] ) . '</a>' . "\n";
				}
			}
			if ( 'other' !== $slug && isset( $meta[ $slug . '-link' ] ) && ! empty( $meta[ $slug . '-link' ] ) ) {
				$output .= '<a href="' . esc_url( $meta[ $slug . '-link' ] ) . '" title="' . esc_attr( $title ) . '"' . $target . ' class="' . esc_attr( $class ) . '">' . esc_html( $datas['name'] ) . '</a>' . "\n";
			}
		}

		return apply_filters( 'speekr_get_talk_links', $output, $post_id, $meta );
	}
}

if ( ! function_exists( 'speekr_get_loader' ) ) {
	/**
	 * Get the markup for our SVG loader.
	 * @return (string) The SVG markup.
	 *
	 * @author Geoffrey Crofte
	 * @since  1.0
	 */
	function speekr_get_loader() {
		$loader = '<div class="speekr-loader" aria-hidden="true"><svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="30px" viewBox="0 0 24 30" style="enable-background:new 0 0 50 50;" xml:space="preserve">
			<rect x="0" y="0" width="4" height="10" fill="#333">
				<animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0" dur="0.6s" repeatCount="indefinite" />
			</rect>
			<rect x="10" y="0" width="4" height="10" fill="#333">
				<animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.2s" dur="0.6s" repeatCount="indefinite" />
			</rect>
			<rect x="20" y="0" width="4" height="10" fill="#333">
				<animateTransform attributeType="xml" attributeName="transform" type="translate" values="0 0; 0 20; 0 0" begin="0.4s" dur="0.6s" repeatCount="indefinite" />
			</rect>
		</svg></div>';

		return apply_filters( 'speekr_get_loader', $loader );
	}
}
