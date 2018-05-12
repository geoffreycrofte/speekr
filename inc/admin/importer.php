<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

global $speekr_options;

$speekr_options = speekr_get_options();

/**
 * Speekr settings sections & fields.
 *
 * @return void
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_plugin_importer() {

	// Categories & Tags
	add_settings_section( 'speekr_option_import', '<i class="dashicons dashicons-tag" aria-hidden="true"></i>&nbsp;' . __( 'From Category and Tag', 'speekr' ), 'speekr_import_section_text', SPEEKR_SLUG . '_import' );
	add_settings_field( 'speekr_category_import', __( 'Import From Categories', 'speekr' ), 'speekr_select_categories', SPEEKR_SLUG . '_import', 'speekr_option_import' );
	add_settings_field( 'speekr_tag_import', __( 'Import From Tags', 'speekr' ), 'speekr_select_tags', SPEEKR_SLUG . '_import', 'speekr_option_import' );
	add_settings_field( 'speekr_tag_import_submit', '<p class="hide-if-no-js"><button type="submit" name="speekr-submit-tags" class="speekr-button">' . __( 'Import posts', 'speekr' ) . '</button></p>', '__return_null', SPEEKR_SLUG . '_import', 'speekr_option_import' );

	// Posts list
	add_settings_section( 'speekr_option_import_posts', '<i class="dashicons dashicons-admin-post" aria-hidden="true"></i>&nbsp;' . __( 'From Posts Selection', 'speekr' ), 'speekr_import_section_text2', SPEEKR_SLUG . '_import' );
	add_settings_field( 'speekr_post_import', __( 'Import by Selecting posts', 'speekr' ), 'speekr_select_posts', SPEEKR_SLUG . '_import', 'speekr_option_import_posts' );

	// Register settings and sanitize them
	register_setting( SPEEKR_SETTING_SLUG . '_import', SPEEKR_SETTING_SLUG, 'speekr_sanitize_importing' );

}
add_filter( 'admin_init', 'speekr_plugin_importer' );

/**
 * Description Section Text.
 *
 * @return string
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_import_section_text() {
?>
	<p class="speekr-description"><?php _e( 'Import existing talks from you categories and tags.', 'speekr' ); ?></p>
<?php
}

/**
 * Display a list of categories
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_select_categories() {
	global $speekr_options;

	$opts = $speekr_options;

	$terms = get_terms( array(
		'hide_empty' => true,
		'taxonomy'   => apply_filters( 'speekr_importer_allowed_taxonomy', 'category' ),
	) );

	$output = '<p class="speekr-checkboxes">';

	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$id = $term->term_id;
			$output .= '<span class="speekr-checkbox-option speekr-category-' . esc_attr( $id ) . '">
					<input name="' . SPEEKR_SETTING_SLUG . '[cat]" id="speekr-cat-' . esc_attr( $id ) . '" type="checkbox" value="' . esc_attr( $id ) . '">
					<label class="speekr-label" for="speekr-cat-' . esc_attr( $id ) . '">' . esc_html( $term->name ) . '</label>
				</span><br>';
		}
	} else {
		$output .= '<em class="speekr-description">' . __( 'No tags available.', 'speekr' ) . '</em>';
	}

	$output .= '</p>';

	echo $output;
}

/**
 * Display a list of tags
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_select_tags() {
	global $speekr_options;

	$opts = $speekr_options;

	$terms = get_terms( array(
		'hide_empty' => true,
		'taxonomy'   => apply_filters( 'speekr_importer_allowed_tags', 'post_tag' ),
	) );

	$output = '<p class="speekr-checkboxes">';

	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$id = $term->term_id;
			$output .= '<span class="speekr-checkbox-option speekr-tag-' . esc_attr( $id ) . '">
					<input name="' . SPEEKR_SETTING_SLUG . '[cat]" id="speekr-tag-' . esc_attr( $id ) . '" type="checkbox" value="' . esc_attr( $id ) . '">
					<label class="speekr-label" for="speekr-tag-' . esc_attr( $id ) . '">' . esc_html( $term->name ) . '</label>
				</span><br>';
		}
	} else {
		$output .= '<em class="speekr-description">' . __( 'No tags available.', 'speekr' ) . '</em>';
	}

	$output .= '</p>';

	echo $output;
}

/**
 * Description Section Text.
 *
 * @return string
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_import_section_text2() {
?>
	<p class="speekr-description"><?php _e( 'Import existing talks from your existing blog posts.', 'speekr' ); ?></p>
<?php
}

/**
 * Display a list of posts
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_select_posts() {
	global $speekr_options;

	$opts = $speekr_options;

	$posts = get_posts( array(
		'post_type' => array( 'post' ),
	) );
	$output = '<p class="speekr-checkboxes">';

	if ( ! empty( $posts ) ) {
		foreach ( $posts as $post ) {
			$id = $post->ID;
			$output .= '<span class="speekr-checkbox-option speekr-tag-' . esc_attr( $id ) . '">
					<input name="' . SPEEKR_SETTING_SLUG . '[cat]" id="speekr-tag-' . esc_attr( $id ) . '" type="checkbox" value="' . esc_attr( $id ) . '">
					<label class="speekr-label" for="speekr-tag-' . esc_attr( $id ) . '">' . esc_html( $post->post_title ) . '</label>
				</span><br>';
		}
	} else {
		$output .= '<em class="speekr-description">' . __( 'No posts available.', 'speekr' ) . '</em>';
	}

	$output .= '</p>';

	echo $output;
}


/**
 * Sanitize Options.
 *
 * @return array Sanitized options
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
//function speekr_sanitize_importing( $options ) {

//}

/**
 * Generate Importer Page.
 *
 * @return void The importer page.
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_importer_page() {
	global $speekr_options;
?>
	<div class="wrap">

		<h1 class="screen-reader-text"><?php echo SPEEKR_PLUGIN_NAME . ' - ' . __( 'Import your existing talks.', 'speekr' ); ?></h1>

		<div class="speekr-settings">
			<div class="speekr-main-content">
				<div class="speekr-settings-header">
					<?php echo speekr_get_logo_title( __( 'Import your existing talks.', 'speekr' ) ); ?>
					<p class="speekr-version-number"><?php echo SPEEKR_PLUGIN_NAME . '&nbsp;v.' . SPEEKR_VERSION; ?></p>
				</div><!-- .speekr-settings-header -->

				<div class="speekr-settings-content">
					<form method="post" action="options.php">
					<?php
						settings_fields( SPEEKR_SETTING_SLUG . '_import' );
						do_settings_sections( SPEEKR_SLUG . '_import' );
					?>
					<p class="submit hide-if-no-js">
						<button class="button button-primary" type="submit"><?php _e( 'Import Selected Posts', 'speekr' ); ?></button>
					</p>
					<p class="hide-if-js">
						<?php _e( 'You need JS to use the importer.', 'speekr' ); ?>
					</p>
					</form>
				</div><!-- .speekr-settings-content -->
			</div>

			<div class="speekr-sidebar">
				<?php speekr_print_sidebar(); ?>
			</div>
		</div><!-- .speekr-settings -->
	</div><!-- .wrap -->
<?php
}
