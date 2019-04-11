//noinspection JSUnusedAssignment
var Types = Types || {};

Types.settings = Types.settings || {};

( function( $ ) {

    var modelData = $.parseJSON(WPV_Toolset.Utils.editor_decode64($('#types_model_data').html()));
    Types.settings.ajaxInfo = modelData.ajaxInfo || {};

    Types.settings.save = function( setting_id ) {

        var data = {
            action: 'types_settings_action',
            setting: setting_id,
            setting_value: $("input[name^='"+setting_id+"']" ).serialize(),
            wpnonce: Types.settings.ajaxInfo.fieldAction.nonce,
        };

        $( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );

        $.ajax({
            type: "POST",
            dataType: "json",
            url: ajaxurl,
            data: data,
            success: function( response ) {
                if ( response.success ) {
                    $( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
                } else {
                    $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
                }
            },
            error: function( ajaxContext ) {
                $( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
            },
            complete: function() {

            }
        });
    }

    $( 'body' ).on( 'change', '[data-types-setting-save]', function() {
        Types.settings.save( $( this ).attr( 'data-types-setting-save' ) );
    } );

} )( jQuery );