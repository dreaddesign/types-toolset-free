( function( $ ) {

    var linkDeactivate = $( '#types-leave-feedback-trigger' ).parent().parent().find( '.deactivate > a' ),
        dialogClosed = 0;

    linkDeactivate.on( 'click', function( e ) {
        if( dialogClosed == 1 ) {
            location.href = $( this ).attr( 'href' );
            return;
        }

        e.preventDefault();

        $.post( ajaxurl, {
            action: 'types_feedback_dont_show_for_90_days'
        } );

        var dialogFeedback = $( '#types-feedback' );

        dialogFeedback.dialog({
            'dialogClass' : 'wp-dialog types-feedback',
            'modal' : true,
            'autoOpen' : false,
            'closeOnEscape' : true,
            'width': 500,
            close: function() {
                dialogClosed = 1;
                linkDeactivate.trigger( 'click' );
            }
        } ).dialog( 'open' );
    } );

    $( 'body' ).on( 'click', '#types-leave-feedback-dialog-survey-link, #types-leave-feedback-dialog-survey-link-cancel', function() {
        $( '#types-feedback' ).dialog( 'close' );
    } );

} )( jQuery );