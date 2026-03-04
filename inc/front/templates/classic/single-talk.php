<?php
/**
 * Single Talk Template — classic (non-FSE) theme fallback.
 *
 * Fires for: is_singular( 'talks' )
 *
 * Child theme override: place your custom version at:
 *   {your-child-theme}/speekr/single-talk.php
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

get_header();
?>
<div class="speekr-classic-template speekr-single-talk">
	<div class="container wide-max-width">
		<?php
		$attributes = array();
		require SPEEKR_DIRNAME . '/src/blocks/single-talk/render.php';
		?>
	</div>
</div>
<?php
get_footer();
