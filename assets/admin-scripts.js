jQuery( document ).ready( function() {

	jQuery( '.hmes-log-table td.expand' ).click( function() {

		var td = jQuery( this );

		if ( td.hasClass( 'expanded' ) ) {

			td.removeClass( 'expanded' ).html( '+' ).closest( 'tr' ).removeClass( 'expanded' );
		} else {
			td.addClass( 'expanded').html( '-' ).closest( 'tr' ).addClass( 'expanded' );
		}

	} );

	jQuery( '.hm-es-reindex-submit' ).click( function( e ) {

		e.preventDefault();

		var self = jQuery( this );

		HMESIndexTypeManager.reindex( self.attr( 'data-type-name' ) );
	} );


	jQuery( '.hm-es-status' ).each( function() {

		var name = jQuery( this ).attr( 'data-type-name' );

		HMESIndexTypeManager.updateStatus( name );

	} );

} );

HMESIndexTypeManager = new function() {

	var self = this;

	self.getNonce = function() {

		return jQuery( '#hm_es_settings' ).val();
	};

	self.reindex = function( type, callback ) {

		jQuery.post( ajaxurl, { action: 'hmes_refresh_index', type_name: type, nonce: self.getNonce() }, function( data ) {

		} ).done( function() {

			var recurse = function( data ) {

				if ( ! data.is_doing_full_index) {
					return;
				}

				setTimeout( function() {
					self.updateStatus( type, function( data ) { recurse( data ) } );
				}, 5000 )
			};

			self.updateStatus( type, function( data ) {
				recurse( data );
			} )

		} );

	};

	self.updateStatus = function( type, callback ) {

		var element = jQuery( '.hm-es-status-' + type );
		var messageElement = jQuery( '.hm-es-status-message-' + type );

		messageElement.html( 'Fetching...' );

		element.removeClass( 'status-warning' ).removeClass( 'status-error').removeClass( 'status-ok' ).addClass( 'status-warning' );

		self.getStatus( type, function( data ) {

			data = jQuery.parseJSON( data );

			var string = ( data.is_doing_full_index ) ? 'Indexing ' : 'Ready ';
			string += '(' + data.indexed_count + ' of ' + data.database_count + ' indexed)';

			var newClass = '';

			if ( data.error ) {

				newClass = 'status-error';

			} else if ( data.is_doing_full_index ) {

				newClass = 'status-warning';

			} else {

				newClass = 'status-ok';
			}

			if ( ! data.error ) {

				element.css( 'width', ( ( data.indexed_count / data.database_count ) * 100 ) + '%' );
			}

			messageElement.html( string );

			element.removeClass( 'status-warning' ).removeClass( 'status-error').removeClass( 'status-ok' ).addClass( newClass );

			if ( typeof( callback ) == 'function' ) {

				callback( data );
			}

		} );
	};

	self.getStatus = function( type, callback ) {

		jQuery.post( ajaxurl, { action: 'hmes_get_type_status', type_name: type, nonce: self.getNonce() }, function( data ) {

			callback( data );

		} );
	};

}