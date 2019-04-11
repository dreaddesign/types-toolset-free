var Toolset = Toolset || {};
Toolset.Gui = Toolset.Gui || {};
Toolset.Gui.Mixins = Toolset.Gui.Mixins || {};

/**
 * Extend the knockout library with various binding handlers.
 *
 * Usage:
 * - Add this script as a dependency (handle is Toolset_Gui_Base::SCRIPT_GUI_MIXIN_KNOCKOUT_EXTENSIONS)
 * - Toolset.Gui.Mixins.KnockoutExtensions.call(self);
 * - self.initKnockout();
 *
 * @constructor
 * @since 2.5.11
 */
Toolset.Gui.Mixins.KnockoutExtensions = function() {

    var self = this;

    /**
     * Initialize custom Knockout bindings and other modifications.
     *
     * @since 2.2
     */
    self.initKnockout = function () {

        var $ = jQuery;

        // Taken from http://knockoutjs.com/examples/animatedTransitions.html
        // Here's a custom Knockout binding that makes elements shown/hidden via jQuery's fadeIn()/fadeOut() methods
        ko.bindingHandlers.fadeVisible = {
            init: function (element, valueAccessor) {
                // Initially set the element to be instantly visible/hidden depending on the value
                var value = valueAccessor();
                $(element).toggle(ko.unwrap(value)); // Use "unwrapObservable" so we can handle values that may or may not be observable
            },
            update: function (element, valueAccessor) {
                // Whenever the value subsequently changes, slowly fade the element in or out
                var value = valueAccessor();
                ko.unwrap(value) ? $(element).fadeIn() : $(element).fadeOut();
            }
        };


        var applyDisplayMode = function (displayMode, element, immediately) {
            switch (displayMode) {
                case 'show':
                    element.css('visibility', 'visible');
                    if (immediately) {
                        element.show();
                    } else {
                        element.slideDown().css('display', 'none').fadeIn();
                    }
                    break;
                case 'hide':
                    element.css('visibility', 'hidden');
                    if (immediately) {
                        element.show();
                    } else {
                        element.slideDown();
                    }
                    break;
                case 'remove':
                    if (immediately) {
                        element.hide();
                    } else {
                        element.slideUp().fadeOut();
                    }
                    element.css('visibility', 'hidden');
                    break;
            }
        };


        /**
         * Binding for displaying an element in three modes:
         *
         * - 'show' will simply display the element
         * - 'hide' will hide it, but leave the free space for another message to be displayed soon
         * - 'remove' will hide it completely
         *
         * Show/remove values use animations.
         *
         * @since 2.2
         */
        ko.bindingHandlers.threeModeVisibility = {
            init: function (element, valueAccessor) {
                var displayMode = ko.unwrap(valueAccessor());
                applyDisplayMode(displayMode, $(element), true);
            },
            update: function (element, valueAccessor) {
                var displayMode = ko.unwrap(valueAccessor());
                applyDisplayMode(displayMode, $(element), false);
            }
        };


        var disablePrimary = function (element, valueAccessor) {
            var isDisabled = ko.unwrap(valueAccessor());
            if (isDisabled) {
                $(element).prop('disabled', true).removeClass('button-primary');
            } else {
                $(element).prop('disabled', false).addClass('button-primary');
            }
        };

        /**
         * Disable primary button and update its class.
         *
         * @since 2.2
         */
        ko.bindingHandlers.disablePrimary = {
            init: disablePrimary,
            update: disablePrimary
        };


        var redButton = function (element, valueAccessor) {
            var isRed = ko.unwrap(valueAccessor());
            if (isRed) {
                jQuery(element).addClass('toolset-red-button');
            } else {
                jQuery(element).removeClass('toolset-red-button');
            }
        };


        /**
         * Add or remove a class that makes a button red.
         *
         * @since 2.0
         */
        ko.bindingHandlers.redButton = {
            init: redButton,
            update: redButton
        };


        // Update textarea's value and scroll it to the bottom.
        var valueScroll = function (element, valueAccessor) {
            var value = ko.unwrap(valueAccessor());
            var textarea = $(element);

            textarea.val(value);
            textarea.scrollTop(textarea[0].scrollHeight);
        };

        ko.bindingHandlers.valueScroll = {
            init: valueScroll,
            update: valueScroll
        };


        /**
         * Set the readonly attribute value.
         *
         * @type {{update: ko.bindingHandlers.readOnly.update}}
         * @since m2m
         */
        ko.bindingHandlers.readOnly = {
            update: function (element, valueAccessor) {
                var value = ko.utils.unwrapObservable(valueAccessor());
                if (value) {
                    element.setAttribute("readonly", true);
                } else {
                    element.removeAttribute("readonly");
                }
            }
        };


        /**
         * New computed type that allows to force the reading on the observable
         *
         * Check this {@link https://stackoverflow.com/questions/13769481/force-a-computed-property-function-to-run/29960082#29960082|Stackoveflow} example
         * @since m2m
         */
        ko.notifyingWritableComputed = function (options, context) {
            var _notifyTrigger = ko.observable(0);
            var originalRead = options.read;
            var originalWrite = options.write;

            // intercept 'read' function provided in options
            options.read = function () {
                // read the dummy observable, which if updated will
                // force subscribers to receive the new value
                _notifyTrigger();
                return originalRead();
            };

            // intercept 'write' function
            options.write = function (v) {
                // run logic provided by user
                originalWrite(v);

                // force reevaluation of the notifyingWritableComputed
                // after we have called the original write logic
                _notifyTrigger(_notifyTrigger() + 1);
            };

            // just create computed as normal with all the standard parameters
            return ko.computed(options, context);
        }
    };

};