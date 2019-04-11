//noinspection JSUnusedAssignment
var Types = Types || {};

Types.information = Types.information || {};

( function( $ ) {
    Types.information.openDialog = function( id ) {
        // dialog
        var dialog = $( '#' + id );

        if( dialog.length ) {
            dialog.dialog( {
                dialogClass : 'wp-dialog types-information-dialog',
                modal : true,
                autoOpen : false,
                closeOnEscape : true,
                minWidth: 800,
                open: function() {
                    dialog.find( 'a' ).blur();
                },
            } ).dialog( 'open' );
        }
    }

    $( 'body' ).on( 'click', '[data-types-open-dialog]', function() {
        Types.information.openDialog( $( this ).data( 'types-open-dialog' ) );
    } );

    Types.information.openPointer = function( trigger ) {
        var content = $( '#' + trigger.data( 'types-open-pointer' ) );
        $( '.types-information-active-pointer' ).pointer( 'close' );

        if( trigger.length ) {
            trigger.addClass( 'types-information-active-pointer' );
            trigger.pointer( {
                pointerClass : 'types-information-pointer',
                content: content.html(),
                position: {
                    edge: 'bottom',
                    align: 'right'
                },
                buttons: function( event, t ) {
                    var button_close = $( '<a href="javascript:void(0);" class="notice-dismiss alignright"></a>' );
                    button_close.bind( 'click.pointer', function( e ) {
                        e.preventDefault();
                        t.element.pointer( 'close' );
                    });
                    return button_close;
                },
                show: function( event, t ){
                    t.pointer.css( 'marginLeft', '54px' );
                },
                close: function( event, t ){
                    t.pointer.css( 'marginLeft', '0' );
                },
            } ).pointer( 'open' );
        }
    }

    $( 'body' ).on( 'click', '[data-types-open-pointer]', function() {
        Types.information.openPointer( $( this ) );
    } );

} )( jQuery );