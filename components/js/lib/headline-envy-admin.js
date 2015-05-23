var headline_envy_admin = headline_envy_admin || {};

( function( $, my ) {
	'use strict';

	// We need to save this method to our own class var so that we can monkey patch it
	my.set_featured_image = wp.media.featuredImage.set;

	my.event = my.event || {};

	my.init = function() {
		this.init_titles();
		this.init_images();

		$( document ).on( 'click', '#save-post, #wp-preview, #post-preview, #publish', function( e ) {
			if ( my.edited_titles ) {
				$( 'body' ).addClass( 'saving-post' );
			}//end if
		} );

		this.load_title_experiments();
		this.load_image_experiments();

		// once we've loaded the experiments, let's add a field we can watch for on submission
		// so we don't accidentally purge experiments in the event of a JS error or an early
		// form submission.
		var $load_marker = $( '<input>', {
			name: 'headline_envy_loaded',
			type: 'hidden',
			value: 'true'
		} );

		this.$title_container.after( $load_marker );
	};

	my.init_titles = function() {
		this.edited_titles = false;
		this.experiment_titles = this.experiment_titles || [];

		this.$title_container = $( document.getElementById( 'he-titles' ) );
		this.$add_button = this.$title_container.find( '.add-title' );

		this.title_template = $( document.getElementById( 'he-title-template' ) ).html();

		$( document ).on( 'click', '#he-titles .add-title', function() {
			my.add_title();
			this.edited_titles = true;
		});

		$( document ).on( 'click', '#he-titles .dashicons-dismiss', function( e ) {
			e.preventDefault();

			my.remove_title( $( this ).closest( '.he-title' ), true );
			this.edited_titles = true;
		});

		$( document ).on( 'click', '#he-titles .select-title', function() {
			var $container = $( this ).closest( '.he-container' );
			$container.addClass( 'title-select' );
		});

		$( document ).on( 'click', '#he-titles .cancel-select-title', function() {
			var $container = $( this ).closest( '.he-container' );
			$container.removeClass( 'title-select' );

			$container.find( '[name="he-winner"]:checked' ).prop( 'checked', false );
		});

		$( document ).on( 'keydown', '#he-titles input[type="text"]', function( e ) {
			my.edited_titles = true;
		} );

		$( document ).on( 'change', '.experiment-options select', function( e ) {
			var $el = $( this );
			var goal = $el.val();

			var $results = $el.closest( '.headline-results' ).find( '.experiment-results' );
			$results.find( 'table' ).hide();
			$results.find( 'table[data-goal="' + goal + '"]' ).css( 'display', 'table' );
		} );
	};

	my.init_images = function() {
		if ( '1' !== this.test_images ) {
			return;
		}

		this.experiment_images = this.experiment_images || [];

		this.current_images = {};
		this.file_frame = wp.media.frames.file_frame;

		this.$thumbnail_container = $( document.getElementById( 'postimagediv' ) );
		this.image_template = $( document.getElementById( 'he-image-template' ) ).html();

		this.$thumbnail_container.append( $( document.getElementById( 'he-image-ui' ) ).html() );

		this.$image_ui = this.$thumbnail_container.find( '.he-image-ui' );

		$( document ).on( 'featured_image_set', function( e ) {
			my.$image_ui.addClass( 'active' );
		} );

		$( document ).on( 'click', '#remove-post-thumbnail', function( e ) {
			my.$image_ui.removeClass( 'active' );
		} );

		$( document ).on( 'click', '.he-image-ui .dashicons-dismiss', function( e ) {
			e.preventDefault();

			my.remove_image( $( this ).closest( '.he-image' ), true );
			my.edited_titles = true;
		});

		$( document ).on( 'click', '#postimagediv .select-image', function() {
			var $container = $( this ).closest( '.he-container' );
			$container.addClass( 'image-select' );
		});

		$( document ).on( 'click', '#postimagediv .cancel-select-image', function() {
			var $container = $( this ).closest( '.he-container' );
			$container.removeClass( 'image-select' );

			$container.find( '[name="he-image-winner"]:checked' ).prop( 'checked', false );
		});

		$( document ).on( 'click', '.he-container .add-image', function( e ) {
			e.preventDefault();

			// Instantiate a file/media frame object for our use
			my.file_frame = wp.media( {
				title: my.alternate_image_select_title,
				button: {
					text: my.alternate_image_select_button
				},
				library : {
					type : 'image'
				}
			} );

			// Put the user in the right place
		    my.file_frame.on( 'toolbar:create:select', function() {
				my.file_frame.state().set( 'filterable', 'uploaded' );
		    } );

			// Watch for the user to select 'set' the image they want
		    my.file_frame.on( 'select', function () {
				var attachment = my.file_frame.state().get('selection').first().toJSON();

				// Make sure the image hasn't already been used
				if (
					   'undefined' !== typeof my.current_images[ attachment.id ]
					|| attachment.id === Number( my.thumbnail_id )
				) {
					alert( my.image_already_used );
					return;
				}

				var ratio = 256 / attachment.width;

				var data = {
					attachment: {
						url: attachment.url,
						width: 256,
						height: attachment.height * ratio,
						title: attachment.title
					},
					value: attachment.id
				};

				my.add_image( data );
		    } );

			// Open the file/media frame
			my.file_frame.open();
		} );
	};

	/**
	 * add title to the UI
	 */
	my.add_title = function( data ) {
		data = data || {};

		var $title = $( this.title_template );
		var $titles = this.$title_container.find( '.he-title' );

		data.num = data.num || $titles.length + 1;
		data.id = data.id || 'he-title-' + data.num;
		data.improvement = data.improvement || '0%';
		data.conversion_rate = data.conversion_rate || '0%';

		$title
			.attr( 'data-variation', data.variation || '' )
			.attr( 'data-winner', data.winner || 'false' );

		$title
			.find( 'input[type="text"]' )
			.attr( 'id', data.id )
			.attr( 'name', 'headline_envy_title[' + data.num + ']' )
			.val( data.value || '' );

		$title
			.find( 'input[type="radio"]' )
			.val( data.variation || '' );

		$title
			.find( 'label' )
			.attr( 'for', data.id );

		$title
			.find( '.he-status' )
			.attr( 'title', data.improvement + ' Improvement, ' + data.conversion_rate + ' Conversion rate' )
			.text( data.improvement );

			this.$add_button.before( $title );

		$( document ).trigger( 'headline-envy-add-title' );
	};

	/**
	 * add an image to the UI
	 */
	my.add_image = function( data ) {
		data = data || {};

		var $image  = $( this.image_template );
		var $images = this.$thumbnail_container.find( '.he-image' );

		data.num = data.num || $images.length + 1;
		data.id = data.id || 'he-image-' + data.num;
		data.improvement = data.improvement || '0%';
		data.conversion_rate = data.conversion_rate || '0%';

		$image
			.attr( 'data-variation', data.variation || '' )
			.attr( 'data-winner', data.winner || 'false' );

		$image
			.find( 'input[type="hidden"]' )
			.attr( 'id', data.id )
			.attr( 'name', 'headline_envy_image[' + data.num + ']' )
			.val( data.value || '' );

		$image
			.find( 'input[type="radio"]' )
			.val( data.variation || '' );

		$image
			.find( 'label' )
			.attr( 'for', data.id );

		$image
			.find( '.he-status' )
			.attr( 'title', data.improvement + ' Improvement, ' + data.conversion_rate + ' Conversion rate' )
			.text( data.improvement );

		$image
			.find( 'img' )
			.attr( 'src', data.attachment.url )
			.attr( 'width', data.attachment.width )
			.attr( 'height', data.attachment.height )
			.attr( 'alt', data.attachment.title );

		this.current_images[ data.value ] = true;

		this.$thumbnail_container.find( '.he-image:last' ).after( $image );

		$( document ).trigger( 'headline-envy-add-image' );
	};

	/**
	 * handles removing an alternate title
	 */
	my.remove_title = function( $el, verify ) {
		var value = $.trim( $el.find( 'input' ).val() );
		var title = $.trim( $el.find( 'input[type="text"]' ).val() );

		var remove_item = true;

		if ( value && verify ) {
			remove_item = confirm( my.title_remove_confirm + ' "' + title + '"?' );
		}//end if

		if ( ! remove_item ) {
			return;
		}//end if

		$el.remove();

		$( document ).trigger( 'headline-envy-remove-title' );
	};

	/**
	 * handles removing an alternate image
	 */
	my.remove_image = function( $el, verify ) {
		var value = $.trim( $el.find( 'input[type="hidden"]' ).val() );
		var title = $.trim( $el.find( 'img' ).attr( 'alt' ) );

		var remove_item = true;

		if ( value && verify ) {
			remove_item = confirm( my.image_remove_confirm + ' "' + title + '"?' );
		}//end if

		if ( ! remove_item ) {
			return;
		}//end if

		$el.remove();

		$( document ).trigger( 'headline-envy-remove-image' );
	};

	/**
	 * load the title experiments
	 */
	my.load_title_experiments = function() {
		for ( var i in this.experiment_titles ) {
			this.add_title( this.experiment_titles[ i ] );
		}//end for
	};

	/**
	 * load the image experiments
	 */
	my.load_image_experiments = function() {
		if ( '1' !== this.test_images ) {
			return;
		}

		for ( var i in this.experiment_images ) {
			my.add_image( this.experiment_images[ i ] );
		}//end for
	};

	/**
	 * Monkey patch the featured image setting so we know when it's happened
	 */
	wp.media.featuredImage.set = function( attachment_id ) {
		// Make sure the image isn't a current alternate
		if ( 'undefined' !== typeof my.current_images[ attachment_id ] ) {
			alert( my.image_already_used );
			return;
		}

		// Update the thumbnail_id value
		my.thumbnail_id = attachment_id;
		// Here we're just calling the very same functino we just monkey patched
		my.set_featured_image( attachment_id );
		// Give ourselves a reliable way to know when a featured image has been set.
		$( document ).trigger({ type: 'featured_image_set', attachment_id: attachment_id });
	};

	$( function() {
		my.init();
	});
})( jQuery, headline_envy_admin );
