;( function( $, window, document, undefined ) {
	
	/**
	 * Move cover replacement (embedded) below Featured Image link.
	 */
	$( '#speekr-cover-replacement' ).insertAfter( $( '#postimagediv' ).find( '.inside' ) );

	/**
	 * Embed media cover replacement activation (or deactivation)
	 */
	var $embed_media    = $( '.speekr-main-embeded-media' ),
		$embed_checkbox = $( '#speekr-is-embeded' ),
		check_embed     = function( $el ) {
			if ( $el.is( ':checked' ) ) {
				$embed_media.attr( 'aria-hidden', 'false' ).fadeIn( 400 );
			} else {
				$embed_media.attr( 'aria-hidden', 'true' ).hide();
			}
		};

	check_embed( $embed_checkbox );

	$embed_checkbox.on( 'change.speekr', function() {
		check_embed( $(this) );
	} );

	/**
	 * Add a new "Other" link.
	 */
	$( '#speekr-new-other-links' ).html( '<p class="speekr-increment"><button type="button" class="button speekr-button" id="speekr-add-item"><i class="dashicons dashicons-plus" aria-hidden="true"></i>&nbsp;' + speekr.add_other_item + '</button>' );

	var $place = $( '.speekr-increment' );

	$( '#speekr-add-item' ).on( 'click.speekr', function() {
		var $line  = $( '.speekr-to-duplicate' ),
			id     = $line.find( 'label' ).attr( 'for' ),
			$clone = $line.clone(),
			cname  = 'speekr-content-media-links-other-l-',
			cname2 = 'speekr-content-media-links-other-u-';

		$line.removeClass('speekr-to-duplicate');

		id = parseInt( id.split( cname )[1] );


		$clone.find('label[for=' + ( cname + id ) + ']').attr( 'for', cname + ( id + 1 ) );
		$clone.find('input[id=' + ( cname + id ) + ']').attr( 'id', cname + ( id + 1 ) );
		$clone.find('label[for=' + ( cname2 + id ) + ']').attr( 'for', cname2 + ( id + 1 ) );
		$clone.find('input[id=' + ( cname2 + id ) + ']').attr( 'id', cname2 + ( id + 1 ) );
		$clone.find('input').val('');
		
		$line.after( $clone );
		
		// get the last line
		// get the # of the line (to change value/for/id)
		// clone it and change the #
		// insert it at the last place
		return false;
	} );

	/**
	 * Hide/Show Speekr as content Editor
	 */
	var check_editor = function( $el ) {
			if ( $el.is( ':checked' ) ) {
				$editor.attr( 'aria-hidden', 'false' ).fadeIn( 400 );
				$c_editor.parent().after('<div class="speekr-mb-divider"></div>');
				$('#content_ifr').css('height', $c_editor.data( 'editorheight' ) + 'px' );
			} else {
				$editor.attr( 'aria-hidden', 'true' ).hide();
				$c_editor.parent().next('.speekr-mb-divider').remove()
			}
		},
		$editor   = $('#wp-content-wrap'),
		$c_editor = $('#speekr-show-content');

	check_editor( $c_editor );

	$c_editor.on( 'change.speekr', function() {
		check_editor( $(this) );
	} );

} )( jQuery, window, document );