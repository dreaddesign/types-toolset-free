var Toolset = Toolset || {};

Toolset.Gui = Toolset.Gui || {};

/**
 * Generic viewmodel for an item to be displayed in a listing (Toolset.Gui.ListingViewModel and its derivatives).
 *
 * @param {{displayName:string}} model
 * @param {*} itemActions An object with methods to perform actions on field definitions.
 * @constructor
 * @since 2.2
 */
Toolset.Gui.ItemViewModel = function(model, itemActions) {

    var self = this;

    self.displayName = ko.observable(model.displayName);


    /**
     * An object with methods to perform actions on field definitions.
     *
     * Each action accepts an array of field definitions, or a single field definition, as first parameter.
     *
     * @since 2.2
     */
    self.itemActions = itemActions;


    /**
     * Get an updated object with the same properties as the original model.
     *
     * It is assumed that if a property is a function, we're dealing with a knockout observable and the return value
     * will be returned instead of the function itself.
     *
     * @returns {*}
     * @since 2.0
     */
    self.getModelObject = function() {
        var ownModelProperties = _.keys(model);
        var modelObject = {};

        _.each(ownModelProperties, function(propertyName) {
            if(_.has(self, propertyName)) {
                if(_.isFunction(self[propertyName])) {
                    modelObject[propertyName] = self[propertyName]();
                } else {
                    modelObject[propertyName] = self[propertyName];
                }
            }
        });

        return modelObject;
    };


    /**
     * Update this ViewModel's properties by properties from a model object.
     *
     * If there is a property on the model that isn't on the viewmodel, or if such property isn't a function
     * (which is expected to be a knockout observable), it will be created.
     *
     * If there is the property and is a function, it will be called with the new value as a first parameter.
     *
     * @param updatedModel Model object with updated values.
     * @since 2.0
     */
    self.updateModelObject = function(updatedModel) {
        var ownModelProperties = _.keys(updatedModel);

        _.each(ownModelProperties, function(propertyName) {
            if (!_.has(self, propertyName) && !_.isFunction(self[propertyName])) {
                self[propertyName] = ko.observable();
            }
            self[propertyName](updatedModel[propertyName]);
        });
    };


    /**
     * Number of (AJAX) actions currently in progress.
     *
     * Do not touch it directly, use beginAction() and finishAction() instead.
     */
    self.inProgressActionCount = ko.observable(0);


    /**
     * Show a spinner if there is at least one AJAX action in progress.
     */
    self.isSpinnerVisible = ko.pureComputed(function() {
        return (self.inProgressActionCount() > 0);
    });


    /**
     * Indicate a beginning of an AJAX action.
     *
     * Make sure you also call finishAction() afterwards, no matter what the result is.
     */
    self.beginAction = function() {
        self.inProgressActionCount(self.inProgressActionCount() + 1);
    };


    /**
     * Indicate that an AJAX action was completed.
     */
    self.finishAction = function() {
        self.inProgressActionCount(self.inProgressActionCount() - 1);
    };


    self.isSelectedForBulkAction = ko.observable(false);


    /**
     * This will be updated by the main ViewModel.
     *
     * @since 2.2
     */
    self.isBeingDisplayed = ko.observable(false);


    /**
     * When the item is not displayed in the listing table, we don't want it to be selected for a bulk action.
     *
     * @since 2.2
     */
    self.isBeingDisplayed.subscribe(function(newValue) {
        if(false == newValue) {
            self.isSelectedForBulkAction(false);
        }
    });


    /**
     * Determine CSS class for the tr tag depending on field status.
     *
     * @since 2.0
     */
    self.trClass = ko.computed(function() {
        // To be overridden
        return '';
    });


    /**
     * Simulates a link when displayNameLink exists
     *
     * @since 2.3
     */
    self.onDisplayNameClick = function() {
        if (!_.isUndefined(model.editLink)) {
            document.location = model.editLink;
        }
    }
};
