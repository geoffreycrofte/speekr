<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}

/**
 * Register custom meta boxes to speekr new custom posts.
 *
 * @param  (object) $post The current editing Post object.
 * @return void
 *
 * @see  /inc/common/custom-posts.php $args['register_meta_box_cb']
 * 
 * @since  1.0
 * @author Geoffre Crofte
 */
function speekr_custom_meta_boxes( $post ) {

	add_meta_box(
		'speekr-summary',
		__( 'Talk Summary', 'speekr' ),
		'speekr_summary_mb',
		null,
		'normal',
		'high',
		null // callback_args (array)
	);

	add_meta_box(
		'speekr-media-links',
		__( 'Media Links', 'speekr' ),
		'speekr_content_media_links_mb',
		null,
		'normal',
		'high',
		null // callback_args (array)
	);

	add_meta_box(
		'speekr-conference',
		__( 'About the Conference', 'speekr' ),
		'speekr_conference_mb',
		null,
		'side',
		'high',
		null // callback_args (array)
	);

	add_meta_box(
		'speekr-content',
		__( 'Talk as Content', 'speekr' ),
		'speekr_the_content_mb',
		null,
		'normal',
		'high',
		null // callback_args (array)
	);

}

/**
 * Add custom small Summary with wp_editor content.
 *
 * @param  (object) $post The current editing Post object.
 * @return void
 * 
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_summary_mb( $post ) {

	$content = get_post_meta( $post->ID, 'speekr-summary', true );
	$editor_id = 'speekr_summary';

	$settings = array(
		'media_buttons' => false,
		'teeny'         => true,
		'editor_height' => '200px',
	);
	wp_editor( $content, $editor_id, $settings );
}

/**
 * Add custom Content Type meta boxes.
 *
 * @param  (object) $post The current editing Post object.
 * @return void
 * 
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_content_media_links_mb( $post ) {

	$media_links  = speekr_get_content_media_links();
	$speekr_ml    = get_post_meta( $post->ID, 'speekr-media-links', true );

	$output  = '<p class="speekr-mb-description">' . __( 'You can provide your talk in several formats.', 'speekr' ) . '</p>';
	$divider = '<div class="speekr-mb-divider"></div>';
	$select  = '<select name="speekr-media-links[embeded-media]" id="speekr-main-type">';

	$output .= '<div class="speekr-mb-block">';

	foreach ( $media_links as $k => $v ) {

		$k = esc_attr( $k );

		if ( $k !== 'other' ) {
			$select .= '<option value="' . $k . '"' . ( isset( $speekr_ml[ 'embeded-media' ] ) && $speekr_ml[ 'embeded-media' ] === $k ? ' selected="selected"' : '' ) . '>' . esc_html( $v['name'] ) . '</option>';

			$output .= '<p class="speekr-mb-line">
						<label for="speekr-content-media-links-' . $k . '">' . esc_html( $v['name'] ) . '</label><br>
						<input type="url" name="speekr-media-links[' . $k . '-link]" id="speekr-content-media-links-' . $k . '" value="' . esc_attr( isset( $speekr_ml[ $k . '-link' ] ) ? $speekr_ml[ $k . '-link' ] : '' ) . '" />
					</p>';
		} else {
			$output .= $divider;
			$output .= '<p class="speekr-mb-description">' . __( 'Add custom links to your talk.', 'speekr' ) . '</p>';

			$output .= '<div class="speekr-other-links" id="speekr-other-links" role="region" aria-live="polite" aria-relevant="additions removals">';

			$delbtn = '<button type="button" class="speekr-remove-link-btn speekr-button speekr-button-mini" aria-controls="speekr-other-links"><span class="screen-reader-text">' . __( '', 'speekr' ) . '</span><i class="dashicons dashicons-no-alt" aria-hidden="true"></i></button>';

			$output .= '<script id="speekr-del-btn" type="text/template">' . $delbtn . '</script>';

			if ( ! isset( $speekr_ml[ 'other-link' ] ) ||  ( isset( $speekr_ml[ 'other-link' ] ) && ! is_array( $speekr_ml[ 'other-link' ] ) ) ) {
				// Empty other link.
				$output .= '<p class="speekr-mb-line speekr-inlined-inputs">
						<span class="speekr-small-col">
							<label for="speekr-content-media-links-other-l-1">' . __( 'Label of the link', 'speekr' ) . '</label><br>
							<input type="text" name="speekr-media-links[other-link-label][]" id="speekr-content-media-links-other-l-1" value="" />
						</span>
						<span class="speekr-big-col">
							<label for="speekr-content-media-links-other-u-1">' . __( 'URL of the Link', 'speekr' ) . '</label><br>
							<input type="url" name="speekr-media-links[other-link-url][]" id="speekr-content-media-links-other-u-1" value="" />
						</span>
						<span class="speekr-remove-link"></span>
				</p>';
			} else {
				// Build list of other custom links.
				$i = 1;
				$c = count( $speekr_ml[ 'other-link' ] );

				foreach ( $speekr_ml[ 'other-link' ] as $link ) {
					$del_btn = $c > 1 ? $delbtn : '';
					$output .= '<p class="speekr-mb-line speekr-inlined-inputs' . ( $c === $i ? ' speekr-to-duplicate' : '' ) . '">
							<span class="speekr-small-col">
								<label for="speekr-content-media-links-other-l-' . $i . '">' . __( 'Label of the link', 'speekr' ) . '</label><br>
								<input type="text" name="speekr-media-links[other-link-label][]" id="speekr-content-media-links-other-l-' . $i . '" value="' . esc_attr( isset( $link[ 'label' ] ) ? $link[ 'label' ] : '' ) . '" />
							</span>
							<span class="speekr-big-col">
								<label for="speekr-content-media-links-other-u-' . $i . '">' . __( 'URL of the Link', 'speekr' ) . '</label><br>
								<input type="url" name="speekr-media-links[other-link-url][]" id="speekr-content-media-links-other-u-' . $i . '" value="' . esc_attr( isset( $link[ 'url' ] ) ? $link[ 'url' ] : '' ) . '" />
							</span>
							<span class="speekr-remove-link">' . $del_btn . '</span>
					</p>';
					$i++;
				}
			}

			// Prepare JS scripting.
			$output .= '<div id="speekr-new-other-links"></div></div><!-- .other-links -->';
		}

	}

	$output .= '<div id="speekr-cover-replacement">';
	$output .= $divider . '
				<p class="speekr-mb-line speekr-checkbox-line">
					<input type="checkbox" name="speekr-media-links[embeded]" id="speekr-is-embeded"' . ( isset( $speekr_ml[ 'embeded' ] ) && $speekr_ml[ 'embeded' ] === true ? ' checked="checked"' : '' ) . '>&nbsp;<label for="speekr-is-embeded">' . __( 'Replace with embeded media?', 'speekr' ) . '</label>
				</p>';

	$output .= '<div class="speekr-mb-line speekr-main-embeded-media">
					<p class="speekr-mb-description">
						<label for="speekr-main-type">' . __( 'Replace with:', 'speekr' ) . '</label>
					</p>
					' . $select . '</select>
				</div><!-- .speekr-main-embeded-media -->';
	$output .= '</div><!-- #speekr-cover-replacement -->';

	$output .= '</div><!-- .speekr-mb-block -->';

	echo apply_filters( 'speekr_content_media_links_mb', $output, $post );

}

/**
 * Add custom Conference info meta boxes.
 *
 * @param  (object) $post The current editing Post object.
 * @return void
 * 
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_conference_mb( $post ) {

	$speekr_conf = get_post_meta( $post->ID, 'speekr-conf', true );
	
	$output = '<p class="speekr-mb-description">' . __( 'What’s its name and where did it take place?', 'speekr' ) . '</p>
			<div class="speekr-mb-block">
				<p class="speekr-mb-line">
					<label for="speekr-conf-name">' . __( 'Conference Name', 'speekr' )  . '</label>
					<br>
					<input type="text" name="speekr-conf[name]" id="speekr-conf-name" value="' . esc_attr( isset( $speekr_conf[ 'name' ] ) ? $speekr_conf[ 'name' ] : '' ) . '" />
				</p>
				<p class="speekr-mb-line">
					<label for="speekr-conf-url">' . __( 'Conference Link', 'speekr' )  . '</label>
					<br>
					<input type="text" name="speekr-conf[url]" id="speekr-conf-url" value="' . esc_attr( isset( $speekr_conf[ 'url' ] ) ? $speekr_conf[ 'url' ] : '' ) . '" />
				</p>
			</div>';

	echo apply_filters( 'speekr_conference_mb', $output, $post );
}

/**
 * Add a bigger Editor to have a real Single page for the talk.
 *
 * @param  (object) $post The current editing Post object.
 * @return void
 * 
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_the_content_mb( $post ) {

	$as_article = get_post_meta( $post->ID, 'speekr-as-article', true );
	$height     = apply_filters( 'speekr_editor_height', 400 );

	// Checkbox before content.
	$output = '<p class="speekr-mb-line speekr-checkbox-line"><input type="checkbox" name="speekr-as-article" id="speekr-show-content"' . ( 'on' === $as_article ? ' checked="checked"' : '' ) . ' data-editorheight="' . (int) $height . '">
		<label for="speekr-show-content">' . __( 'Make this Speekr item a blog post.', 'speekr' ) . '</label><br>
		<span class="speekr-description" aria-hidden="true">' . __( 'Uncheck this checkbox will not delete the content below, don’t worry.', 'speekr' ) . '</span></p>';

	echo apply_filters( 'speekr_the_content_mb', $output, $post );

	// Prepare editor content.
	$content = $post->post_content;
	$editor_id = 'content';

	$settings = array(
		'editor_height' => (int) $height,
	);
	wp_editor( $content, $editor_id, $settings );
}

/**
 * Needed to prepare the next function.
 *
 * @return void
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_prepare_for_sticky() {
    // Unable to use "post_submitbox_minor_actions" action (/wp-admin/includes/meta-boxes.php) because $post_type is set early.
    global $post;
    if ( isset( $post->post_type ) && in_array( $post->post_type, array( 'talks' ) ) ) {
        $post->post_type_original = $post->post_type;
        $post->post_type = 'post';
    }
}
add_action( 'submitpost_box', 'speekr_prepare_for_sticky' );

/**
 * Print checkbox "Featured Post" on Public Statuses metabox.
 *
 * @return void
 * @see /wp-admin/includes/meta-boxes.php
 *
 * @author Geoffrey Crofte
 * @since  1.0
 */
function speekr_add_sticky_on_cpt() {
    global $post;
    if ( isset( $post->post_type_original ) && in_array( $post->post_type_original, array( 'talks' ) ) ) {
    	$is_stuck = get_post_meta( $post->ID, 'speekr-is-featured', true );
        echo '<div class="misc-pub-section misc-pub-post-status curtime misc-pub-speekr"><input type="checkbox" id="speekr-features-post" name="speekr-sticky"' . ( $is_stuck ? ' checked="checked"' : '' ) . '><label for="speekr-features-post">Featured post</label></div>';
        $post->post_type = $post->post_type_original;
        unset( $post->post_type_original );
    }
}
add_action( 'post_submitbox_misc_actions', 'speekr_add_sticky_on_cpt' );

/**
 * Save all the datas about Speekr Metaboxes.
 *
 * @param  (int) $post_id The current post ID.
 * @return void
 *
 * @since  1.0
 * @author Geoffrey Crofte
 */
function speekr_save_mb( $post_id ) {

	if ( ! isset( $_POST['_wpnonce'] ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['post_type'] ) && 'talks' === $_POST['post_type'] ) {

		if ( isset( $_POST['speekr-media-links'] ) ) {
			$media_links = speekr_get_content_media_links();
			$newml       = array();

			// Case of named media links to "sanitize" declared links.
			foreach ( $media_links as $k => $v ) {
				if ( 'other' === $k ) {
					continue;
				}
				$newml[ $k . '-link' ] = isset( $_POST['speekr-media-links'][ $k . '-link' ] ) ? esc_url( $_POST['speekr-media-links'][ $k . '-link' ] ) : '';
			}

			// Case of Others media links.
			$i = 0;
			foreach ( $_POST['speekr-media-links']['other-link-label'] as $label  ) {
				$newml[ 'other-link' ][] = array( 
					'label' => esc_attr( $label ),
					'url'   => esc_url( $_POST['speekr-media-links']['other-link-url'][ $i ] ),
				);
				$i++;
			}

			$newml = apply_filters( 'speekr_mb_media_links_sanitized', $newml, $media_links );

			$newml['embeded'] = isset( $_POST['speekr-media-links']['embeded'] ) && $_POST['speekr-media-links']['embeded'] === 'on' ? true : false;
			$newml['embeded-media'] = isset( $_POST['speekr-media-links']['embeded-media'] ) ? $_POST['speekr-media-links']['embeded-media'] : '';

			update_post_meta( $post_id, 'speekr-media-links', $newml );
		}

		if ( isset( $_POST['speekr-conf'] ) ) {
			$conf['name'] = isset( $_POST['speekr-conf']['name'] ) ? esc_html( $_POST['speekr-conf']['name'] ) : '';
			$conf['url']  = isset( $_POST['speekr-conf']['url'] ) ? esc_url( $_POST['speekr-conf']['url'] ) : '';
			update_post_meta( $post_id, 'speekr-conf', $conf );
		}

		if ( isset( $_POST['speekr_summary'] ) ) {
			update_post_meta( $post_id, 'speekr-summary', $_POST['speekr_summary'] );
		}

		// Is post an article too?
		if ( isset( $_POST['speekr-as-article'] ) && 'on' === $_POST['speekr-as-article'] ) {
			update_post_meta( $post_id, 'speekr-as-article', 'on' );
		} else {
			update_post_meta( $post_id, 'speekr-as-article', 'off' );
		}

		// Is featured post?
		if ( isset( $_POST['speekr-sticky'] ) && 'on' === $_POST['speekr-sticky'] ) {
			update_post_meta( $post_id, 'speekr-is-featured', true );
		} else {
			update_post_meta( $post_id, 'speekr-is-featured', false );
		}

	}  
}
add_action( 'save_post', 'speekr_save_mb' );
