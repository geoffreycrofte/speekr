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
		'speekr-type',
		__( 'Content Type', 'speekr' ),
		'speekr_content_type_mb',
		null,
		'side',
		'high',
		null // callback_args (array)
	);

}

/**
 * Add custom Content Type meta boxes
 * 
 */
function speekr_content_type_mb( $post ) {

	$link_types = array(
		'youtube'     => __( 'Youtube', 'speekr' ),
		'vimeo'       => __( 'Vimeo', 'speekr' ),
		'speakerdeck' => __( 'SpeakerDeck', 'speekr' ),
		'slideshare'  => __( 'Slideshare', 'speekr' ),
	);

	$outline = '<p>' . __( 'What is the main format of your presentation?', 'speekr' ) . '</p>';
	$speekr_meta = get_post_meta( $post->ID, 'speekr-meta', true );

	$outline .= '<div class="speekr-mb-block">';

	foreach ( $link_types as $k => $v ) {

		$outline .= '<p class="speekr-mb-line"><label for="speekr-content-type-' . esc_attr( $k ) . '">' . esc_html( $v ) . '</label><br>';

		$outline .= '<input type="url" name="speekr-meta[' . esc_attr( $k ) . '-link]" id="speekr-content-type-' . esc_attr( $k ) . '" value="' . esc_attr( isset( $speekr_meta[ esc_attr( $k ) . '-link' ] ) ? $speekr_meta[ esc_attr( $k ) . '-link' ] : '' ) . '" /></p>';
	}

	$outline .= '</div>';

	echo apply_filters( 'speekr_content_type_mb', $outline, $post );

}

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

		// TODO: Should be sanitized.
		$speekr_meta = update_post_meta( $post_id, 'speekr-meta', $_POST['speekr-meta'] );

	}  
}
add_action( 'save_post', 'speekr_save_mb' );