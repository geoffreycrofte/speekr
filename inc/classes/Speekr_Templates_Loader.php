<?php if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

class Speekr_Templates_Loader {
	
	// The Template directory
	private $template_dir;

	// The template(s) we are going to add.
	private $templates;

	/**
	 * Filtering and loading templates
	 */
	public function __construct ( ) {
		$this->template_dir = SPEEKR_DIRNAME . '/inc/front/templates';
		$this->templates = $this->load_plugin_templates();

		add_filter( 'theme_page_templates', array( $this, 'register_plugin_templates' ) );
		add_filter( 'template_include', array( $this, 'add_template_filter' ) );
	}

	/**
	 * Loading templates from the templates folder inside the plugin
	 */
	private function load_plugin_templates () {

		$template_dir = $this->template_dir;

		// Reads all templates from the folder
		if ( is_dir( $template_dir ) ) {
			
			if ( $dh = opendir( $template_dir ) ) {
				while ( ( $file = readdir( $dh ) ) !== false ) {

					$full_path = $template_dir . '/' . $file;

					if ( filetype( $full_path ) == 'dir' ) {
						continue;
					}

					// Gets Template Name from the file comment block.
					$filedata = get_file_data( $full_path, array(
						'Template Name' => 'Template Name',
					));

					$templates[ $file ] = $filedata['Template Name'];
				}
				closedir( $dh );
			}
	  }		
	  return $templates;
	}

	/**
	 * theme_page_templates Filter callback
	 *
	 * Merges plugins' template with theme's, making them available for the user
	 * 
	 * @param array $theme_templates
	 * @return array $theme_templates
	 */
	public function register_plugin_templates ( $theme_templates ) {
		global $post;

		// $post can be null during REST API requests and on 404 pages.
		if ( ! $post ) {
			return $theme_templates;
		}

		// only available for Speekr Pages
		if ( speekr_get_pages_id( 'list_page' ) === $post->ID || defined('REST_REQUEST') ) {
			// Merging the WP templates with this plugin's active templates
			$theme_templates = array_merge( $theme_templates, $this->templates );
		}

		return $theme_templates;
	}

	/**
	 * template_include Filter callback
	 * 
	 * Include plugin's template if there's one chosen for the rendering page 
	 *
	 * @param string $template path
	 * @return string $template path
	 */
	 public function add_template_filter ( $template ) {
		global $post;

		// $post is null on 404 pages and some REST/AJAX contexts — bail gracefully.
		if ( ! $post ) {
			return $template;
		}

		$user_selected_template = get_page_template_slug( $post->ID );

		// We need to check if the selected template is inside the plugin folder
		$template_dir = $this->template_dir;
		$file_name = pathinfo( $template_dir . '/' . $user_selected_template, PATHINFO_BASENAME );

		$is_plugin = false;
		if ( file_exists( $template_dir . '/' . $file_name ) ) {
			$is_plugin = true;
		}

		// If selected template is not empty, it's not the Default Template
		// AND if it's a plugin template, we replace the normal flow to include the selected template
		if ( $user_selected_template != '' && $is_plugin ) {
			$template = $template_dir . '/' . $file_name;
		}       
	
		return $template;
	}   
	
}