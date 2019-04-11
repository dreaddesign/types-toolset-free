/*
 * Validation JS
 *
 * - Initializes validation on selector (forms)
 * - Adds/removes rules on elements contained in var wptoolsetValidationData
 * - Checks if elements are hidden by conditionals
 *
 * @see class WPToolset_Validation
 *
 *
 */
var wptValidationForms = [];
var wptValidationDebug = false;
//Contains IDs for CRED form that were already initialised, to prevent multiple initialisation
var initialisedCREDForms = [];

var wptValidation = (function ($) {
    function init() {
        /**
         * add extension to validator method
         */
        $.validator.addMethod("extension", function (value, element, param) {
            param = typeof param === "string" ? param.replace(/,/g, "|") : param;
            if ($(element).attr('res') && $(element).attr('res') != "") {
                return true;
            }
            return ( this.optional(element) || value.match( new RegExp(".(" + param + ")$", "i") ) );
        });

        /**
         * add hexadecimal to validator method
         */
        $.validator.addMethod("hexadecimal", function (value, element, param) {
            return ( value == "" || /(^#[0-9A-F]{6}$)|(^#[0-9A-F]{3}$)/i.test(value) );
        });

        /**
         * add equalto method
         */
        $.validator.addMethod("equalto", function (value, element, param) {
            if (param[1]) {
                return ( value == $("input[name='" + param[1] + "']").val() );
            }
            return false;
        });

        /**
         * add skype to validator method
         */
        $.validator.addMethod("skype", function (value, element, param) {
            return ( value == "" || /^([a-z0-9\.\_\,\-\#]+)$/i.test(value) );
        });

        /**
         * add extension to validator method require
         */
        $.validator.addMethod("required", function (value, element, param) {
            var _name = $(element).attr('name');
            var _value = $(element).val();

            // check if dependency is met
            // Last commits includes "required" parameter so this condition is not necessary.
            /*
            if (!this.depend(param, element)) {
                return "dependency-mismatch";
            }
            */
            switch (element.nodeName.toLowerCase()) {
                case 'select':
                    return _value && $.trim(_value).length > 0;
                case 'input':
                    if (jQuery(element).hasClass("wpt-form-radio")) {
                        var val = jQuery('input[name="' + _name + '"]:checked').val();

                        if (wptValidationDebug) {
                            console.log("radio " + (typeof val != 'undefined' && val && $.trim(val).length > 0));
                        }

                        return ( typeof val != 'undefined' && val && $.trim(val).length > 0 );
                    }

                    var $element = jQuery(element).siblings('input[type="hidden"]').first();
                    var elementFieldType = $element.attr('data-wpt-type');
                    if ($element
                        && !$element.prop("disabled")
                        && ( elementFieldType == 'file'
                            || elementFieldType == 'video'
                            || elementFieldType == 'image' )
                    ) {
                        var val = $element.val();
                        if (wptValidationDebug) {
                            console.log("hidden " + (val && $.trim(val).length > 0));
                        }

                        return ( val && $.trim(val).length > 0 );
                    }

                    if (jQuery(element).attr('type') == "checkbox") {
                        if (wptValidationDebug) {
                            console.log("checkbox " + (element.checked));
                        }
                        return element.checked;
                    }

                    if (jQuery(element).hasClass("hasDatepicker")) {
                        if (wptValidationDebug) {
                            console.log("hasDatepicker");
                        }
                        return false;
                    }

                    if (this.checkable(element)) {
                        if (wptValidationDebug) {
                            console.log("checkable " + (this.getLength(value, element) > 0));
                        }
                        return ( this.getLength(value, element) > 0 );
                    }

                    if (wptValidationDebug) {
                        console.log(_name + " default: " + value + " val: " + _value + " " + ($.trim(_value).length > 0));
                    }

                    return ( $.trim(_value).length > 0 );
                default:
                    return ( $.trim(value).length > 0 );
            }
        });

        /**
         * Add validation method for datepicker adodb_xxx format for date fields
         */
        $.validator.addMethod(
            "dateADODB_STAMP",
            function (a, b) {
                return this.optional(b) || /^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/.test(a) && -12219292800 < a && a < 32535215940
            },
            "Please enter a valid date"
        );

        if (wptValidationDebug) {
            console.log("INIT");
            console.log(wptValidationForms);
        }

        _.each(wptValidationForms, function (formID) {
            //Only apply to non CRED elements, CRED ones will be init on cred_form_ready
            if(formID.indexOf('#cred') == -1){
                _initValidation(formID);
                applyRules(formID);
            }
        });
    }

    function _initValidation(formID) {
        if (wptValidationDebug) {
            console.log("_initValidation " + formID);
        }
        var $form = $(formID);
        $form.validate({
            // :hidden is kept because it's default value.
            // All accepted by jQuery.not() can be added.
            ignore: 'input[type="hidden"]:not(.js-wpt-date-auxiliar),:not(.js-wpt-validate)',
            errorPlacement: function (error, element) {
                error.insertBefore(element);
            },
            highlight: function (element, errorClass, validClass) {
                // Expand container
                $(element).parents('.collapsible').slideDown();
                if (formID == '#post') {
                    var box = $(element).parents('.postbox');
                    if (box.hasClass('closed')) {
                        $('.handlediv', box).trigger('click');
                    }
                }
                $(element).parent('div').addClass('has-error');
                // $.validator.defaults.highlight(element, errorClass, validClass); // Do not add class to element
            },
            unhighlight: function (element, errorClass, validClass) {
                $("input#publish, input#save-post").removeClass("button-primary-disabled").removeClass("button-disabled");
                $(element).parent('div').removeClass('has-error');
                // $.validator.defaults.unhighlight(element, errorClass, validClass);
            },
            invalidHandler: function (form, validator) {
                if (formID == '#post') {
                    $('#publishing-action .spinner').css('visibility', 'hidden');
                    $('#publish').bind('click', function () {
                        $('#publishing-action .spinner').css('visibility', 'visible');
                    });
                    $("input#publish").addClass("button-primary-disabled");
                    $("input#save-post").addClass("button-disabled");
                    $("#save-action .ajax-loading").css("visibility", "hidden");
                    $("#publishing-action #ajax-loading").css("visibility", "hidden");
                }
            },
            errorElement: 'small',
            errorClass: 'wpt-form-error'
        });

        // On some pages the form may not be ready yet at this point (e.g. Edit Term page).
        jQuery(document).ready(function () {
            if (wptValidationDebug) {
                console.log($form.selector);
            }

            jQuery(document).off('submit', $form.selector, null);
            jQuery(document).on('submit', $form.selector, function () {
                if (wptValidationDebug) {
                    console.log("submit " + $form.selector);
                }

                var currentFormId = formID.replace('#', '');
                currentFormId = currentFormId.replace('-', '_');
				if ( ! _.has( window, 'cred_settings_' + currentFormId ) ) {
					return;
				}
                var cred_settings = window[ 'cred_settings_' + currentFormId ];

                if (wptValidationDebug) {
                    console.log("validation...");
                }

                var isAjaxForm = (cred_settings.use_ajax && 1 == cred_settings.use_ajax);
                var isValidForm = $form.valid();

                if (isValidForm) {

                    if (wptValidationDebug) {
                        console.log("form validated " + $form);
                    }

                    $('.js-wpt-remove-on-submit', $(this)).remove();

                    /**
                     * toolset-form-onsubmit-validate-success
                     *
                     * Event triggered when a cred form on submit is validated
                     *
                     * @since 2.5.1
                     */
                    Toolset.hooks.doAction('toolset-form-onsubmit-validation-success', formID, isAjaxForm, cred_settings);

                } else {

                    if (wptValidationDebug) {
                        console.log("form not valid!");
                    }

                    /**
                     * toolset-form-onsubmit-validate-error
                     *
                     * Event triggered when a cred form on submit is NOT validated
                     *
                     * @since 2.5.1
                     */
                    Toolset.hooks.doAction('toolset-form-onsubmit-validation-error', formID, isAjaxForm, cred_settings);
                }

                //If form is an ajax form return false on submit
                if (isAjaxForm) {

                    /**
                     * toolset-ajax-submit
                     *
                     * Event submit of ajax form, it is triggered ONLY when onsubmit belogns to a form that is ajax and it is valid as well
                     *
                     * @since 2.5.1
                     */
                    Toolset.hooks.doAction('toolset-ajax-submit', formID, isValidForm, cred_settings);
                    return false;
                }
            });
        });
    }

	// @bug This event callback is defined in a limbo never executed, hence the callback will never be fired
	// @bug The event lists used here should be space-separated, otherwise it will get fired at an event with a name
	// "js_event_wpv_pagination_completed," (mind the last comma), which obviously does not exist
    $(document).on('js_event_wpv_pagination_completed, js_event_wpv_parametric_search_results_updated', function (event, data) {
        if (typeof wptValidation !== 'undefined') {
            wptValidation.init();
        }
        if (typeof wptCond !== 'undefined') {
            wptCond.init();
        }
        if (typeof wptRep !== 'undefined') {
            wptRep.init();
        }
        if (typeof wptCredfile !== 'undefined') {
            wptCredfile.init('body');
        }
        if (typeof toolsetForms !== 'undefined') {
            toolsetForms.cred_tax = new toolsetForms.CRED_taxonomy();
            if (typeof initCurrentTaxonomy == 'function') {
                initCurrentTaxonomy();
            }
        }

        if (typeof wptDate !== 'undefined') {
            wptDate.init('body');
        }

        if (typeof jQuery('.wpt-suggest-taxonomy-term') && jQuery('.wpt-suggest-taxonomy-term').length) {
            jQuery('.wpt-suggest-taxonomy-term').hide();
        }

        if (typeof credFrontEndViewModel !== 'undefined') {
            credFrontEndViewModel.reloadTinyMCE();
        }
    });

    function isIgnored($el) {
        var ignore = $el.parents('.js-wpt-field').hasClass('js-wpt-validation-ignore') || // Individual fields
            $el.parents('.js-wpt-remove-on-submit').hasClass('js-wpt-validation-ignore'); // Types group of fields
        return ignore;
    }

    function applyRules(container) {
        $('[data-wpt-validate]', $(container)).each(function () {
            _applyRules($(this).data('wpt-validate'), this, container);
        });
    }

    function _applyRules(rules, selector, container) {
        var element = $(selector, $(container));

        if (element.length > 0) {
            if (isIgnored(element)) {
                element.rules('remove');
                element.removeClass('js-wpt-validate');
            } else if (!element.hasClass('js-wpt-validate')) {
                _.each(rules, function (value, rule) {
                    var _rule = {messages: {}};
                    _rule[rule] = value.args;
                    if (value.message !== 'undefined') {
                        _rule.messages[rule] = value.message;
                    }
                    element.rules('add', _rule);
                    element.addClass('js-wpt-validate');
                });
            }
        }
    }

    return {
        init: init,
        applyRules: applyRules,
        isIgnored: isIgnored,
        _initValidation: _initValidation
    };

})(jQuery);

jQuery(document).on('toolset_ajax_fields_loaded', function (evt, data) {
    wptValidation._initValidation('#' + data.form_id);
    wptValidation.applyRules('#' + data.form_id);
});

//cred_form_ready will fire when a CRED form is ready, so we init it's validation rules then
jQuery(document).on('cred_form_ready', function (evt, data) {
    if (initialisedCREDForms.indexOf(data.form_id) == -1) {
        wptValidation._initValidation('#' + data.form_id);
        wptValidation.applyRules('#' + data.form_id);
        initialisedCREDForms.push(data.form_id);
    }
});

jQuery(document).ready(function () {

    //init ready CRED forms
    if( typeof( credFrontEndViewModel ) != 'undefined' ) {
        for(var credFormIDIndex in credFrontEndViewModel.readyCREDForms){
            var credFormID = credFrontEndViewModel.readyCREDForms[credFormIDIndex];
            if(initialisedCREDForms.indexOf(credFormID) == -1) {
                wptValidation._initValidation('#' + credFormID);
                wptValidation.applyRules('#' + credFormID);
                initialisedCREDForms.push(credFormID);
            }
        }
    }

    wptCallbacks.reset.add(function () {
        wptValidation.init();
    });
    wptCallbacks.addRepetitive.add(function (container) {
        wptValidation.applyRules(container);
    });
    wptCallbacks.removeRepetitive.add(function (container) {
        wptValidation.applyRules(container);
    });
    wptCallbacks.conditionalCheck.add(function (container) {
        wptValidation.applyRules(container);
    });
});
