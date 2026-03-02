<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register the Conferences CPT.
 *
 * @return void
 * @since  2.0
 */
function speekr_register_conferences_cpt() {

	$labels = array(
		'name'                  => __( 'Conferences', 'speekr' ),
		'singular_name'         => __( 'Conference', 'speekr' ),
		'add_new'               => __( 'New conference', 'speekr' ),
		'add_new_item'          => __( 'Add new conference', 'speekr' ),
		'edit_item'             => __( 'Edit conference', 'speekr' ),
		'new_item'              => __( 'New conference', 'speekr' ),
		'view_item'             => __( 'View conference', 'speekr' ),
		'view_items'            => __( 'View conferences', 'speekr' ),
		'search_items'          => __( 'Search conferences', 'speekr' ),
		'not_found'             => __( 'No conferences found', 'speekr' ),
		'all_items'             => __( 'All conferences', 'speekr' ),
	);

	$args = array(
		'label'            => __( 'Conferences', 'speekr' ),
		'labels'           => $labels,
		'description'      => __( 'Conferences where talks were given.', 'speekr' ),
		'public'           => true,
		'show_ui'          => true,
		'show_in_rest'     => true,
		'show_in_menu'     => 'speekr',
		'show_in_nav_menus' => false,
		'menu_icon'        => 'dashicons-calendar-alt',
		'menu_position'    => 8,
		'capability_type'  => 'post',
		'supports'         => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
		'rewrite'          => array(
			'slug'       => 'conference',
			'with_front' => false,
		),
		'has_archive'      => true,
	);

	register_post_type( 'speekr_conference', $args );
}
add_action( 'init', 'speekr_register_conferences_cpt' );

/**
 * Register post meta fields for the Conferences CPT.
 *
 * @return void
 * @since  2.0
 */
function speekr_register_conference_meta() {

	// Field 1 — Conference date (ISO 8601 string, e.g. "2025-06-15").
	register_post_meta( 'speekr_conference', '_speekr_conf_date', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 2 — Conference city.
	register_post_meta( 'speekr_conference', '_speekr_conf_city', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 3 — Conference country.
	register_post_meta( 'speekr_conference', '_speekr_conf_country', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 4 — Conference URL.
	register_post_meta( 'speekr_conference', '_speekr_conf_url', array(
		'single'            => true,
		'type'              => 'string',
		'show_in_rest'      => true,
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 5 — Talk reference (post ID of the referenced Talk).
	register_post_meta( 'speekr_conference', '_speekr_conf_talk_ref', array(
		'single'            => true,
		'type'              => 'integer',
		'show_in_rest'      => true,
		'sanitize_callback' => 'absint',
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 6 — Speaker references (array of speekr_speaker post IDs).
	register_post_meta( 'speekr_conference', '_speekr_conf_speakers', array(
		'single'       => true,
		'type'         => 'array',
		'show_in_rest' => array(
			'schema' => array(
				'type'  => 'array',
				'items' => array(
					'type' => 'integer',
				),
			),
		),
		'default'      => array(),
		'auth_callback' => function() { return current_user_can( 'edit_posts' ); },
	) );
}
add_action( 'init', 'speekr_register_conference_meta' );
