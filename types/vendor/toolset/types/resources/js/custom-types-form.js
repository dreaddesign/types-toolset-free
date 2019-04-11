/**
 *
 * Post Types form JS
 *
 *
 */

jQuery(document).ready(function($){
    /**
     * setup title
     */
    var labelPostType = $('#post-body-content .js-wpcf-slugize-source').val() != ''
        ? $('#post-body-content .js-wpcf-slugize-source').val()
        : $('#post-body-content .js-wpcf-slugize-source' ).data('anonymous-post-type');

    $('.js-wpcf-singular').html( labelPostType );
    $('#post-body-content').on('keyup input cut paste', '.js-wpcf-slugize-source', function() {
        $('.js-wpcf-singular').html($(this).val());
    });
    /*
     * 
     * Submit form trigger
     */
    $('.wpcf-types-form').submit(function(){

        /**
         * do not check builtin post types
         */
        if ( '_builtin' == jQuery('.wpcf-form-submit', jQuery(this)).data('post_type_is_builtin') ) {
            return true;
        }
        /*
         * Check if singular and plural are same
         */
        if ( jQuery('#name-singular').val().length > 0 ) {
            if ( jQuery('#name-singular').val().toLowerCase() == jQuery('#name-plural').val().toLowerCase()) {
                if (jQuery('#wpcf_warning_same_as_slug input[type=checkbox]').is(':checked')) {
                    return true;
                }
                jQuery('#wpcf_warning_same_as_slug').fadeOut();
                alert(jQuery('#name-plural').data('wpcf_warning_same_as_slug'));
                jQuery('#name-plural').after(
                    '<div class="wpcf-error message updated" id="wpcf_warning_same_as_slug"><p>'
                    + jQuery('#name-plural').data('wpcf_warning_same_as_slug')
                    + '</p><p><input type="checkbox" name="ct[labels][ignore]" />'
                    + jQuery('#name-plural').data('wpcf_warning_same_as_slug_ignore')
                    + '</p></div>'
                    ).focus().bind('click', function(){
                        jQuery('#wpcf_warning_same_as_slug').fadeOut();
                    });
                wpcfLoadingButtonStop();
                jQuery('html, body').animate({
                    scrollTop: 0
                }, 500);
                return false;
            }
            jQuery(this).removeClass('js-types-do-not-show-modal');
        }

        /**
         * check for reserved names and already used slugs
         */
        return jQuery( this ).wpcfProveSlug();
    });
    /**
     * modal advertising
     */
    /*if(
        jQuery.isFunction(jQuery.fn.types_modal_box)) {
        jQuery('.wpcf-types-form').types_modal_box();
    }
    */
    /**
     * choose icon
     */
    $( document ).on( 'click', '.js-wpcf-choose-icon', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="wpcf-dashicons"><span class="spinner"></span>'+$thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'wpcf-choose-icon wpcf-ui-dialog',
            modal: true,
            minWidth: 800,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('wpcf-title'),
            position: { my: "center top+50", at: "center top", of: window },

        });
        // load remote content
        dialog.load(
            ajaxurl, 
            {
                action: 'wpcf_edit_post_get_icons_list',
                _wpnonce: $thiz.data('wpcf-nonce'),
                slug: $thiz.data('wpcf-value'),
                "wpcf-post-type": $thiz.data('wpcf-post-type'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('keyup input cut paste', '.js-wpcf-search', function() {
                    if ( '' == $(this).val() ) {
                        $('li', dialog).show();
                    } else {
                        var re = new RegExp($(this).val(), "i");
                        $('li', dialog).each(function(){
                            if ( !$(this).data('wpcf-icon').match(re) ) {
                                $(this).hide();
                            } else {
                                $(this).show();
                            }
                        });
                    }
                });
                $(dialog).on('click', 'a', function() {
                    var $icon = $(this).data('wpcf-icon');
                    $('#wpcf-types-icon').val($icon);
                    $thiz.data('wpcf-value', $icon);
                    classes = 'wpcf-types-menu-image dashicons-before dashicons-'+$icon;
                    $('div.wpcf-types-menu-image').removeClass().addClass(classes);
                    dialog.dialog( "close" );
                    return false;
                });
            }
            );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * post types
     */
    $(document).on( 'change', '.js-wpcf-relationship-checkbox', function() {
        var $value = $(this).data('wpcf-value');
        var $type = $(this).data('wpcf-type');

        if ( $(this).is(':checked') ) {
            $(this).parent().addClass('active');
            $('.js-wpcf-relationship-checkbox').each(function(){
                if ( $value == $(this).data('wpcf-value') && $type != $(this).data('wpcf-type') ) {
                    $(this).attr('disabled', 'disabled').parent().addClass('disabled');
                    $(this).closest('li').attr('title', $(this).data('wpcf-message-disabled'));
                }
            });
        } else {
            $(this).parent().removeClass('active');
            $('.js-wpcf-relationship-checkbox').each(function(){
                if ( $value == $(this).data('wpcf-value') ){
                    $(this).removeAttr('disabled').parent().removeClass('disabled');
                    $(this).closest('li').removeAttr('title');
                }
            });
        }
    });
    $('#relationship :disabled').each(function(){
        $(this).closest('li').attr( 'title', $(this).data('wpcf-message-disabled'));
    });
    /**
     * choose fields
     */
    $( document ).on( 'click', '.js-wpcf-edit-child-post-fields', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;"><span class="spinner"></span>'+$thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        /**
         * params for dialog
         */
        var dialog_data = {
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'wpcf-child-post-fields-dialog wpcf-ui-dialog',
            modal: true,
            minWidth: 800,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('wpcf-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: $thiz.data('wpcf-buttons-apply'),
                click: function() {
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_post_save_child_fields',
                            _wpnonce: $('#wpcf-fields-save-nonce').val(),
                            parent: $('#wpcf-parent').val(),
                            child: $('#wpcf-child').val(),
                            current: $(':input', dialog).serialize()
                        }
                    })
                    /**
                     * close dialog
                     */
                    $( this ).dialog( "close" );
                },
                class: 'button-primary'
            }, {
                text: $thiz.data('wpcf-buttons-cancel'),
                click: function() {
                    $( this ).dialog( "close" );
                },
                class: 'wpcf-ui-dialog-cancel'
            }]
        };
        /**
         * remove button apply
         */
        if ( 'new' == $thiz.data('wpcf-save-status') ) {
            dialog_data.buttons.shift();
            dialog_data.buttons[0].class = 'button-primary';
        }
        /**
         * open the dialog
         */
        dialog.dialog(dialog_data);
        // load remote content
        dialog.load(
            ajaxurl, 
            {
                action: 'wpcf_edit_post_get_child_fields_screen',
                _wpnonce: $thiz.data('wpcf-nonce'),
                parent: $thiz.data('wpcf-parent'),
                child: $thiz.data('wpcf-child'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('change', '.wpcf-form-radio', function() {
                    if ('specific' == $(this).val()) {
                        $('#wpcf-specific').slideDown();
                    } else {
                        $('#wpcf-specific').slideUp();
                    }
                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * update groups with type
     */
    $('#field_groups').on('change', '.js-wpcf-custom-fields-group', function(){ });

    /**
     * load column box
     */

    function wpcf_edit_post_get_child_fields_box_message_helper() {
        var $container = $('#wpcf-custom-field-message');
        if ( $('.js-wpcf-custom-field-order-container li').length ) {
            $container.html($container.data('wpcf-message-drag'));
        } else {
            $container.html('');
        }
    }

    var initGroupFields = 1;
    function wpcf_edit_post_get_child_fields_box() {
        var currentGroups = [],
            currentFields = [],
            target = $('#custom_fields .wpcf-box');

        if ( 0 == target.length )
            return;

        // current groups
        $('#field_groups .js-wpcf-custom-fields-group:checked').each(function(){
            currentGroups.push( $( this ).data( 'wpcf-group-id' ) );
        });

        // current fields (get them from sortables to have the right order)
        $( '.js-wpcf-custom-field-order-container li[id^="wpcf-custom-field"]' ).each( function() {
            currentFields.push( $( this ).attr( 'id' ).replace( 'wpcf-custom-field-', '' ) );

        } );

        target.load(
            ajaxurl,
            {
                action: 'wpcf_edit_post_get_fields_box',
                _wpnonce: target.data('wpcf-nonce'),
                id: target.data('wpcf-id'),
                type: target.data('wpcf-type'),
                current_groups: currentGroups,
                current_fields: currentFields,
                init: initGroupFields
            },
            function (responseText, textStatus, XMLHttpRequest) {
                initGroupFields = 0;
                $('#custom_fields .inside .wpcf-custom-field-group-container').masonry({
                    itemSelector: '.js-wpcf-custom-field-group',
                    columnWidth: 250
                });
                $("#custom_fields .wpcf-custom-field-order ul").sortable();
                $('.js-wpcf-custom-field-group-container').on('change', 'input', function() {
                    var $key = $(this).data('wpcf-key');
                    if ( $(this).is(':checked')) {
                        // only append field to sortable if it does not already exists
                        if( !$( '#custom_fields .wpcf-custom-field-order ul' ).find( '#wpcf-custom-field-'+$key ).length ) {
                            $('#custom_fields .wpcf-custom-field-order ul').append(
                                '<li class="menu-item-handle ui-sortable-handle" id="wpcf-custom-field-'+$key+'"><input type="hidden" name="ct[custom_fields]['+$key+']" value="1">'+ $('label', $(this).parent()).html()+ '</li>');
                        }

                        // check all other inputs with the same name
                        $( '[data-wpcf-key=' + $(this).data( 'wpcf-key' ) ).each( function() {
                            $( this ).attr( 'checked', 'checked' );
                        })
                    } else {
                        $('#wpcf-custom-field-'+$key).remove();

                        // uncheck all other inputs with the same name
                        $( '[data-wpcf-key=' + $(this).data( 'wpcf-key' ) ).each( function() {
                            $( this ).removeAttr( 'checked' );
                        })
                    }
                    wpcf_edit_post_get_child_fields_box_message_helper();
                });
                wpcf_edit_post_get_child_fields_box_message_helper();
            }
        );
    }
    wpcf_edit_post_get_child_fields_box();
    $('#field_groups').on( 'change', '.js-wpcf-custom-fields-group', function(){
        wpcf_edit_post_get_child_fields_box();
        return false;
    });

});

