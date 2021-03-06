'use strict';

( function ( $ ) {
	$( '.fsp-settings-collapser' ).on( 'click', function () {
		let _this = $( this );

		if ( ! _this.parent().hasClass( 'fsp-is-open' ) )
		{
			_this.parent().find( '.fsp-settings-collapse' ).slideToggle();
			_this.find( '.fsp-settings-collapse-state' ).toggleClass( 'fsp-is-rotated' );
		}
	} );

	$( '#fspResetToDefault' ).on( 'click', function () {
		FSPoster.confirm( fsp__( 'Are you sure to reset settings to default?' ), function () {
			let fsNodeId	= $( '[name="fs_node_id"]' ).val();
			let fsNodeType 	= $( '[name="fs_node_type"]' ).val();

			FSPoster.ajax( 'reset_custom_settings', { 'fs_node_id': fsNodeId, 'fs_node_type': fsNodeType }, function ( res ) {
				$( '[data-modal-close=true]' ).click();

				FSPoster.toast( res[ 'msg' ], 'success' );
			} );
		}, 'far fa-save', fsp__( 'Reset to default' ) );
	} );

	$( '#fspSaveSettings' ).on( 'click', function () {
		let data = FSPoster.serialize( $( '#fspSettingsForm' ) );

		FSPoster.ajax( 'save_custom_settings', data, function ( res ) {
			FSPoster.toast( res[ 'msg' ], 'success' );
		} );
	} );

	$( '#fspURLShortener' ).on( 'change', function () {
		if ( $( this ).is( ':checked' ) )
		{
			$( '#fspShortenerRow' ).slideDown();
		}
		else
		{
			$( '#fspShortenerRow' ).slideUp();
		}
	} ).trigger( 'change' );

	$( '#fspShortenerSelector' ).on( 'change', function () {
		if ( $( this ).val() === 'bitly' )
		{
			$( '#fspBitly' ).slideDown();
		}
		else
		{
			$( '#fspBitly' ).slideUp();
		}
	} ).trigger( 'change' );

	$( '#fspCustomURL' ).on( 'change', function () {
		if ( $( this ).is( ':checked' ) )
		{
			$( '#fspCustomURLRow_1' ).slideUp();
			$( '#fspCustomURLRow_2' ).slideDown();
		}
		else
		{
			$( '#fspCustomURLRow_1' ).slideDown();
			$( '#fspCustomURLRow_2' ).slideUp();
		}
	} ).trigger( 'change' );

	$( '#fspUseGA' ).on( 'click', function () {
		$( this ).parent().parent().children( 'input' ).val( 'utm_source={network_name}&utm_medium={account_name}&utm_campaign=FS%20Poster' );
	} );
} )( jQuery );