<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Get option Page URL.
 * @return (string) The option page URL.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_option_page_url() {
	return apply_filters( 'speekr_get_option_page_url', admin_url( 'edit.php?post_type=' .speekr_get_cpt_slug() . '&page=' . SPEEKR_SLUG ) );
}

/**
 * Get importer Page URL.
 * @return (string) The importer page URL.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_importer_page_url() {
	return apply_filters( 'speekr_get_importer_page_url', admin_url( 'edit.php?post_type=' .speekr_get_cpt_slug() . '&page=speekr-importer' ) );
}

/**
 * Get new post Page URL.
 * @return (string) The new post page URL.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_new_post_url() {
	return apply_filters( 'speekr_get_new_post_url', admin_url( 'post-new.php?post_type=' . speekr_get_cpt_slug() ) );
}

/**
 * Get Post list page.
 * @return (string) The talks page URL.
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_get_posts_list_url() {
	return apply_filters( 'speekr_get_new_post_url', admin_url( 'edit.php?post_type=' . speekr_get_cpt_slug() ) );
}
