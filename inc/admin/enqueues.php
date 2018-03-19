<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Enqueue styles and scripts files
 *
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_enqueues() {

	if ( 
		( isset( $_GET['page'] ) && $_GET['page'] === 'speekr' )
		||
		( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'talks' )
		||
		( isset( $_GET['post'] ) && get_post_type( (int) $_GET['post'] ) === 'talks' )
	) {
		wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/admin.min.css', array(), SPEEKR_VERSION, 'all' );
		wp_enqueue_script( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery' ), SPEEKR_VERSION, true );

		$loc_datas = array(
			'add_other_item'  => __( 'Add a new link', 'speekr' ),
			'confirm_rm_item' => __( 'Are you sure you want to remove this link?', 'speekr' ),
		);
		wp_localize_script( 'speekr-main', 'speekr', $loc_datas );
	}
}
add_action( 'admin_enqueue_scripts', 'speekr_enqueues' );

/**
 * Add icon menu on the WP Admin main menu.
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_crappy_admin_styles() {
	$font = SPEEKR_PLUGIN_URL . 'assets/fonts/admin/speekr';

	echo '<style>
@font-face {
	font-family: "speekr";
	src:  url("' . $font . '.eot?l880m3");
	src:  url("' . $font . '.eot?l880m3#iefix") format("embedded-opentype"),
		  url("' . $font . '.ttf?l880m3") format("truetype"),
		  url("' . $font . '.woff?l880m3") format("woff"),
		  url("' . $font . '.svg?l880m3#speekr") format("svg");
	font-weight: normal;
	font-style: normal;
}
.dashicons-speekr,
.dashicons-speekr:before {
	font-family: "speekr" !important;
	speak: none;
	font-style: normal;
	font-weight: normal;
	font-variant: normal;
	text-transform: none;
	line-height: 1;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}
.dashicons-speekr:before {
	content: "\e900";
}
</style>';
}
add_action( 'admin_head', 'speekr_crappy_admin_styles' );
