<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Hook get_content to display our list of talks.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_display_list_content( $content ) {
	global $id;

	$options = speekr_get_options();

	$list = isset( $options['list_page'] ) && $options['list_page'] === $id ? speekr_get_list_content( $options ) : '';

	return $content . $list;
}
add_filter( 'the_content', 'speekr_display_list_content' );

/**
 * Retrieve the list of talks.
 *
 * @return (string) [description]
 */
function speekr_get_list_content( $options ) {
	$talks_args = array(
		'post_type' => 'talks',
	);

	$talks = new WP_Query( $talks_args );
	$list  = '';

	if ( $talks->have_posts() ) {

		$layout   = isset( $options['list_layout'] ) ? $options['list_layout'] : 'mixed';
		$head_tag = apply_filters( 'speekr_tag_title_in_list', 'h2' );

		$list .= '<div class="speekr-talks-list speekr-layout-' . $layout . '">';

		while ( $talks->have_posts() ) {
			$talks->the_post();
			$id    = get_the_ID();
			$metas = get_post_meta( $id );

			// METAS
			$conf       = isset( $metas['speekr-conf'] ) && isset( $metas['speekr-conf'][0] ) ? unserialize( $metas['speekr-conf'][0] ) : null;
			$is_article = isset( $metas['speekr-as-article'] ) && isset( $metas['speekr-as-article'][0] ) ? ( $metas['speekr-as-article'][0] === 'on' ? true : false ) : null;
			$is_linked  = apply_filters( 'speekr_talk_is_linked', $is_article, $id );
			$medialinks = isset( $metas['speekr-media-links'] ) && isset( $metas['speekr-media-links'][0] ) ? unserialize( $metas['speekr-media-links'][0] ) : null;
			$is_feat    = isset( $metas['speekr-is-featured'] ) && isset( $metas['speekr-is-featured'][0] ) ? $metas['speekr-is-featured'][0] : false;
			$classes    = $is_feat ? ' talk-featured' : '';

			$list .= '<article class="' . implode( ' ', get_post_class() ) . $classes . '" itemscope itemtype="http://schema.org/CreativeWork">';
			
			// Image / Embed / Video
			$media = speekr_get_media_header( $id, $medialinks, $is_linked, $is_feat );
			$list .= '<div class="talk-media-container">' . $media . '</div>';

			// Title.
			$title = get_the_title();
			$title = $is_linked ? '<a itemprop="url" href="' . get_permalink() . '">' . $title . '</a>' : $title;
			$list .= '<'. $head_tag . ' class="talk-title" itemprop="name">' . $title . '</' . $head_tag . '>';

			// Metadata begins
			$list .= $is_feat ? '<div class="talk-summ-container">' : '';
			$list .= $is_feat ? '<div class="talk-meta-n-summ">' : '';
			$list .= '<div class="talk-metadatas">';

			// Place of the conference.
			if ( ! empty( $conf['name'] ) ) {
				$nofollow = apply_filters( 'speekr_nofollow_policy', ' rel="nofollow"' );
				$targetbk = apply_filters( 'speekr_targetblank_policy', ' target="_blank"' );

				$confname = empty( $conf['url'] ) ? $conf['name'] : '<a href="' . esc_url( $conf['url'] ) . '" itemprop="url"' . $targetbk . $nofollow . '><span itemprop="name">' . esc_html( $conf['name'] ) . '</span></a>';
				$list .= '<p class="talk-place" itemprop="subjectOf" itemscope itemtype="http://schema.org/Event">' . $confname . '</p>';
			}

			// Date
			$list .= ! empty( $conf['name'] ) ? '<span class="talk-sep">&nbsp;–&nbsp;</span>' : '';
			$list .= '<time class="talk-time" itemprop="datePublished" datetime="' . date( DATE_ISO8601, get_the_time( 'U' ) ) . '">' . get_the_date() . '</time>';

			// Metadata ends
			$list .= '</div><!-- .talk-metadatas -->';

			// Summary content.
			if ( isset( $metas['speekr-summary'][0] ) ) {
				$list .= '<div class="talk-summary" itemprop="description">' . wpautop( $metas['speekr-summary'][0] )  . '</div><!-- .talk-summary -->';
			}

			$list .= $is_feat ? '</div><!-- .talk-meta-n-summ -->' : '';

			// Links.
			$links = speekr_get_talk_links( $id, $medialinks, $is_linked );
			$full  = $is_linked ? '<a href="' . get_permalink() . '" title="' . sprintf( __( 'Read more about %s', 'speekr' ), '&quot;' . get_the_title() . '&quot;') . '" class="talk-link talk-link-more" rel="nofollow">' . __( 'Read More', '' ) . '</a>' : '';
			$list .= '<div class="talk-links">' . $links . $full . '</div><!-- .talk-links -->';
			$list .= $is_feat ? '</div><!-- .talk-summ-container -->' : '';

			$list .= '</article>';
		}

		$list .= '</div><!-- .speekr-talks-list -->';
	}

	return apply_filters( 'speekr_get_list_content', $list, $talks_args, $options );
}

/**
 * Edit body_class depending on Speekr page you are on.
 *
 * @param (array) $classes  The existing body classes.
 * @return (array) 			The new array of classes.
 *
 * @since 1.0
 * @author Geoffrey Crofte
 */
function speekr_edit_body_class( $classes ) {
	global $post;

	if ( ! isset( $post->ID ) ) {
		return $classes;
	}

	$options = speekr_get_options();

	if ( isset( $options['list_page'] ) && $options['list_page'] === $post->ID ) {
		$classes[] = apply_filters( 'speekr_talks_page_classes', 'speekr-talks-page talks-list' );
	}

	return $classes;
}
add_filter( 'body_class', 'speekr_edit_body_class' );
