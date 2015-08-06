var headline_envy_admin = headline_envy_admin || {};

( function( $, my ) {
	'use strict';

	my.event = my.event || {};

	// Cache the jquery selected document
	var $document = $( document );

	my.init = function() {
		this.edited_titles = false;
		this.experiment_titles = this.experiment_titles || [];

		this.$title_container = $( document.getElementById( 'he-titles' ) );
		this.$add_button = this.$title_container.find( '.add-title' );

		this.title_template = $( document.getElementById( 'he-title-template' ) ).html();

		$document.on( 'click', '#he-titles .add-title', function() {
			my.add_title();
			my.edited_titles = true;
		});

		$document.on( 'click', '#he-titles .dashicons-dismiss', function( e ) {
			e.preventDefault();

			my.remove_title( $( this ).closest( '.he-title' ), true );
			my.edited_titles = true;
		});

		$document.on( 'click', '#he-titles .select-title', function() {
			var $container = $( this ).closest( '.he-container' );
			$container.addClass( 'title-select' );
		});

		$document.on( 'click', '#he-titles .cancel-select-title', function() {
			var $container = $( this ).closest( '.he-container' );
			$container.removeClass( 'title-select' );

			$container.find( '[name="he-winner"]:checked' ).prop( 'checked', false );
		});

		$document.on( 'keydown', '#he-titles input[type="text"]', function( e ) {
			my.edited_titles = true;
		} );

		$document.on( 'click', '#publish', function( e ) {
			if ( my.edited_titles ) {
				$( 'body' ).addClass( 'saving-post' );
			}//end if
		} );

		$document.on( 'change', '.experiment-options select', function( e ) {
			var $el = $( this );
			var goal = $el.val();

			var $results = $el.closest( '.headline-results' ).find( '.experiment-results' );
			$results.find( 'table' ).hide();
			$results.find( 'table[data-goal="' + goal + '"]' ).css( 'display', 'table' );
		} );

		this.load_experiment();
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

		$document.trigger( 'headline-envy-add-title' );
	};

	/**
	 * handles removing an alternate title
	 */
	my.remove_title = function( $el, verify ) {
		var value = $.trim( $el.find( 'input' ).val() );
		var title = $.trim( $el.find( 'input[type="text"]' ).val() );

		var remove_item = true;

		if ( value && verify ) {
			remove_item = confirm( 'Are you sure you want to remove: "' + title + '"?' );
		}//end if

		if ( ! remove_item ) {
			return;
		}//end if

		$el.remove();

		$document.trigger( 'headline-envy-remove-title' );
	};

	/**
	 * load the experiment with pre-set titles
	 */
	my.load_experiment = function() {
		for ( var i in this.experiment_titles ) {
			// Ensure we run a hasOwnProperty check to avoid unexpected JS errors
			if ( this.experiment_titles.hasOwnProperty( i ) ) {
				this.add_title(this.experiment_titles[i]);
			}
		}//end for

		// once we've loaded titles, let's add a field we can watch for on submission
		// so we don't accidentally purge titles in the event of a JS error or an early
		// form submission.
		var $load_marker = $( '<input>', {
			name: 'headline_envy_titles_loaded',
			type: 'hidden',
			value: 'true'
		} );

		this.$title_container.after( $load_marker );
	};

	$( function() {
		my.init();
	});
})( jQuery, headline_envy_admin );
