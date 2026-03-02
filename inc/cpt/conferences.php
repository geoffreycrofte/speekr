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

	// Field 7 — Latitude (float, set by Nominatim geocoding on post save).
	register_post_meta( 'speekr_conference', '_speekr_conf_lat', array(
		'single'            => true,
		'type'              => 'number',
		'show_in_rest'      => true,
		'sanitize_callback' => function( $v ) { return (float) $v; },
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );

	// Field 8 — Longitude (float, set by Nominatim geocoding on post save).
	register_post_meta( 'speekr_conference', '_speekr_conf_lng', array(
		'single'            => true,
		'type'              => 'number',
		'show_in_rest'      => true,
		'sanitize_callback' => function( $v ) { return (float) $v; },
		'auth_callback'     => function() { return current_user_can( 'edit_posts' ); },
	) );
}
add_action( 'init', 'speekr_register_conference_meta' );

/**
 * Auto-geocode a Conference post on save using Nominatim (OpenStreetMap).
 *
 * Runs only when city and country are present. Skips if coordinates are already
 * stored (avoids redundant API calls on re-saves). Rate limit: 1 req/sec max;
 * custom User-Agent is required by Nominatim policy.
 *
 * @param int $post_id The saved post ID.
 * @return void
 * @since 4.0
 */
function speekr_geocode_conference_on_save( $post_id ) {
	if ( wp_is_post_autosave( $post_id ) ) return;
	if ( wp_is_post_revision( $post_id ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;

	$city    = get_post_meta( $post_id, '_speekr_conf_city', true );
	$country = get_post_meta( $post_id, '_speekr_conf_country', true );
	if ( empty( $city ) || empty( $country ) ) return;

	// Skip if already geocoded — prevent re-hitting API on every save.
	// To re-geocode: clear meta manually or add a force flag in future.
	if ( get_post_meta( $post_id, '_speekr_conf_lat', true ) ) return;

	$query    = urlencode( $city . ', ' . $country );
	$url      = 'https://nominatim.openstreetmap.org/search?q=' . $query . '&format=json&limit=1';
	$response = wp_remote_get( $url, array(
		'headers' => array(
			'User-Agent' => 'Speekr WordPress Plugin/1.0 (https://github.com/geoffreycrofte/speekr)',
		),
		'timeout' => 10,
	) );

	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		set_transient( 'speekr_geocode_failed_' . $post_id, 1, 5 * MINUTE_IN_SECONDS );
		return;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body[0]['lat'] ) || empty( $body[0]['lon'] ) ) {
		set_transient( 'speekr_geocode_failed_' . $post_id, 1, 5 * MINUTE_IN_SECONDS );
		return;
	}

	update_post_meta( $post_id, '_speekr_conf_lat', (float) $body[0]['lat'] );
	update_post_meta( $post_id, '_speekr_conf_lng', (float) $body[0]['lon'] );
}
add_action( 'save_post_speekr_conference', 'speekr_geocode_conference_on_save' );

/**
 * Show an admin notice when Nominatim geocoding failed on the last conference save.
 *
 * @return void
 * @since 4.0
 */
function speekr_geocode_failure_notice() {
	global $post;
	if ( ! isset( $post->ID ) || 'speekr_conference' !== get_post_type( $post->ID ) ) return;
	if ( ! get_transient( 'speekr_geocode_failed_' . $post->ID ) ) return;
	delete_transient( 'speekr_geocode_failed_' . $post->ID );
	echo '<div class="notice notice-warning is-dismissible"><p>'
		. esc_html__( 'Could not resolve location — add coordinates manually.', 'speekr' )
		. '</p></div>';
}
add_action( 'admin_notices', 'speekr_geocode_failure_notice' );
