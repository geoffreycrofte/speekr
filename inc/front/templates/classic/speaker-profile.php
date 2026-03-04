<?php
/**
 * Speaker Profile Template — classic (non-FSE) theme fallback.
 *
 * Fires for: is_singular( 'speekr_speaker' )
 *
 * Child theme override: place your custom version at:
 *   {your-child-theme}/speekr/speaker-profile.php
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

get_header();
?>
<section class="speekr-classic-template speekr-speaker-profile">
	<div class="container wide-max-width">
		<?php
		$attributes = array();
		require SPEEKR_DIRNAME . '/src/blocks/speaker-profile/render.php';
		?>
	</div>
</section>
<?php
get_footer();
