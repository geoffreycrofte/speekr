<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Welcome Notice, for onboarding.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_welcome_notice(){
	global $pagenow;

	$notice = '';

	if ( $pagenow !== 'plugins.php' ) {
		return;
	}

	if ( ! current_user_can( 'edit_users' ) ) {
		return;
	}

	$notice = '<div class="notice speekr-notice is-dismissible">
			<div class="speekr-settings-header">' . speekr_get_logo_title( 'Let’s get the party started!' ) . '</div>
			<div class="speekr-notice-content">
				<ol class="speekr-steps">
					<li>
						<p class="speekr-step-title" id="speekr-step-1">' . __( 'Need to Import?', 'speekr' ) . '</p>
						<p class="speekr-step-content" aria-labelledby="speekr-step-1">' . __( 'Already have published contents about talks you gave? Try to import them with our tool.', 'speekr' ) . '</p>
						<p class="speekr-step-cta">
							<a href="' . speekr_get_importer_page_url() . '" class="speekr-button speekr-button-primary speekr-button-mini">' . __( 'Import my Talks', 'speekr' ) . '</a>
						</p>
					</li>
					<li>
						<p class="speekr-step-title" id="speekr-step-2">' . __( 'Main Settings', 'speekr' ) . '</p>
						<p class="speekr-step-content" aria-labelledby="speekr-step-2">' . __( 'Choose the page that will list your talks, choose the best layout for your website.', 'speekr' ) . '</p>
						<p class="speekr-step-cta">
							<a href="' . speekr_get_option_page_url() . '" class="speekr-button speekr-button-primary speekr-button-mini">' . __( 'Go to Settings Page', 'speekr' ) . '</a>
						</p>
					</li>
					<li>
						<p class="speekr-step-title" id="speekr-step-3">' . __( 'Publish You First Talk', 'speekr' ) . '</p>
						<p class="speekr-step-content" aria-labelledby="speekr-step-3">' . __( 'Follow the path of being more visible and spread your first talk with Speekr.', 'speekr' ) . '</p>
						<p class="speekr-step-cta">
							<a href="' . speekr_get_new_post_url() . '" class="speekr-button speekr-button-primary speekr-button-mini">' . __( 'Write my first talk', 'speekr' ) . '</a>
						</p>
					</li>
				</ol>
			</div>
		  </div>';

	echo $notice;
}
add_action( 'admin_notices', 'speekr_welcome_notice' );
