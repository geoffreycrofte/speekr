<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register Speekr post type.
 *
 * @return void
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_register_post_types() {
	
	$labels = array(
		'name'                  => __( 'Talks', 'speekr' ),
		'menu_name'             => SPEEKR_PLUGIN_NAME,
		'singular_name'         => __( 'Talk', 'speekr' ),
		'add_new'               => __( 'New talk' ),
		'add_new_item'          => __( 'Add new talk', 'speekr' ),
		'edit_item'             => __( 'Edit talk', 'speekr' ),
		'new_item'              => __( 'New talk', 'speekr' ),
		'view_item'             => __( 'View talk', 'speekr' ),
		'view_items'            => __( 'View talks', 'speekr' ),
		'search_items'          => __( 'Search talks', 'speekr' ),
		'not_found'             => __( 'No talks found', 'speekr' ),
		'all_items'             => __( 'All talks', 'speekr' ),
		'attributes'            => __( 'Talks Attributes', 'speekr' ),
		'attributes'            => __( 'Talks Attributes', 'speekr' ),
		'insert_into_item'      => __( 'Insert into talk', 'speekr' ),
		'set_featured_image'    => __( 'Set Cover Image', 'speekr' ),
		'remove_featured_image' => __( 'Remove Cover Image', 'speekr' ),
		'use_featured_image'    => __( 'Use Cover Image', 'speekr' ),
	);

	$args = array(
		'label'                => __( 'Talks', 'speekr' ),
		'labels'               => $labels,
		'description'          => __( 'You talks made public.', 'speekr' ),
		'public'               => true,
		'excluded_from_search' => false,
		'publicly_queryable'   => true,
		'show_ui'              => true,
		'show_in_nav_menus'    => true,
		'show_in_menu'         => true, // or 'something.php' to put this menu item as submenu
		'show_in_admin_bar'    => false,
		'menu_position'        => 6,
		'menu_icon'            => 'dashicons-speekr',
		'capability_type'      => 'post',
		'register_meta_box_cb' => 'speekr_custom_meta_boxes',
		'query_var'            => 'talks',
		'can_export'           => true,
		//'taxonomies'           => 'speekr_categories' TODO?
		'supports'             => array(
			'title',
			//'editor',
			'author',
			'thumbnail',
			//'custom-fields',
			//'comments',
			'revisions',
			'page-attributes'
		),
		'rewrite'              => array(
			'slug'       => apply_filters( 'speekr_talks_rewrite_slug', __( 'talks', 'speekr' ) ), 
			'with_front' => false, // more '/talks/something' than '/blog/talks/something' 
			'feeds'      => false,
			'pages'      => false, // no pagination ('page/2' no longer provided)
		),
	);

	$args = apply_filters( 'speekr_talk_cpt_args', $args );

	register_post_type( speekr_get_cpt_slug(), $args );

}
add_action( 'init', 'speekr_register_post_types' );