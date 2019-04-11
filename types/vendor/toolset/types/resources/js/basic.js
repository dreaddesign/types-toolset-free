/** * * Use this file only for scripts needed in full version.
 * Before moving from embedded JS - make sure it's needed only here.
 *
 *
 */
jQuery(document).ready(function($){
    
    $('a.current').each( function() {
        var href = $(this).attr('href');
        if ('undefined' != typeof(href) && href.match(/page=wpcf\-edit(\-(type|usermeta))?/)) {
            $(this).attr('href', window.location.href);
        }
    });
    /**
     * colorbox for images
     */
    bind_colorbox_to_thumbnail_preview();

    /**
     * tooltip
     * http://www.mkyong.com/jquery/how-to-create-a-tooltips-with-jquery/
     */
    var changeTooltipPosition = function(event) {
        var tooltipX = event.pageX - 8;
        var tooltipY = event.pageY + 8;
        $('div.tooltip').css({top: tooltipY, left: tooltipX});
    };

    var showTooltip = function(event) {
        $('div.tooltip').remove();
        $('<div class="tooltip">'+$(this).data('tooltip')+'</div>').appendTo('body');
        changeTooltipPosition(event);
    };

    var hideTooltip = function() {
        $('div.tooltip').remove();
    };

    $(document).on( 'mousemove', '.js-wpcf-tooltip', changeTooltipPosition);
    $(document).on( 'mouseenter', '.js-wpcf-tooltip', showTooltip);
    $(document).on( 'mouseleave', '.js-wpcf-tooltip', hideTooltip);

});

/**
 * colorbox for images
 */
function bind_colorbox_to_thumbnail_preview() {
    jQuery('.js-wpt-file-preview img').each(function(){
        if ( jQuery(this).data('full-src')) {
            jQuery(this).on('click', function() {
                jQuery.colorbox({
                    href: jQuery(this).data('full-src'),
                    maxWidth: "75%",
                    maxHeight: "75%",
                    close: wpcf_js.close
                });
            });
        }
    });
}

/**
 * check for predefined or already used slugs
 * used for create edit CTP and CT
 */
jQuery.fn['wpcfProveSlug'] = function() {
    var form = jQuery( this ),
        inputSlug = form.find( 'input[name^="ct[slug]"]' );

    // if form already checked
    if( form.data( 'wpcfCheckNoReservedOrAlreadyUsed' ) ) {
        return true;
    }

    // taxonomy form
    if( form.hasClass( 'wpcf-tax-form') ) {
        var inputSlugPreSave = form.find( 'input[name^="ct[wpcf-tax]"]' ).length
            ? form.find( 'input[name^="ct[wpcf-tax]"]' ).val()
            : 0;

    // post type form
    } else if( form.hasClass( 'wpcf-types-form' ) ) {
        var inputSlugPreSave = form.find( 'input[name^="ct[wpcf-post-type]"]' ).length
            ? form.find( 'input[name^="ct[wpcf-post-type]"]' ).val()
            : 0;
    } else {
        return false;
    }

    jQuery.ajax( {
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: {
            slug: inputSlug.val(),
            slugPreSave: inputSlugPreSave,
            context: 'taxonomy',
            action: 'wpcf_get_forbidden_names'
        },
        cache: false
    } ).done( function( data ) {
        // remove any previous error
        inputSlug.parent().find( 'label.wpcf-form-error' ).remove();

        // already in use
        if( data.already_in_use == 1 ) {
            inputSlug.before( '<label for="' + inputSlug.attr('id') + '" class="wpcf-form-error">' + wpcfFormUsedOrReservedSlug + '</label>' );

            form.find( 'input[type="submit"]' ).removeAttr( 'disabled' );

            // not in use
        } else {
            // modal advertising dialog is shown on this event
            jQuery( document ).trigger( 'js-wpcf-event-types-show-modal' );

            form.data( 'wpcfCheckNoReservedOrAlreadyUsed', true ).submit();
        }
    } );

    return false;
}