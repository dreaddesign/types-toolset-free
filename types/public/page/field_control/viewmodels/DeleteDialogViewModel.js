/**
 * Viewmodel of the dialog for deleting a single field definition.
 *
 * Requires the 'types-delete-field-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *     a boolean determining whether user has accepted the change.
 * @since 2.0
 */
Types.page.fieldControl.viewmodels.DeleteDialogViewModel = function(subject, closeCallback) {

    var self = this;

    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el);
        };
        
        var isSingleFieldAction = !_.isArray(subject);
        
        var title = (
            isSingleFieldAction 
                ? Types.page.fieldControl.strings.misc['deleteField'] + ' ' + subject.displayName()
                : Types.page.fieldControl.strings.misc['deleteFields']
        );

        var dialog = Types.page.fieldControl.dialogHandler.create(
            'types-delete-field-dialog',
            title,
            {},
            [
                {
                    text: Types.page.fieldControl.strings.button['delete'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(true);
                    },
                    'class': 'button-primary types-delete-button'
                },
                {
                    text: Types.page.fieldControl.strings.button['cancel'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(false);
                    },
                    'class': 'wpcf-ui-dialog-cancel'
                }
            ]
        );

        ko.applyBindings(self, dialog.el);
    };


    return self;
};