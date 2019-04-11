/*
 * Group edit page JS
 *
 * This file should be used from now on as dedicated JS for group edit page.
 * Avoid adding new functionalities to basic.js
 *
 * Thanks!
 *
 * @since Types 1.1.5
 * @autor srdjan
 *
 *
 */

jQuery(document).ready(function($){

    /**
     * bind validation checkboxes
     */
    $('#post-body-content').on('change', '.js-wpcf-validation-checkbox', function() {
        next = $(this).closest('tr');

        // as there different number of hidden inputs between the checkbox
        // and the validation error message input we need to trick
        for( i = 0; i < 10; i++ ) {
            next = next.next();

            // break loop if next tr is found
            if( next.prop( 'tagName' ).toLowerCase() == 'tr' )
                break;

        }

        if( next.attr( 'type' ) == 'hidden' )
            next = next.next();

        if ( $(this).is(':checked') ) {
            $('input', next).removeAttr('disabled').focus();
        } else {
            $('input', next).attr('disabled', 'disabled');
        }
    });

    // Invoke drag on mouse enter
    $('#wpcf-fields-sortable').on('mouseenter', '.js-types-sortable', function(){
        if (!$(this).parent().hasClass('ui-sortable')) {
            $(this).parent().sortable({
                revert: true,
                handle: '.js-types-sort-button',
                start: function(e, ui){
                        ui.placeholder.height(ui.item.find('.wpcf-form-fieldset').height());
                    }
            });
        }
    });
    // Sort and Drag
    $('#wpcf-fields-sortable').sortable({
        cursor: 'ns-resize',
        axis: 'y',
        handle: 'img.wpcf-fields-form-move-field',
        forcePlaceholderSize: true,
        tolerance: 'pointer',
        start: function(e, ui){
                ui.placeholder.height(ui.item.height() + 23);
            }
    });

    $.fn.typesFieldOptionsSortable = function() {

        $( '.wpcf-fields-radio-sortable, .wpcf-fields-select-sortable, .wpcf-fields-checkboxes-sortable', this ).sortable({
            cursor: 'ns-resize',
            axis: 'y',
            handle: '.js-types-sort-button',
            start: function(e, ui){
                ui.placeholder.height(ui.item.height() - 2);
            }
        });

        $( '.wpcf-fields-checkboxes-sortable', this ).sortable({
            start: function(e, ui){
                ui.placeholder.height(ui.item.height() + 13);
            }
        });
    }

    $.fn.typesMarkExistingField = function() {

        var slug = $( '.wpcf-forms-field-slug', this );

        if( slug.length && slug.val() != '' )
            slug.attr( 'data-types-existing-field', slug.val() );
    }

    $( 'body' ).typesFieldOptionsSortable();


    $('[data-wpcf-type="checkbox"],[data-wpcf-type=checkboxes]').each( function() {
        $(this).bind('change', function() {
            wpcf_checkbox_value_zero($(this))
        });
        wpcf_checkbox_value_zero($(this));
    });

    /**
     * confitonal logic button close on group edit screen
     */
    $( 'body' ).on('click', '#conditional-logic-button-ok', function(){
        $(this).parent().slideUp('slow', function() {
            $('#conditional-logic-button-open').fadeIn();
        });
        return false;
    });

    /**
     * delete option
     */
    $(document).on('click', '.js-wpcf-button-delete', function() {
        var $thiz = $(this);
        if (confirm($(this).data('message-delete-confirm'))) {
            $thiz.closest('tr').fadeOut(function(){
                $(this).remove();
                $('.'+$thiz.data('id')).fadeOut(function(){
                    $(this).remove();
                });
            });
        }
        return false;
    });
});

function wpcf_checkbox_value_zero(field) {
    var passed = true;

    if (jQuery(field).hasClass('wpcf-value-store-error-error')) {
        jQuery(field).prev().remove();
        jQuery(field).removeClass('wpcf-value-store-error-error');
    }

    var value = jQuery(field).val();
    if (value === '') {
        passed = false;
        if (!jQuery(field).hasClass('wpcf-value-store-error-error')) {
            jQuery(field).before('<div class="wpcf-form-error">' + jQuery(field).data('required-message') + '</div>').addClass('wpcf-value-store-error-error');
            var legend = jQuery(field).closest('div.ui-draggable').children('fieldset').children('legend');
            if ( legend.hasClass('legend-collapsed') ) {
                legend.click();
            }
            var fieldset = jQuery(field).closest('fieldset');
            if ( jQuery('legend.legend-collapsed', fieldset ) ) {
                jQuery('legend.legend-collapsed', fieldset).click();
            }
        }
        jQuery(field).focus();
    }
    if (value === '0') {
        passed = false;
        if (!jQuery(field).hasClass('wpcf-value-store-error-error')) {
            jQuery(field).before('<div class="wpcf-form-error">' + jQuery(field).data('required-message-0') + '</div>').addClass('wpcf-value-store-error-error');
        }
        jQuery(field).focus();
    }
    return !passed;
}
