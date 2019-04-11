
var Types = Types || {};

Types.page = Types.page || {};

/**
 * Main script for the Add Term (edit-tags.php) page.
 *
 * Currently it does only one thing: After a new term is added via AJAX, it recognizes the event
 * and if there are custom term fields, starts a page reload.
 *
 * The reason for enforcing a page reload is that we need to completely reset the inputs rendered by
 * toolset-forms but in doing so we lose all kinds of event bindings. toolset-forms is not built for such situation
 * and implementing the needed changes in legacy code (and also some third-party one) only to avoid one reload
 * seems rather impractical.
 *
 * See wp-admin/edit-tags.php and wp-admin/js/tags.php for details.
 *
 * @since 2.1
 */
Types.page.addTerm = (function($) {

    var self = this;

    self.formSelector = 'form#addtag';

    self.init = function() {

        $(document).ready(function () {

            //noinspection JSUnresolvedVariable
            /**
             * @type {{autohidden_columns:bool,last_displayed_column:string}}
             */
            self.l10n = (typeof(types_page_add_term_l10n) == 'undefined') ? {} : types_page_add_term_l10n;

            if(self.hasTypesFields()) {

                $(document).bind('ajaxSuccess', self.catchAfterTermCreatedEvent);
                self.createFauxSubmitButton();
                self.maybeAnnotateLastDisplayedColumn();
            }
        });
    };


    /**
     * Hide the submit button from the form, and display its clone instead.
     *
     * When the cloned button is clicked, we'll manually trigger form validation and click on the original
     * button only if the validation is successful.
     *
     * This is used to outrun the binding on the original submit button from that's defined in wp-admin/js/tags.js
     * (and we can't do anything about that).
     *
     * @since 2.1
     */
    self.createFauxSubmitButton = function() {

        var form = $(self.formSelector);
        var submitSection = form.find('p.submit');
        var originalSubmitButton = submitSection.find('#submit');

        var customSubmitButton = originalSubmitButton.clone();

        originalSubmitButton.css('display', 'none');

        customSubmitButton.off();
        customSubmitButton.attr('id', 'types-custom-submit');

        var handleSubmitAction = function(e) {

            if(form.valid()) {
                WPV_Toolset.Utils.Spinner.show(WPV_Toolset.Utils.Spinner.find(form));
                originalSubmitButton.click();
            }

            // If the form isn't valid, the validation code has displayed all the required messages, etc.

            e.preventDefault();
            e.stopPropagation();
        };

        customSubmitButton.click(handleSubmitAction);
        customSubmitButton.submit(handleSubmitAction);

        submitSection.append(customSubmitButton).append(WPV_Toolset.Utils.Spinner.create());

        // Set the new custom button as default.
        self.setDefaultSubmitButton(form, customSubmitButton);
    };


    /**
     * When the form contains other buttons, we need to ensure our button will be handled as the default one
     * (when user presses enter, it's submit event will be invoked). There is no standard way to define a default
     * button, and this was causing issues with file-based custom fields (which come with an Upload button).
     *
     * For more insight see these SO questions:
     * - http://stackoverflow.com/q/925334/3191395
     * - http://stackoverflow.com/q/699065/3191395
     *
     * @param form
     * @param defaultButton
     *
     * @since 2.1
     */
    self.setDefaultSubmitButton = function(form, defaultButton) {

        // Binding on keydown, because keypress was too late.
        form.find('input').keydown(function(e) {
            // Lucky number for Enter
            if(13 == e.which) {
                defaultButton.submit();
                return false;
            }
        });

    };


    /**
     * Indicate whether the Add Term form contains some Types fields.
     *
     * @returns {boolean}
     */
    self.hasTypesFields = function() {
        return ($('#types-groups-exist').length !== 0);
    };


    /**
     * Process an event and determine if it was a term creation.
     *
     * Reload the page if needed.
     *
     * @param event
     * @param xhr
     * @param settings
     *
     * @since 2.1
     */
    self.catchAfterTermCreatedEvent = function(event, xhr, settings) {

        var data = '?' + settings.data;
        var action = WPV_Toolset.Utils.getParameterByName('action', data);

        if('add-tag' == action && self.hasTypesFields()) {

            var hasAjaxError = ( 0 < $('div#ajax-response > div.error').length );

            // If an error message is displayed, we'll scroll to the top of the page
            // (because of Types fields the form could have become too long).
            if(hasAjaxError) {
                WPV_Toolset.Utils.Spinner.hide(WPV_Toolset.Utils.Spinner.find($(self.formSelector)));
                $('html, body').animate({ scrollTop: 0 }, "slow");
            } else {
                var newUrl = WPV_Toolset.Utils.updateUrlQuery('message', '1');
                window.location.replace(newUrl);
            }
        }
    };


    /**
     * Add an annotation to one of the column headers if some columns have been hidden automatically.
     *
     * @since 2.1
     */
    self.maybeAnnotateLastDisplayedColumn = function() {

        if(_.has(self.l10n, 'autohidden_columns') && self.l10n.autohidden_columns) {
            $('th.column-' + self.l10n.last_displayed_column).append(
                '<span class="types-column-autohiding-annotation">' + self.l10n.annotation + '</span>'
            );

            // Hide the annotation as soon as user touches the screen options toggle.
            $(document).bind('screen:options:open', function() {
                $('.types-column-autohiding-annotation').remove();
            });
        }

    };


    self.init();

})(jQuery);
