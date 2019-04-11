;( function( $ ) {
    $( document ).on( 'click', '[data-' + toolset_admin_notices.triggerNoticeDismissible + ']', function() {
        var notice = $( this ).parents( '.toolset-notice-wp' );
        var ajaxRequestData = {};
        ajaxRequestData['action'] = toolset_admin_notices.action;
        ajaxRequestData[toolset_admin_notices.varnameNonce] = toolset_admin_notices.nonce;
        ajaxRequestData[toolset_admin_notices.varnameAction] = toolset_admin_notices.triggerNoticeDismissible;
        ajaxRequestData[toolset_admin_notices.varnameNoticeId] = $( this ).data( toolset_admin_notices.triggerNoticeDismissible );

        var stopSimilarNotices = [];

        $("input[name='dismiss-similar-notices[]']:checked").each(function () {
            stopSimilarNotices.push( $(this).val() );
        });

        ajaxRequestData[toolset_admin_notices.varnameDismissSimilarNotices] = stopSimilarNotices;

        $.ajax( {
            url: ajaxurl,
            method: 'POST',
            data: ajaxRequestData
        } ).done( function( ajaxResponseData ) {
            notice.fadeTo( 100, 0, function() {
                notice.slideUp( 100, function() {
                    notice.remove();
                });
            });
        } );
    } );


    $( document ).on( 'click', '[data-toolset-admin-notices-toggle-visibility]', function() {
        var elementSelectorString = $( this ).data( 'toolset-admin-notices-toggle-visibility' );
        $( elementSelectorString ).toggle();
    } )

    jQuery( '.notice.js-toolset-fadable' ).each( function() {
        var $this = jQuery( this );
        var text = $this.text();
        // Formula taken from https://ux.stackexchange.com/a/85898
        var miliseconds = Math.max( Math.min( text.length * 50, 2000 ), 7000 );
        setTimeout( function() {
            $this.fadeOut( );
        }, miliseconds )
    } );

} ( jQuery ) );
