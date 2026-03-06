<?php
/**
 * Talks Archive Template — classic (non-FSE) theme fallback.
 *
 * Fires for: is_post_type_archive( 'talks' )
 *
 * Child theme override: place your custom version at:
 *   {your-child-theme}/speekr/archive-talk.php
 *
 * @package Speekr
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

get_header();
?>
<section id="primary" class="speekr-classic-template  speekr-archive-talk">
	<div class="container wide-max-width entry-content">
		<?php
		$attributes = array();
		require SPEEKR_DIRNAME . '/src/blocks/talks-list/render.php';
		?>
	</div>
</section>
<?php
get_footer();
