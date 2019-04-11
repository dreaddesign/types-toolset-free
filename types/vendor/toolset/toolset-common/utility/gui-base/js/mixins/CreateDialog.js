var Toolset = Toolset || {};
Toolset.Gui = Toolset.Gui || {};
Toolset.Gui.Mixins = Toolset.Gui.Mixins || {};

/**
 * Add a method for an easy and repeatable invocation of a Toolset dialog.
 *
 * Usage:
 * - Initialize appropriate Toolset_Template_Dialog_Box instance when rendering the page in PHP.
 * - Add this script as a dependency (handle is Toolset_Gui_Base::SCRIPT_GUI_MIXIN_CREATE_DIALOG)
 * - Toolset.Gui.Mixins.KnockoutExtensions.call(self);
 * - self.createDialog();
 *
 * @constructor
 * @since 2.5.11
 */
Toolset.Gui.Mixins.CreateDialog = function() {

    var self = this;

    /**
     * Create a Toolset dialog.
     *
     * For details, see https://git.onthegosystems.com/toolset/toolset-common/wikis/best-practices/dialogs.
     *
     * @param {string} dialogId Id of the HTML element holding the dialog template.
     * @param {string} title Dialog title to be displayed
     * @param {*} templateContext Context for the dialog (underscore) template.
     * @param buttons Button definitions according to jQuery UI Dialogs.
     * @param [options] Further options that will be passed directly.
     * @returns {{DDLayout.DialogView}} A dialog object.
     * @since 2.1
     */
    self.createDialog = function(dialogId, title, templateContext, buttons, options) {

        var dialogDuplicate = DDLayout.DialogView.extend({});

        var processedOptions = _.defaults(options || {}, {
            title: title,
            selector: '#' + dialogId,
            template_object: templateContext,
            buttons: buttons,
            width: 600
        });

        var dialog = new dialogDuplicate(processedOptions);

        return dialog;
    };

};