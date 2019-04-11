/**
 *
 *
 * Relationship JS for post edit screen.
 */

/*
 * Child tables on post edit screen handling.
 * 
 * @type tChildTable._L10.Anonym$0
 */
var tChildTable = (function($) {

    function init(selector) {
        // Init hierarchical taxonomies
        tCatBox.init();
        // Bind wpList event
        $('.js-types-child-table .categorychecklist')
                .bind('wpListAddEnd', taxAdjust);
        // Init non-hierarchical taxonomies
        tTagBox.init(selector);
        /**
         * bind to children pagination buttons
         */
        $('.wpcf-pr-pagination-link').on('click', function() {
            param_pagination_name = $(this).data('pagination-name');
            if ( param_pagination_name ) {
                number_of_posts = $('select[name="'+param_pagination_name+'"]').val();
                re = new RegExp(param_pagination_name+'=\\d+');
                $(this).attr(
                    'href',
                    $(this).attr('href').replace(re, param_pagination_name+'='+number_of_posts)
                );
            }
            return true;
        });
    }

    function taxAdjust() {
        var list = $(this);
        list.find('input[name^="tax_input["], input[name^="post_category[]"]').each(function() {
            $(this).attr('name', list.attr('data-types'));
        });
    }

    return {
        init: init,
        reset: init
    }
})(jQuery, undefined);

/*
 * Hierarchical taxonomies form handling on post edit screen.
 */
(function($) {

    tCatBox = {
        init: function() {
            $('.js-types-child-categorydiv').each(function() {
                var this_id = $(this).attr('id'), catAddBefore, catAddAfter, taxonomy, settingName, typesID;
                taxonomy = $(this).data('types-reltax');
                typesID = $(this).attr('id');

                // Ajax Cat
                $('#new' + typesID).one('focus', function() {
                    $(this).val('').removeClass('form-input-tip')
                });

                $('#new' + typesID).keypress(function(event) {
                    if (13 === event.keyCode) {
                        event.preventDefault();
                        $('#' + typesID + '-add-submit').click();
                    }
                });
                $('#' + typesID + '-add-submit').click(function() {
                    $('#new' + typesID).focus();
                });

                catAddBefore = function(s) {
                    if (!$('#new' + typesID).val())
                        return false;
                    s.data += '&' + $(':checked', '#' + typesID + 'checklist').serialize();
                    $('#' + typesID + '-add-submit').prop('disabled', true);
                    return s;
                };

                catAddAfter = function(r, s) {
                    var sup, drop = $('#new' + typesID + '_parent');

                    $('#' + typesID + '-add-submit').prop('disabled', false);
                    if ('undefined' != s.parsed.responses[0] && (sup = s.parsed.responses[0].supplemental.newcat_parent)) {
                        drop.before(sup);
                        drop.remove();
                    }
                };

                $('#' + typesID + 'checklist').wpList({
                    what: 'types_reltax_add',
                    alt: '',
                    response: typesID + '-ajax-response',
                    addBefore: catAddBefore,
                    addAfter: catAddAfter
                });

                $('#' + typesID + '-add-toggle').on('click', function() {
                    $('#' + typesID + '-adder').toggleClass('wp-hidden-children');
                    $('a[href="#' + typesID + '-all"]', '#' + typesID + '-tabs').click();
                    $('#new' + typesID).focus();
                    return false;
                });

                $('#' + typesID + 'checklist, #' + typesID + 'checklist-pop').on('click', 'li.popular-category > label input[type="checkbox"]', function() {
                    var t = $(this), c = t.is(':checked'), id = t.val();
                    if (id && t.parents('#taxonomy-' + typesID).length)
                        $('#in-' + typesID + '-' + id + ', #in-popular-' + typesID + '-' + id).prop('checked', c);
                });

            });
        }
    }
}(jQuery));

/*
 * Non-hierarchical taxonomies form handling on post edit screen.
 */
(function($) {

    tTagBox = {
        clean: function(tags) {
            var comma = wpcfGetCommaSign();
            if (',' !== comma)
                tags = tags.replace(new RegExp(comma, 'g'), ',');
            tags = tags.replace(/\s*,\s*/g, ',').replace(/,+/g, ',').replace(/[,\s]+$/, '').replace(/^[,\s]+/, '');
            if (',' !== comma)
                tags = tags.replace(/,/g, comma);
            return tags;
        },
        parseTags: function(el) {
            var id = el.id, num = id.split('-check-num-')[1], taxbox = $(el).closest('.js-types-child-tagsdiv'),
                    thetags = taxbox.find('.the-tags'), comma = wpcfGetCommaSign(),
                    current_tags = thetags.val().split(comma), new_tags = [];

            delete current_tags[num];

            $.each(current_tags, function(key, val) {
                val = $.trim(val);
                if (val) {
                    new_tags.push(val);
                }
            });

            thetags.val(this.clean(new_tags.join(comma)));

            this.quickClicks(taxbox);
            return false;
        },
        quickClicks: function(el) {
            var thetags = $('.the-tags', el),
                    tagchecklist = $('.tagchecklist', el),
                    id = $(el).attr('id'),
                    current_tags, disabled,
                    comma = wpcfGetCommaSign();

            if (!thetags.length) {
                return;
            }

            disabled = thetags.prop('disabled');

            current_tags = thetags.val().split(comma);
            tagchecklist.empty();

            $.each(current_tags, function(key, val) {
                var span, xbutton;

                val = $.trim(val);

                if (!val) {
                    return;
                }

                // Create a new span, and ensure the text is properly escaped.
                span = $('<span />').text(val);

                // If tags editing isn't disabled, create the X button.
                if (!disabled) {
                    xbutton = $('<a id="' + id + '-check-num-' + key + '" class="ntdelbutton">X</a>');
                    xbutton.click(function() {
                        tTagBox.parseTags(this);
                    });
                    span.prepend('&nbsp;').prepend(xbutton);
                }

                // Append the span to the tag list.
                tagchecklist.append(span);
            });
        },
        flushTags: function(el, a, f) {
            a = a || false;
            var tags = $('.the-tags', el),
                    newtag = $('input.js-types-newtag', el),
                    comma = wpcfGetCommaSign(),
                    newtags, text;

            text = a ? $(a).text() : newtag.val();
            tagsval = tags.val();
            newtags = tagsval ? tagsval + comma + text : text;

            newtags = this.clean(newtags);
            newtags = array_unique_noempty(newtags.split(comma)).join(comma);
            tags.val(newtags);
            this.quickClicks(el);

            if (!a) {
                newtag.val('');
            }

            if ('undefined' == typeof(f)) {
                newtag.focus();
            }

            return false;
        },
        get: function(id) {
            var tax = $('#' + id).data('types-tax');

            $.post(ajaxurl, {'action': 'get-tagcloud', 'tax': tax}, function(r, stat) {
                if (0 == r || 'success' != stat) {
                    r = wpAjax.broken;
                }

                r = $('<p id="tagcloud-' + id + '" class="the-tagcloud">' + r + '</p>');
                $('a', r).click(function() {
                    tTagBox.flushTags($(this).closest('td').children('.js-types-child-tagsdiv'), this);
                    return false;
                });

                $('#' + id).after(r);
            });
        },
        init: function(selector) {

            var t = this, ajaxtag = $('.ajaxtag', selector);

            $('.js-types-child-tagsdiv', selector).each(function() {
                tTagBox.quickClicks(this);
            });

            $('input.js-types-addtag', ajaxtag).click(function() {
                t.flushTags($(this).closest('.js-types-child-tagsdiv'));
            });

            $('div.taghint', ajaxtag).click(function() {
                $(this).css('visibility', 'hidden').parent().siblings('.js-types-newtag').focus();
            });

            $('input.js-types-newtag', ajaxtag).blur(function() {
                if (this.value == '')
                    $(this).parent().siblings('.taghint').css('visibility', '');
            }).focus(function() {
                $(this).parent().siblings('.taghint').css('visibility', 'hidden');
            }).keyup(function(e) {
                if (13 == e.which) {
                    tTagBox.flushTags($(this).closest('.js-types-child-tagsdiv'));
                    return false;
                }
            }).keypress(function(e) {
                if (13 == e.which) {
                    e.preventDefault();
                    return false;
                }
            }).each(function() {
                var tax = $(this).data('types-tax'),
                comma = wpcfGetCommaSign();
                $(this).suggest(ajaxurl + '?action=ajax-tag-search&tax=' + tax, {delay: 500, minchars: 2, multiple: true, multipleSep: comma + ' '});
            });

            // save tags on post save/publish
            $('#post').submit(function() {
                $('.js-types-child-tagsdiv', selector).each(function() {
                    tTagBox.flushTags(this, false, 1);
                });
            });

            // tag cloud
            $('a.js-types-child-tagcloud-link', selector).click(function() {
                tTagBox.get($(this).attr('id'));
                $(this).unbind().click(function() {
                    $(this).siblings('.the-tagcloud').toggle();
                    return false;
                });
                return false;
            });
        }
    };
}(jQuery));

jQuery(document).ready(function($) {
    tChildTable.init();
});

jQuery(document).ready(function($) {
    var frame_relationship = [];
    window.wpcf_pr_edited = false;
    // Mark as edited field
    $('#wpcf-post-relationship table').on('click', ':input', function () {
        window.wpcf_pr_edited = true;
        $(this).parent().addClass('wpcf-pr-edited');
    });

    /*
     * Parent form
     */
    jQuery('.wpcf-pr-has-apply').click(function () {
        var $thiz = jQuery(this);
        jQuery(this).parent().slideUp().parent().parent().find('.wpcf-pr-edit').fadeIn();
        var txt = new Array();
        jQuery(this).parent().find('input:checked').each(function () {
            txt.push(jQuery(this).next().html());
        });
        if (txt.length < 1) {
            var wpcf_pr_has_update = $thiz.data('text-empty');
        } else {
            var txt_update = txt.join(', ');
            var wpcf_pr_has_update = $thiz.data('text-has').replace("%s", txt_update);
        }
        jQuery(this).parent().parent().parent().find('.wpcf-pr-has-summary').html(wpcf_pr_has_update);
    });
    jQuery('.wpcf-pr-belongs-apply').click(function () {
        var $thiz = jQuery(this);
        jQuery(this).parent().slideUp().parent().parent().find('.wpcf-pr-edit').fadeIn();
        var txt = new Array();
        jQuery(this).parent().find('input:checked').each(function () {
            txt.push(jQuery(this).next().html());
        });
        if (txt.length < 1) {
            var wpcf_pr_belongs_update = $thiz.data('text-empty');
        } else {
            var txt_update = txt.join(', ');
            var wpcf_pr_belongs_update = $thiz.data('text-has').replace("%s", txt_update);
        }
        jQuery(this).parent().parent().parent().find('.wpcf-pr-belongs-summary').html(wpcf_pr_belongs_update);
        return false;
    });
    jQuery('.wpcf-pr-has-cancel').click(function () {
        jQuery(this).parent().find('.checkbox').removeAttr('checked');
        for (var checkbox in window.wpcf_pr_has_snapshot) {
            jQuery('#' + window.wpcf_pr_has_snapshot[checkbox]).attr('checked', 'checked');
        }
        jQuery(this).parent().slideUp().parent().parent().find('.wpcf-pr-edit').fadeIn();
    });
    jQuery('.wpcf-pr-belongs-cancel').click(function () {
        jQuery(this).parent().find('.checkbox').removeAttr('checked');
        for (var checkbox in window.wpcf_pr_belongs_snapshot) {
            jQuery('#' + window.wpcf_pr_belongs_snapshot[checkbox]).attr('checked', 'checked');
        }
        jQuery(this).parent().slideUp().parent().parent().find('.wpcf-pr-edit').fadeIn();
    });
    jQuery('.wpcf-pr-edit').click(function () {
        window.wpcf_pr_has_snapshot = new Array();
        window.wpcf_pr_belongs_snapshot = new Array();
        var this_id = jQuery(this).attr('id');
        if (this_id == 'wpcf-pr-has-edit') {
            jQuery(this).next().find('.checkbox:checked').each(function () {
                window.wpcf_pr_has_snapshot.push(jQuery(this).attr('id'));
            });
        } else {
            jQuery(this).next().find('input:checked').each(function () {
                window.wpcf_pr_belongs_snapshot.push(jQuery(this).attr('id'));
            });
        }
        jQuery(this).fadeOut().next().slideDown();
    });

    /**
     * POST EDIT SCREEN
     */
    $('#wpcf-post-relationship').on('click', '.js-types-add-child', function () {
        if ($(this).hasClass('disabled'))
            return false;

        wpcfInitValueOfSelect2DoneClear();
        var $button = $(this), $table = $button.parents('.js-types-relationship-child-posts').find('table');

        typesRelationControlsAjaxStart();

        $.ajax({
            url: $button.attr('href'),
            type: 'get',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $button.after('<div style="margin-top:20px;"></div>').next().addClass('wpcf-ajax-loading-small');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        $('tbody', $table).prepend(data.output);
                        wpcfRelationshipInit('', 'add');
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire($('tbody tr', $table).first());
                        }
                    }
                    if (typeof data.conditionals != 'undefined' && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                    if ('undefined' != typeof data.child_id) {
                        $('#types-child-row-' + data.child_id).on('click', '.js-wpt-file-upload', function (event) {
                            wptFile.bindOpen($(this), false);
                        });
                    }
                }
                $button.next().fadeOut(function () {
                    $(this).remove();
                });
                if ('undefined' != typeof wptFile) {
                    wptFile.init();
                }
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var data_for_events = {
                    table: $table
                };

                $(document).trigger('js_event_wpcf_types_relationship_child_added', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            },
            complete: function () {
                typesRelationControlsAjaxComplete();
            }
        });
        return false;
    });
    jQuery( 'body' ).on( 'click', '.wpcf-pr-delete-ajax', function () {
        if ($(this).hasClass('disabled'))
            return false;

        wpcfInitValueOfSelect2DoneClear();
        var $button = $(this), $table = $button.parents('.js-types-relationship-child-posts').find('table');
        var answer = confirm(wpcf_pr_del_warning);
        if (answer == false) {
            return false;
        }
        var object = jQuery(this);

        typesRelationControlsAjaxStart();

        jQuery.ajax({
            url: jQuery(this).attr('href'),
            type: 'get',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                object.after('<div style="margin-top:20px;"></div>').next()
                    .addClass('wpcf-ajax-loading-small');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        object.parent().parent().fadeOut(function () {
                            jQuery(this).remove();
                            wpcfRelationshipInit('', 'delete');
                        });
                    }
                }
                object.next().fadeOut(function () {
                    jQuery(this).remove();
                });
                /**
                 * reload
                 */
                selectedIndex = $('#wpcf-post-relationship .wpcf-pr-pagination-select').prop('selectedIndex');
                if ($('tbody tr', $table).length < 2) {
                    if (selectedIndex) {
                        selectedIndex--;
                        $('#wpcf-post-relationship .wpcf-pr-pagination-select').prop('selectedIndex', selectedIndex);
                    }
                }
                $('#wpcf-post-relationship .wpcf-pr-pagination-select').trigger('change');
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var data_for_events = {
                    table: $table
                };

                $(document).trigger('js_event_wpcf_types_relationship_child_deleted', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            },
            complete: function () {
                typesRelationControlsAjaxComplete();
            }
        });
        return false;
    });
    jQuery( 'body' ).on( 'click', '.wpcf-pr-update-belongs', function () {
        var object = jQuery(this);
        jQuery.ajax({
            url: jQuery(this).attr('href'),
            type: 'post',
            dataType: 'json',
            data: jQuery(this).attr('href') + '&' + object.prev().serialize(),
            cache: false,
            beforeSend: function () {
                object.after('<div style="margin-top:20px;"></div>').next()
                    .addClass('wpcf-ajax-loading-small');
            },
            success: function (data) {
                object.next().fadeOut(2000, function () {
                    jQuery(this).remove();
                });
            }
        });
        return false;
    });
    $('#wpcf-post-relationship').on('click', '.wpcf-pr-pagination-link', function () {
        if (wpcfPrIsEdited()) {
            var answer = confirm(wpcf_pr_pagination_warning);
            if (answer == false) {
                return false;
            } else {
                window.wpcf_pr_edited = false;
            }
        }
        var $button = $(this), $update = $button.parents('.js-types-relationship-child-posts');
        $.ajax({
            url: $button.attr('href'),
            type: 'get',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $button.after('<div style="margin-top:20px;"></div>').next()
                    .addClass('wpcf-ajax-loading-small');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        $update.html(data.output);
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire($update);
                        }
                    }
                    if (typeof data.conditionals != 'undefined'
                        && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                }
                $button.next().fadeOut(function () {
                    $(this).remove();
                });
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var $table = $update.find('table'),
                    data_for_events = {
                        table: $table
                    };

                $(document).trigger('js_event_wpcf_types_relationship_children_paged', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            }
        });
        return false;
    });
    $('#wpcf-post-relationship').on('change', '.wpcf-pr-pagination-select', function () {
        if (wpcfPrIsEdited()) {
            var answer = confirm(wpcf_pr_pagination_warning);
            if (answer == false) {
                return false;
            } else {
                window.wpcf_pr_edited = false;
            }
        }
        var $button = $(this), $update = $button.parents('.js-types-relationship-child-posts');
        $.ajax({
            url: $button.val(),
            type: 'get',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $button.after('<div style="margin-top:20px;"></div>').next().addClass('wpcf-ajax-loading-small');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        $update.html(data.output);
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire($update);
                        }
                    }
                    if (typeof data.conditionals != 'undefined' && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                }
                $button.next().fadeOut(function () {
                    $(this).remove();
                });
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var $table = $update.find('table'),
                    data_for_events = {
                        table: $table
                    };

                $(document).trigger('js_event_wpcf_types_relationship_children_paged', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            }
        });
        return false;
    });
    $('#wpcf-post-relationship').on('click', '.wpcf-sortable a', function () {
        if (wpcfPrIsEdited()) {
            var answer = confirm(wpcf_pr_pagination_warning);
            if (answer == false) {
                return false;
            } else {
                window.wpcf_pr_edited = false;
            }
        }
        var $button = $(this), $update = $button.parents('.js-types-relationship-child-posts');
        $.ajax({
            url: $button.attr('href'),
            type: 'get',
            dataType: 'json',
            cache: false,
            beforeSend: function () {
                $button.after('<div style="margin-top:20px;"></div>').next().addClass('wpcf-ajax-loading-small');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        $update.html(data.output);
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire($update);
                        }
                    }
                    if (typeof data.conditionals != 'undefined'
                        && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                }
                $button.next().fadeOut(function () {
                    $(this).remove();
                });
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var $table = $update.find('table'),
                    data_for_events = {
                        table: $table
                    };

                $(document).trigger('js_event_wpcf_types_relationship_children_sorted', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            }
        });
        return false;
    });
    $('#wpcf-post-relationship').on('click', '.wpcf-pr-save-ajax', function () {
        if ($(this).hasClass('disabled'))
            return false;

        wpcfInitValueOfSelect2DoneClear();

        var $button = $(this), $row = $button.parents('tr'), rowId = $row.attr('id'), valid = true, $table = $row.closest('.js-types-child-table');
        if (typeof wptValidation == 'undefined') {
            $('.js-types-validate', $row).each(function () {
                if ($('#post').validate().element($(this)) == false) {
                    if (typeof typesValidation == 'undefined'
                        || typesValidation.conditionalIsHidden($(this)) == false) {
                        valid = false;
                    }
                }
            });
        } else {
            $('.js-wpt-validate', $row).each(function () {
                if ($('#post').validate().element($(this)) == false) {
                    if (typeof wptValidation == 'undefined'
                        || !wptValidation.isIgnored($(this))) {
                        valid = false;
                    }
                }
            });
        }
        if (valid == false) {
            return false;
        }
        $button.parents('.js-types-relationship-child-posts')
            .find('.wpcf-pr-edited').removeClass('wpcf-pr-edited');
        var height = $row.height(), rand = Math.round(Math.random() * 10000);
        window.wpcf_pr_edited = false;

        typesRelationControlsAjaxStart();

        $.ajax({
            url: $button.attr('href'),
            type: 'post',
            dataType: 'json',
            data: $row.find(':input').serialize(),
            cache: false,
            beforeSend: function () {
                $row.after('<tr id="wpcf-pr-update-' + rand + '"><td style="height: ' + height + 'px;"><div style="margin-top:20px;" class="wpcf-ajax-loading-small"></div></td></tr>').hide();
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        var $updatedRowContent = $(data.output);
                        bindRowEventHandlers(false);
                        $row.replaceWith($updatedRowContent);
                        bindRowEventHandlers(true);
                        wpcfDisableControls();
                        $('#wpcf-pr-update-' + rand + '').remove();
                        wpcfRelationshipInit('', 'save');
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire('#' + rowId);
                        }
                        if ('undefined' != typeof wptFile) {
                            wptFile.init();
                        }
                    }
                    if (typeof data.conditionals != 'undefined' && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                    /**
                     * rebind images
                     */
                    if ('function' == typeof bind_colorbox_to_thumbnail_preview) {
                        bind_colorbox_to_thumbnail_preview();
                    }
                    /**
                     * show errors
                     */
                    $('#wpcf-post-relationship div.message').detach();
                    if ('undefined' != typeof data.errors && 0 < data.errors.length) {
                        $('#wpcf-post-relationship h3.hndle').after(data.errors);
                    }
                    /**
                     * select2
                     */
                    wpcfInitValueOfSelect2DoneClear();
                    wpcfBindSelect2($);

                    var data_for_events = {
                        table: $table
                    };

                    $(document).trigger('js_event_wpcf_types_relationship_child_saved', [data_for_events]);
                    $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
                }
            },
            complete: function () {
                typesRelationControlsAjaxComplete();
            }
        });
        return false;
    });
    $('#wpcf-post-relationship').on('click', '.wpcf-pr-save-all-link', function () {
        var $button = jQuery(this);
        if ($button.attr('disabled') == 'disabled') {
            return false;
        }
        $button.attr('disabled', 'disabled');
        var $update = $button.parents('.js-types-relationship-child-posts'), updateId = $update.attr('id'), $table = $('table', $update), valid = true;
        if (typeof wptValidation == 'undefined') {
            $('.js-types-validate', $table).each(function () {
                if (typeof typesValidation == 'undefined'
                    || typesValidation.conditionalIsHidden($(this)) == false) {
                    if ($('#post').validate().element($(this)) == false) {
                        valid = false;
                    }
                }
            });
        } else {
            $('.js-wpt-validate', $table).each(function () {
                if (typeof wptValidation == 'undefined'
                    || !wptValidation.isIgnored($(this))) {
                    if ($('#post').validate().element($(this)) == false) {
                        valid = false;
                    }
                }
            });
        }
        if (valid == false) {
            $button.removeAttr('disabled');
            return false;
        }
        var rand = Math.round(Math.random() * 10000), height = $('tbody', $table).height();
        window.wpcf_pr_edited = false;
        $('.wpcf-pr-edited', $table).removeClass('wpcf-pr-edited');
        $.ajax({
            url: $button.attr('href'),
            type: 'post',
            dataType: 'json',
            data: $(this).attr('href') + '&' + $(':input', $update).serialize(),
            cache: false,
            beforeSend: function () {
                $('tbody', $table).empty().prepend('<tr id="wpcf-pr-update-' + rand + '"><td style="height: ' + height + 'px;"><div style="margin-top:20px;" class="wpcf-ajax-loading-small"></div></td></tr>');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        $update.replaceWith(data.output);
                        $button.removeAttr('disabled');
                        wpcfRelationshipInit('', 'save_all');
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire('#' + updateId);
                        }
                    }
                    if (typeof data.conditionals != 'undefined' && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                    if ('undefined' != typeof wptFile) {
                        wptFile.init();
                    }
                    /**
                     * rebind images
                     */
                    if ('function' == typeof bind_colorbox_to_thumbnail_preview) {
                        bind_colorbox_to_thumbnail_preview();
                    }
                    /**
                     * show errors
                     */
                    $('#wpcf-post-relationship div.message').detach();
                    if ('undefined' != typeof data.errors && 0 < data.errors.length) {
                        $('#wpcf-post-relationship h3.hndle').after(data.errors);
                    }
                }
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var $table = $update.find('table'),
                    data_for_events = {
                        table: $table
                    };

                $(document).trigger('js_event_wpcf_types_relationship_children_saved', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            }
        });
        return false;
    });

    /**
     * Handle the click on a link to set or remove a featured image.
     *
     * @since unknown
     */
    var setRemoveFeaturedImageHandler = function (event) {
        var $setRemoveLink = $(this);
        var $featuredImageContainer = $setRemoveLink.parent();

        var $data = $setRemoveLink.data();
        var $id = $setRemoveLink.attr('id');

        var currentAttachmentId = function (newValue) {
            if (typeof newValue == 'undefined') {
                return $('.feature-image-id', $setRemoveLink.parent()).val();
            } else {
                $setRemoveLink.html(0 == newValue ? $data.set : $data.remove);
                $setRemoveLink.parent().find('.feature-image-id').val(newValue);
            }
        };

        var setImagePreview = function (url) {
            var $imagePreviewContainer = $featuredImageContainer.find('.wpt-file-preview');
            if (null == url || url.length == 0) {
                $imagePreviewContainer.html('');
            } else {
                if (0 == $imagePreviewContainer.find('img').length) {
                    $imagePreviewContainer.append('<img src="">');
                }
                $imagePreviewContainer.find('img').attr('src', url);
            }
        };

        if (0 == currentAttachmentId()) {
            if (event) {
                event.preventDefault();
            }

            // If the media frame already exists, we're going to reopen it,
            // but in all cases, we need to overwrite the callback.
            //
            // When a row is updated (replaced with new element), the callback would
            // still hold reference to elements from the old row and break.
            if (!frame_relationship[$id]) {

                // No media frame exists yet, create it.
                frame_relationship[$id] = wp.media.frames.customHeader = wp.media({
                    title: $setRemoveLink.html(),

                    // Tell the modal to show only images.
                    library: {
                        type: "image"
                    }
                });
            }

            // When an image is selected, run a callback.
            frame_relationship[$id].on('select', function () {

                // Grab the selected attachment.
                var attachment = frame_relationship[$id].state().get('selection').first();

                if ('undefined' != typeof attachment.id) {
                    currentAttachmentId(attachment.id);
                }

                if ('undefined' != typeof attachment.attributes.sizes.thumbnail) {
                    setImagePreview(attachment.attributes.sizes.thumbnail.url);
                }

                frame_relationship[$id].close();
            });

            frame_relationship[$id].open();

        } else {
            setImagePreview(null);
            currentAttachmentId(0);
        }
        return false;
    };

    var bindRowEventHandlers = function (bind) {
        var $postRelationshipTable = $('#wpcf-post-relationship');
        if (bind) {
            $postRelationshipTable.on('click', '.feature-image', setRemoveFeaturedImageHandler);
        } else {
            $postRelationshipTable.off('click', '.feature-image', setRemoveFeaturedImageHandler);
        }
    };

    // We need to hide the _wpcf_belongs_xxxx_id field for WPML.
    jQuery('#icl_mcs_details table tbody tr').each(function () {
        var name = jQuery(this).find('td').html();
        if (name.search(/^_wpcf_belongs_.*?_id/) != -1) {
            jQuery(this).hide();
        }

    });

    // Pagination
    $('#wpcf-post-relationship').on('change', '.wpcf-relationship-items-per-page', function () {
        var $button = $(this), $update = $button.parents('.js-types-relationship-child-posts');
        $.ajax({
            url: ajaxurl,
            type: 'get',
            dataType: 'json',
            data: $button.data('action') + '&_wpcf_relationship_items_per_page=' + $button.val(), //+'&'+update.find('.wpcf-pagination-top :input').serialize(),
            cache: false,
            beforeSend: function () {
                $button.after('<div style="margin-top:20px;" class="wpcf-ajax-loading-small"></div>');
            },
            success: function (data) {
                if (data != null) {
                    if (typeof data.output != 'undefined') {
                        $update.html(data.output);
                        $button.next().fadeOut(function () {
                            $(this).remove();
                        });
                        tChildTable.reset();
                        if (typeof wptCallbacks != 'undefined') {
                            wptCallbacks.reset.fire($update);
                        }
                    }
                    if (typeof data.conditionals != 'undefined'
                        && typeof wptCond != 'undefined') {
                        wptCond.addConditionals(data.conditionals);
                    }
                }
                /**
                 * select2
                 */
                wpcfBindSelect2($);

                var $table = $update.find('table'),
                    data_for_events = {
                        table: $table
                    };

                $(document).trigger('js_event_wpcf_types_relationship_children_reloaded', [data_for_events]);
                $(document).trigger('js_event_wpcf_types_relationship_children_changed', [data_for_events]);
            }
        });
    });
    /*
     * 
     * Init
     */
    wpcfRelationshipInit('', 'init');
    bindRowEventHandlers(true);
});


function wpcfPrIsEdited() {
    if (jQuery('.wpcf-pr-edited').length < 1) {
        return false;
    }
    return true;
}

function wpcfPrUpdateIDs(ids) {
    var x;
    for (x in ids) {
        jQuery('#wpcf-post-relationship table td').find(':input[name^="wpcf_post_relationship[' + x + ']"]').each(function() {
            jQuery(this).attr('name', jQuery(this).attr('name').replace("[" + x + "]", "[" + ids[x] + "]"));
        });
    }
}

/**
 * Basic checks on Child tables inside .wpcf-pr-has-entries
 */
function wpcfRelationshipInit(selector, context) {
    jQuery(selector + '.wpcf-pr-has-entries').each(function() {
        var container = jQuery(this);
        jQuery(this).find('table').each(function() {
            var table = jQuery(this);
            // Show/hide if no children posts
            if (table.find('tbody tr').length < 1) {
                table.css('visibility', 'hidden');
                container.find('.wpcf-pagination-boottom')
                        .css('visibility', 'hidden');
                container.find('.wpcf-pr-save-all-link')
                        .attr('disabled', 'disabled');
            } else {
                table.css('visibility', 'visible');
                container.find('.wpcf-pagination-boottom')
                        .css('visibility', 'visible');
                container.find('.wpcf-pr-save-all-link')
                        .removeAttr('disabled');
            }
        });
    });
}

/**
 * select2
 */
function wpcfBindSelect2($) {
    $( '.wpcf-pr-belongs:not([data-belongs-title])' ).each( function() {
         wpcfBindSelect2For( $( this ) );
    } );
}

function wpcfBindSelect2For( element ) {
	var $ = jQuery,
		options = element.find( 'option' ),
		element_s2_instance;
	
	if ( options.length < 16 ) {
		element_s2_instance = element.toolset_select2({
			allowClear: true,
			triggerChange: true,
		});
	} else {
		element_s2_instance = element.toolset_select2({
			allowClear: true,
			ajax: {
				url: ajaxurl + '?action=wpcf_relationship_search&nounce='+element.data('nounce'),
				dataType: 'json',
				delay: 250,
				type: 'post',
				data: function (params) {
					return {
						s:			params.term,
						page:		params.page,
						post_id:	element.data('post-id'),
						post_type:	element.data('post-type')
					};
				},
				processResults: function (data, params) {
					params.page = params.page || 1;
					return {
						results: data.items,
						pagination: {
						  more: ( params.page * wpcf_post_relationship_messages.parent_per_page ) < data.total_count
						}
					};
				},
				cache: false
			},
			escapeMarkup: function (markup) { return markup; },
			//minimumInputLength: 2,// No minimum input length so we can search by empty terms, hece offer latests parents
			triggerChange: true,
			defaultResults: function() {
				var results = {};
				results.items = [],
				$.each( options, function( index, option ) {
					results.items.push( { id: option.value, text: option.text } );
				});
				return results;
			}
		});
	}
	element_s2_instance
		.on('toolset_select2:select', function( evt ) {
			$.ajax({
				url:		ajaxurl,
				dataType:	"json",
				data: 		{
					action: 	'wpcf_relationship_update',
					nounce:		element.data('nounce'),
					post_id: 	element.data('post-id'),
					post_type:	element.data('post-type'),
					p:			element.val()
				},
				success: function( response ) {
					var parent_edit_button = element
												.closest( '.form-item' )
													.find( '.js-wpcf-pr-parent-edit' );
					parent_edit_button
						.removeClass( 'disabled' )
						.fadeIn( 'fast' )
						.addClass( 'wpcf-saved' )
						.attr( 'href', response.data.edit_link + '?post=' + element.val() + '&action=edit' );
					setTimeout( function() {
							parent_edit_button.removeClass( 'wpcf-saved' );
						},
						1000
					);
				}
			});
		})
		.on('toolset_select2:unselect', function( evt ) {
			$.ajax({
				url:		ajaxurl,
				dataType:	"json",
				data: 		{
					action: 	'wpcf_relationship_update',
					nounce:		element.data('nounce'),
					post_id: 	element.data('post-id'),
					post_type:	element.data('post-type'),
					p:			0
				},
				success: function() {
					var parent_edit_button = element
												.closest( '.form-item' )
													.find( '.js-wpcf-pr-parent-edit' );
					parent_edit_button
						.addClass( 'disabled wpcf-deleted' )
						.attr( 'href', '#');
					setTimeout( function() {
							parent_edit_button
								.fadeOut( 500, function() {
									parent_edit_button.removeClass( 'wpcf-deleted' );
								});
						},
						1000
					);
				}
			});
		});
}
jQuery(document).ready(function($) {
    wpcfBindSelect2($);


    $( '.wpcf-pr-belongs[data-belongs-title]' ).each( function() {
        var inputRelationId = $( this ),
            inputShowRelationTitle = $( '<input type="textfield" readonly="readonly" style="cursor:pointer; width: 100%; max-width:300px;">' );

        inputShowRelationTitle.val( inputRelationId.data( 'belongs-title' ) );
        inputRelationId.hide();

        inputRelationId.after( inputShowRelationTitle );

        inputShowRelationTitle.on( 'click', function() {
            inputShowRelationTitle.remove();
            inputRelationId.show();
            wpcfBindSelect2For( inputRelationId );
        } );
    } );
});

/**
 * Returns comma sign
 *
 * Using postL10 if not undefined, otherwise tagsBoxL10n
 * and if both not defined it will return ','
 */
function wpcfGetCommaSign() {
    var comma = ',';

    if( typeof postL10n !== 'undefined' && typeof postL10n.comma !== 'undefined' ) {
        comma = postL10n.comma;
    } else if( typeof window.tagsBoxL10n !== 'undefined' && typeof window.tagsBoxL10n.tagDelimiter !== 'undefined' ) {
        comma = window.tagsBoxL10n.tagDelimiter;
    }

    return comma;
}

/**
 * Fix for Select2
 *
 * A stored value in Select2 is shown in the select on init (page/ajax reload), but not the hidden input value
 * which is needed for save. To get the hidden input also updated we need to call select2( 'val', currentValue)
 * to prevent a endless loop in initSelection callback we store if we already set the val (wpcfInitValueOfSelect2Done).
 *
 * This storage has to be cleared (wpcfInitValueOfSelect2DoneClear) after an select2 is added / updated / deleted
 */
var wpcfInitValueOfSelect2Done = {};

function wpcfInitValueOfSelect2( elementID, value ) {
    if( wpcfInitValueOfSelect2Done[elementID] != 1 ) {
        jQuery( '#'+elementID ).toolset_select2( 'val', value );
    }

    wpcfInitValueOfSelect2Done[elementID] = 1;
}

function wpcfInitValueOfSelect2DoneClear() {
    if( Object.keys(wpcfInitValueOfSelect2Done).length ) {
        jQuery.each( wpcfInitValueOfSelect2Done, function( key, val ) {
            wpcfInitValueOfSelect2Done[key] = 0;
        } );
    }
}

function wpcfDisableControls() {
    jQuery( '.js-types-add-child, .wpcf-pr-save-ajax, .wpcf-pr-save-ajax ~ a.button-secondary, .wpcf-pr-delete-ajax' ).addClass( 'disabled' );
    jQuery( 'input[name^="save"]' ).attr( 'disabled', 'disabled' );
}

function wpcfEnableControls() {
    jQuery( '.js-types-add-child, .wpcf-pr-save-ajax, .wpcf-pr-save-ajax ~ a.button-secondary, .wpcf-pr-delete-ajax' ).removeClass( 'disabled' );
    jQuery( 'input[name^="save"]' ).removeAttr( 'disabled' );
}

function typesRelationControlsAjaxStart() {
    wpcfDisableControls();
}

function typesRelationControlsAjaxComplete() {
    wpcfEnableControls();
}

jQuery( document ).on( 'js_event_wpcf_types_relationship_children_changed', function( event, data ) {
	typesRelationControlsAjaxComplete();
});
