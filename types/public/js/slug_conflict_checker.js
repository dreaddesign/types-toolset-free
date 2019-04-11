var Types = Types || {};

/**
 * Helper for checking an input field for slug conflicts.
 *
 * See the build() method for details.
 *
 * @type {slugConflictChecker}
 * @since 2.1
 */
Types.slugConflictChecker = new function() {

    var self = this;

    /**
     * Checker object prototype.
     *
     * @constructor
     */
    var Checker = function(element, domains, currentDomain, currentId, nonce, callback) {

        var checker = this;

        /**
         * Invoke the check for conflicts manually.
         */
        checker.check = function() {

            WPV_Toolset.Utils.Ajax.call(
                {
                    action: 'types_check_slug_conflicts',
                    wpnonce: nonce,
                    domains: domains,
                    value: element.val(),
                    exclude: {
                        domain: currentDomain,
                        id: currentId
                    }
                },

                /**
                 * @param response
                 * @param {{isConflict:boolean,displayMessage:string}} responseData
                 */
                function(response, responseData) {
                    if(responseData.isConflict) {
                        callback(true, responseData.displayMessage);
                    } else {
                        callback(false);
                    }
                },
                function() {
                    // Do nothing
                }
            );
        };

        /**
         * Bind to the change events of the provided elements and invoke check() when needed.
         *
         * @since 2.1
         */
        checker.bind = function() {
            element.on('change paste keyup input propertychange', _.debounce(function () {
                checker.check();
            }, 1000));
        };

    };


    /**
     * Build a slug config checker for a specific scenario.
     *
     * @param element A jQuery element that should be observed for slug conflicts.
     * @param {[string]} domains Possible domains of slug conflicts. Check Types_Ajax_Handler_Check_Slug_Conflicts 
     *     for details.
     * @param {string} currentDomain Domain of the object that is being edited.
     * @param {string|int} currentId ID of the object that is being edited (it needs to be skipped while checking
     *     for conflicts).
     * @param {string} nonce A valid nonce for the types_check_slug_conflicts AJAX call.
     * @param {function(isSuccess:bool,displayMessage:string|undefined)} callback Function that handles checking result. 
     * @returns {Checker} The initialized checker object.
     * @since 2.1
     */
    self.build = function(element, domains, currentDomain, currentId, nonce, callback) {
        return new Checker(element, domains, currentDomain, currentId, nonce, callback); 
    };
    
};