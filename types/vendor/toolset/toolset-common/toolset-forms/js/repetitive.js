/*
 * Repetitive JS.
 *
 *
 */
var wptRep = (function ($) {
    var count = {};
    function initRepetitiveFields() {
        // Reorder label and description for repetitive
        // Note that we target usual labels and descriptions but a classname can be used to keep some auxiliar items
        $('.js-wpt-repetitive').each(function () {
            var $this = $(this),
                $parent;
            if ($('body').hasClass('wp-admin')) {
                var title = $('label:not(.js-wpt-auxiliar-label)', $this).first().clone();
                var description = $('.description:not(.js-wpt-auxiliar-description)', $this).first().clone();
                $('.js-wpt-field-item', $this).each(function () {
                    $('label:not(.js-wpt-auxiliar-label)', $this).remove();
                    $('.description:not(.js-wpt-auxiliar-description)', $this).remove();
                });
                $this.prepend(description).prepend(title);
            }
            if ($this.hasClass('js-wpt-field-items')) {// This happens on the frontent
                $parent = $this;
            } else {// This happens on the backend
                $parent = $this.find('.js-wpt-field-items');
            }
            if ($(this).find('.js-wpt-repdelete').length > 1) {
                $(this).find('.js-wpt-repdelete').show();
            } else if ($(this).find('.js-wpt-repdelete').length == 1) {
                $(this).find('.js-wpt-repdelete').hide();
            }
            _toggleCtl($parent);
        });

    }
    function init() {
        _.defer(function() {
            initRepetitiveFields();
        });
        
        // Add field
        $(document).off('click', '.js-wpt-repadd', null);
        $(document).on('click', '.js-wpt-repadd', function (e) {
            e.preventDefault();
            var $this = $(this),
                    parent,
                    tpl;
            $parent = $this.closest('.js-wpt-field-items');
            if (1 > $parent.length) {
                return;
            }
            if ($('body').hasClass('wp-admin')) {
                // Get template from the footer templates by wpt-id data attribute
                tpl = $('<div>' + $('#tpl-wpt-field-' + $this.data('wpt-id')).html() + '</div>');
                // Remove label and descriptions from the template
                // Note that we target usual labels and descriptions but a classname can be used to keep some auxiliar items
                $('label:not(.js-wpt-auxiliar-label)', tpl).first().remove();
                $('.description:not(.js-wpt-auxiliar-description)', tpl).first().remove();
                // Adjust ids and labels where needed for the template content
                $('[id]', tpl).each(function () {
                    var $this = $(this), uniqueId = _.uniqueId('wpt-form-el');
                    tpl.find('label[for="' + $this.attr('id') + '"]').attr('for', uniqueId);
                    $this.attr('id', uniqueId);
                });
                // Calculate _count to build the name atribute
                var _count = tpl.html().match(/\[%%(\d+)%%\]/);
                if (_count != null) {
                    _count = _countIt(_count[1], $this.data('wpt-id'));
                } else {
                    _count = '';
                }
                // Adjust the _count to avoid duplicates when some intermediary has been deleted
                while ($('[name*="[' + _count + ']"]', $parent).length > 0) {
                    _count++;
                }
                // Insert the template before the button
                $this.before(tpl.html().replace(/\[%%(\d+)%%\]/g, '[' + _count + ']'));
            } else {
                /**
                 * template
                 */
                tpl = $('<div>' + $('#tpl-wpt-field-' + $this.data('wpt-id')).html() + '</div>');

                $('[id]', tpl).each(function () {
                    var $this = $(this), uniqueId = _.uniqueId('wpt-form-el');
                    $this.attr('id', uniqueId);
                });
                // Calculate _count to build the name atribute
                var _count = tpl.html().match(/\[%%(\d+)%%\]/);
                if (_count != null) {
                    _count = _countIt(_count[1], $this.data('wpt-id'));
                } else {
                    _count = '';
                }
                // Adjust the _count to avoid duplicates when some intermediary has been deleted
                while ($('[name*="[' + _count + ']"]', $parent).length > 0) {
                    _count++;
                }
                // Insert the template before the button
                $this.before(tpl.html().replace(/\[%%(\d+)%%\]/g, '[' + _count + ']'));

            }
            wptCallbacks.addRepetitive.fire($parent);
            _toggleCtl($parent);
            $this.trigger('blur');// To prevent it from staying on the active state

            /*
             * Fires after the creation of a new repetitive field holding the parent div for the field
             * @since 2.5.0
             */
            jQuery(document).trigger('toolset_repetitive_field_added', $parent);

            return false;
        });
        // Delete field
        $(document).off('click', '.js-wpt-repdelete', null);
        $(document).on('click', '.js-wpt-repdelete', function (e) {
            //$('.js-wpt-field-items').on('click', '.js-wpt-repdelete', function (e) {
            e.preventDefault();
            $parent = $(this).closest('.js-wpt-field-items');
            if ($('body').hasClass('wp-admin')) {
                var $this = $(this), value;
                // Allow deleting if more than one field item
                if ($('.js-wpt-field-item', $parent).length > 1) {
                    var formID = $this.parents('form').attr('id');
                    $this.parents('.js-wpt-field-item').remove();
                    wptCallbacks.removeRepetitive.fire(formID);
                }
                /**
                 * if image, try delete images
                 * TODO check this, I do not like using parent() for this kind of things
                 */
                if ('image' == $this.data('wpt-type')) {
                    value = $this.parent().parent().find('input').val();
                    $parent.parent().append(
                            '<input type="hidden" name="wpcf[delete-image][]" value="'
                            + value
                            + '"/>'
                            );
                }
            } else {
                if ($('.wpt-repctl', $parent).length > 1) {
                    $(this).closest('.wpt-repctl').remove();
                    wptCallbacks.removeRepetitive.fire(formID);
                }
            }
            _toggleCtl($parent);
            return false;
        });
    }
    function _toggleCtl($sortable) {
        var sorting_count;
        if ($('body').hasClass('wp-admin')) {
            sorting_count = $('.js-wpt-field-item', $sortable).length;
        } else {
            sorting_count = $('.wpt-repctl', $sortable).length;
        }
        if (sorting_count > 1) {
            $('.js-wpt-repdelete', $sortable).prop('disabled', false).show();
            $('.js-wpt-repdrag', $sortable).css({opacity: 1, cursor: 'move'}).show();
            if (!$sortable.hasClass('ui-sortable')) {
                $sortable.sortable({
                    handle: '.js-wpt-repdrag',
                    axis: 'y',
                    stop: function (event, ui) {
                        $sortable.find('.js-wpt-repadd').detach().appendTo($sortable);
                    }
                });
            }
        } else {
            $('.js-wpt-repdelete', $sortable).prop('disabled', true).hide();
            $('.js-wpt-repdrag', $sortable).css({opacity: 0.5, cursor: 'default'}).hide();
            if ($sortable.hasClass('ui-sortable')) {
                $sortable.sortable('destroy');
            }
        }
    }
    function _countIt(_count, id) {
        if (typeof count[id] == 'undefined') {
            count[id] = _count;
            return _count;
        }
        return ++count[id];
    }
    return {
        init: init
    };
})(jQuery);

jQuery(document).ready(wptRep.init);
