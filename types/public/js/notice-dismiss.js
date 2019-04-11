( function( $ ) {

    $( 'body' ).on( 'click', '[data-types-notice-dismiss-permanent] .notice-dismiss', function() {
        $.post( ajaxurl, {
            action: 'types_notice_dismiss_permanent',
            types_notice_dismiss_permanent: $( this ).closest( '.notice' ).data( 'types-notice-dismiss-permanent' )
        } );

        return false;
    } );

} )( jQuery );