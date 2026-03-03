<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register FSE block templates for block themes.
 *
 * Uses the register_block_template() API (WP 6.7+).
 * Templates appear in Site Editor and are used automatically when a block theme is active.
 * Underscores in template slugs are valid in WP 6.9.1 (validation bug fixed).
 *
 * @return void
 * @since  1.1
 */
function speekr_register_block_templates() {
	$tmpl_dir = SPEEKR_DIRNAME . '/templates/';

	$templates = array(
		'speekr//singular-speekr_speaker'   => array(
			'title' => __( 'Speaker Profile', 'speekr' ),
			'file'  => 'singular-speekr_speaker.html',
		),
		'speekr//archive-talks'             => array(
			'title' => __( 'Talks Archive', 'speekr' ),
			'file'  => 'archive-talks.html',
		),
		'speekr//single-talks'              => array(
			'title' => __( 'Single Talk', 'speekr' ),
			'file'  => 'single-talks.html',
		),
		'speekr//archive-speekr_conference' => array(
			'title' => __( 'Conference Archive', 'speekr' ),
			'file'  => 'archive-speekr_conference.html',
		),
	);

	foreach ( $templates as $name => $def ) {
		$path = $tmpl_dir . $def['file'];
		if ( ! file_exists( $path ) ) {
			continue;
		}
		register_block_template(
			$name,
			array(
				'title'   => $def['title'],
				'content' => file_get_contents( $path ), // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			)
		);
	}
}
add_action( 'init', 'speekr_register_block_templates' );

/**
 * Load Speekr CPT templates for classic (non-FSE) themes.
 *
 * Override lookup order (first match wins):
 *   1. {child-theme}/speekr/{template}.php
 *   2. {parent-theme}/speekr/{template}.php
 *   3. {plugin}/inc/front/templates/classic/{template}.php
 *
 * Does not affect block themes — FSE templates registered above take precedence.
 * Does not conflict with Speekr_Templates_Loader (handles page-template meta only).
 *
 * @param  string $template Current template path.
 * @return string           Modified template path if a CPT match is found; original otherwise.
 * @since  1.1
 */
function speekr_template_include( $template ) {
	$classic_dir  = SPEEKR_DIRNAME . '/inc/front/templates/classic/';
	$child_dir    = trailingslashit( get_stylesheet_directory() ) . 'speekr/';
	$parent_dir   = trailingslashit( get_template_directory() ) . 'speekr/';
	$template_map = array();

	if ( is_singular( 'speekr_speaker' ) ) {
		$file         = 'speaker-profile.php';
		$template_map = array(
			$child_dir  . $file,
			$parent_dir . $file,
			$classic_dir . $file,
		);
	} elseif ( is_post_type_archive( speekr_get_cpt_slug() ) ) {
		$file         = 'archive-talk.php';
		$template_map = array(
			$child_dir  . $file,
			$parent_dir . $file,
			$classic_dir . $file,
		);
	} elseif ( is_singular( speekr_get_cpt_slug() ) ) {
		$file         = 'single-talk.php';
		$template_map = array(
			$child_dir  . $file,
			$parent_dir . $file,
			$classic_dir . $file,
		);
	} elseif ( is_post_type_archive( 'speekr_conference' ) ) {
		$file         = 'archive-conference.php';
		$template_map = array(
			$child_dir  . $file,
			$parent_dir . $file,
			$classic_dir . $file,
		);
	} elseif ( is_singular( 'speekr_conference' ) ) {
		$file         = 'single-conference.php';
		$template_map = array(
			$child_dir  . $file,
			$parent_dir . $file,
			$classic_dir . $file,
		);
	}

	foreach ( $template_map as $candidate ) {
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}

	return $template;
}
add_filter( 'template_include', 'speekr_template_include' );
