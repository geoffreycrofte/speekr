<?php
/**
 * Conference Archive Template — classic (non-FSE) theme fallback.
 *
 * Fires for: is_post_type_archive( 'speekr_conference' )
 *
 * Child theme override: place your custom version at:
 *   {your-child-theme}/speekr/archive-conference.php
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

get_header();
?>
<section class="speekr-classic-template speekr-conference-archive">
	<div class="container wide-max-width entry-content">
	<?php
	$attributes = array();
	require SPEEKR_DIRNAME . '/src/blocks/conference-archive/render.php';
	?>
	<div class="container wide-max-width">
</section>
<?php
get_footer();
