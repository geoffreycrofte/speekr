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

				case 'custom':
					$embed = ! empty( $meta['embed-code'] ) ? $meta['embed-code'] : false;
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


if ( ! function_exists( 'speekr_get_logo_title' ) ) {
	/**
	 * Get the markup for the Logo header part in setting pages.
	 *
	 * @param  (string) $subtitle The subtitle below the plugin name.
	 * @return (string)           The entir markupt.
	 *
	 * @since  1.0
	 * @author Geoffrey Crofte
	 */
	function speekr_get_logo_title( $subtitle = '' ) {
		$header = '
		<h1 class="speerk-settings-h1">
			<i class="speekr-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><path d="M75,2H5A5,5,0,0,0,0,7v4a5,5,0,0,0,4,4.9V55a6,6,0,0,0,6,6H38v6.86l-10.33,8.4a2,2,0,1,0,2.52,3.1l9.43-7.66,9.93,8.15a2,2,0,0,0,1.27.46,2,2,0,0,0,1.27-3.55L42,68.48V61H70a6,6,0,0,0,6-6V15.9A5,5,0,0,0,80,11V7A5,5,0,0,0,75,2ZM72,55a2,2,0,0,1-2,2H10a2,2,0,0,1-2-2V16H72Zm4-44a1,1,0,0,1-1,1H5a1,1,0,0,1-1-1V7A1,1,0,0,1,5,6H75a1,1,0,0,1,1,1Z"/><path d="M22.67,34.5a2,2,0,0,0,2-2,2.5,2.5,0,0,1,5,0,2,2,0,0,0,4,0,6.5,6.5,0,0,0-13,0A2,2,0,0,0,22.67,34.5Z"/><path d="M48.67,34.5a2,2,0,0,0,2-2,2.5,2.5,0,0,1,5,0,2,2,0,0,0,4,0,6.5,6.5,0,0,0-13,0A2,2,0,0,0,48.67,34.5Z"/><path d="M31.67,42c0,3.91,5.11,6.5,8.5,6.5s8.5-2.59,8.5-6.5a2,2,0,0,0-4,0c0,.91-2.6,2.5-4.5,2.5s-4.5-1.59-4.5-2.5a2,2,0,0,0-4,0Z"/></svg></i>
			<span>
				<span class="speekr-title">' . SPEEKR_PLUGIN_NAME . '</span>
				<span class="speekr-subtitle">' . esc_html( $subtitle ) . '</span>
			</span>
		</h1>';

		return apply_filters( 'speekr_get_logo_title', $header );
	}
}

/**
 * Print the Admin Sidebar.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_print_sidebar() {
	do_action( 'speekr_before_sidebar_content' ); ?>
	<div class="speekr-sidebar-widget">
		<div class="speekr-sw-title">
			<p><i class="dashicons dashicons-format-chat" aria-hidden="true"></i>&nbsp;<?php _e( 'Be social!', 'speekr' ); ?></p>
		</div>
		<div class="speekr-sw-content">
			<p><?php _e( 'Find us on:', 'speekr' ); ?></p>
			<ul class="speekr-social">
				<li><a href="https://twitter.com/speekr_plugin">Twitter</a></li>
				<li><a href="https://wordpress.org/plugins/<?php echo SPEEKR_SLUG; ?>">WordPress</a></li>
			</ul>

			<div class="speekr-mb-divider"></div>

			<p><?php _e( 'You like the plugin? Rate it on WordPress.org!' ) ?></p>
			<p><a class="speekr-rate" href="https://wordpress.org/support/plugin/<?php echo SPEEKR_SLUG; ?>/reviews/#new-post">★★★★★</a></p>
		</div>
	</div>

	<div class="speekr-sidebar-widget">
		<div class="speekr-sw-title">
			<p><i class="dashicons dashicons-art" aria-hidden="true"></i>&nbsp;<?php _e( 'Contributors', 'speekr' ); ?></p>
		</div>
		<div class="speekr-sw-content">
			<p><?php _e( 'This plugin is crafted with love and freely by several awesome folks.', 'speekr' ); ?></p>
			<dl class="speekr-dl">
				<dt><?php _e( 'Original Idea', 'speekr' ); ?></dt>
				<dd><a href="https://stephaniewalter.fr/" target="_blank">Stéphanie Walter</a> &amp; <a href="https://geoffrey.crofte.fr/en/" target="_blank">Geoffrey Crofte</a></dd>

				<dt><?php _e( 'Admin &amp; Front Design', 'speekr' ); ?></dt>
				<dd><a href="https://stephaniewalter.fr/" target="_blank">Stéphanie Walter</a> &amp; <a href="https://geoffrey.crofte.fr/en/" target="_blank">Geoffrey Crofte</a></dd>

				<dt><?php _e( 'Front &amp; Back Developments', 'speekr' ); ?></dt>
				<dd><a href="https://geoffrey.crofte.fr/en/" target="_blank">Geoffrey Crofte</a></dd>

				<dt><?php _e( 'Icon Design', 'speekr' ); ?></dt>
				<dd><a href="https://stephaniewalter.fr/" target="_blank">Stéphanie Walter</a>. Find the Icon Set on <a href="https://thenounproject.com/stephaniewalter/collection/speaker" target="_blank">The Noun Project</a>.</dd>
			</dl>
		</div>
	</div>
<?php
	do_action( 'speekr_after_sidebar_content' );
}
