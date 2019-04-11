var Toolset = Toolset || {};

Toolset.Gui = Toolset.Gui || {};

/**
 * Abstract controller for Toolset admin pages.
 *
 * It offers generic support for loading data passed from PHP in a safe way, initializing Knockout, helper function
 * for accessing underscore templates provided by PHP, loading dependencies and, finally, loading the main page viewmodel.
 *
 * See Toolset_Gui_Base for thorough documentation.
 *
 * @constructor
 * @since 2.2
 */
Toolset.Gui.AbstractPage = function() {

    var self = this;


    // Apply mixins which can be also used in a stand-alone fashion otherwise.
    Toolset.Gui.Mixins.CreateDialog.call(self);
    Toolset.Gui.Mixins.KnockoutExtensions.call(self);

    /**
     * Log all arguments to console if debugging is turned on.
     *
     * @since 2.0
     */
    self.debug = function () {
        if (self.isDebug) {
            console.log.apply(console, arguments);
        }
    };


    self.isDebug = false;


    /**
     * Log an arbitrary number of arguments.
     *
     * @since 2.2
     */
    self.log = function() {
        console.log.apply(console, arguments);
    };


    /**
     * Read model data from PHP passed in a standard way through Toolset_Gui_Base and Twig.
     *
     * The result will be stored in self.modelData.
     *
     * @param {string} [selector] CSS selector to target the element with the encoded model data. Defaults to
     *     the Toolset GUI Base default value, so better leave it alone. It is taken into account only first time
     *     this function is called.
     *
     * @returns {*} The loaded model data.
     *
     * @since 2.2
     */
    self.getModelData = function(selector) {

        if(!_.has(this, 'modelData')) {
            if(typeof(selector) == 'undefined') {
                selector = '#toolset_model_data';
            }

            self.modelData = jQuery( selector ).length
                ? jQuery.parseJSON(WPV_Toolset.Utils.editor_decode64(jQuery(selector).html()))
                : false;
        }

        return self.modelData;
    };


    /**
     * Safely retrieve a string from modelData.
     *
     * It expects the string to be placed in modelData.strings.
     *
     * @param {string|[string]} stringPath Name of the string or its path
     *     (['path', 'to', 'string'] for modelData.strings.path.to.string).
     * @returns {string} The requested string or an empty string.
     * @since m2m
     */
    self.getString = function(stringPath) {

        var modelData = self.getModelData();

        if (!_.has(modelData, 'strings')) {
            return '';
        }

        var getString = function (stringPath, source) {

            if (_.isArray(stringPath)) {

                if(stringPath.length === 1) {
                    return getString(_.first(stringPath), source);
                }

                var key = _.head(stringPath);
                var subpath = _.tail(stringPath);

                if (!_.has(source, key)) {
                    return '';
                }

                return getString(subpath, source[key]);
            } else if (_.isString(stringPath) && _.has(source, stringPath)) {
                return source[stringPath];
            }

            return '';

        };

        return getString(stringPath, modelData['strings']);
    };


    /**
     * Initialize the getter function for templates.
     *
     * Creates a self.templates helper with functions for retrieving and rendering a template. If used correctly,
     * only self.templates.renderUnderscore will be needed.
     *
     * Can be extended to allow for different types of templates in the future.
     *
     * The set of available templates is determined by the "templates" property of model data.
     *
     * @since 2.2
     */
    self.initTemplates = function() {

        var modelData = self.getModelData();

        if( _.has(modelData, 'templates') && _.isObject(_.property('templates')(modelData))) {

            self.templates = new function() {

                var templates = this;

                templates.raw = _.property('templates')(modelData);

                /**
                 * @param {string} templateName
                 * @returns {string} Raw template content.
                 */
                templates.getRawTemplate = function(templateName) {
                    if(_.has(templates.raw, templateName)) {
                        return templates.raw[templateName];
                    } else {
                        self.log('Template "' + templateName + '" not found.');
                        return '';
                    }
                };

                templates.compiledUnderscoreTemplates = {};

                /**
                 * @param {string} templateName
                 * @returns {function} Compiled underscore template
                 */
                templates.getUnderscoreTemplate = function(templateName) {
                    if(!_.has(templates.compiledUnderscoreTemplates, templateName)) {
                        templates.compiledUnderscoreTemplates[templateName] = _.template(templates.getRawTemplate(templateName));
                    }
                    return templates.compiledUnderscoreTemplates[templateName];
                };


                /**
                 * Compile an underscore template (with using cache) and render it.
                 *
                 * @param {string} templateName
                 * @param {object} context Underscore context for rendering the template.
                 * @returns {string} Rendered markup.
                 */
                templates.renderUnderscore = function(templateName, context) {
                    var compiled = templates.getUnderscoreTemplate(templateName);
                    return compiled(context);
                };

            };
        }
    };

    /**
     * This will be called before the first step of controller initialization.
     *
     * @since 2.2
     */
    self.beforeInit = function() {};


    /**
     * This will be called as the last step of the controller initialization.
     *
     * @since 2.2
     */
    self.afterInit = function() {};


    /**
     * Load dependencies (e.g. by head.js) and continue by calling the nextStep callback when ready.
     *
     * To be overridden.
     *
     * @param {function} nextStep
     * @since 2.2
     */
    self.loadDependencies = function(nextStep) { nextStep(); };

    /**
     * Create and initialize the main ViewModel for the page.
     *
     * To be overridden.
     *
     * @return {*}
     * @since 2.2
     */
    self.getMainViewModel = function() {};


    /**
     * Get the jQuery element that wraps the whole page.
     *
     * @returns {*}
     * @since 2.2
     */
    self.getPageContent = function() {
        return jQuery('#toolset-page-content');
    };


    /**
     * Initialize the main viewmodel.
     *
     * That means creating it and then hiding the spinner that was displayed by default, and displaying the
     * wrapper for the main page content that was hidden by default.
     *
     * @since 2.2
     */
    self.initMainViewModel = function() {

        self.viewModel = self.getMainViewModel();

        var pageContent = self.getPageContent();

        // Show the listing after it's been fully rendered by knockout.
        pageContent.find('.toolset-page-spinner').hide();
        pageContent.find('.toolset-actual-content-wrapper').show();

    };


    /**
     * The whole initialization sequence.
     *
     * @since 2.2
     */
    self.init = function() {
        self.beforeInit();

        self.initTemplates();
        self.initKnockout();

        self.loadDependencies(function() {
            self.initMainViewModel();

            self.afterInit();
        });

    };

};
