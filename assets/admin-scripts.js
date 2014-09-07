jQuery( document ).ready( function() {

	jQuery( '.hmes-log-table td.expand' ).click( function() {

		var td = jQuery( this );

		if ( td.hasClass( 'expanded' ) ) {

			td.removeClass( 'expanded' ).html( '+' ).closest( 'tr' ).removeClass( 'expanded');
		} else {
			td.addClass( 'expanded').html( '-' ).closest( 'tr' ).addClass( 'expanded' );
		}

	} );

} );