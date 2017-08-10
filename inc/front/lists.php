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

	$list = isset( $options['list_page'] ) && $options['list_page'] === $id ? speekr_get_list_content() : '';

	return $content . $list;
}
add_filter( 'the_content', 'speekr_display_list_content' );

/**
 * Retrieve the list of talks.
 *
 * @return (string) [description]
 */
function speekr_get_list_content() {
	$talks_args = array(
		'post_type' => 'talks',
	);

	$talks = new WP_Query( $talks_args );
	$list  = '';

	if ( $talks->have_posts() ) {

		$options  = speekr_get_options();
		$layout   = isset( $options['list_layout'] ) ? $options['list_layout'] : 'mixed';
		$head_tag = apply_filters( 'speekr_tag_title_in_list', 'h2' );

		$list .= '<div class="speekr-talks-list speekr-layout-' . $layout . '">';

		while ( $talks->have_posts() ) {
			$talks->the_post();
			$id = get_the_ID();

			$is_article = get_post_meta( $id, 'speekr-as-article', true ) === 'on' ? true : false;
			$medialinks = get_post_meta( $id, 'speekr-media-links', true );

			$list .= '<article class="' . implode( ' ', get_post_class() ) . '">';
			
			// Image / Embed / Video
			$media = speekr_get_media_header( $id, $medialinks );
			$list .= '<div class="talk-media">' . $media . '</div>';

			// Title.
			$title = get_the_title();
			$title = $is_article ? '<a href="' . get_permalink() . '">' . $title . '</a>' : $title;
			$list .= '<'. $head_tag . '>' . $title . '</' . $head_tag . '>';

			// Summary content.
			$list .= '<div class="talk-content">' . get_post_meta( $id, 'speekr-summary', true )  . '</div>';

			$list .= '</article>';
		}

		$list .= '</div><!-- .speekr-talks-list -->';
	}

	return apply_filters( 'speekr_get_list_content', $list, $talks_args, $options );
}