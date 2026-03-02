<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register Topics taxonomy on the Talks CPT.
 *
 * @return void
 * @since  2.0
 */
function speekr_register_topics_taxonomy() {
    $labels = array(
        'name'              => __('Topics', 'speekr'),
        'singular_name'     => __('Topic', 'speekr'),
        'search_items'      => __('Search Topics', 'speekr'),
        'all_items'         => __('All Topics', 'speekr'),
        'edit_item'         => __('Edit Topic', 'speekr'),
        'update_item'       => __('Update Topic', 'speekr'),
        'add_new_item'      => __('Add New Topic', 'speekr'),
        'new_item_name'     => __('New Topic Name', 'speekr'),
        'menu_name'         => __('Topics', 'speekr'),
        'not_found'         => __('No topics found', 'speekr'),
        'popular_items'     => __('Popular Topics', 'speekr'),
        'add_or_remove_items' => __('Add or remove topics', 'speekr'),
        'choose_from_most_used' => __('Choose from the most used topics', 'speekr'),
    );

    $args = array(
        'labels'            => $labels,
        'hierarchical'      => false,
        'public'            => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'topic'),
    );

    $args = apply_filters('speekr_topics_taxonomy_args', $args);

    register_taxonomy('speekr_topic', array(speekr_get_cpt_slug()), $args);
}
add_action( 'init', 'speekr_register_topics_taxonomy' );
