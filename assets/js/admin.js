;( function( $, window, document, undefined ) {
	
	/**
	 * Accessibility Improvement.
	 */
	$( '[class^="speekr-"] .hide-if-no-js' ).attr( 'aria-hidden', 'false' );
	$( '[class^="speekr-"] .hide-if-js' ).attr( 'aria-hidden', 'true' );

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
	 * Filter gray on logo.
	 */
	$( '[id^="speekr-content-media-links-"]' ).on( 'blur.speekr', function() {
		if ( $(this).val() ) {
			$(this).closest( '.speekr-mb-line' ).find( 'label' ).addClass( 'is-filled' );
		} else {
			$(this).closest( '.speekr-mb-line' ).find( 'label' ).removeClass( 'is-filled' );
		}
	} ).trigger( 'blur.speekr' );

	/**
	 * Embed Custom Selection
	 */
	$( '#speekr-embed-type' ).attr( 'aria-controls', 'speekr-custom-embed' )
		.on( 'change.speekr', function() {
		if ( $(this).val() === 'custom' ) {
			$( '.speekr-custom-embed' ).removeClass('hide-if-js').attr( 'aria-hidden', 'false' );
		} else {
			$( '.speekr-custom-embed' ).addClass('hide-if-js').attr( 'aria-hidden', 'true' );
		}
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


	/**
	 * Notices Removing.
	 * Ajax action.
	 */
	$( '.speekr-notice' ).on( 'click.speekr', '.notice-dismiss', function() {
		var $_this  = $(this),
			$notice = $_this.closest( '.speekr-notice' ),
			notice  = $notice.data( 'notice' ),
			nonce   = $notice.data( 'nonce' ),
			action  = 'speekr_remove_notice';

		$.post(
			ajaxurl,
			{ action: action, _wpnonce: nonce, notice: notice }
		).done( function( data ) {
			console.log(data);
		} );
	} );

	/**
	 * Importer.
	 * Ajax action.
	 */
	var can_import = function() {
		if ( $( '.speekr-importer [type="checkbox"]:checked' ).length === 0 ) {
			$( '.speekr-importer button' ).addClass( 'disabled' );
		} else {
			$( '.speekr-importer button' ).removeClass( 'disabled' );
		}
	};

	can_import();

	$( '.speekr-importer [type="checkbox"]' ).on( 'change.speekr', can_import );

	$( '.speekr-importer' ).on( 'click.speekr', 'button', function() {

		if ( $(this).hasClass( 'disabled' ) ) {
			return;
		}

		var $_this    = $(this),
			$importer = $_this.closest( '.speekr-importer' ),
			nonce     = $importer.data( 'nonce' ),
			action    = 'speekr_import_posts',
			posts     = { 'cats': [], 'tags': [], 'posts': [] },
			$ld_nb    = $( '.speekr-progress-nb' ),
			$ld_total = $( '.speekr-progress-max' ),
			$ld_prog  = $( '.speekr-progress-bar-value' ),
			$ld_perc  = $( '.speekr-progress-bar-percent' ),
			$ld_posts = $( '.speekr-progress-posts' ),
			importr   = function(action, nonce, posts, loop) {
				
				loop = loop || null;

				$.post(
					ajaxurl,
					{ action: action, _wpnonce: nonce, posts: posts, loop: loop }
				).done( function( data ) {
					console.log(data);
					var datas = data.data.datas,
						loop  = {
							'max':     datas.max,
							'current': datas.current,
							'list':    datas.list,
						},
						percent = parseInt( ( loop.current + 1 ) / loop.max * 100 );

					// Set loader styles
					$ld_posts.addClass( 'is-visible' ).attr( 'aria-hidden', 'false' );
					$ld_nb.text( loop.current + 1 );
					$ld_total.text( loop.max );
					$ld_perc.text( percent + '%' );
					$ld_prog.css('width', percent + '%' );
					
					if ( (datas.max - 1) > datas.current ) {
						// Ajaxception…
						importr( action, nonce, posts, loop );
					} else {
						$( '.speekr-progress-cta' ).addClass( 'is-visible' ).attr( 'aria-hidden', 'false' );
					}
				} );
			}; 

		// Get from categories
		$importer.find( '[name="speekr_settings[cat]"]:checked' ).each(function(){
			posts.cats.push( $(this).val() );
		});

		// Get from tags
		$importer.find( '[name="speekr_settings[tag]"]:checked' ).each(function(){
			posts.tags.push( $(this).val() );
		});

		// Get from posts
		$importer.find( '[name="speekr_settings[post]"]:checked' ).each(function(){
			posts.posts.push( $(this).val() );
		});

		// Show loader & launch importer
		$( '.speekr-importer-loader' ).addClass( 'is-visible' ).attr( 'aria-hidden', 'false' );
		importr( action, nonce, posts );

		return false;
	} );

} )( jQuery, window, document );
