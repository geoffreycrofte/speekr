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
	add_settings_section( 'speekr_option_layout', '<i class="dashicons dashicons-layout" aria-hidden="true"></i>&nbsp;' . __( 'Global Layout', 'speekr' ), 'speekr_layout_section_text', SPEEKR_SLUG );
	add_settings_field( 'speekr_list_layout', __( '“My Talks” Layout', 'speekr' ), 'speekr_get_list_layout', SPEEKR_SLUG, 'speekr_option_layout' );
	add_settings_field( 'speekr_list_page', '<label for="speekr-list-page">' . __( '“My Talks” Page', 'speekr' ) . '</label>', 'speekr_get_list_page', SPEEKR_SLUG, 'speekr_option_layout' );

	// Styles Section.
	add_settings_section( 'speekr_option_styles', '<i class="dashicons dashicons-admin-appearance" aria-hidden="true"></i>&nbsp;' . __( 'Painting', 'speekr' ), 'speekr_styles_section_text', SPEEKR_SLUG );
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

	$output = '<p class="speekr-radios speekr-square-radio-button">';

	$svg_grid = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><path d="M32,12H10a6,6,0,0,0-6,6V37a6,6,0,0,0,6,6H32a6,6,0,0,0,6-6V18A6,6,0,0,0,32,12Zm2,25a2,2,0,0,1-2,2H10a2,2,0,0,1-2-2V18a2,2,0,0,1,2-2H32a2,2,0,0,1,2,2Z"/><path d="M33,48H8a2,2,0,0,0,0,4H33a2,2,0,0,0,0-4Z"/><path d="M8,60H25.59a2,2,0,1,0,0-4H8a2,2,0,0,0,0,4Z"/><path d="M32.07,64H8a2,2,0,0,0,0,4H32.07a2,2,0,0,0,0-4Z"/><path d="M70,12H48a6,6,0,0,0-6,6V37a6,6,0,0,0,6,6H70a6,6,0,0,0,6-6V18A6,6,0,0,0,70,12Zm2,25a2,2,0,0,1-2,2H48a2,2,0,0,1-2-2V18a2,2,0,0,1,2-2H70a2,2,0,0,1,2,2Z"/><path d="M71,48H46a2,2,0,0,0,0,4H71a2,2,0,0,0,0-4Z"/><path d="M46,60H63.59a2,2,0,0,0,0-4H46a2,2,0,0,0,0,4Z"/><path d="M70.07,64H46a2,2,0,0,0,0,4H70.07a2,2,0,0,0,0-4Z"/></svg>';

	$svg_list = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><path d="M44,16H74a2,2,0,0,0,0-4H44a2,2,0,0,0,0,4Z"/><path d="M44,24H65.11a2,2,0,0,0,0-4H44a2,2,0,0,0,0,4Z"/><path d="M44,32H72.89a2,2,0,0,0,0-4H44a2,2,0,0,0,0,4Z"/><path d="M32,8H10a6,6,0,0,0-6,6V31a6,6,0,0,0,6,6H32a6,6,0,0,0,6-6V14A6,6,0,0,0,32,8Zm2,23a2,2,0,0,1-2,2H10a2,2,0,0,1-2-2V14a2,2,0,0,1,2-2H32a2,2,0,0,1,2,2Z"/><path d="M74,47H44a2,2,0,0,0,0,4H74a2,2,0,0,0,0-4Z"/><path d="M44,59H65.11a2,2,0,0,0,0-4H44a2,2,0,0,0,0,4Z"/><path d="M72.89,63H44a2,2,0,0,0,0,4H72.89a2,2,0,0,0,0-4Z"/><path d="M32,43H10a6,6,0,0,0-6,6V66a6,6,0,0,0,6,6H32a6,6,0,0,0,6-6V49A6,6,0,0,0,32,43Zm2,23a2,2,0,0,1-2,2H10a2,2,0,0,1-2-2V49a2,2,0,0,1,2-2H32a2,2,0,0,1,2,2Z"/></svg>';

	do_action( 'speekr_get_admin_layout_values_before_list', $layouts );

	foreach ( $layouts as $k => $v ) {
		$layout = '<span class="speekr-layout-' . $k . '">
				<input name="' . SPEEKR_SETTING_SLUG . '[list_layout]" id="speekr-global-layout-' . esc_attr( $k ) . '" type="radio" value="' . esc_attr( $k ) . '"' . ( isset( ${ 'c_' . esc_attr( $k ) } ) ? ${ 'c_' . esc_attr( $k ) } : '' ) . '>
				<label class="speekr-label" for="speekr-global-layout-' . esc_attr( $k ) . '">
					<span class="speekr-layout-icon">' . ( isset( ${ 'svg_' . esc_attr( $k ) } ) ? ${ 'svg_' . esc_attr( $k ) } : '' ) . '</span>
					<span class="speekr-layout-text">' . esc_html( $v ) . '</span>
				</label>
			</span>';

		$output .= apply_filters( 'speekr_get_list_layout_' . $k , $layout, $k, $v );
	}

	$output .= '</p>';

	echo $output;
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

	$opts    = $speekr_options;
	$curr_id = isset( $opts['list_page'] ) ? $opts['list_page'] : 0;
	$pages   = get_pages( array(
		'post_status' => array( 'publish', 'draft' ),
	) );

	$output = '<span class="speekr-list-page-block"><select name="' . SPEEKR_SETTING_SLUG . '[list_page]" id="speekr-list-page" required>';
	
	$output .= '<option value="">(' . __( 'Select a page', 'speekr' ) . ')</option>';

	foreach( $pages as $p ) {
		$output .='<option value="' . $p->ID . '"' . ( (int) $p->ID === (int) $curr_id ? ' selected="selected"' : '' ) . '>' . esc_html( $p->post_title ) . ( $p->post_status === 'draft' ? ' (' . __( 'draft' ) . ')' : '' ) . '</option>';
	}

	$output .= '</select>
		<p>' . sprintf( __( 'Edit your %stalks page%s' ), '<a href="' . get_edit_post_link( (int) $curr_id ) . '" target="_blank">', '</a>' ) . '</p>
	</span><!-- .speekr-list-page-block -->
	<span class="speekr-or hide-if-no-js">or</span>';
	$output .= '<button type="submit" name="" class="hide-if-no-js speekr-button speekr-button-secondary" aria-hidden="true" data-nonce="' . wp_create_nonce( 'create_default_page' ) . '" data-ajax-action="speekr_create_default_page"><i class="dashicons dashicons-welcome-add-page" aria-hidden="true"></i>&nbsp;' . _x( 'Create a page', 'admin option', 'speekr' ) . speekr_get_loader() . '</button>';

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

	${ 'css_' . ( isset( $opts['css'] ) ? esc_attr( $opts['css'] ) : '' ) } = ' checked="checked"';

	
	echo '<p class="speekr-line-radio speekr-radios speekr-vertical-radios">';

	foreach ( $csss as $k => $v ) {
		echo '<span class="speekr-radio-option speekr-css-' . $k . '">
				<input name="' . SPEEKR_SETTING_SLUG . '[css]" id="speekr-css-' . esc_attr( $k ) . '" type="radio" value="' . esc_attr( $k ) . '"' . ( isset( ${ 'css_' . esc_attr( $k ) } ) ? ${ 'css_' . esc_attr( $k ) } : '' ) . '>
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

	// No risk, save integer value.
	$newoptions['list_page'] = (int) $options['list_page'];

	// Authorized values.
	$css_auth = speekr_get_admin_css_values();
	$newoptions['css'] = array_key_exists( $options['css'], $css_auth ) ? $options['css'] : 'both';

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
	global $speekr_options;
?>
	<div class="wrap">
		<h1 class="screen-reader-text"><?php echo SPEEKR_PLUGIN_NAME . ' - ' . __( 'Customize your experience', 'speekr' ); ?></h1>

		<div class="speekr-settings">
			<div class="speekr-main-content">
				<div class="speekr-settings-header">
					<?php echo speekr_get_logo_title( __( 'Customize your experience', 'speekr' ) ); ?>
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
			</div>

			<div class="speekr-sidebar">
				<?php speekr_print_sidebar(); ?>
			</div>
		</div><!-- .speekr-settings -->
	</div><!-- .wrap -->
<?php
}
