/**
 * Viewmodel of the dialog for changing type of a single field definition.
 *
 * Requires the 'types-change-field-type-dialog' assets to be present.
 *
 * Call the display() method to invoke the dialog.
 *
 * @param fieldDefinition
 * @param {function} closeCallback Function that will be called when the dialog is closed. First argument is
 *     a boolean determining whether user has accepted the change. If they did, there are more arguments:
 *     - slug of the new field type
 *     
 * @since 2.0
 */
Types.page.fieldControl.viewmodels.ChangeFieldTypeDialogViewModel = function(fieldDefinition, closeCallback) {

    var self = this;


    /**
     * Currently selected target field type slug.
     */
    self.selectedType = ko.observable();
    
    
    self.isSelected = function(fieldType) {
       return ko.pureComputed({
           read: function() {
               return (self.selectedType() == fieldType);
           }
       });
    };


    var typeCanBeRepetitive = function(fieldTypeSlug) {
        if(_.has(Types.page.fieldControl.fieldTypeDefinitions, fieldTypeSlug)) {
            var fieldType = Types.page.fieldControl.fieldTypeDefinitions[fieldTypeSlug];
            return (_.has(fieldType, 'canBeRepetitive') ? fieldType['canBeRepetitive'] : false);
        }
        return false;
    };
    
    
    self.reducingCardinality = ko.pureComputed(function() {
        return (fieldDefinition.isRepetitive() && 'single' == self.targetCardinality());
    });


    /**
     * Determine if the field definition can be converted into a given type.
     *
     * Respects the conversion matrix as well as field cardinality.
     *
     * @param {string} fieldType Field type slug.
     * @returns {boolean}
     * @since 2.0
     */
    self.canConvertTo = function(fieldType) {
        if(! _.has(Types.page.fieldControl.typeConversionMatrix, fieldDefinition.type())) {
            return false;
        }
        var possibleConversions = Types.page.fieldControl.typeConversionMatrix[fieldDefinition.type()];
        if(! _.contains(possibleConversions, fieldType)) {
            return false;
        }
        return !(fieldDefinition.isRepetitive() && !typeCanBeRepetitive(fieldType));
    };


    self.targetTypeCanBeRepetitive = ko.pureComputed(function() {
        return typeCanBeRepetitive(self.selectedType());
    });


    self.targetCardinality = ko.observable(fieldDefinition.isRepetitive() ? 'repetitive' : 'single');

    self.targetTypeCanBeRepetitive.subscribe(function(newValue) {
        if(false == newValue) {
            self.targetCardinality('single');
        }
    });



    /**
     * Display the dialog.
     */
    self.display = function() {

        var cleanup = function(dialog) {
            jQuery(dialog.$el).ddldialog('close');
            ko.cleanNode(dialog.el.parentNode);
        };

        var dialog = Types.page.fieldControl.dialogHandler.create(
            'types-change-field-type-dialog',
            Types.page.fieldControl.strings.misc['changeFieldType'] + ' ' + fieldDefinition.displayName(),
            {},
            [
                {
                    text: Types.page.fieldControl.strings.button['apply'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(true, self.selectedType(), self.targetCardinality());
                    },
                    'class': 'button-primary',
                    'data-bind': 'redButton: reducingCardinality'
                },
                {
                    text: Types.page.fieldControl.strings.button['cancel'],
                    click: function() {
                        cleanup(dialog);
                        closeCallback(false);
                    },
                    'class': 'wpcf-ui-dialog-cancel'
                }
            ],
            {
                width: 800
            }
        );

        // We need to bind to the parent element, which also includes the area with dialog buttons.
        // The reason is that we're binding the Apply button style.
        // Note that the cleanup function must reference the same element.
        ko.applyBindings(self, dialog.el.parentNode);

        self.selectedType(fieldDefinition.type());
    };


    return self;
};