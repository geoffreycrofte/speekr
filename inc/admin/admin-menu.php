<?php
/**
 * Speekr Admin Menu
 *
 * Registers the top-level "Speekr" menu page. All CPTs use
 * 'show_in_menu' => 'speekr' to appear as sub-items here.
 *
 * @package Speekr
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Register the top-level Speekr admin menu.
 */
function speekr_register_admin_menu() {
	add_menu_page(
		__( 'Speekr', 'speekr' ),          // Page title
		__( 'Speekr', 'speekr' ),          // Menu title
		'edit_posts',                       // Capability
		'speekr',                           // Menu slug — CPTs target this with show_in_menu
		'speekr_admin_menu_page',           // Callback
		'dashicons-speekr',                 // Custom plugin icon (font registered in enqueues.php)
		25                                  // Position (below Comments at 25)
	);
}
add_action( 'admin_menu', 'speekr_register_admin_menu' );

/**
 * Render the Speekr menu landing page.
 *
 * Simple overview page — redirects to Talks list as the primary content.
 */
function speekr_admin_menu_page() {
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Speekr', 'speekr' ); ?></h1>
		<p><?php esc_html_e( 'Manage your speaker profile, talks, and conference appearances.', 'speekr' ); ?></p>
		<p>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=speekr_conference' ) ); ?>" class="button">
				<?php esc_html_e( 'View Conferences', 'speekr' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=talks' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'View Talks', 'speekr' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=speekr_speaker' ) ); ?>" class="button">
				<?php esc_html_e( 'View Speakers', 'speekr' ); ?>
			</a>
		</p>
	</div>
	<?php
}
