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

		$output = $classes = $embed = '';

		if ( isset( $meta['embeded'] ) && $meta['embeded'] ) {
			// do something with embeded thing.
			switch ( $meta['embeded-media'] ) {
				case 'youtube':
					$embed = ! empty( $meta['youtube-link'] ) ? '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . speekr_get_youtube_id( $meta['youtube-link'] ) . '?rel=0" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>' : false;
					$classes = ' talk-iframe';
					break;
				
				case 'vimeo':
					$embed = ! empty( $meta['vimeo-link'] ) ? '<iframe src="https://player.vimeo.com/video/' . speekr_get_vimeo_id( $meta['vimeo-link'] ) . '?title=0&byline=0&portrait=0" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>' : false;
					$classes = ' talk-iframe';
					break;

				case 'speakerdeck':
					$embed = ! empty( $meta['speakerdeck-link'] ) ? speekr_get_speakerdeck_iframe( $meta['speakerdeck-link']) : false;
					$classes = ' talk-iframe';
					break;

				case 'slideshare':
					$slideshare = ! empty( $meta['slideshare-link'] ) ? speekr_get_slideshare_iframe( $meta['slideshare-link'] ) : false;
					$embed = isset( $slideshare['html'] ) ? $slideshare['html'] : false;
					$img   = isset( $slideshare['thumbnail'] ) ? $slideshare['thumbnail'] : '';
					$classes = ' talk-iframe';
					break;

				case 'dailymotion':
					$embed = '';
					break;

				case 'slides':
					$embed = ! empty ( $meta['slides-link'] ) ? '<iframe src="' . speekr_get_slides_iframe_url( $meta['slides-link'] ) . '" scrolling="no" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>' : false;
					$classes = ' talk-iframe';
					break;

				default:
					$embed = '';
					break;
			}

			$classes = apply_filters( 'speekr_media_embed_header_classes', $classes, $embed );
			$embed   = apply_filters( 'speekr_media_embed_header', $embed, $classes );

			do_action( 'speekr_media_embed_header_before_ouput', $embed, $classes );

			$img      = isset( $img ) ? ';background-image:url(' . esc_url( $img ) . ')' : '';
			$classes .= ! empty( $classes ) ? '" style="padding-bottom:' . speekr_get_medias_height() / speekr_get_medias_width() * 100 . '%' . $img : '';
			$output  .= $embed ? $embed : '';

		} else {
			$thumbnail = get_the_post_thumbnail( $post_id, speekr_get_media_name( 'list' ) );
			$thumbnail = $is_linked ? '<a href="' . get_permalink( $post_id ) . '">' . $thumbnail . '</a>' : $thumbnail;
			$output .= $thumbnail;
		}

		$output = '<div class="talk-media' . $classes . '">' . $output . '</div>';

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
		$nofollow   = apply_filters( 'speekr_nofollow_policy', ' rel="nofollow"' );
		$targetbk   = apply_filters( 'speekr_targetblank_policy', ' target="_blank"' );
		$output     = '';

		foreach ( $auth_links as $slug => $datas ) {

			$target = apply_filters( 'speekr_talk_links_target', '', $slug );
			$title  = sprintf( __( 'See this talk on %s', 'speekr' ), $datas['name'] );
			$class  = apply_filters( 'speekr_talk_links_classes', 'talk-link talk-link-' . $slug, $slug ); 

			if ( 'other' === $slug && is_array( $meta['other-link'] ) && ! empty( $meta['other-link'] ) ) {
				foreach ( $meta['other-link'] as $link ) {
					if ( empty( $link['url'] ) || empty( $link['label'] ) ) {
						continue;
					}
					$more_class = apply_filters( 'speekr_talk_links_other_classes', 'talk-link-' . sanitize_key( $link['label'] ), $link );

					$output .= '<a href="' . esc_url( $link['url'] ) . '" title="' . esc_attr( $title ) . '"' . $target . ' class="' . esc_attr( $class ) . ' ' . esc_attr( $more_class ) . '"' . $nofollow . $targetbk  . '>' . esc_html( $link['label'] ) . '</a>' . "\n";
				}
			}
			if ( 'other' !== $slug && isset( $meta[ $slug . '-link' ] ) && ! empty( $meta[ $slug . '-link' ] ) ) {
				$output .= '<a href="' . esc_url( $meta[ $slug . '-link' ] ) . '" title="' . esc_attr( $title ) . '"' . $target . ' class="' . esc_attr( $class ) . '"' . $nofollow . $targetbk  . '>' . esc_html( $datas['name'] ) . '</a>' . "\n";
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
