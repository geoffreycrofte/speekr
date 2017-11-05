;( function( $, window, document, undefined ) {
	
	/**
	 * Accessibility Improvement.
	 */
	$( '.speekr-settings .hide-if-no-js' ).attr( 'aria-hidden', 'false' );

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
	$( '#speekr-new-other-links' ).html( '<p class="speekr-increment"><button type="button" class="button speekr-button speekr-button-mini" id="speekr-add-item"><i class="dashicons dashicons-plus" aria-hidden="true"></i>&nbsp;' + speekr.add_other_item + '</button>' );

	var $place     = $( '.speekr-increment' ),
		$container = $( '.speekr-other-links' );

	$( '#speekr-add-item' ).on( 'click.speekr', function() {
		var $line   = $( '.speekr-to-duplicate' ),
			id      = $line.find( 'label' ).attr( 'for' ),
			$clone  = $line.clone(),
			cname   = 'speekr-content-media-links-other-l-',
			cname2  = 'speekr-content-media-links-other-u-',
			$delbtn = $( '#speekr-del-btn' ).html(),
			nbitems = $container.find( '.speekr-mb-line' ).length;

		$line.removeClass( 'speekr-to-duplicate' );

		id = parseInt( id.split( cname )[1] );

		$clone.find( 'label[for=' + ( cname + id ) + ']' ).attr( 'for', cname + ( id + 1 ) );
		$clone.find( 'input[id=' + ( cname + id ) + ']' ).attr( 'id', cname + ( id + 1 ) );
		$clone.find( 'label[for=' + ( cname2 + id ) + ']' ).attr( 'for', cname2 + ( id + 1 ) );
		$clone.find( 'input[id=' + ( cname2 + id ) + ']' ).attr( 'id', cname2 + ( id + 1 ) );
		$clone.find( 'input' ).val('');
		
		$line.after( $clone );

		// If we just added a second line, adding del btn too
		if ( nbitems === 1 ) {
			$container.find( '.speekr-mb-line' ).each( function(){
				$(this).find( '.speekr-remove-link' ).html( $delbtn );
			} );
		}

		return false;
	} );

	/**
	 * Remove a metabox Other Link line
	 */
	$container.on( 'click.speekr', '.speekr-remove-link-btn', function() {

		if ( confirm( speekr.confirm_rm_item ) ) {
			var $_this  = $(this),
				$parent = $_this.closest( '.speekr-mb-line' ),
				nbitems = $container.find( '.speekr-mb-line' ).length;

			$parent.addClass( 'to-remove' );
			setTimeout(function(){
				$parent.remove();

				// If we remove the model to duplicate, attribute another model.
				if ( $parent.hasClass( 'speekr-to-duplicate' ) ) {
					$container.find( '.speekr-mb-line:last' ).addClass( 'speekr-to-duplicate' );
				}
				// If we remove the before-the-last item, remove the del button.
				if ( nbitems === 2 ) {
					$container.find( '.speekr-mb-line:first' ).find( '.speekr-remove-link-btn' ).remove();
				}
			}, 400 );
		}

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

	/**
	 * AJAX Actions on some buttons.
	 */
	$( '.speekr-button[data-ajax-action]' ).on( 'click.speekr', function() {
		var $_this = $(this),
			action = $_this.data( 'ajax-action' );

		$_this.addClass( 'speekr-loading' );

		$.post(
			ajaxurl,
			{ action: action, _wpnonce: $_this.data( 'nonce' ) }
		).done( function( data ) {
			
			$_this.removeClass( 'speekr-loading' );
			$_this.next( '.speekr-success, .speekr-error' ).remove();

			if ( data.success === true ) {
				$_this.after( '<p class="speekr-success">' + data.data.message + '</p>' );
				$_this.trigger( 'speekr-success', data.data );
			} else {
				$_this.after( '<p class="speekr-error">' + data.data.message + '</p>' );
				$_this.trigger( 'speekr-error', data.data );
			}
		});

		return false;
	} )

	// AJAX Actions on specific buttons.
	.on( 'speekr-success', function( e, data ) {
		var action = $( e.target ).data('ajax-action');

		if ( action === 'speekr_create_default_page' ) {
			var $select = $( '#speekr-list-page' );
			$select.find( 'option[selected]' ).removeAttr( 'selected' );
			$select.append( '<option value="' + data.item_id + '" selected="selected">' + data.item_title + '</option>' );
		}
	} );

} )( jQuery, window, document );
