var Toolset = Toolset || {};

Toolset.Gui = Toolset.Gui || {};

/**
 * Main (generic) ViewModel of the listing page.
 *
 * Holds the collection of item ViewModels, handles their sorting and filtering (search).
 *
 * @param {{[]}} itemModels
 * @param {{sortBy:string,itemsPerPage:int}} defaults
 * @param itemSearchFunction
 * @constructor
 * @since 2.2
 */
Toolset.Gui.ListingViewModel = function(itemModels, defaults, itemSearchFunction) {

    var self = this;


    self.isInitialized = false;


    self.items = ko.observableArray();


    // ------------------------------------------------------------------------
    // Sorting functionality
    // ------------------------------------------------------------------------


    self.onSort = function (propertyName) {
        var newDirection = (
            sortHelper.currentSortBy() === propertyName
                ? sortHelper.currentSortDirection() * -1
                : 1 // If it is not the current column it starts with ascending sorting.
        );
        sortHelper.sortItems(propertyName, newDirection);
    };


    /**
     * Determine a current class for an column sorting indicator icon based on property name.
     *
     * @param {string} propertyName Name of property that the column uses for sorting.
     * @returns {string} One or more CSS classes.
     * @since 2.0
     */
    self.sortIconClass = function (propertyName, sortType) {
        if ( 'undefined' === typeof sortType || ['alpha', 'numeric'].indexOf( sortType ) === -1 ) {
            sortType = 'alpha';
        }
        if (sortHelper.currentSortBy() === propertyName) {
            if (1 === sortHelper.currentSortDirection()) {
                return 'fa fa-sort-' + sortType + '-asc';
            } else {
                return 'fa fa-sort-' + sortType + '-desc';
            }
        } else {
            return 'fa sort-icon-inactive fa-sort-' + sortType + '-asc';
        }
    };


    /**
     * Helper object that encapsulates the functionality related to sorting.
     *
     * @since 2.0
     */
    var sortHelper = new function () {

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
        var comparator = function (itemA, itemB) {

            var a = itemA[helper.currentSortBy()]() || '', b = itemB[helper.currentSortBy()]() || '';
            var result = 0;

            a = a.toLowerCase();
            b = b.toLowerCase();

            if (0 === a.length && 0 === b.length) {
                result = 0;
            } else if (0 === a.length) {
                result = 1;
            } else if (0 === b.length) {
                result = -1;
            } else {
                result = (a === b ? 0 : (a > b ? 1 : -1));
            }

            return (result * helper.currentSortDirection());
        };


        /** Sort direction, 1 for ascending and -1 for descending. */
        helper.currentSortDirection = ko.observable(1);

        /** Property name. */
        helper.currentSortBy = ko.observable(defaults.sortBy);


        helper.changeSortStrategy = function (propertyName, direction) {

            if ('asc' === direction) {
                direction = 1;
            } else if ('desc' === direction) {
                direction = -1;
            } else if (typeof(direction) === 'undefined') {
                direction = helper.currentSortDirection();
            }

            helper.currentSortDirection(direction);
            helper.currentSortBy(propertyName);

        };


        /**
         * Completely handle item sorting.
         *
         * Performs the sorting only when initialization is actually finished to avoid resource wasting.
         *
         * @param {string} propertyName Name of the item property to sort by. The property must be an
         *    function that returns a string when called without a parameter (for example, a ko.observable).
         * @param {int|string} direction 1|-1|'asc'|'desc'
         * @since 2.0
         */
        helper.sortItems = function (propertyName, direction) {
            helper.changeSortStrategy(propertyName, direction);

            if (self.isInitialized) {
                self.items.sort(comparator);
            }
        };

    };

    /**
     * Update sorted items
     *
     * It is necessary when a item propety is updated and
     * the list is ordered by it
     *
     * @since 2.3
     */
    self.updateSort = function () {
        sortHelper.sortItems(sortHelper.currentSortBy(), sortHelper.currentSortDirection());
    };

    /**
     * Returns current sort by property
     *
     * If a item is updated, sorting will be necessary if the property updated
     * is the same that the 'sort by' property
     *
     * @since 2.3
     */
    self.getCurrentSortBy = sortHelper.currentSortBy;


    // ------------------------------------------------------------------------
    // Searching and pagination functionality
    // ------------------------------------------------------------------------


    self.searchString = ko.observable('');


    self.currentPage = ko.observable(1);


    self.itemsPerPage = ko.observable(defaults.itemsPerPage);


    self.totalPages = ko.pureComputed(function () {
        var pageCount = Math.max(Math.ceil(self.itemCount() / self.itemsPerPage()), 1);
        return ( jQuery.isNumeric(pageCount) ? pageCount : 1);
    });


    /**
     * Total count of items that can be displayed now (after filtering).
     */
    self.itemCount = ko.pureComputed(function () {
        return self.itemsFilteredBySearch().length;
    });


    self.isFirstPage = ko.pureComputed(function () {
        return ( 1 === self.currentPage() );
    });

    self.isLastPage = ko.pureComputed(function () {
        return (self.totalPages() === self.currentPage());
    });


    self.itemsFilteredBySearch = ko.pureComputed(function () {
        var searchString = self.searchString();
        if (_.isEmpty(searchString)) {

            _.each(self.items(), function (item) {
                item.isBeingDisplayed(true);
            });

            return self.items();

        } else {
            return _.filter(self.items(), function (item) {

                var isMatch = itemSearchFunction(item, searchString);

                item.isBeingDisplayed(isMatch);
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
        read: function () {
            return self.currentPage();
        },
        write: function (page) {
            page = parseInt(page);
            if (page < 1) {
                self.currentPage(1);
            } else if (page > self.totalPages()) {
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
    self.gotoPage = function (page) {
        switch (page) {
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
     * The array of actually visible items, after searching and pagination.
     *
     * @since 2.0
     */
    self.itemsToShow = ko.pureComputed(function () {
        return _.first(_.rest(self.itemsFilteredBySearch(), self.itemsPerPage() * (self.currentPage() - 1)), self.itemsPerPage()) || [];
    });


    /**
     * When a search term changes, always show the first page, otherwise no results might be visible.
     *
     * @since m2m
     */
    self.searchString.subscribe(function(searchString) {
        if(searchString.length > 0) {
            self.gotoPage('first');
        }
    });


    // ------------------------------------------------------------------------
    // Item actions
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
    self.displayMessage = function (text, type) {
        self.hideDisplayedMessage();
        self.displayedMessage({text: text, type: type});
        self.messageVisibilityMode('show');
        if ( type !== 'error' ) {
            self.autoHideDislayedMessage( text );
        }
    };


    /**
     * Hide the message if it is displayed, but leave free space instead of it.
     *
     * If the message was completely hidden before, do nothing.
     */
    self.hideDisplayedMessage = function () {
        if ('show' === self.messageVisibilityMode()) {
            self.messageVisibilityMode('hide');
        }
        // Adjust message height to one line.
        self.displayedMessage({text: 'A', type: 'info'});
    };


    /**
     * Hide the message completely.
     */
    self.removeDisplayedMessage = function () {
        self.messageVisibilityMode('remove');
    };


    /**
     * Auto hide dislayed message after a time depending on text long
     *
     * @param {string} text Text needed for timing calculation
     * @since m2m
     */
    self.autoHideDislayedMessage = function( text ) {
        var miliseconds = Math.max( Math.min( text.length * 50, 2000 ), 7000 );
        setTimeout( self.removeDisplayedMessage, miliseconds );
    }


    /**
     * Determine CSS class for the message, based on it's type.
     */
    self.messageNagClass = ko.pureComputed(function () {
        switch (self.displayedMessage().type) {
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
    self.isSpinnerVisible = ko.pureComputed(function () {
        return (self.inProgressActionCount() > 0);
    });


    /**
     * Indicate a beginning of an AJAX action.
     *
     * Make sure you also call finishAction() afterwards, no matter what the result is.
     */
    self.beginAction = function (items) {
        self.inProgressActionCount(self.inProgressActionCount() + 1);
        _.each(items, function (item) {
            item.beginAction();
        });
    };


    /**
     * Indicate that an AJAX action was completed.
     */
    self.finishAction = function (items) {
        self.inProgressActionCount(self.inProgressActionCount() - 1);
        _.each(items, function (item) {
            item.finishAction();
        });
    };


    /**
     * An object with methods to perform actions on items.
     *
     * Each action should an array of items, or a single item, as first parameter.
     *
     * @since 2.0
     */
    self.itemActions = {};


    // ------------------------------------------------------------------------
    // Bulk actions
    // ------------------------------------------------------------------------


    //noinspection JSUnresolvedVariable
    /**
     * Array of objects describing available bulk actions.
     *
     * It will be used by knockout to populate the select input items dynamically.
     *
     * @returns {[{value:string,displayName:string,handler:function|undefined}]}
     * @since 2.0
     */
    self.bulkActions = ko.observableArray([]);


    self.selectedItems = ko.pureComputed(function () {
        return _.filter(self.itemsToShow(), function (item) {
            return item.isSelectedForBulkAction();
        });
    });


    self.selectedBulkAction = ko.observable('-1');


    self.isBulkActionAllowed = ko.pureComputed(function () {
        return ('-1' !== self.selectedBulkAction() && self.selectedItems().length > 0);
    });


    /**
     * Find the selected bulk action by it's value and execute it's handler if possible.
     *
     * @since 2.0
     */
    self.onBulkAction = function () {
        var action = _.findWhere(self.bulkActions(), {value: self.selectedBulkAction()});
        if (typeof(action) !== 'undefined' && _.has(action, 'handler') && _.isFunction(action.handler)) {
            action.handler(self.selectedItems());
        }
    };


    /**
     * True if all visible rows are selected for a bulk action, false otherwise.
     * When written to, the value will influence all visible rows.
     *
     * @since 2.0
     */
    self.allVisibleItemsSelection = ko.computed({
        read: function () {
            if (0 === self.itemsToShow().length) {
                return false;
            }
            return _.every(self.itemsToShow(), function (item) {
                return item.isSelectedForBulkAction();
            });
        },
        write: function (value) {
            _.each(self.itemsToShow(), function (item) {
                item.isSelectedForBulkAction(value);
            })
        }
    });


    // ------------------------------------------------------------------------
    // Initialization
    // ------------------------------------------------------------------------


    self.createItemViewModels = function (itemModels) {
        // to be overridden
    };


    self.init = function () {

        self.createItemViewModels(itemModels);

        ko.applyBindings(self);

        self.currentPage(1);

        // Now we can finally sort
        self.isInitialized = true;
        sortHelper.sortItems(sortHelper.currentSortBy(), sortHelper.currentSortDirection());
    };

    /**
     * Some functions are needed in 'child' ListViewModels
     * for example sorting
     * @since 2.3
     */
    return self;
};
