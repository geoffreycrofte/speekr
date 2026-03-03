<?php
/**
 * Single Conference Template — classic (non-FSE) theme fallback.
 *
 * Fires for: is_singular( 'speekr_conference' )
 *
 * Reuses the conference-archive render block — the archive block shows all
 * conferences, which is acceptable for MVP (a dedicated single-conference
 * block is not in scope).
 *
 * Child theme override: place your custom version at:
 *   {your-child-theme}/speekr/single-conference.php
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

get_header();
?>
<main id="primary" class="site-main speekr-classic-template">
	<?php
	$attributes = array();
	require SPEEKR_DIRNAME . '/src/blocks/conference-archive/render.php';
	?>
</main>
<?php
get_footer();
