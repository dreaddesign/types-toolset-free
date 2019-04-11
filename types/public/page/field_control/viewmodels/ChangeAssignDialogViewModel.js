/**
 * Viewmodel of the dialog for changing assignment of a field definition to field groups.
 *
 * Requires the 'types-change-assignment-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param {Types.page.fieldControl.viewmodels.FieldDefinitionViewModel} fieldDefinition
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *     a boolean determining whether user has accepted the change. If yes, the second argument is an array of
 *     newly assigned group slugs.
 *
 * @returns {Types.page.fieldControl.viewmodels.ChangeAssignDialogViewModel}
 * @since 2.0
 */
Types.page.fieldControl.viewmodels.ChangeAssignDialogViewModel = function(fieldDefinition, closeCallback) {
   
    var self = this;

    /**
     * @type {Types.page.fieldControl.viewmodels.FieldDefinitionViewModel}
     */
    self.fieldDefinition = fieldDefinition;


    /**
     * Array of slugs of currently selected groups.
     */
    self.selectedGroups = ko.observableArray(_.toArray(self.fieldDefinition.groups()));


    self.totalGroupCount = Types.page.fieldControl.groups.length;


    /**
     * Returns a ko.pureComputed for an individual group slug that keeps self.selectedGroups up-to-date.
     *
     * @param {string} groupSlug
     */
    self.isGroupChecked = function(groupSlug) {

        return ko.pureComputed({
            read: function() {
                return _.contains(self.selectedGroups(), groupSlug);
            },
            write: function(value) {
                if(value) {
                    var selectedGroups = self.selectedGroups();
                    selectedGroups.push(groupSlug);
                    self.selectedGroups(_.uniq(selectedGroups));
                } else {
                    self.selectedGroups(_.without(self.selectedGroups(), groupSlug));
                }
            }
        });
    };


    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el.parentNode);
        };

        var dialog = Types.page.fieldControl.dialogHandler.create(
            'types-change-assignment-dialog',
            Types.page.fieldControl.strings.misc['changeAssignmentToGroups'] + ' ' + fieldDefinition.displayName(),
            {},
            [
                {
                    text: Types.page.fieldControl.strings.button['apply'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(true, self.selectedGroups());
                    },
                    'class': 'button-primary',
                    'data-bind': 'disablePrimary: (0 == totalGroupCount)'
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

        // We need to bind to the parent element, which also includes the area with dialog buttons.
        // The reason is that we're binding the Apply button style.
        // Note that the cleanup function must reference the same element.
        ko.applyBindings(self, dialog.el.parentNode);

    };
    
    
    return self;
};
