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
function speekr_plugin_settings() {

	// Global Layout Section.
	add_settings_section( 'speekr_option_layout', __( 'Global Layout', 'speekr' ), 'speekr_layout_section_text', SPEEKR_SLUG );
	add_settings_field( 'speekr_list_layout', __( 'List Layout' ), 'speekr_get_list_layout', SPEEKR_SLUG, 'speekr_option_layout' );
	add_settings_field( 'speekr_list_page', __( 'List Page' ), 'speekr_get_list_page', SPEEKR_SLUG, 'speekr_option_layout' );

	// Styles Section.
	add_settings_section( 'speekr_option_styles', __( 'Painting', 'speekr' ), 'speekr_styles_section_text', SPEEKR_SLUG );

	// Register settings and sanitize them
	register_setting( SPEEKR_SETTING_SLUG . '_layout', SPEEKR_SETTING_SLUG, 'speekr_sanitize_settings' );

}
add_filter( 'admin_init', 'speekr_plugin_settings' );

/**
 * Description Section Text.
 *
 * @return string
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_layout_section_text() {
?>
	<p class="speekr-description"><?php _e( 'Choose your global layout options here.', 'speekr' ); ?></p>
<?php
}

/**
 * Print Global Layout option control.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_list_layout() {
	global $speekr_options;

	$opts = $speekr_options;

	$layouts = array(
		'mixed' => __( 'Mixed', 'speekr' ),
		'grid'  => __( 'Grid', 'speekr' ),
		'list'  => __( 'List', 'speekr' ),
	);

	$c_mixed = ! isset( $opts['list_layout'] ) ? ' checked="checked"' : '';
	$c_grid  = $c_list = '';

	if ( isset( $opts['list_layout'] ) ) {
		${ 'c_' . esc_attr( $opts['list_layout'] ) } = ' checked="checked"';
	}
	
	echo '<p class="speekr-square-radio-button">';

	foreach ( $layouts as $k => $v ) {
		echo '<span class="speekr-layout-' . $k . '">
				<input name="' . SPEEKR_SETTING_SLUG . '[list_layout]" id="speekr-global-layout-' . esc_attr( $k ) . '" type="radio" value="' . esc_attr( $k ) . '"' . ${ 'c_' . esc_attr( $k ) } . '>
				<label class="speekr-label" for="speekr-global-layout-' . esc_attr( $k ) . '">' . esc_html( $v ) . '</label>
			</span>';
	}

	echo '</p>';
}

function speekr_get_list_page() {
	global $speekr_options;

	$opts  = $speekr_options;
	$pages = get_pages();

	echo '<select name="' . SPEEKR_SETTING_SLUG . '[list_page]" id="speekr-list-page">';
	
	foreach( $pages as $p ) {
		echo '<option value="' . $p->ID . '"' . ( isset( $opts['list_page'] ) && $p->ID === $opts['list_page'] ? ' selected="selected"' : '' ) . '>' . esc_html( $p->post_title ) . '</option>';
	}

	echo '</select>';
}

/**
 * Description Section Text.
 *
 * @return string
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_styles_section_text() {
?>
	<p class="speekr-description"><?php _e( 'Choose more precise styles for your talks list and talk pages.', 'speekr' ); ?></p>
<?php
}

/**
 * Sanitize Options.
 *
 * @return array Sanitized options
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_sanitize_settings( $options ) {

	$newoptions = array();

	// TODO: should be sanitized.
	$newoptions['list_layout'] = $options['list_layout'];
	$newoptions['list_page'] = (int) $options['list_page'];

	return $newoptions;
}

/**
 * Generate Settings Page.
 *
 * @return void The settings page
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_settings_page() {
?>
	<div class="wrap speekr-settings">
		
		<div class="speekr-settings-header">
			<h1 class="speerk-settings-h1">
				<i class="dashicons dashicons-nametag" aria-hidden="true"></i>
				<span>
					<span class="speekr-title"><?php echo SPEEKR_PLUGIN_NAME; ?></span>
					<span class="speekr-subtitle"><?php _e( 'Customize your experience', 'speekr' ); ?></span>
				</span>
			</h1>
			<p class="speekr-version-number"><?php echo SPEEKR_PLUGIN_NAME . '&nbsp;v.' . SPEEKR_VERSION; ?></p>
		</div><!-- .speekr-settings-header -->
		
		<div class="speekr-settings-content">
			<form method="post" action="options.php">
			<?php
				settings_fields( SPEEKR_SETTING_SLUG . '_layout' );
				do_settings_sections( SPEEKR_SLUG );
				submit_button();
			?>
			</form>
		</div><!-- .speekr-settings-content -->

	</div><!-- .speekr-settings.wrap -->
<?php
}
