var headline_envy = headline_envy || {};

( function( $, my ) {
	'use strict';

	my.init = function() {
		this.experiments = {};
		this.collect_experiments();
		this.activate_experiments();
	};

	my.activate_experiments = function() {
		if ( 'undefined' === typeof window.optimizely ) {
			return;
		}//end if

		for ( var i in my.experiments ) {
			if (my.experiments.hasOwnProperty(i)) {
				window.optimizely.push([
					'activate',
					my.experiments[i].experiment
				]);
			}
		}//end for
	};

	my.collect_experiments = function() {
		$( 'headline-envy' ).each( function() {
			var $el = $( this );
			var experiment = $el.data( 'experiment' );

			my.experiments[ experiment ] = {
				experiment: experiment
			};
		});
	};

	$( function() {
		my.init();
	});
})( jQuery, headline_envy );
