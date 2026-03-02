<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Add an options page in the Settings menu.
 * 
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_add_settings_menu(){
	// Topics taxonomy — explicit submenu under Speekr menu (Talks CPT now uses show_in_menu=>'speekr',
	// so WordPress may not auto-place the taxonomy submenu reliably).
	add_submenu_page(
		'speekr',
		__( 'Topics', 'speekr' ),
		__( 'Topics', 'speekr' ),
		'edit_posts',
		'edit-tags.php?taxonomy=speekr_topic&post_type=talks'
	);

	// Settings and Import were previously parented to edit.php?post_type=talks.
	// Now that Talks uses show_in_menu=>'speekr', re-parent both here.
	add_submenu_page(
		'speekr',
		__( 'Speekr Options', 'speekr'),
		__( 'Settings', 'speekr'),
		apply_filters( 'speekr_settings_page_capabilities', 'manage_options' ),
		SPEEKR_SLUG,
		'speekr_settings_page'
	);

	add_submenu_page(
		'speekr',
		__( 'Speekr Importer', 'speekr'),
		__( 'Import', 'speekr'),
		apply_filters( 'speekr_importer_page_capabilities', 'publish_posts' ),
		'speekr-importer',
		'speekr_importer_page'
	);
}
add_action( 'admin_menu', 'speekr_add_settings_menu' );

/**
 * Keep the Speekr menu open and highlighted when viewing the Topics taxonomy.
 * WordPress defaults to the Posts menu for edit-tags.php pages.
 */
add_filter( 'parent_file', function ( $parent_file ) {
	if ( 'edit-tags.php' === $GLOBALS['pagenow'] && isset( $_GET['taxonomy'] ) && 'speekr_topic' === $_GET['taxonomy'] ) {
		return 'speekr';
	}
	return $parent_file;
} );

add_filter( 'submenu_file', function ( $submenu_file ) {
	if ( 'edit-tags.php' === $GLOBALS['pagenow'] && isset( $_GET['taxonomy'] ) && 'speekr_topic' === $_GET['taxonomy'] ) {
		return 'edit-tags.php?taxonomy=speekr_topic&post_type=talks';
	}
	return $submenu_file;
} );

/**
 * Add a link to the plugin description in the plugin list.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_plugin_action_links( $links, $file ) {
	$links[] = '<a href="' . speekr_get_option_page_url() . '">' . __( 'Settings' ) . '</a>';
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( SPEEKR_FILE ), 'speekr_plugin_action_links',  10, 2 );
