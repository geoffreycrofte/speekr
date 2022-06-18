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
	$meta   = speekr_get_user_meta( 'notice' );
	var_dump($meta);

	if ( isset( $meta[ 'welcome' ] ) && 'off' === $meta[ 'welcome' ] ) {
		return;
	}

	if ( ! is_speekr_plugin_allowed_pages() ) {
		return;
	}

	if ( ! speekr_current_user_can_do() ) {
		return;
	}

	wp_enqueue_style( 'speekr-main', SPEEKR_PLUGIN_URL . 'assets/css/admin.min.css', array(), SPEEKR_VERSION, 'all' );

	$notice = '<div class="notice speekr-notice is-dismissible" data-notice="welcome" data-nonce="' . wp_create_nonce( 'speekr_notice' ) . '">
			<div class="speekr-settings-header">' . speekr_get_logo_title( 'Let’s get the party started!' ) . '</div>
			<div class="speekr-notice-content">
				<ol class="speekr-steps">
					<li>
						<p class="speekr-step-title" id="speekr-step-1">' . __( 'Need to Import?', 'speekr' ) . '</p>
						<p class="speekr-step-content" aria-labelledby="speekr-step-1">' . __( 'Already have published contents about talks you gave? Import those with our tool.', 'speekr' ) . '</p>
						<p class="speekr-step-cta">
							<a href="' . speekr_get_importer_page_url() . '" class="speekr-button speekr-button-primary speekr-button-mini">' . __( 'Import my Talks', 'speekr' ) . '</a>
						</p>
					</li>
					<li>
						<p class="speekr-step-title" id="speekr-step-2">' . __( 'Main Settings', 'speekr' ) . '</p>
						<p class="speekr-step-content" aria-labelledby="speekr-step-2">' . __( 'Choose the best layout for your website and the page that will list your talks.', 'speekr' ) . '</p>
						<p class="speekr-step-cta">
							<a href="' . speekr_get_option_page_url() . '" class="speekr-button speekr-button-primary speekr-button-mini">' . __( 'Customize my Talks', 'speekr' ) . '</a>
						</p>
					</li>
					<li>
						<p class="speekr-step-title" id="speekr-step-3">' . __( 'Publish Talks', 'speekr' ) . '</p>
						<p class="speekr-step-content" aria-labelledby="speekr-step-3">' . sprintf( __( 'Follow the path of being more visible and spread your talks with %s.', 'speekr' ), SPEEKR_PLUGIN_NAME ) . '</p>
						<p class="speekr-step-cta">
							<a href="' . speekr_get_new_post_url() . '" class="speekr-button speekr-button-primary speekr-button-mini">' . __( 'Write Talks', 'speekr' ) . '</a>
						</p>
					</li>
				</ol>
			</div>
		  </div>';

	echo $notice;
}
add_action( 'admin_notices', 'speekr_welcome_notice' );
