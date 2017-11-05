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
	add_settings_field( 'speekr_list_layout', __( 'List Layout', 'speekr' ), 'speekr_get_list_layout', SPEEKR_SLUG, 'speekr_option_layout' );
	add_settings_field( 'speekr_list_page', '<label for="speekr-list-page">' . __( 'List Page', 'speekr' ) . '</label>', 'speekr_get_list_page', SPEEKR_SLUG, 'speekr_option_layout' );

	// Styles Section.
	add_settings_section( 'speekr_option_styles', __( 'Painting', 'speekr' ), 'speekr_styles_section_text', SPEEKR_SLUG );
	add_settings_field( 'speekr_css_activate', __( 'Use default CSS?', 'speekr' ), 'speekr_get_css_activation', SPEEKR_SLUG, 'speekr_option_styles' );

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

	$layouts = speekr_get_admin_layout_values();

	$c_grid = ! isset( $opts['list_layout'] ) ? ' checked="checked"' : '';
	${ 'c_' . ( isset( $opts['list_layout'] ) ? esc_attr( $opts['list_layout'] ) : '' ) } = ' checked="checked"';
	
	echo '<p class="speekr-radios speekr-square-radio-button">';

	foreach ( $layouts as $k => $v ) {
		echo '<span class="speekr-layout-' . $k . '">
				<input name="' . SPEEKR_SETTING_SLUG . '[list_layout]" id="speekr-global-layout-' . esc_attr( $k ) . '" type="radio" value="' . esc_attr( $k ) . '"' . ${ 'c_' . esc_attr( $k ) } . '>
				<label class="speekr-label" for="speekr-global-layout-' . esc_attr( $k ) . '">' . esc_html( $v ) . '</label>
			</span>';
	}

	echo '</p>';
}

/**
 * Get the select option to select main list page.
 *
 * @return string
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_list_page() {
	global $speekr_options;

	$opts  = $speekr_options;
	$pages = get_pages( array(
		'post_status' => array( 'publish', 'draft' ),
	) );

	$output = '<select name="' . SPEEKR_SETTING_SLUG . '[list_page]" id="speekr-list-page">';
	
	foreach( $pages as $p ) {
		$output .='<option value="' . $p->ID . '"' . ( isset( $opts['list_page'] ) && $p->ID === $opts['list_page'] ? ' selected="selected"' : '' ) . '>' . esc_html( $p->post_title ) . ( $p->post_status === 'draft' ? ' (' . __( 'draft' ) . ')' : '' ) . '</option>';
	}

	$output .= '</select>';
	$output .= '<span class="speekr-or">or</span>';
	$output .= '<button type="submit" name="" class="hide-if-no-js speekr-button speekr-button-secondary" aria-hidden="true" data-ajax-action="speekr_create_default_page">' . _x( 'Create a page', 'admin option', 'speekr' ) . speekr_get_loader() . '</button>';

	echo apply_filters( 'speekr_get_list_page', $output, $opts );
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
 * Print CSS files option control.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_css_activation() {
	global $speekr_options;

	$opts = $speekr_options;

	$csss = speekr_get_admin_css_values();

	$css_both = ! isset( $opts['css'] ) ? ' checked="checked"' : '';
	$css_layout = $css_nope = '';

	if ( isset( $opts['css'] ) ) {
		${ 'css_' . esc_attr( $opts['css'] ) } = ' checked="checked"';
	}
	
	echo '<p class="speekr-line-radio speekr-radios speekr-vertical-radios">';

	foreach ( $csss as $k => $v ) {
		echo '<span class="speekr-radio-option speekr-css-' . $k . '">
				<input name="' . SPEEKR_SETTING_SLUG . '[css]" id="speekr-css-' . esc_attr( $k ) . '" type="radio" value="' . esc_attr( $k ) . '"' . ${ 'css_' . esc_attr( $k ) } . '>
				<label class="speekr-label" for="speekr-css-' . esc_attr( $k ) . '">' . esc_html( $v ) . '</label>
			</span>';
	}

	echo '</p>';
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

	// Authorized values.
	$css_auth = speekr_get_admin_css_values();
	$newoptions['css'] = in_array( $options['css'], $css_auth ) ? $options['css'] : 'both';

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
