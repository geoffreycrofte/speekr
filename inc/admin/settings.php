<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Speekr settings sections & fields
 * @return void
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_plugin_settings() {

	// API Section.
	add_settings_section( 'speekr_option_layout', __( 'Layout', 'speekr' ), 'speekr_layout_section_text', SPEEKR_SLUG );
	add_settings_field( 'speekr_global_layout', __( 'Global Layout' ), 'speekr_get_global_layout', SPEEKR_SLUG, 'speekr_option_layout' );

	// Register settings and sanitize them
	register_setting( SPEEKR_SETTING_SLUG . '_layout', SPEEKR_SETTING_SLUG, 'speekr_sanitize_settings' );
}
add_filter( 'admin_init', 'speekr_plugin_settings' );

/**
 * Description Section Text
 *
 * @return string
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_layout_section_text() {
?>
	<p><?php _e( 'Choose your global layout here.', 'speekr' ); ?></p>
<?php
}

/**
 * Print Global Layout option control
 * @return void
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_get_global_layout() {
	$options = speekr_get_options();

	$layouts = array(
		'featured' => __( 'Featured', 'speekr' ),
		'grid'     => __( 'Grid', 'speekr' ),
		'list'     => __( 'List', 'speekr' ),
	);

	$c_featured = ! isset( $options['global_layout'] ) ? ' checked="checked"' : '';
	$c_grid  = $c_list = '';

	if ( isset( $options['global_layout'] ) ) {
		${ 'c_' . esc_attr( $options['global_layout'] ) } = ' checked="checked"';
	}
	
	foreach ( $layouts as $k => $v ) {
		echo '<span class="speekr-layout-' . $k . '">
				<input name="' . SPEEKR_SETTING_SLUG . '[global_layout]" id="speekr-global-layout-' . esc_attr( $k ) . '" type="radio" value="' . esc_attr( $k ) . '"' . ${ 'c_' . esc_attr( $k ) } . '>
				<label class="speekr-label" for="speekr-global-layout-' . esc_attr( $k ) . '">' . esc_html( $v ) . '</label>
			</span>';
	}
}

/**
 * Sanitize Options
 * @return array Sanitized options
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_sanitize_settings( $options ) {

	$newoptions = array();
	$oldoptions = speekr_get_options();

	// Don't try to save api_key if contains "*" has security placeholder
	if ( isset ( $options['api_key'] ) && ! preg_match( '#\*#', $options['api_key'] ) ) {
		$newoptions['api_key'] = $options['api_key'];
	} elseif ( isset( $options['api_key'] ) ) {
		$newoptions['api_key'] = $oldoptions['api_key'];
	}

	// Don't secure password…
	$newoptions['api_password'] = isset( $options['api_password'] ) ? $options['api_password'] : '';

	return $newoptions;
}

/**
 * Generate Settings Page
 * @return void The settings page
 *
 * @author Geoffrey Crofte
 * @since 1.0
 */
function speekr_settings_page() {
?>
	<div class="wrap speekr_settings">
		<h1><i class="dashicons dashicons-nametag" aria-hidden="true"></i><?php echo SPEEKR_PLUGIN_NAME; ?></h1>

		<form method="post" action="options.php">
		<?php
			settings_fields( SPEEKR_SETTING_SLUG . '_layout' );
			do_settings_sections( SPEEKR_SLUG );
			submit_button();
		?>
		</form>
	</div>
<?php
}
