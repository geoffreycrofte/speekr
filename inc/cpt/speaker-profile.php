<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register Speaker Profile CPT.
 *
 * show_in_rest: true is mandatory for block editor support.
 * supports 'editor' and 'custom-fields' are required for block editor + REST meta access.
 *
 * @return void
 * @since  2.0
 */
function speekr_register_speaker_profile_cpt() {
	$labels = array(
		'name'               => __( 'Speaker Profiles', 'speekr' ),
		'singular_name'      => __( 'Speaker Profile', 'speekr' ),
		'add_new'            => __( 'New Speaker Profile', 'speekr' ),
		'add_new_item'       => __( 'Add New Speaker Profile', 'speekr' ),
		'edit_item'          => __( 'Edit Speaker Profile', 'speekr' ),
		'new_item'           => __( 'New Speaker Profile', 'speekr' ),
		'view_item'          => __( 'View Speaker Profile', 'speekr' ),
		'search_items'       => __( 'Search Speaker Profiles', 'speekr' ),
		'not_found'          => __( 'No speaker profiles found', 'speekr' ),
		'not_found_in_trash' => __( 'No speaker profiles found in Trash', 'speekr' ),
		'all_items'          => __( 'All Speaker Profiles', 'speekr' ),
	);

	$args = array(
		'label'             => __( 'Speaker Profile', 'speekr' ),
		'labels'            => $labels,
		'public'            => true,
		'show_ui'           => true,
		'show_in_rest'      => true,
		'menu_icon'         => 'dashicons-id',
		'menu_position'     => 7,
		'capability_type'   => 'post',
		'supports'          => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
		'rewrite'           => array(
			'slug'       => 'speaker-profile',
			'with_front' => false,
		),
		'show_in_nav_menus' => false,
		'has_archive'       => false,
	);

	register_post_type( 'speekr_speaker', $args );
}
add_action( 'init', 'speekr_register_speaker_profile_cpt' );


/**
 * Sanitize _speekr_headshots meta value.
 *
 * @param  mixed $value Raw value from REST API or post meta save.
 * @return array        Sanitized array of headshot objects.
 */
function speekr_sanitize_headshots( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$clean = array();
	foreach ( $value as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$clean[] = array(
			'id'    => absint( isset( $item['id'] ) ? $item['id'] : 0 ),
			'label' => sanitize_text_field( isset( $item['label'] ) ? $item['label'] : '' ),
		);
	}
	return $clean;
}

/**
 * Sanitize _speekr_social_links meta value.
 *
 * @param  mixed $value Raw value from REST API or post meta save.
 * @return array        Sanitized array of social link objects.
 */
function speekr_sanitize_social_links( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}
	$clean = array();
	foreach ( $value as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}
		$clean[] = array(
			'platform' => sanitize_text_field( isset( $item['platform'] ) ? $item['platform'] : '' ),
			'url'      => esc_url_raw( isset( $item['url'] ) ? $item['url'] : '' ),
			'label'    => sanitize_text_field( isset( $item['label'] ) ? $item['label'] : '' ),
		);
	}
	return $clean;
}

/**
 * Sanitize _speekr_rider meta value.
 *
 * @param  mixed $value Raw value from REST API or post meta save.
 * @return array        Sanitized rider object.
 */
function speekr_sanitize_rider( $value ) {
	if ( ! is_array( $value ) ) {
		return array(
			'av'            => '',
			'travel'        => '',
			'dietary'       => '',
			'accessibility' => '',
		);
	}
	return array(
		'av'            => sanitize_textarea_field( isset( $value['av'] ) ? $value['av'] : '' ),
		'travel'        => sanitize_textarea_field( isset( $value['travel'] ) ? $value['travel'] : '' ),
		'dietary'       => sanitize_textarea_field( isset( $value['dietary'] ) ? $value['dietary'] : '' ),
		'accessibility' => sanitize_textarea_field( isset( $value['accessibility'] ) ? $value['accessibility'] : '' ),
	);
}

/**
 * Register all Speaker Profile post meta fields.
 *
 * All fields use show_in_rest so the block editor and REST API can read/write them.
 * Structured fields (headshots, social_links, rider) use expanded schema for type safety.
 *
 * @return void
 * @since  2.0
 */
function speekr_register_speaker_profile_meta() {

	// Field 1: _speekr_headshots — array of { id, label } objects.
	register_post_meta( 'speekr_speaker', '_speekr_headshots', array(
		'single'            => true,
		'type'              => 'array',
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'id'    => array( 'type' => 'integer', 'minimum' => 1 ),
						'label' => array( 'type' => 'string' ),
					),
				),
			),
		),
		'sanitize_callback' => 'speekr_sanitize_headshots',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 2: _speekr_bio_short — plain string (short bio).
	register_post_meta( 'speekr_speaker', '_speekr_bio_short', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_textarea_field',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 4: _speekr_social_links — array of { platform, url, label } objects.
	register_post_meta( 'speekr_speaker', '_speekr_social_links', array(
		'single'            => true,
		'type'              => 'array',
		'show_in_rest'      => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type'                 => 'object',
					'additionalProperties' => false,
					'properties'           => array(
						'platform' => array( 'type' => 'string' ),
						'url'      => array( 'type' => 'string', 'format' => 'uri' ),
						'label'    => array( 'type' => 'string' ),
					),
				),
			),
		),
		'sanitize_callback' => 'speekr_sanitize_social_links',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 5: _speekr_rider — single object with av, travel, dietary, accessibility keys.
	register_post_meta( 'speekr_speaker', '_speekr_rider', array(
		'single'            => true,
		'type'              => 'object',
		'show_in_rest'      => array(
			'schema' => array(
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => array(
					'av'            => array( 'type' => 'string' ),
					'travel'        => array( 'type' => 'string' ),
					'dietary'       => array( 'type' => 'string' ),
					'accessibility' => array( 'type' => 'string' ),
				),
			),
		),
		'sanitize_callback' => 'speekr_sanitize_rider',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );
}
add_action( 'init', 'speekr_register_speaker_profile_meta' );
