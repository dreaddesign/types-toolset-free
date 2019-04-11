/**
 * fields edit
 */
jQuery(document).ready(function($){

    var wpcfBody = $( 'body' );

    /**
     * Store all current used field slugs
     * @type {Array}
     */
    var allFieldSlugs = [];
    $.ajax({
            url: ajaxurl,
            method: "POST",
            dataType: 'json',
            data: {
                group_id: $( 'input[name="wpcf[group][id]"]' ).val(),
                action: 'wpcf_get_all_field_slugs_except_current_group',
                return: 'ajax-json'
            }
        })
        .done(function( slugs ){
            if( slugs.length ) {
                $.merge( allFieldSlugs, slugs );
            }
        });

    /**
     * function to update currently selected conditions
     * in the description of "Where to Include These Fields" box
     */
    function update_fields() {
        var msgAll = $( '.wpcf-fields-group-conditions-description' ),
            msgCondNone = $( '.js-wpcf-fields-group-conditions-none' ),
            msgCondSet = $( '.js-wpcf-fields-group-conditions-set' ),
            msgCondAll = $( '.js-wpcf-fields-group-conditions-condition' ),

            conditions = {
                'postTypes' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-post-types' ),
                    'inputsIDs' : 'wpcf-form-groups-support-post-type-',
                    'activeConditionsLabels' : []
                },

                'terms' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-terms' ),
                    'inputsIDs' : 'wpcf-form-groups-support-tax-',
                    'activeConditionsLabels' : []
                },

                'templates' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-templates' ),
                    'inputsIDs' : 'wpcf-form-groups-support-templates-',
                    'activeConditionsLabels' : []
                },

                'data-dependencies' : {
                    'description' : $( '.js-wpcf-fields-group-conditions-data-dependencies' ),
                    'activeConditionsLabels' : []
                },

                taxonomies : {
                    description: $( '.js-wpcf-fields-group-conditions-taxonomies' ),
                    inputsIDs: 'wpcf-form-groups-support-taxonomy-',
                    activeConditionsLabels: []
                }
            },
            conditionsCount = 0,
            uiDialog = $( '.wpcf-filter-dialog' );

        // reset
        msgAll.hide();
        msgCondAll.find( 'span' ).html( '' );

        // update hidden inputs if dialog is open
        if( uiDialog.length ) {
            // reset all hidden inputs
            $( '[id^=wpcf-form-groups-support-]' ).val( '' );
            $( '[id^=wpcf-form-groups-support-tax]' ).remove();

            $( 'input[type=checkbox]:checked', uiDialog ).each( function() {
                // taxonomies are the only not using a prefix ('tax' is inside name)
                if( $( this ).data( 'wpcf-prefix' ) == '' ) {
                    $( '<input/>' ).attr( {
                        type: 'hidden',
                        id: 'wpcf-form-groups-support-' + $( this ).attr( 'name' ),
                        name: 'wpcf[group][taxonomies][' + $( this ).attr( 'data-wpcf-taxonomy-slug' ) + '][' + $( this ).attr( 'data-wpcf-value' ) + ']',
                        'data-wpcf-label': $( this ).attr( 'data-wpcf-name' ),
                        value: $( this ).attr( 'data-wpcf-value' ),
                    } ).appendTo( '.wpcf-conditions-container' );
                // taxonomies on term fields
                } else if( $( this ).data( 'wpcf-prefix' ) == 'taxonomy-'  ) {
                    $( '<input/>' ).attr( {
                        type: 'hidden',
                        id: 'wpcf-form-groups-support-taxonomy-' + $( this ).attr( 'name' ),
                        name: 'wpcf[group][taxonomies][' + $( this ).attr( 'data-wpcf-value' ) + ']',
                        'data-wpcf-label': $( this ).attr( 'data-wpcf-name' ),
                        value: $( this ).attr( 'data-wpcf-value' ),
                        class: 'js-wpcf-filter-support-taxonomy wpcf-form-hidden form-hidden hidden',
                    } ).appendTo( '.wpcf-conditions-container' );
                } else {
                    var id = '#wpcf-form-groups-support-' + $( this ).data( 'wpcf-prefix' ) + $( this ).attr( 'name' );
                    var value = $( this ).data( 'wpcf-value' );
                    $( id ).val( value );
                }
            } );
        }

        // get all active conditions
        $.each( conditions, function( id, condition ) {
            if( id == 'data-dependencies' ) {
                $( '.js-wpcf-filter-container .js-wpcf-condition-preview li' ).each( function() {
                    conditionsCount++;
                    conditions[id]['activeConditionsLabels'].push( '<br />' + $( this ).html() );
                } );
            } else {
                var selector = 'input[id^=' + condition.inputsIDs + ']';
                $( selector ).filter( function() {
                    return this.value && this.value != '0';
                } ).each( function() {
                    conditionsCount++;
                    var label = $( this ).data( 'wpcf-label' );
                    conditions[id]['activeConditionsLabels'].push( label );
                })
            }
        });

        // show box description depending of conditions count
        if( conditionsCount > 0 ) {
            msgCondSet.show();
            $.each( conditions, function( id, condition ) {
                if( condition['activeConditionsLabels'].length ) {
                    condition['description'].show().find( 'span' ).html( condition['activeConditionsLabels'].join( ', ' ) );
                }
            } );
        } else {
            msgCondNone.show();
        }

        // show association option when there is more than one condition added
        if( conditionsCount > 1 ) {
            $( '#wpcf-fields-form-filters-association-form' ).show();
        } else {
            $( '#wpcf-fields-form-filters-association-form' ).hide();
        }

    }

    update_fields();

    /**
     * remove field link
     */
    $(document).on('click', '.js-wpcf-field-remove', function() {
        if ( confirm($(this).data('message-confirm')) ) {
            $(this).closest('.postbox').slideUp(function(){
                $(this).remove();
                if ( 1 > $('#post-body-content .js-wpcf-fields .postbox').length ) {
                    $( '.js-wpcf-fields-add-new-last, .js-wpcf-second-submit-container' ).addClass( 'hidden' );
                }
            });
        }
        return false;
    });
    /**
     * change field type
     */
    $(document).on('change', '.js-wpcf-fields-type', function(){
        $('.js-wpcf-fields-type-message').remove();
        $(this).parent().append('<div class="js-wpcf-fields-type-message updated settings-error notice"><p>'+$(this).data('message-after-change')+'</p></div>');
        $('tbody tr', $(this).closest('table')).each(function(){
            if ( !$(this).hasClass('js-wpcf-fields-typeproof') ) {
                $(this).hide();
            }
        });
    });
    /**
     * choose filter
     */
    $( document ).on( 'click', '.js-wpcf-filter-container .js-wpcf-filter-button-edit', function() {
        var thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="wpcf-filter-contant"><span class="spinner"></span>'+thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'wpcf-filter-dialog wpcf-ui-dialog',
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: thiz.data('wpcf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: thiz.data('wpcf-buttons-apply'),
                click: function() {

                    var currentOpenDialog = $( this ).closest( '.wpcf-filter-dialog' ).length ? $( this ).closest( '.wpcf-filter-dialog' ) : $( this ).closest( '.wpcf-conditions-dialog' ),
                        groupConditions,
                        fieldNonce,
                        fieldName,
                        fieldGroupId,
                        fieldMetaType,
                        extraMetaField = jQuery( '#data-dependant-meta', currentOpenDialog );

                    if( extraMetaField.length ) {
                        groupConditions = ( extraMetaField.data( 'wpcf-action' ) == 'wpcf_edit_field_condition_get' ) ? 0 : 1;
                        fieldName = extraMetaField.data( 'wpcf-id' );
                        fieldGroupId = extraMetaField.data( 'wpcf-group-id' );
                        fieldMetaType = extraMetaField.data( 'wpcf-meta-type' );
                        fieldNonce = extraMetaField.data( 'wpcf-buttons-apply-nonce' );
                    } else {
                        groupConditions = ( thiz.data( 'wpcf-action' ) == 'wpcf_edit_field_condition_get' ) ? 0 : 1;
                        fieldName = thiz.data( 'wpcf-id' );
                        fieldGroupId = thiz.data( 'wpcf-group-id' );
                        fieldMetaType = thiz.data( 'wpcf-meta-type' );
                        fieldNonce = thiz.data('wpcf-buttons-apply-nonce');
                    }
                    /**
                     * show selected values
                     */
                    //$('.js-wpcf-filter-ajax-response', thiz.closest('.js-wpcf-filter-container')).html(affected);

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_save',
                            _wpnonce: fieldNonce,
                            id: fieldName,
                            group_conditions: groupConditions,
                            group_id: fieldGroupId,
                            meta_type: fieldMetaType,
                            conditions: $( 'form', currentOpenDialog ).serialize()
                        }
                    })
                        .done(function(html){
                            var conditionsPreview, button;

                            if( groupConditions == 1 ) {
                                conditionsPreview = $( '.js-wpcf-filter-container .js-wpcf-condition-preview' );
                                button = $('.js-wpcf-filter-container .js-wpcf-condition-button-edit');
                            } else {
                                conditionsPreview = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-preview');
                                button = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-button-edit');
                            }

                            // updated field conditions
                            conditionsPreview.html( html );

                            // update button label
                            if( html == '' ) {
                                button.html( button.data( 'wpcf-label-set-conditions' ) );
                            } else {
                                button.html( button.data( 'wpcf-label-edit-condition' ) );
                            }

                            // close dialog
                            update_fields();
                            dialog.dialog( "close" );
                        });
                },
                class: 'button-primary'
            }, {
                text: thiz.data('wpcf-buttons-cancel'),
                click: function() {
                    $( this ).dialog( "close" );
                },
                class: 'wpcf-ui-dialog-cancel'
            }]
        });
        // load remote content
        var $current = [];
        var allFields = $( 'form.wpcf-fields-form input[name^=wpcf\\[group\\]][value!=""]' ).serialize();

        $(thiz.data('wpcf-field-to-clear-class'), thiz.closest('.inside')).each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });

        var current_page = thiz.data('wpcf-page');
        if( undefined == current_page ) {
            current_page = 'wpcf-edit';
        }

        dialog.load(
            ajaxurl,
            {
                method: 'post',
                action: 'wpcf_ajax_filter',
                _wpnonce: thiz.data('wpcf-nonce'),
                id: thiz.data('wpcf-id'),
                type: thiz.data('wpcf-type'),
                page: current_page,
                current: $current,
                all_fields: allFields
            },
            function (responseText, textStatus, XMLHttpRequest) {
                // tabs
                var menu = $( '.wpcf-tabs-menu' ).detach();

                menu.appendTo( ".wpcf-filter-dialog .ui-widget-header" );

                $(".wpcf-tabs-menu span").click(function(event) {
                    event.preventDefault();

                    $(this).parent().addClass("wpcf-tabs-menu-current");
                    $(this).parent().siblings().removeClass("wpcf-tabs-menu-current");
                    var tab = $(this).data("open-tab");
                    $(".wpcf-tabs > div").not(tab).css("display", "none");
                    $(tab).fadeIn();
                });

                wpcfAddPostboxToggles();
                $(dialog).on('click', 'a[data-wpcf-icon]', function() {
                    var $icon = $(this).data('wpcf-icon');
                    $('#wpcf-types-icon').val($icon);
                    classes = 'wpcf-types-menu-image dashicons-before dashicons-'+$icon;
                    $('div.wpcf-types-menu-image').removeClass().addClass(classes);
                    dialog.dialog( "close" );
                    return false;
                });
                /**
                 * bind search taxonomies
                 */
                $(dialog).on('keyup input cut paste', '.js-wpcf-taxonomy-search', function() {
                    var $parent = $(this).closest('.inside');
                    if ( '' == $(this).val() ) {
                        $('li', $parent).show();
                    } else {
                        var re = new RegExp($(this).val(), "i");
                        $('li input', $parent).each(function(){
                            if (
                                    false
                                    || $(this).data('wpcf-slug').match(re)
                                    || $(this).data('wpcf-name').match(re)
                               ) {
                                $(this).parent().show();
                            } else {
                                $(this).parent().hide();
                            }
                        });
                    }
                });

                /**
                 * Data Dependant
                 */
                $(dialog).on('click', '.js-wpcf-condition-button-add-row', function() {
                    var button = $( this );
                    button.attr( 'disabled', 'disabled' );

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_get_row',
                            _wpnonce: $( this ).data('wpcf-nonce'),
                            id: $(this).data('wpcf-id'),
                            group_id: $( this ).data( 'wpcf-group-id' ),
                            meta_type: $( this ).data('wpcf-meta-type')
                        }
                    })
                        .done(function(html){
                            button.removeAttr( 'disabled' );
                            $('.js-wpcf-fields-conditions', $(dialog)).append(html);

                            var receiveError = $('.js-wpcf-fields-conditions', $(dialog) ).find( '.js-wpcf-received-error' );

                            if( receiveError.length ) {
                                button.remove();
                            } else {
                                $( dialog ).on( 'click', '.js-wpcf-custom-field-remove', function() {
                                    return wpcf_conditional_remove_row( $( this ) );
                                } );
                                wpcf_setup_conditions();
                                $( dialog ).on( 'change', '.js-wpcf-cd-field', function() {
                                    wpcf_setup_conditions();
                                } );
                            }
                        });
                    return false;
                });
                $(dialog).on('click', '.js-wpcf-custom-field-remove', function() {
                    return wpcf_conditional_remove_row($(this));
                });
                /**
                 * bind to switch logic mode
                 */
                $(dialog).on('click', '.js-wpcf-condition-button-display-logic', function() {
                    var $container = $(this).closest('form');
                    if ( 'advance-logic' == $(this).data('wpcf-custom-logic') ) {
                        $('.js-wpcf-simple-logic', $container).show();
                        $('.js-wpcf-advance-logic', $container).hide();
                        $(this).data('wpcf-custom-logic', 'simple-logic');
                        $(this).html($(this).data('wpcf-content-advanced'));
                        $('.js-wpcf-condition-custom-use', $container).val(0);
                    } else {
                        $('.js-wpcf-simple-logic', $container).hide();
                        $('.js-wpcf-advance-logic', $container).show();
                        $(this).data('wpcf-custom-logic', 'advance-logic');
                        $(this).html($(this).data('wpcf-content-simple'));
                        wpcf_conditional_create_summary(this, $container);
                        $('.js-wpcf-condition-custom-use', $container).val(1);
                    }
                    return false;
                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * add new - choose field
     */
    $( document ).on( 'click', '.js-wpcf-fields-add-new', function() {
        var $thiz = $(this);
        var $current;
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;" class="wpcf-choose-field"><span class="spinner"></span>'+$thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('wpcf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window }
        });
        // load remote content
        var $current = [];
        $($thiz.data('wpcf-field-to-clear-class'), $thiz.closest('.inside')).each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });
        $('#post-body-content .postbox .js-wpcf-slugize').each(function(){
            if ( $(this).val() ) {
                $current.push($(this).val());
            }
        });

        // top or bottom "add new field" clicked
        var position = $thiz.hasClass( 'js-wpcf-fields-add-new-last' )
            ? 'bottom'
            : 'top';

        function add_field_to_fields_list( html ) {

            var newField;

            if( position == 'top' ) {
                $( '#post-body-content .js-wpcf-fields' ).prepend( html );
                newField = $( '#post-body-content .js-wpcf-fields .postbox' ).first();
            } else {
                $( '#post-body-content .js-wpcf-fields .js-wpcf-fields-add-new-last' ).before( html );
                newField = $( '#post-body-content .js-wpcf-fields .postbox' ).last();
            }

            $( 'html, body' ).animate( {
                scrollTop: newField.offset().top - 50
            }, 1000 );

            dialog.dialog( 'close' );

            wpcfBindAutoCreateSlugs();
            wpcfAddPostboxToggles();

            newField.typesFieldOptionsSortable();
            newField.typesMarkExistingField();

            // show bottom "Add new field" and "Save Group Fields" buttons
            $( '.js-wpcf-fields-add-new, .js-wpcf-second-submit-container' ).removeClass( 'hidden' );
            wpcf_setup_conditions();
        }

        // This can be wpcf-postmeta, wpcf-usermeta or wpcf-termmeta.
        var fieldKind = $thiz.data('wpcf-type');

        dialog.load(
            ajaxurl,
            {
                action: 'wpcf_edit_field_choose',
                _wpnonce: $thiz.data('wpcf-nonce'),
                id: $thiz.data('wpcf-id'),
                type: fieldKind,
                current: $current
            },
            function (responseText, textStatus, XMLHttpRequest) {
                var $fields = '';
                var $dialog =  $(this).closest('.ui-dialog-content')
                /**
                 * choose new field
                 */
                $(dialog).on('click', 'button.js-wpcf-field-button-insert', function() {
                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_insert',
                            _wpnonce: $('#wpcf-fields-add-nonce').val(),
                            type: $(this).data('wpcf-field-type'),
                            field_kind: fieldKind
                        }
                    })
                    .done(function(html){
                        add_field_to_fields_list( html );
                    });
                });
                /**
                 * choose from existed fields
                 */
                $(dialog).on('click', '.js-wpcf-switch-to-exists', function() {

                    var current_page = $thiz.data('wpcf-page');
                    if( undefined == current_page ) {
                        current_page = 'wpcf-edit';
                    }

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_select',
                            _wpnonce: $('#wpcf-fields-add-nonce').val(),
                            id: $thiz.data('wpcf-id'),
                            type: $thiz.data('wpcf-type'),
                            current: $current,
                            page: current_page,
                            a:'c'
                        }
                    })
                    .done(function(html){
                        $fields = $dialog.html();
                        $dialog.html(html);
                        $(dialog).on('click', '.js-wpcf-switch-to-new', function() {
                            $dialog.html($fields);
                            return false;
                        });
                        /**
                         * filter
                         */
                        $(dialog).on('keyup input cut paste', '.js-wpcf-fields-search', function() {
                            if ( '' == $(this).val() ) {
                                $('.js-wpcf-field-button-use-existed', dialog).show();
                            } else {
                                var re = new RegExp($(this).val(), "i");
                                $('.js-wpcf-field-button-use-existed', dialog).each(function(){
                                    if (
                                        false
                                        || $(this).data('wpcf-field-id').match(re)
                                        || $(this).data('wpcf-field-type').match(re)
                                        || $('span', $(this)).html().match(re)
                                    ) {
                                        $(this).show();
                                    } else {
                                        $(this).hide();
                                    }
                                });
                            }
                        });
                        /**
                         * choose exist field
                         */
                        $(dialog).on('click', 'button.js-wpcf-field-button-use-existed', function() {
                            $.ajax({
                                url: ajaxurl,
                                method: "POST",
                                data: {
                                    action: 'wpcf_edit_field_add_existed',
                                    id: $(this).data('wpcf-field-id'),
                                    type: $(this).data('wpcf-type'),
                                    _wpnonce: $('#wpcf-fields-add-nonce').val()
                                }
                            })
                            .done(function(html){
                                add_field_to_fields_list( html );
                            });
                        });
                    });

                });
            }
        );
        //prevent the browser to follow the link
        return false;
    });
    /**
     * update box fifle by field name
     */
    wpcfBody.on( 'keyup', '.wpcf-forms-set-legend', function(){
        var val = $(this).val();
        if ( val ) {
            val = val.replace(/</, '&lt;');
            val = val.replace(/>/, '&gt;');
            val = val.replace(/'/, '&#39;');
            val = val.replace(/"/, '&quot;');
        }
        $(this).parents('.postbox').find('.wpcf-legend-update').html(val);
    });

    // Check radio and select if same values
    // Check checkbox has a value to store
    $('.wpcf-fields-form').submit(function(){
        wpcfLoadingButton();
        var passed = true;
        var checkedArr = new Array();
        $('.wpcf-compare-unique-value-wrapper').each(function(index){
            var childID = $(this).attr('id');
            checkedArr[childID] = new Array();
            $(this).find('.wpcf-compare-unique-value').each(function(index, value){
                var parentID = $(this).parents('.wpcf-compare-unique-value-wrapper').first().attr('id');
                var currentValue = $(this).val();
                if (currentValue != ''
                    && $.inArray(currentValue, checkedArr[parentID]) > -1) {

                    var fieldContainer = $(this).parents( '.postbox' );

                    // open fields container if closed
                    if( fieldContainer.hasClass( 'closed' ) ) {
                        fieldContainer.find( '.hndle' ).trigger( 'click.postboxes' );
                    }

                    passed = false;

                    $('#'+parentID).children('.wpcf-form-error-unique-value').remove();

                    // make sure error msg is only applied ounce
                    if( ! $('#'+parentID).find( '.wpcf-form-error' ).length ) {
                        if( document.getElementById( parentID ).tagName == 'TBODY' ) {
                            $('#'+parentID).append('<tr><td colspan="5"><div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueValuesCheckText+'</div><td></tr>');
                        } else {
                            $('#'+parentID).append('<div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueValuesCheckText+'</div>');
                        }
                    }

                    $(this).parents('fieldset').children('.fieldset-wrapper').slideDown();
                    $(this).focus();
                }

                checkedArr[parentID].push(currentValue);
            });
        });
        if (passed == false) {
            // Bind message fade out
            wpcfBody.on( 'keyup', '.wpcf-compare-unique-value', function(){
                $(this).parents('.wpcf-compare-unique-value-wrapper').find('.wpcf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            wpcf_fields_form_submit_failed();
            return false;
        }
        // Check field names unique
        passed = true;
        checkedArr = new Array();
        $('.wpcf-forms-field-name').each(function(index){
            var currentValue = $(this).val().toLowerCase();
            if (currentValue != ''
                && $.inArray(currentValue, checkedArr) > -1) {
                passed = false;

                // apply error msg to all fields with the same name
                $( '.wpcf-forms-field-name' ).each( function() {
                    if( $( this ).val().toLowerCase() == currentValue ) {
                        if (!$(this).hasClass('wpcf-name-checked-error')) {
                            $(this).before('<div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueNamesCheckText+'</div>').addClass('wpcf-name-checked-error');
                        }
                    };

                    // scroll to last expanded postbox with this issue
                    if( $( this ).closest( '.postbox' ).find('.handlediv' ).attr('aria-expanded') == 'true' ) {
                        $( this ).parents( 'fieldset' ).children('.fieldset-wrapper').slideDown();
                        $( this ).first().focus();
                    }
                } );

            }
            checkedArr.push(currentValue);
        });
        if (passed == false) {
            // Bind message fade out
            wpcfBody.on( 'keyup', '.wpcf-forms-field-name', function(){
                $(this).removeClass('wpcf-name-checked-error').prev('.wpcf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            wpcf_fields_form_submit_failed();
            return false;
        }

        // Check field slugs unique
        passed = true;
        checkedArr = [];
        $.merge( checkedArr, allFieldSlugs );
        /**
         * first fill array with defined, but unused fields
         */
        $('#wpcf-form-groups-user-fields .wpcf-fields-add-ajax-link:visible').each(function(){
            checkedArr.push($(this).data('slug'));
        });
        $('.wpcf-forms-field-slug').each(function(index){

            // skip for "existing fields" if no change in input slug
            if( $( this ).data( 'types-existing-field' ) && $( this ).data( 'types-existing-field' ) == $( this ).val() )
                return true;

            var currentValue = $(this).val().toLowerCase();
            if (currentValue != ''
                && $.inArray(currentValue, checkedArr) > -1) {
                passed = false;

                // apply error msg to all fields with the same slug
                $( '.wpcf-forms-field-slug' ).each( function() {
                   if( $( this ).val() == currentValue ) {
                       if (!$(this).hasClass('wpcf-slug-checked-error')) {
                           $(this).before('<div class="wpcf-form-error-unique-value wpcf-form-error">'+wpcfFormUniqueSlugsCheckText+'</div>').addClass('wpcf-slug-checked-error');
                       }
                   };

                   // scroll to last expanded postbox with this issue
                   if( $( this ).closest( '.postbox' ).find('.handlediv' ).attr('aria-expanded') == 'true' ) {
                       $( this ).parents( 'fieldset' ).children('.fieldset-wrapper').slideDown();
                       $( this ).first().focus();
                   }
                } );
            }
            checkedArr.push(currentValue);
        });

        // Conditional check
        if (wpcfConditionalFormDateCheck() == false) {
            wpcf_fields_form_submit_failed();
            return false;
        }

        // check to make sure checkboxes have a value to save.
        $('[data-wpcf-type=checkbox],[data-wpcf-type=checkboxes]').each(function () {
            if (wpcf_checkbox_value_zero(this)) {
                passed = false;
            }
        });

        if (passed == false) {
            // Bind message fade out
            wpcfBody.on( 'keyup', '.wpcf-forms-field-slug', function(){
                $(this).removeClass('wpcf-slug-checked-error').prev('.wpcf-form-error-unique-value').fadeOut(function(){
                    $(this).remove();
                });
            });
            wpcf_fields_form_submit_failed();
            return false;
        }

        /**
         * modal advertising dialog is shown on this event
         */
        $( document ).trigger( 'js-wpcf-event-types-show-modal' );
    } );
});

/**
 * on form submit fail
 */
function wpcf_fields_form_submit_failed() {
    wpcfLoadingButtonStop();
    wpcf_highlight_first_error();
}

/**
 * scroll to first issue
 */
function wpcf_highlight_first_error() {
    var $ = jQuery,
        firstError = $( '.wpcf-form-error' ).first(),
        postBox = firstError.closest( '.postbox' );


    if( postBox.hasClass( 'closed' ) ) {
        postBox.removeClass( 'closed' );
        postBox.find( '.handlediv' ).attr( 'aria-expanded', 'true' );
    }

    firstError.next( 'input' ).focus();
}

/**
 * remove row
 */
function wpcf_conditional_remove_row(element)
{
    element.closest('tr').remove();
    wpcf_setup_conditions();
    return false;
}
/**
 * Create advance logic
 */
function wpcf_conditional_create_summary(button, parent)
{
    if ( jQuery('.js-wpcf-advance-logic textarea', parent).val() ) {
        return;
    }
    var condition = '';
    var skip = true;
    parent = jQuery(button).closest('form');
    jQuery('.wpcf-cd-entry', parent).each(function(){
        if (!skip) {
            condition += jQuery('.js-wpcf-simple-logic', parent).find('input[type=radio]:checked').val() + ' ';
        }
        skip = false;

        var field = jQuery(this).find('.js-wpcf-cd-field :selected');

        condition += '($(' + jQuery(this).find('.js-wpcf-cd-field').val() + ')';

        // We need to translate from currently supported "simple" to "advanced" syntax. Ironically, the advanced one
        // currently supports only a subset of comparison operators.
        //
        // While we're at it, we translate all operators to their "text-only" equivalents because that's what they're
        // going to be sanitized into anyway.
        var comparisonOperator = jQuery(this).find('.js-wpcf-cd-operation').val();
        switch(comparisonOperator) {
            case '=':
            case '===':
                comparisonOperator = 'eq';
                break;
            case '>':
                comparisonOperator = 'gt';
                break;
            case '>=':
                comparisonOperator = 'gte';
                break;
            case '<':
                comparisonOperator = 'lt';
                break;
            case '<=':
                comparisonOperator = 'lte';
                break;
            case '<>':
            case '!==':
                comparisonOperator = 'ne';
                break;
        }

        condition += ' ' + comparisonOperator;
        // Date
        if (field.hasClass('wpcf-conditional-select-date')) {
            var date = jQuery(this).find('.wpcf-custom-field-date');
            var month = date.children(':first');
            var mm = month.val();
            var jj = month.next().val();
            var aa = month.next().next().val();
            condition += ' DATE(' + jj + ',' + mm + ',' + aa + ')) ';
        } else {
            condition += ' ' + jQuery(this).find('.js-wpcf-cd-value').val() + ') ';
        }
    });
    jQuery('.js-wpcf-advance-logic textarea', parent).val(condition);
}

/**
 * check condition methods
 */
function wpcf_setup_conditions()
{
    /**
     * move button "Add another condition" to mid if there is no condition
     */
    var dialog = jQuery( '.wpcf-filter-dialog' ).length ? jQuery( '.wpcf-filter-dialog' ) : jQuery( '.wpcf-conditions-dialog' ),
        btnAddCondition = jQuery('.js-wpcf-condition-button-add-row', dialog );

    if( 0 == jQuery('.js-wpcf-fields-conditions tr', dialog ).length ) {
        btnAddCondition.html( btnAddCondition.data( 'wpcf-label-add-condition' ) );
        btnAddCondition.addClass( 'wpcf-block-center' ).removeClass( 'alignright' );
    } else {
        btnAddCondition.html( btnAddCondition.data( 'wpcf-label-add-another-condition' ) );
        btnAddCondition.addClass( 'alignright' ).removeClass( 'wpcf-block-center' );
    }

    /**
     * checked condition method
     */
    if ( 1 < jQuery('.js-wpcf-fields-conditions tr', dialog ).length ) {
        jQuery('.wpcf-cd-relation.simple-logic').show();
    } else {
        jQuery('.wpcf-cd-relation.simple-logic').hide();
    }
    /**
     * bind select
     */
    jQuery('.js-wpcf-cd-field').on('change', function() {
        if ( jQuery(this).val() ) {
            jQuery('.js-wpcf-cd-operation, .js-wpcf-cd-value', jQuery(this).closest('tr')).removeAttr('disabled');
        } else {
            jQuery('.js-wpcf-cd-operation, .js-wpcf-cd-value', jQuery(this).closest('tr')).attr('disabled', 'disabled');
        }
    });
}

function wpcfAddPostboxToggles()
{
    jQuery('.postbox .hndle, .postbox .handlediv').unbind('click.postboxes');
    postboxes.add_postbox_toggles();
}

/**
 * fixes for dialogs
 */
( function( $ ) {
    // on dialogopen
    $( document ).on( 'dialogopen', '.ui-dialog', function( e, ui ) {
        // normalize primary buttons
        $( 'button.button-primary, button.wpcf-ui-dialog-cancel' )
            .blur()
            .addClass( 'button' )
            .removeClass( 'ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only' );
    } );

    // resize
    var resizeTimeout;
    $( window ).on( 'resize scroll', function() {
        clearTimeout( resizeTimeout );
        resizeTimeout = setTimeout( dialogResize, 200 );
    } );

    function dialogResize() {
        $( '.ui-dialog' ).each( function() {
            $( this ).css( {
                'maxWidth': '100%',
                'top': $( window ).scrollTop() + 50 + 'px',
                'left': ( $( 'body' ).innerWidth() - $( this ).outerWidth() ) / 2 + 'px'
            } );
        } );
    }


    /**
     * choose condition
     */
    $( document ).on( 'click', '.js-wpcf-condition-button-edit', function() {
        var $thiz = $(this);
        // show a spinner or something via css
        var dialog = $('<div style="display:none;height:450px;"><span class="spinner"></span>'+$thiz.data('wpcf-message-loading')+'</div>').appendTo('body');
        // open the dialog
        dialog.dialog({
            // add a close listener to prevent adding multiple divs to the document
            close: function(event, ui) {
                // remove div with all data and events
                dialog.remove();
            },
            dialogClass: 'wpcf-conditions-dialog wpcf-ui-dialog',
            closeText: false,
            modal: true,
            minWidth: 810,
            maxHeight: .9*$(window).height(),
            title: $thiz.data('wpcf-dialog-title'),
            position: { my: "center top+50", at: "center top", of: window },
            buttons: [{
                text: $thiz.data('wpcf-buttons-apply'),
                click: function() {
                    var groupConditions = ( $thiz.data( 'wpcf-action' ) == 'wpcf_edit_field_condition_get' ) ? 0 : 1;

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_save',
                            _wpnonce: $thiz.data('wpcf-buttons-apply-nonce'),
                            id: $thiz.data('wpcf-id'),
                            group_conditions: groupConditions,
                            group_id: $thiz.data( 'wpcf-group-id' ),
                            meta_type: $thiz.data('wpcf-meta-type'),
                            conditions: $('form', $(this).closest('.wpcf-conditions-dialog')).serialize()
                        }
                    })
                        .done(function(html){

                            var conditionsPreview, button;

                            if( groupConditions == 1 ) {
                                conditionsPreview = $( '.js-wpcf-filter-container .js-wpcf-condition-preview' );
                                button = $('.js-wpcf-filter-container .js-wpcf-condition-button-edit');
                            } else {
                                conditionsPreview = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-preview');
                                button = $('#types-custom-field-'+$thiz.data('wpcf-id')+' .js-wpcf-condition-button-edit');
                            }

                            // updated field conditions
                            conditionsPreview.html( html );

                            // update button label
                            if( html == '' ) {
                                button.html( button.data( 'wpcf-label-set-conditions' ) );
                            } else {
                                button.html( button.data( 'wpcf-label-edit-condition' ) );
                            }

                            // close dialog
                            dialog.dialog( "close" );
                        });
                    return false;
                },
                class: 'button-primary'
            }, {
                text: $thiz.data('wpcf-buttons-cancel'),
                click: function() {
                    /**
                     * close dialog
                     */
                    $( this ).dialog( "close" );
                },
                class: 'wpcf-ui-dialog-cancel'
            }]
        });
        /**
         * load dialog content
         */
        dialog.load(
            ajaxurl,
            {
                action: $thiz.data('wpcf-action'),
                _wpnonce: $thiz.data('wpcf-nonce'),
                id: $thiz.data('wpcf-id'),
                group: $thiz.data('wpcf-group'),
                group_id: $thiz.data('wpcf-group-id'),
            },
            function (responseText, textStatus, XMLHttpRequest) {
                $(dialog).on('click', '.js-wpcf-condition-button-add-row', function() {
                    var button = $( this );
                    button.attr( 'disabled', 'disabled' );

                    $.ajax({
                        url: ajaxurl,
                        method: "POST",
                        data: {
                            action: 'wpcf_edit_field_condition_get_row',
                            _wpnonce: $(this).data('wpcf-nonce'),
                            id: $(this).data('wpcf-id'),
                            group_id: $( this ).data( 'wpcf-group-id' ),
                            meta_type: $(this).data('wpcf-meta-type')
                        }
                    })
                        .done(function(html){
                            button.removeAttr( 'disabled' );
                            $('.js-wpcf-fields-conditions', $(dialog)).append(html);

                            var receiveError = $('.js-wpcf-fields-conditions', $(dialog) ).find( '.js-wpcf-received-error' );

                            if( receiveError.length ) {
                                button.remove();
                            } else {
                                $(dialog).on('click', '.js-wpcf-custom-field-remove', function() {
                                    return wpcf_conditional_remove_row($(this));
                                });
                                wpcf_setup_conditions();
                                $(dialog).on('change', '.js-wpcf-cd-field', function() {
                                    wpcf_setup_conditions();
                                });
                            }

                        });
                    return false;
                });
                $(dialog).on('click', '.js-wpcf-custom-field-remove', function() {
                    return wpcf_conditional_remove_row($(this));
                });
                /**
                 * bind to switch logic mode
                 */
                $(dialog).on('click', '.js-wpcf-condition-button-display-logic', function() {
                    var $container = $(this).closest('form');
                    if ( 'advance-logic' == $(this).data('wpcf-custom-logic') ) {
                        $('.js-wpcf-simple-logic', $container).show();
                        $('.js-wpcf-advance-logic', $container).hide();
                        $(this).data('wpcf-custom-logic', 'simple-logic');
                        $(this).html($(this).data('wpcf-content-advanced'));
                        $('.js-wpcf-condition-custom-use', $container).val(0);
                    } else {
                        $('.js-wpcf-simple-logic', $container).hide();
                        $('.js-wpcf-advance-logic', $container).show();
                        $(this).data('wpcf-custom-logic', 'advance-logic');
                        $(this).html($(this).data('wpcf-content-simple'));
                        wpcf_conditional_create_summary(this, $container);
                        $('.js-wpcf-condition-custom-use', $container).val(1);
                    }
                    return false;
                });
            }
        );
    });
} )( jQuery );
