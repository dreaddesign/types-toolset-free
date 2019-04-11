/**
 * Main ViewModel of the page.
 *
 * Holds the collection of field definition ViewModels, handles their sorting and filtering (search).
 *
 * @param fieldDefinitionModels
 * @constructor
 */
Types.page.fieldControl.viewmodels.ListingViewModel = function(fieldDefinitionModels) {

    var self = this;
    
    
    self.isInitialized = false;
    

    self.fieldDefinitions = ko.observableArray();


    // ------------------------------------------------------------------------
    // Sorting functionality
    // ------------------------------------------------------------------------



    self.onSort = function(propertyName) {
        var newDirection = (
            sortHelper.currentSortBy() == propertyName
            ? sortHelper.currentSortDirection() * -1
            : sortHelper.currentSortDirection()
        );
        sortHelper.sortFieldDefinitions(propertyName, newDirection);
    };


    /**
     * Determine a current class for an column sorting indicator icon based on property name.
     *
     * @param {string} propertyName Name of property that the column uses for sorting.
     * @returns {string} One or more CSS classes.
     * @since 2.0
     */
    self.sortIconClass = function(propertyName) {
        if(sortHelper.currentSortBy() == propertyName) {
            if(1 == sortHelper.currentSortDirection()) {
                return 'fa fa-sort-alpha-asc';
            } else {
                return 'fa fa-sort-alpha-desc';
            }
        } else {
            return 'fa sort-icon-inactive fa-sort-alpha-asc';
        }
    };


    /**
     * Helper object that encapsulates the functionality related to sorting.
     *
     * @since 2.0
     */
    var sortHelper = new function() {

        var helper = this;

        /**
         * Compare two models by current sort settings of the collection.
         *
         * Handle empty values as ones with the highest value (they will be at the end on ascending order).
         *
         * @param itemA
         * @param itemB
         * @returns {number} -1|0|1
         * @since 2.0
         */
        var comparator = function(itemA, itemB) {

            var a = itemA[helper.currentSortBy()]() || '', b = itemB[helper.currentSortBy()]() || '';
            var result = 0;

            a = a.toLowerCase();
            b = b.toLowerCase();

            if(0 == a.length && 0 == b.length) {
                result = 0;
            } else if(0 == a.length) {
                result = 1;
            } else if(0 == b.length) {
                result = -1;
            } else {
                result = (a == b ? 0 : (a > b ? 1 : -1));
            }

            return (result * helper.currentSortDirection());
        };


        /** Sort direction, 1 for ascending and -1 for descending. */
        helper.currentSortDirection = ko.observable(1);

        /** Property name. */
        helper.currentSortBy = ko.observable('displayName');

        helper.changeSortStrategy = function(propertyName, direction) {

            if('asc' == direction) {
                direction = 1;
            } else if('desc' == direction) {
                direction = -1;
            } else if(typeof(direction) == 'undefined') {
                direction = helper.currentSortDirection();
            }

            helper.currentSortDirection(direction);
            helper.currentSortBy(propertyName);

        };


        /**
         * Completely handle field definition sorting.
         *
         * Performs the sorting only when initialization is actually finished to avoid resource wasting.
         *
         * @param {string} propertyName Name of the field definition property to sort by. The property must be an
         *    function that returns a string when called without a parameter (for example, a ko.observable).
         * @param {int|string} direction 1|-1|'asc'|'desc'
         * @since 2.0
         */
        helper.sortFieldDefinitions = function(propertyName, direction) {
            helper.changeSortStrategy(propertyName, direction);

            if(self.isInitialized) {
                self.fieldDefinitions.sort(comparator);
            }
        };

    };



    // ------------------------------------------------------------------------
    // Searching and pagination functionality
    // ------------------------------------------------------------------------


    self.searchString = ko.observable('');


    self.currentPage = ko.observable(1);


    self.itemsPerPage = ko.observable(Types.page.fieldControl.itemsPerPage);


    self.totalPages = ko.pureComputed(function(){
        return Math.max(Math.ceil(self.itemCount() / self.itemsPerPage()), 1);
    });


    /**
     * Total count of items that can be displayed now (after filtering).
     */
    self.itemCount = ko.pureComputed(function() {
        return self.fieldDefinitionsFilteredBySearch().length;
    });
    
    
    self.isFirstPage = ko.pureComputed(function() { return ( 1 == self.currentPage() ); });
    
    self.isLastPage = ko.pureComputed(function() { return (self.totalPages() == self.currentPage()); });

    
    self.fieldDefinitionsFilteredBySearch = ko.pureComputed(function() {
        var searchString = self.searchString();
        if(_.isEmpty(searchString)) {

            _.each(self.fieldDefinitions(), function(fieldDefinition) {
                fieldDefinition.isBeingDisplayed(true);
            });

            return self.fieldDefinitions();

        } else {
            return _.filter(self.fieldDefinitions(), function(fieldDefinition) {

                var isMatch = _.some([fieldDefinition.slug(), fieldDefinition.displayName()], function(value) {
                    return (typeof(value) != 'undefined' && value.toLowerCase().indexOf(searchString) > -1);
                });

                fieldDefinition.isBeingDisplayed(isMatch);
                return isMatch;
            });
        }
    });


    /**
     * Safely get/set new current page number.
     * 
     * @since 2.0
     */
    self.currentPageSafe = ko.computed({
        read: function() { return self.currentPage(); },
        write: function(page) {
            if(page < 1) {
                self.currentPage(1);
            } else if(page > self.totalPages()) {
                self.currentPage(self.totalPages());
            } else {
                self.currentPage(page);
            }
        }
    });


    /**
     * Safely change current page.
     * 
     * @param {string} page first|previous|next|last
     * @since 2.0
     */
    self.gotoPage = function(page) {
        switch(page) {
           case 'first':
               self.currentPageSafe(1);
               break;
           case 'previous':
               self.currentPageSafe(self.currentPage() - 1);
               break;
           case 'next':
               self.currentPageSafe(self.currentPage() + 1);
               break;
           case 'last':
               self.currentPageSafe(self.totalPages());
               break;
        } 
    };


    /**
     * The array of actually visible field definitions, after searching and pagination.
     * 
     * @since 2.0
     */
    self.fieldDefinitionsToShow = ko.pureComputed(function() {
        return _.first(_.rest(self.fieldDefinitionsFilteredBySearch(), self.itemsPerPage() * (self.currentPage()-1)), self.itemsPerPage());
    });


    // ------------------------------------------------------------------------
    // Field actions
    // ------------------------------------------------------------------------


    /**
     * Currently displayed message.
     *
     * Text can contain HTML code. Type can be 'info' or 'error' for different message styles.
     */
    self.displayedMessage = ko.observable({text: '', type: 'info'});


    /**
     * Determine how the message is being displayed at the moment.
     *
     * Allowed values are those of the threeModeVisibility knockout binding.
     *
     * @since 2.0
     */
    self.messageVisibilityMode = ko.observable('remove');


    /**
     * Display a message.
     *
     * Overwrites previous message if there was one displayed.
     *
     * @param {string} text Message content.
     * @param {string} type 'info'|'error'
     */
    self.displayMessage = function(text, type) {
        self.hideDisplayedMessage();
        self.displayedMessage({text: text, type: type});
        self.messageVisibilityMode('show');
    };


    /**
     * Hide the message if it is displayed, but leave free space instead of it.
     *
     * If the message was completely hidden before, do nothing.
     */
    self.hideDisplayedMessage = function() {
        if('show' == self.messageVisibilityMode()) {
            self.messageVisibilityMode('hide');
        }
        // Adjust message height to one line.
        self.displayedMessage({text: 'A', type: 'info'});
    };


    /**
     * Hide the message completely.
     */
    self.removeDisplayedMessage = function() {
        self.messageVisibilityMode('remove');
    };


    /**
     * Determine CSS class for the message, based on it's type.
     */
    self.messageNagClass = ko.pureComputed(function() {
        switch(self.displayedMessage().type) {
            case 'error':
                return 'error';
            case 'info':
            default:
                return 'updated';
        }
    });


    /**
     * Number of AJAX actions currently in progress.
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
    self.beginAction = function(fieldDefinitions) {
        self.inProgressActionCount(self.inProgressActionCount() + 1);
        _.each(fieldDefinitions, function(fieldDefinition) {
            fieldDefinition.beginAction();
        });
    };


    /**
     * Indicate that an AJAX action was completed.
     */
    self.finishAction = function(fieldDefinitions) {
        self.inProgressActionCount(self.inProgressActionCount() - 1);
        _.each(fieldDefinitions, function(fieldDefinition) {
            fieldDefinition.finishAction();
        });
    };

    
    /**
     * Run an action on one or more field definitions.
     *
     * Handles all GUI updates as well as the underlying AJAX call.
     *
     * @param {string} fieldAction Name of the action to be performed on a field (see
     *     Types_Ajax::callback_field_control_action() for details).
     * @param {[object]} fieldDefinitions One or more field definitions this action applies to.
     * @param {object|undefined} [data] Custom action-specific data.
     * @param {function|undefined} [successCallback] Callback function that will be called on success. It will get
     *     two parameters, the full AJAX response and the "data" part for convenience.
     * @param {function|undefined} [failCallback] Callback function that will be called on failuer. Same params as above.
     * @since 2.0
     */
    self.doFieldAction = function(fieldAction, fieldDefinitions, data, successCallback, failCallback) {

        self.beginAction(fieldDefinitions);
        self.hideDisplayedMessage();

        //noinspection JSUnresolvedVariable
        var nonce = Types.page.fieldControl.ajaxInfo.fieldAction.nonce;

        var callback = function(messageType, genericMessageString, callback, response, data) {

            var messageText = data.message || genericMessageString;

            // If we have an array of messages, ue that instead.
            if(_.has(data, 'messages') && _.isArray(data.messages)) {
                var messages = _.without(data.messages, '');
                if(0 == messages.length) {
                    // keep the default text
                } else if(1 == messages.length) {
                    messageText = (messages[0]);
                } else {
                    // This will display a simple list of messages.
                    messageText = Types.page.fieldControl.templates.renderUnderscore('messageMultiple', {
                        messages: messages
                    });
                }
            }

            self.displayMessage(messageText, messageType);
            
            if(_.isFunction(callback)) {
                callback(response, data);
            }

            self.finishAction(fieldDefinitions);
        };

        //noinspection JSUnresolvedVariable
        Types.page.fieldControl.doAjax(
            fieldAction,
            nonce,
            self.getFieldDefinitionModels(fieldDefinitions),
            data || {},
            _.partial(callback, 'info', Types.page.fieldControl.strings.misc.genericSuccess || '', successCallback),
            _.partial(callback, 'error', Types.page.fieldControl.strings.misc.undefinedAjaxError || 'undefined error', failCallback)
        );

    };


    /**
     * Obtain up-to-date models from given field definitions.
     *
     * @param {{[Types.page.fieldControl.viewmodels.FieldDefinitionViewModel]}} fieldDefinitions
     * @returns {{[object]}} Models with the same properties as the original model had.
     * @since 2.0
     */
    self.getFieldDefinitionModels = function(fieldDefinitions) {
        return _.map(fieldDefinitions, function(fieldDefinition) { return fieldDefinition.getModelObject(); });
    };


    /**
     * Update field definition viewmodels by new models.
     *
     * If a model's slug matches field definition, it will be updated.
     *
     * @param fieldModels
     * @param sourceDefinitions
     * @since 2.0
     */
    self.updateFieldDefinitionModels = function(fieldModels, sourceDefinitions) {

        _.each(fieldModels, function(fieldModel) {

            if(_.has(fieldModel, 'slug')) {

                // Find the definition by it's slug
                var fieldDefinition = _.find(sourceDefinitions, function (fieldDefinition) {
                    // Comparing also by metaKey because the slug can change under some circumstances.
                    return (fieldDefinition.slug() == fieldModel.slug || fieldDefinition.metaKey() == fieldModel.metaKey);
                });

                if(typeof(fieldDefinition) != 'undefined') {
                    fieldDefinition.updateModelObject(fieldModel);
                } else {
                    // todo report error
                }

            } else {
                // todo report error
            }

        });

    };


    /**
     * Handle user's input on a field action in a generic way.
     *
     * Works for both bulk and single actions.
     *
     * @param {[object]|object} fieldDefinitions One or more selected field definitions.
     * @param {function|null} conflictFilter Function that for a given field definition returns true if the action
     *     cannot be applied on it.
     * @param {string} conflictStringName Name of the string in Types.page.fieldControl.strings.misc that will be used
     *     for the message about conflicting field definitions.
     * @param {string} fieldActionName Name of the field action to be passed through AJAX.
     * @param {function} onSuccess Callback on action success. It will recieve the complete response, response data and
     *     the array of original field definitions (allways an array) as parameters.
     * @param onFailure Callback to be used when there is an error of some kind. Same as onSuccess with only two parameters.
     * @param {object|undefined} actionData Custom action data that will be passed through the AJAX call.
     * @since 2.0
     */
    self.handleFieldActionInput = function(fieldDefinitions, conflictFilter, conflictStringName, fieldActionName, onSuccess, onFailure, actionData) {

        if(!_.isArray(fieldDefinitions)) {
            fieldDefinitions = [fieldDefinitions];
        }

        if(0 == fieldDefinitions.length) {
            // No message is needed because the bulk action mechanism should never allow this.
            console.log('no fields selected');
            return;
        }

        if(_.isFunction(conflictFilter)) {
            var conflictingDefinitions = _.filter(fieldDefinitions, conflictFilter);

            if(0 < conflictingDefinitions.length) {

                var messageText = Types.page.fieldControl.strings.misc[conflictStringName] + ' '
                    + Types.page.fieldControl.strings.misc['unselectAndRetry'];

                self.displayMessage(
                    Types.page.fieldControl.templates.renderUnderscore('messageDefinitionList', {
                        message: messageText,
                        fieldDefinitions: conflictingDefinitions
                    }),
                    'error'
                );
                return;
            }
        }

        self.doFieldAction(fieldActionName, fieldDefinitions, actionData || {}, _.partial(onSuccess, _, _, fieldDefinitions), onFailure);

    };


    /**
     * An object with methods to perform actions on field definitions.
     *
     * Each action accepts an array of field definitions, or a single field definition, as first parameter.
     *
     * @type {{manageWithTypes: function, stopManagingWithTypes: function}}
     * @since 2.0
     */
    self.fieldActions = {

        manageWithTypes: _.partial(
            self.handleFieldActionInput,
            _,
            function(fieldDefinition) {
                // conflict filter
                return fieldDefinition.isUnderTypesControl();
            },
            'fieldsAlreadyManaged',
            'manage_with_types',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
                console.log("fail", response);
                // todo report error
            }
        ),


        stopManagingWithTypes: _.partial(
            self.handleFieldActionInput,
            _,
            function(fieldDefinition) {
                // conflict filter
                return !fieldDefinition.isUnderTypesControl();
            },
            'fieldsAlreadyUnmanaged',
            'stop_managing_with_types',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
                console.log("fail", response);
                // todo report error
            }
        ),


        changeGroupAssignment: _.partial(
            self.handleFieldActionInput,
            _,
            // no conflict filter because there is no bulk action for this
            null,
            null,
            'change_group_assignment',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
            }
        ),


        deleteFields: _.partial(
            self.handleFieldActionInput,
            _,
            function(fieldDefinition) {
                // conflict filter
                return !fieldDefinition.isUnderTypesControl();
            },
            'cannotDeleteUnmanagedFields',
            'delete_field',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.fieldDefinitions.removeAll(fieldDefinitions);
            },
            function(response) {
                // onFailure
            }
        ),
        
        
        changeFieldType: _.partial(
            self.handleFieldActionInput,
            _,
            // no conflict filter needed
            null, 
            null,
            'change_field_type',
            _,
            _
        ),
        
        
        changeFieldCardinality: _.partial(
            self.handleFieldActionInput,
            _,
            // no conflict filter needed
            null,
            null, 
            'change_field_cardinality',
            function(response, data, fieldDefinitions) {
                // onSuccess
                self.updateFieldDefinitionModels(data.results, fieldDefinitions);
            },
            function(response) {
                // onFailure
            }
        ),

        // For referencing form outside the ListingViewModel.
        updateFieldDefinitionModels: self.updateFieldDefinitionModels
    };


    // ------------------------------------------------------------------------
    // Bulk actions
    // ------------------------------------------------------------------------


    //noinspection JSUnresolvedVariable
    /**
     * Array of objects describing available bulk actions.
     *
     * It will be used by knockout to populate the select input field dynamically.
     *
     * @returns {[{value:string,displayName:string,handler:function|undefined}]}
     * @since 2.0
     */
    self.bulkActions = ko.observableArray([
        {
            value: '-1',
            displayName: Types.page.fieldControl.strings.bulkAction.select
        },
        {
            value: 'delete',
            displayName: Types.page.fieldControl.strings.bulkAction.delete,
            handler: function(fieldDefinitions) {
                Types.page.fieldControl.viewmodels.DeleteDialogViewModel(fieldDefinitions, function(isAccepted) {
                    if(isAccepted) {
                        self.fieldActions.deleteFields(fieldDefinitions);
                    }
                }).display();
            }
        },
        {
            value: 'manageWithTypes',
            displayName: Types.page.fieldControl.strings.bulkAction.manageWithTypes,
            handler: function(fieldDefinitions) {
                Types.page.fieldControl.viewmodels.BulkChangeManagementStatusDialogViewModel(fieldDefinitions, true, function(isAccepted) {
                    if(isAccepted) {
                        self.fieldActions.manageWithTypes(fieldDefinitions);
                    }  
                }).display();
            }
        },
        {
            value: 'stopManagingWithTypes',
            displayName: Types.page.fieldControl.strings.bulkAction.stopManagingWithTypes,
            handler: function(fieldDefinitions) {
                Types.page.fieldControl.viewmodels.BulkChangeManagementStatusDialogViewModel(fieldDefinitions, false, function(isAccepted) {
                    if(isAccepted) {
                        self.fieldActions.stopManagingWithTypes(fieldDefinitions);
                    }
                }).display();
            }
        }
    ]);


    self.selectedFieldDefinitions = ko.pureComputed(function() {
        return _.filter(self.fieldDefinitionsToShow(), function(fieldDefinition) {
            return fieldDefinition.isSelectedForBulkAction();
        });
    });


    self.selectedBulkAction = ko.observable('-1');


    self.isBulkActionAllowed = ko.pureComputed(function() {
        return ('-1' != self.selectedBulkAction() && self.selectedFieldDefinitions().length > 0);
    });


    /**
     * Find the selected bulk action by it's value and execute it's handler if possible.
     *
     * @since 2.0
     */
    self.onBulkAction = function() {
        var action = _.findWhere(self.bulkActions(), {value: self.selectedBulkAction()});
        if(typeof(action) != 'undefined' && _.has(action, 'handler') && _.isFunction(action.handler)) {
            action.handler(self.selectedFieldDefinitions());
        }
    };


    /**
     * True if all visible rows are selected for a bulk action, false otherwise.
     * When written to, the value will influence all visible rows.
     * 
     * @since 2.0
     */
    self.allVisibleFieldDefinitionSelection = ko.computed({
        read: function() {
            if(0 == self.fieldDefinitionsToShow().length) {
                return false;
            }
            return _.every(self.fieldDefinitionsToShow(), function(fieldDefinition) {
                return fieldDefinition.isSelectedForBulkAction();
            });
        },
        write: function(value) {
            _.each(self.fieldDefinitionsToShow(), function(fieldDefinition) {
                fieldDefinition.isSelectedForBulkAction(value);
            })
        }
    });


    // ------------------------------------------------------------------------
    // Initialization
    // ------------------------------------------------------------------------


    var init = function() {

        // Fill field definition with data from PHP
        self.fieldDefinitions(_.map(fieldDefinitionModels, function(definitionModel) {
            return new Types.page.fieldControl.viewmodels.FieldDefinitionViewModel(definitionModel, self.fieldActions);
        }));

        ko.applyBindings(self);

        self.currentPage(1);

        // Now we can finally sort
        self.isInitialized = true;
        sortHelper.sortFieldDefinitions('displayName', 'asc');
    };


    init();
};