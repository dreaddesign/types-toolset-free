/**
 * Viewmodel of the dialog for bulk manage/stop managing fields with Types
 *
 * Requires the 'types-bulk-change-management-status-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {{[Types.page.fieldControl.viewmodels.FieldDefinitionViewModel]}} fieldDefinitions
 * @param {boolean} willBeManagedByTypes
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *     a boolean determining whether user has accepted the change.
 * @since 2.0
 */
Types.page.fieldControl.viewmodels.BulkChangeManagementStatusDialogViewModel = function(fieldDefinitions, willBeManagedByTypes, closeCallback) {

    var self = this;
    
    
    self.willBeManagedByTypes = ko.observable(willBeManagedByTypes);

    self.fieldDefinitions = ko.observableArray(fieldDefinitions);

    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el);
        };
        

        var title = (
            willBeManagedByTypes
                ? Types.page.fieldControl.strings.misc['startManagingFieldsWithTypes']
                : Types.page.fieldControl.strings.misc['stopManagingFieldsWithTypes']
        );

        var dialog = Types.page.fieldControl.dialogHandler.create(
            'types-bulk-change-management-status-dialog',
            title,
            {},
            [
                {
                    text: Types.page.fieldControl.strings.button['apply'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(true);
                    },
                    'class': 'button-primary'
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