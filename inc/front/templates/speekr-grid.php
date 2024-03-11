<?php
/**
 * The template for displaying Talk items.
 *
 * This template displays the content as part of a simple grid layout.
 *
 * @link https://plugins.wordpress.org/speekr
 *
 * @package Geoffrey_Crofte
 *
 * Template Name: Speekr Grid
 */

get_header();
do_action( 'speekr_grid_template_starts' );
?>

    <main id="primary" class="site-main speekr-grid-layout">
        <?php do_action( 'speekr_grid_template_before_the_loop' ); ?>

        <?php
        while ( have_posts() ) :
            the_post();

            get_template_part( 'template-parts/content', 'speekr' );

        endwhile; // End of the loop.
        ?>

        <?php do_action( 'speekr_grid_template_after_the_loop' ); ?>
    </main><!-- #main -->

<?php
do_action( 'speekr_grid_template_ends' );
get_footer();
