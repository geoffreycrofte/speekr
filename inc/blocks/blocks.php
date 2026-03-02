<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register all Speekr blocks discovered in build/blocks/{name}/block.json.
 *
 * Loops over every block.json in the compiled build directory and
 * calls register_block_type() with the containing folder, which lets
 * WordPress load the associated block assets automatically.
 *
 * @return void
 * @since  3.0
 * @author Geoffrey Crofte
 */
function speekr_register_blocks() {
	if ( ! is_dir( SPEEKR_DIRNAME . '/build/blocks' ) ) {
		return;
	}

	foreach ( glob( SPEEKR_DIRNAME . '/build/blocks/*/block.json' ) as $block_json ) {
		register_block_type( dirname( $block_json ) );
	}
}
add_action( 'init', 'speekr_register_blocks' );

/**
 * Register Talk post-meta keys for REST API / block editor access.
 *
 * These are block-editor-only keys for individual media links. The legacy
 * 'speekr-media-links' key (serialised array used by the classic editor) is
 * intentionally NOT registered for REST — both save paths are kept separate to
 * avoid schema complexity and REST 400 errors.
 *
 * @return void
 * @since  3.0
 * @author Geoffrey Crofte
 */
function speekr_register_talk_meta() {
	$post_type = speekr_get_cpt_slug(); // 'talks'

	// Talk Summary — plain text, visible via REST.
	register_post_meta(
		$post_type,
		'speekr-summary',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'sanitize_textarea_field',
			'auth_callback'     => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	// Conference object — name + url, exposed via REST with strict schema.
	register_post_meta(
		$post_type,
		'speekr-conf',
		array(
			'type'         => 'object',
			'single'       => true,
			'show_in_rest' => array(
				'schema' => array(
					'type'                 => 'object',
					'properties'           => array(
						'name' => array( 'type' => 'string' ),
						'url'  => array( 'type' => 'string', 'format' => 'uri' ),
					),
					'additionalProperties' => false,
				),
			),
			'auth_callback' => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	// YouTube media link — block-editor path (separate from legacy speekr-media-links).
	register_post_meta(
		$post_type,
		'_speekr_media_youtube',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	// Vimeo media link — block-editor path.
	register_post_meta(
		$post_type,
		'_speekr_media_vimeo',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	// Slides media link — block-editor path.
	register_post_meta(
		$post_type,
		'_speekr_media_slides',
		array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => true,
			'sanitize_callback' => 'esc_url_raw',
			'auth_callback'     => function() {
				return current_user_can( 'edit_posts' );
			},
		)
	);

	// Dailymotion media link — block-editor path.
	register_post_meta( speekr_get_cpt_slug(), '_speekr_media_dailymotion', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// SpeakerDeck media link — block-editor path.
	register_post_meta( speekr_get_cpt_slug(), '_speekr_media_speakerdeck', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Slideshare media link — block-editor path.
	register_post_meta( speekr_get_cpt_slug(), '_speekr_media_slideshare', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Other links — repeatable array of { label, url } objects.
	register_post_meta( speekr_get_cpt_slug(), '_speekr_media_other', array(
		'single'       => true,
		'type'         => 'array',
		'show_in_rest' => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'       => 'object',
					'properties' => array(
						'label' => array( 'type' => 'string' ),
						'url'   => array( 'type' => 'string', 'format' => 'uri' ),
					),
				),
			),
		),
		'default'      => array(),
		'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
	) );
}
add_action( 'init', 'speekr_register_talk_meta' );

/**
 * Enqueue Speekr editor panel styles in the block editor.
 */
function speekr_enqueue_editor_panel_styles() {
	$asset_file = SPEEKR_DIRNAME . '/build/editor/speekr-panels.css';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	wp_enqueue_style(
		'speekr-editor-panels',
		SPEEKR_PLUGIN_URL . 'build/editor/speekr-panels.css',
		array( 'wp-edit-post' ),
		SPEEKR_VERSION
	);
}
add_action( 'enqueue_block_editor_assets', 'speekr_enqueue_editor_panel_styles' );
