<?php
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Cheatin\' uh?' );
}
/**
 * Add a post display state for special Speekr pages in the page list table.
 *
 * @param (array)   $post_states  An array of post display states.
 * @param (WP_Post) $post         The current post object.
 */
function speekr_add_display_post_states( $post_states, $post ) {
    
    if ( speekr_get_pages_id( 'list_page' ) === $post->ID ) {
        $post_states['speekr_list_page'] = '<abbr title="List of Talks (Speekr Plugin)">' . __( 'List of Talks', 'woocommerce' ) . '</abbr>';
    }

    return $post_states;
}
add_filter( 'display_post_states', 'speekr_add_display_post_states', 10, 2 );
