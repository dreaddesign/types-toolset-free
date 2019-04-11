/**
 * @author Riccardo Strobbia
 * @version 1.4
 * @revision 18/01/2016 17:08
 */
//Backbone.Model Overrides

window.Toolset = window.Toolset || {};

//Backbone.View Overrides
if (Backbone && Backbone.View) {
    Backbone.View.prototype.eventDispatcher = _.extend({}, Backbone.Events);
}


if (typeof WPV_Toolset == 'undefined') {
    var WPV_Toolset = {};
    WPV_Toolset.message = {};
    WPV_Toolset.message.container = null;
}

if (typeof WPV_Toolset.Utils == 'undefined') WPV_Toolset.Utils = {};

WPV_Toolset.Utils.eventDispatcher = _.extend({}, Backbone.Events);

WPV_Toolset.Utils.restoreEventPropagation = function (event) {
    if (jQuery.browser.mozilla) {
        return event;
    }
    if (event.isImmediatePropagationStopped() === false && event.isPropagationStopped() === false) {
        return event;
    }

    if (typeof event.originalEvent === undefined) {
        return event;
    }

    var refEvent = event.originalEvent;

    try {
        refEvent.cancelBubble = false;
        refEvent.defaultPrevented = false;
        refEvent.returnValue = true;
        refEvent.timeStamp = ( new Date() ).getTime();
    } catch (e) {
        //    console.log(e.message );
        return event;
    }


    if (event.target.dispatchEvent) {

        try {
            event.target.dispatchEvent(refEvent);
        } catch (e) {

            return;
        }
    } else if (event.target.fireEvent) {

        event.target.fireEvent(refEvent);
    }

    return refEvent;
};

WPV_Toolset.Utils.do_ajax_post = function (params, callback_object) {
    jQuery.post(ajaxurl, params, function (response) {

        if ((typeof(response) !== 'undefined') && response !== null && ( response.message || response.Data || response.data )) {

            if (callback_object && callback_object.success && typeof callback_object.success == 'function')
                callback_object.success.call(this, response, params);
            WPV_Toolset.Utils.eventDispatcher.trigger('on_ajax_success_' + params.action, response, params);
        }
        else if ((typeof(response) !== 'undefined') && response !== null && response.error) {

            if (callback_object && callback_object.error && typeof callback_object.error == 'function')
                callback_object.error.call(this, response, params);
            WPV_Toolset.Utils.eventDispatcher.trigger('on_ajax_error_' + params.action, response, params);
        }
    }, 'json')
        .fail(function (jqXHR, textStatus, errorThrown) {
            console.log('Ajax call failed', textStatus, errorThrown)
            if (callback_object && callback_object.fail && typeof callback_object.fail == 'function')
                callback_object.fail.call(this, errorThrown);
            WPV_Toolset.Utils.eventDispatcher.trigger('on_ajax_fail_' + params.action, textStatus, errorThrown, params);
        })
        .always(function () {
            //console.log( arguments );
            if (callback_object && callback_object.always && typeof callback_object.always == 'function')
                callback_object.always.call(this, arguments);
            WPV_Toolset.Utils.eventDispatcher.trigger('on_ajax_complete_' + params.action, arguments, params);
        });
};

;(function ($, window, document, undefined) {

    // Create the defaults once
    var pluginName = "wpvToolsetMessage",
        dataPlugin = "plugin_" + pluginName,
        defaults = {
            text: "Enter a customized text to be displayed",
            type: '',
            inline: false,
            position: "after",
            header: false,
            headerText: false,
            close: false,
            use_this: true,
            fadeIn: 100,
            fadeOut: 100,
            stay: false,
            onClose: false,
            onOpen: false,
            onDestroy: false,
            dontShowAgain: null,
            dontShowAgainText: '',
            args: [],
            referTo: null,
            offestX: -20,
            offsetY: 0,
            classname: '',
            stay_for: 1200, // Ignored when 'msPerCharacter is given.
            msPerCharacter: 50 // Ignered when 'stay_for' is given. This value is multiplied by the number of defaults.text characters count.
        },
        has_stay = false,
        is_open = false,
        prev = null,
        prev_text = '';

    // The actual plugin constructor
    function Plugin(element, options) {
        var self = this;

        self.container = $(element);

        self.prms = $.extend({}, defaults, options);
        self._defaults = defaults;
        self._name = pluginName;

        self.box = null;
        self.header = null;
        self.remove = null;
        self.tag = self.prms.inline ? 'span' : 'p';
        self.bool = false;

        if (typeof (options.stay_for) === 'undefined' && typeof(self.prms.msPerCharacter) === 'number') { // If stay_for parameter wasn't passes when the plugin wass called AND msPerCharacter has correct type
            self.prms.stay_for = self.prms.text.length * self.prms.msPerCharacter;
        }

    }

    Plugin.prototype = {
        init: function () {
            var self = this;

            if (self.container.data('has_message')) {
                self.destroy();
            }

            if (self.container.children().length > 0) {
                self.container.children().each(function (i) {
                    if ($(this).text() == self.prms.text) {
                        self.bool = true;
                    }
                });
            }

            if (self.bool) return;

            if (has_stay) {
                if (prev) {
                    var rem = prev;
                    prev = null;
                    has_stay = false;
                    is_open = false;
                    rem.fadeTo(0, 0, function () {
                        rem.remove();
                        rem = null;
                    });
                }
            }

            if (self.prms.header && self.prms.headerText) {
                self.box = $('<div class="toolset-alert toolset-alert-' + self.prms.type + ' ' + self.prms.classname + '" />');
                self.header = $('<h2 class="toolset-alert-self.header" />');
                self.box.append(self.header);
                self.header.text(self.prms.headerText);
                self.box.append('<' + self.tag + '></' + self.tag + '>');
                self.box.find(self.tag).html(self.prms.text);
            }
            else {
                self.box = $('<' + self.tag + ' class="toolset-alert toolset-alert-' + self.prms.type + ' ' + self.prms.classname + '" />');
                self.box.html(self.prms.text);
            }

            if (self.prms.dontShowAgain && typeof  self.prms.dontShowAgain === 'function' && self.prms.dontShowAgainText !== '') {
                self.$dontContainer = $('<span class="dont-wrap"></span>')
                self.$dont_show = $('<input type="checkbox" class="toolset-alert-not-again js-icon-not-again">');
                self.$dont_label = $('<label class="toolset-alert-not-again-label" for="toolset-alert-not-again"></label>');
                self.$dont_label.text(self.prms.dontShowAgainText);
                self.$dontContainer.append(self.$dont_show, self.$dont_label);
                self.box.append(self.$dontContainer);

                self.prms.dontShowAgain.call(self.$dont_show, self, self.prms.args);
            }

            if (self.prms.close) {
                self.remove = $('<i class="toolset-alert-close fa fa-times-circle icon-remove-sign js-icon-remove-sign"></i>');
                self.box.append(self.remove);
                self.remove.on('click', function (event) {
                    self.wpvMessageRemove();
                });
            }


            //if( is_open ) self.wpvMessageRemove();
            if (self.prms.position == 'before') {
                self.container.prepend(self.box);
            } else {
                self.container.append(self.box);
            }
            self.container.data('has_message', true);
            self.box.hide();

            if (null !== self.prms.referTo) {
                self.box.css({
                    "position": "absolute",
                    "z-index": 10000,
                    "top": self.prms.referTo.position().top + self.prms.offestY + "px",
                    "left": self.prms.referTo.position().left + self.prms.referTo.width() + self.prms.offestX + "px"
                });
            }

            self.container.data('message-box', self.box);

            self.box.fadeTo(null != prev ? 0 : self.prms.fadeIn, 1, function () {
                $(this).trigger('wpv-message-open');
                prev = $(this);
                prev_text = self.prms.text;
                is_open = true;
                if (self.prms.onOpen && typeof self.prms.onOpen == 'function') {
                    self.prms.onOpen.apply(self, self.prms.args);
                }
                if (self.prms.stay) {
                    has_stay = true;
                }
                else {
                    var remove_message = _.bind(self.wpvMessageRemove, self);
                    _.delay(remove_message, self.prms.stay_for);
                    //self.wpvMessageRemove();
                }
            });

            return self;
        },
        wpvMessageRemove: function () {

            var self = this;

            if (self.box || self.container.data('message-box')) {
                var box = self.box || self.container.data('message-box');

                box.fadeTo(self.prms.fadeOut, 0, function () {
                    $(this).trigger('wpv-message-remove');
                    is_open = false;
                    prev = null;
                    prev_text = '';
                    has_stay = false;
                    if (self.prms.onClose && typeof self.prms.onClose == 'function') {
                        self.prms.onClose.apply(self, self.prms.args);
                    }

                    $(this).remove();
                    self.container.data('has_message', false);
                    self.container.data('message-box', null);
                    self.box = null;
                });
            }

            return self;
        },
        destroy: function () {
            $(this).trigger('wpv-message-remove');
            this.container.empty();
            if (this.prms.onDestroy && typeof this.prms.onDestroy == 'function') {
                this.prms.onDestroy.apply(this, this.prms.args);
            }
            this.box = null;
            this.container.data('message-box', null);
            this.container.data('has_message', false);
        },
        has_message: function () {
            return this.container.data('has_message');
        }
    };


    $.fn[pluginName] = function (arg) {

        return this.each(function () {
            var args, instance;

            if (!( $(this).data(dataPlugin) instanceof Plugin )) {
                // if no instance, create one
                $(this).data(dataPlugin, new Plugin($(this), arg));
            }
            // do not use this one if you want the plugin to be a singleton bound to the DOM element
            else {
                // if instance delete reference and do another one
                $(this).data(dataPlugin, null);
                $(this).data(dataPlugin, new Plugin($(this), arg));
            }

            instance = $(this).data(dataPlugin);

            instance.element = $(this);

            // call Plugin.init( arg )
            if (typeof arg === 'undefined' || typeof arg === 'object') {

                if (typeof instance['init'] === 'function') {
                    instance.init(arg);
                }

                // checks that the requested public method exists
            } else if (typeof arg === 'string' && typeof instance[arg] === 'function') {

                // copy arguments & remove function name
                args = Array.prototype.slice.call(arguments, 1);

                // call the method
                return instance[arg].apply(instance, args);

            } else {

                $.error('Method ' + arg + ' does not exist on jQuery.' + pluginName);

            }
        });
    };
})(jQuery, window, document);

jQuery(function ($) {
    $.each($('.js-show-toolset-message:not(.js-show-toolset-message-inited)'), function () {
        $(this)
            .addClass('js-show-toolset-message-inited')
            .show()
            .wpvToolsetHelp();
    });
});

if (typeof jQuery.fn.wpvToolsetHelp === 'undefined') {

    /* Help messages */
    (function ($) {

        $.fn.wpvToolsetHelp = function (options) {

            var thiz = this;

            var $container = this;
            var prms = $.extend({
                content: ( thiz.contents().length !== 0 ) ? thiz.contents() : "Enter a customized text to be displayed",
                tutorialButtonText: ( typeof(thiz.data('tutorial-button-text')) !== 'undefined' ) ? thiz.data('tutorial-button-text') : null,
                tutorialButtonURL: ( typeof(thiz.data('tutorial-button-url')) !== 'undefined' ) ? thiz.data('tutorial-button-url') : null,
                linkText: ( typeof(thiz.data('link-text')) !== 'undefined' ) ? thiz.data('link-text') : null,
                linkURL: ( typeof(thiz.data('link-url')) !== 'undefined' ) ? thiz.data('link-url') : null,
                footer: ( typeof(thiz.data('footer')) !== 'undefined' ) ? thiz.data('footer') : false,
                classname: ( typeof(thiz.data('classname')) !== 'undefined' ) ? thiz.data('classname') : '',
                close: ( typeof(thiz.data('close')) !== 'undefined' ) ? thiz.data('close') : true,
                hidden: ( typeof(thiz.data('hidden')) !== 'undefined' ) ? thiz.data('hidden') : false,
                onClose: false,
                args: []
            }, options);

            if ($.type(prms.content) === 'string') {
                prms.content = $('<p>' + prms.content + '</p>');
            }

            var $box = $('<div class="toolset-help ' + prms.classname + '"><div class="toolset-help-content"></div><div class="toolset-help-sidebar"></div></div>');

            var $footer = $('<div class="toolset-help-footer"><button class="js-toolset-help-close js-toolset-help-close-forever button-secondary">' + toolset_utils_texts.wpv_dont_show_it_again + '</button><button class="js-toolset-help-close js-toolset-help-close-once button-primary">' + toolset_utils_texts.wpv_close + '</button></div>');

            if (prms.footer === true) {
                $footer.appendTo($box);
            }

            prms.content.appendTo($box.find('.toolset-help-content'));

            this.wpvHelpRemove = function () {
                if ($box)
                    $box.fadeOut('fast', function () {
                        //    $(this).remove();
                        if (prms.onClose && typeof prms.onClose === 'function') {
                            prms.onClose.apply($container, prms.args);
                        }
                    });
                return this;
            };

            if ((prms.tutorialButtonText && prms.tutorialButtonURL) || (prms.linkText && prms.linkURL)) {
                var $toolbar = $('<p class="toolset-help-content-toolbar"></p>');
                $toolbar.appendTo($box.find('.toolset-help-content'));
                if (prms.tutorialButtonText && prms.tutorialButtonURL) {
                    $('<a href="' + prms.tutorialButtonURL + '" class="btn">' + prms.tutorialButtonText + '</a>').appendTo($toolbar);
                }
                if (prms.linkText && prms.linkURL) {
                    $('<a href="' + prms.linkURL + '">' + prms.linkText + '</a>').appendTo($toolbar);
                }
            }

            if (prms.close === true) {
                $('<i class="icon-remove fa fa-times js-toolset-help-close js-toolset-help-close-main"></i>').appendTo($box);
            }

            // bind close event to all close buttons
            var $closeButtons = $box.find('.js-toolset-help-close');
            if ($closeButtons.length !== 0) {
                $closeButtons.on('click', function () {
                    $container.wpvHelpRemove();
                });
            }

            $box.appendTo($container).hide();
            if ($container.hasClass('js-show-toolset-message')) {
                $box.unwrap();
            }
            if (prms.hidden === false) {
                $box.fadeIn('fast');
            }

            return this;
        };

    })(jQuery);

}

(function ($) {
    $.fn.insertAtIndex = function (index, selector) {
        var opts = $.extend({
            index: 0,
            selector: '<div/>'
        }, {index: index, selector: selector});
        return this.each(function () {
            var p = $(this);
            var i = ($.isNumeric(opts.index) ? parseInt(opts.index, 10) : 0);
            if (i <= 0)
                p.prepend(opts.selector);
            else if (i > p.children().length - 1)
                p.append(opts.selector);
            else
                p.children().eq(i).before(opts.selector);
        });
    };
})(jQuery);

(function ($) {

    $.fn.loaderOverlay = function (action, options)
        // action: 'show'|'hide' attributes are optional.
        // options: fadeInSpeed, fadeOutSpeed, displayOverlay, class. attributes are optional
    {

        var defaults = {
            fadeInSpeed: 'fast',
            fadeOutSpeed: 'fast',
            displayLoader: true,
            css: {
                "opacity": "0.4"
            },
            class: null
        };

        var prms = $.extend(defaults, options);
        var $overlayContainer = this;
        var $overlayEl = $('<div class="loader-overlay" />');

        var showOverlay = function () {
            if (!$overlayContainer.data('has-overlay')) {
                $overlayEl
                    .appendTo($overlayContainer)
                    .css(prms.css)
                    .hide()
                    .fadeIn(prms.fadeInSpeed, function () {
                        $overlayContainer.data('has-overlay', true);
                        $overlayContainer.data('overlay-el', $overlayEl);
                        if (_.isObject(options) && typeof options.onOpen === 'function') {
                            options.onOpen.call(this, arguments);
                        }
                    });
            }
        };

        var hideOverlay = function () {
            if ($overlayContainer.data('has-overlay')) {
                $overlayContainer.data('overlay-el')
                    .fadeOut(prms.fadeOutSpeed, function () {
                        $overlayEl.detach().remove();
                        $overlayContainer.data('has-overlay', false);
                        if (options && typeof options.onRemove === 'function') {
                            options.onRemove.call(this, arguments);
                        }
                    });
            }
        };

        if (prms.class !== null) {
            $overlayEl.addClass(prms.class);
        }
        if (prms.displayLoader) {
            $('<div class="preloader" />').appendTo($overlayEl);
        }

        if (typeof(action) !== 'undefined') { // When 'action' parameter is given

            if (action === 'show') {
                showOverlay();
            }
            else if (action === 'hide') {
                hideOverlay();
            }

        }
        else { // when the method is called without 'action' parameter

            if ($overlayContainer.data('has-overlay')) { // hide overlay if it's displayed
                hideOverlay();
            }
            else { // show overlay if not
                showOverlay();
            }

        }

        return this;
    };

})(jQuery);

(function ($) {
    /*
     Basic usage:
     $element.ddlWpPointer(); // will show a pointer if it's hidden OR hide a pointer if it's shown

     1. $element have to be valid jQuery selector
     2. data-toolipt-header HTML attribute is required to display the header
     3. data-tooltip-content HTML attribute is required to display the content

     Customization:
     $element.ddlWpPointer('action', // action: 'show' | 'hide'
     {
     content: $element // $element have to be valid jQuery selector content element should contain H3 for the header and P for the content. Example: <div><h3>Header</h3><p>Content</p></div>
     edge: 'left' // 'left' | 'right' | 'top' | 'bottom'
     align: 'center' // 'center' | 'right' | 'left'
     offset: 'x y' // example: '0 15'
     })

     */
    $.fn.ddlWpPointer = function (action, options) {
        var $el = this;

        //$.jStorage.flush();

        var defaults = {
            headerText: function () {
                var header = $el.data('tooltip-header');
                if (header) {
                    return header;
                }
                else {
                    return 'use <b>data-tooltip-header="header text"</b> attribute to create a header';
                }
            },
            contentText: function () {
                var content = $el.data('tooltip-content');
                if (content) {
                    return content;
                }
                else {
                    return 'use <b>data-tooltip-content="content text"</b> attribute to create a content';
                }
            },
            content: function () { // returns string by default (data-tooltip-header and data-tooltip-content attibutes), but can be overridden by jQuery obj
                return '<h3>' + defaults.headerText() + '</h3><p>' + defaults.contentText() + '</p>';
            },
            edge: 'left',
            align: 'center',
            offset: '0 0',
            stay_hidden: false
        };

        var prms = $.extend(defaults, options);

        var showPointer = function () {

            if (!$el.data('has-wppointer')) {

                if( typeof prms.onOpen === 'function' ){
                    prms.onOpen.call( this, arguments );
                }

                $el
                    .pointer({
                        content: function () {
                            return prms.content;
                        },
                        pointerClass: 'wp-toolset-pointer wp-toolset-layouts-pointer',
                        position: {
                            edge: prms.edge,
                            align: prms.align,
                            offset: prms.offset
                        },
                        close: function () {

                            $el.data('has-wppointer', false);
                            $el.trigger('help_tooltip_closes', options);
                        }
                    })
                    .pointer('open');

                $el.data('has-wppointer', true);

                if( typeof prms.onComplete === 'function' ){
                    prms.onComplete.call( this, arguments );
                }

            }
        };

        var hidePointer = function () {

            if ($el.data('has-wppointer')) {

                if( typeof prms.onClose === 'function' ){
                    prms.onClose.call( this, arguments );
                }

                $el.pointer('close');
                $el.data('has-wppointer', false);

            }
        };

        if (typeof(action) !== 'undefined') { // When 'action' parameter is given

            if (action === 'show' && prms.stay_hidden !== true) {
                showPointer();
            }
            else if (action === 'hide') {
                hidePointer();
            }

        }
        else { // when the method is called without 'action' parameter

            if ($el.data('has-wppointer')) { // hide pointer if it's displayed
                hidePointer();
            }
            else if (prms.stay_hidden !== true) { // show it if not
                showPointer();
            }

        }

        return this;
    };

})(jQuery);

WPV_Toolset.Utils.Loader = function () {
    //fake comment
    var self = this;

    self.loading = false;
    self.el = null;

    self.loader = jQuery('<div class="ajax-loader spinner"></div>');

    self.loadShow = function (el, after) {
        self.el = el;
        self.loading = true;

        if (typeof after === 'undefined') {
            self.loader.prependTo(self.el).css('visibility', 'visible').show();
        }
        else {
            self.loader.insertAfter(self.el).css('visibility', 'visible').show();
        }

        return self.loader;
    };
    self.loadHide = function () {
        var $completeFlag = jQuery('<i class="fa fa-check" aria-hidden="true"></i>')

        _.each(self.loader[0].attributes, function (attribute) {
            if (attribute.name === 'style') {
                $completeFlag.attr(attribute.name, attribute.value);
            }
        });

        var adjustPosition = function( positionName, position ) {
            position = parseInt(position);

            if (position < 0) {
                $completeFlag.css(positionName, '');
            } else {
                position += 8;
                $completeFlag.css(positionName, position.toString() + 'px');
            }
        };

        adjustPosition( 'left', self.loader.css('left') );
        adjustPosition( 'right', self.loader.css('right') );

        self.loader = $completeFlag.replaceAll(self.loader).css({
            color: 'green',
            fontSize: '22px'
        });

        self.loader.fadeOut(1800, function () {
            self.loader = jQuery('<div class="ajax-loader spinner"></div>').replaceAll( jQuery(this) );
            self.loading = false;
            self.loader.remove();
            jQuery(this).remove();
        });

        return self.loader;
    };
};

if (typeof _ != 'undefined') {
    WPV_Toolset.Utils.flatten = function (x, result, prefix) {
        if (_.isObject(x)) {
            _.each(x, function (v, k) {
                WPV_Toolset.Utils.flatten(v, result, prefix ? prefix + '_' + k : k)
            })
        } else {
            result[prefix] = x
        }
        return result
    };
    WPV_Toolset.Utils.flatten_filter_by_key = function (x, result, prefix, filter) {
        var res = [],
            find = WPV_Toolset.Utils.flatten(x, result, prefix);

        if (!filter) return _.values(find);

        _.each(find, function (element, index, list) {
            if (index.indexOf(prefix ? prefix + '_' + filter : filter) !== -1 || filter === index)
                res.push(element);
        });

        return res;
    }
    WPV_Toolset.Utils.containsObject = function (obj, list) {
        var res = _.find(list, function (val) {
            return _.isEqual(obj, val);
        });
        return (_.isObject(res)) ? true : false;
    };
}
;


(function ($) {
    $.fn.textWidth = function () {
        var text = this.html() || this.text() || this.val();
        return ( $.textWidth(text) );
    };
    $.textWidth = function (text) {
        var div = $('#textWidth');
        if (div.length === 0)
            div = $('<div id="textWidth" style="display: none;"></div>').appendTo($('body'));
        div.html(text);
        return (div.width());
    };
})(jQuery);

//Courtesy from http://stackoverflow.com/questions/24816/escaping-html-strings-with-jquery
WPV_Toolset.Utils.escapeHtml = function (str) {
    if (typeof(str) == "string") {
        try {
            var newStr = "";
            var nextCode = 0;
            for (var i = 0; i < str.length; i++) {
                nextCode = str.charCodeAt(i);
                if (nextCode > 0 && nextCode < 128) {
                    newStr += "&#" + nextCode + ";";
                }
                else {
                    newStr += "?";
                }
            }
            return newStr;
        }
        catch (err) {
        }
    }
    else {
        return str;
    }
};

WPV_Toolset.Utils.editor_decode64 = function (input) {
    var output = "",
        chr1, chr2, chr3 = "",
        enc1, enc2, enc3, enc4 = "",
        i = 0,
        keyStr = "ABCDEFGHIJKLMNOP" +
            "QRSTUVWXYZabcdef" +
            "ghijklmnopqrstuv" +
            "wxyz0123456789+/" +
            "=";

    // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
    var base64test = /[^A-Za-z0-9\+\/\=]/g;
    if (base64test.exec(input)) {
        alert("There were invalid base64 characters in the input text.\n" +
            "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
            "Expect errors in decoding.");
    }
    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

    do {
        enc1 = keyStr.indexOf(input.charAt(i++));
        enc2 = keyStr.indexOf(input.charAt(i++));
        enc3 = keyStr.indexOf(input.charAt(i++));
        enc4 = keyStr.indexOf(input.charAt(i++));

        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;

        output = output + String.fromCharCode(chr1);

        if (enc3 != 64) {
            output = output + String.fromCharCode(chr2);
        }
        if (enc4 != 64) {
            output = output + String.fromCharCode(chr3);
        }

        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";

    } while (i < input.length);

    return WPV_Toolset.Utils.editor_utf8_decode(output);
};

WPV_Toolset.Utils.editor_utf8_decode = function (utftext) {
    var string = "";
    var i = 0;
    var c = c1 = c2 = 0;

    while (i < utftext.length) {

        c = utftext.charCodeAt(i);

        if (c < 128) {
            string += String.fromCharCode(c);
            i++;
        }
        else if ((c > 191) && (c < 224)) {
            c2 = utftext.charCodeAt(i + 1);
            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
            i += 2;
        }
        else {
            c2 = utftext.charCodeAt(i + 1);
            c3 = utftext.charCodeAt(i + 2);
            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
            i += 3;
        }

    }

    return string;
};

// convert unicode character to its corresponding numeric entity
WPV_Toolset.Utils.fixedCharCodeAt = function (str, idx) {
    // ex. fixedCharCodeAt ('\uD800\uDC00', 0); // 65536
    // ex. fixedCharCodeAt ('\uD800\uDC00', 1); // 65536
    idx = idx || 0;
    var code = str.charCodeAt(idx);
    var hi, low;
    if (0xD800 <= code && code <= 0xDBFF) { // High surrogate (could change last hex to 0xDB7F to treat high private surrogates as single characters)
        hi = code;
        low = str.charCodeAt(idx + 1);
        if (isNaN(low)) {
            throw 'High surrogate not followed by low surrogate in fixedCharCodeAt()';
        }
        return ((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000;
    }
    if (0xDC00 <= code && code <= 0xDFFF) { // Low surrogate
        // We return false to allow loops to skip this iteration since should have already handled high surrogate above in the previous iteration
        return false;
        /*hi = str.charCodeAt(idx-1);
         low = code;
         return ((hi - 0xD800) * 0x400) + (low - 0xDC00) + 0x10000;*/
    }
    return code;
};

WPV_Toolset.replace_unicode_characters = function (string) {
    // remove accents, swap ñ for n, etc
    var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;",
        to = "aaaaaeeeeeiiiiooooouuuunc------";
    for (var i = 0, l = from.length; i < l; i++) {
        string = string.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }

    var unicode = '!£$%&()=?^|#§';

    for (var i = 0; i < unicode.length; i++) {
        string = string.replace(new RegExp(unicode.charAt(i).regexEscape(), 'g'), WPV_Toolset.Utils.fixedCharCodeAt(unicode.charAt(i)));
    }

    return string;
};

// escapes regex characters for use in regex constructor
String.prototype.regexEscape = function regexEscape() {
    return this.replace(/[\.\?\+\*\^\$\|\(\{\[\]\\)]/g, '\\$&');
};

// THE TOOLTIP //
;
(function ($, window, document, undefined) {

    // Create the defaults once
    var pluginName = "toolsetTooltip",
        dataPlugin = "plugin_" + pluginName,
        undefined,
        defaults = {
            top: undefined,
            text: '',
            close: null,
            open: null,
            additionalClass: ''
        };

    // The actual plugin constructor
    function Plugin(element, options) {

        this.$element = element;
        this.settings = $.extend({}, defaults, options);
        this._defaults = defaults;
        this._name = pluginName;
        this._remove_tooltip = null;
    }

    // Avoid Plugin.prototype conflicts
    $.extend(Plugin.prototype, {
        init: function () {
            var self = this;
            this.$element.on('mouseenter', function (event) {
                event.stopImmediatePropagation();
                self.show(event);
                jQuery(event.target).trigger('tooltip_show', event);
            });
            this.$element.on('mouseleave', function (event) {
                event.stopImmediatePropagation();
                self.hide(event);
                jQuery(event.target).trigger('tooltip_hide', event);
            });
        },
        show: function (event) {
            var self = this,
                $tooltip = $('<div class="toolset-tooltip" />'),
                offset = self.$element.offset(),
                offset_top = typeof self.settings.top === 'undefined' ? 20 : self.settings.top;

            if (this.settings.additionalClass) {
                $tooltip.addClass(this.settings.additionalClass);
            }

            self._remove_tooltip = $tooltip;

            $tooltip
                .appendTo('body')
                .text(this.settings.text || self.$element.data('tooltip-text'))
                .css({
                    'top': offset.top - $tooltip.height() - offset_top,
                    'left': offset.left - ($tooltip.outerWidth() / 2) + (self.$element.outerWidth() / 2),
                    'zIndex': '9999999'
                })
                .fadeIn(100);

            // Probably $elem doesn't is removed before 'click' event takes place
            // So we need to call _manageCellTooltip( $elem, 'hide') somewhere... but i don't know where ;)

            self.$element.on('mousedown', function () {

                if (self._remove_tooltip) {
                    self._remove_tooltip.remove();
                    self._remove_tooltip = null;
                }
            });

            if (self.settings.open !== null && self.settings.open instanceof Function) {
                self.settings.open.call(self);
            }

            return $tooltip;
        },
        hide: function (event) {
            var self = this;

            if (self._remove_tooltip) {
                if (self.settings.close !== null && self.settings.close instanceof Function) {
                    self.settings.close.call(self);
                }
                self._remove_tooltip.remove();
                self._remove_tooltip = null;
            }
        }
    });

    $.fn[pluginName] = function (arg) {

        return this.each(function () {
            var args, instance;

            if (!( $(this).data(dataPlugin) instanceof Plugin )) {
                // if no instance, create one
                $(this).data(dataPlugin, new Plugin($(this), arg));
            }

            instance = $(this).data(dataPlugin);
            instance.element = $(this);

            // call Plugin.init( arg )
            if (typeof arg === 'undefined' || typeof arg === 'object') {

                if (typeof instance['init'] === 'function') {
                    instance.init(arg);
                }

                // checks that the requested public method exists
            } else if (typeof arg === 'string' && typeof instance[arg] === 'function') {

                // copy arguments & remove function name
                args = Array.prototype.slice.call(arguments, 1);

                // call the method
                return instance[arg].apply(instance, args);

            } else {

                $.error('Method ' + arg + ' does not exist on jQuery.' + pluginName);

            }
        });
    };

})(jQuery, window, document);

;(function ($, window, document, undefined) {
    /*

     highlight v3  !! Modified by Jon Raasch (http://jonraasch.com) to fix IE6 bug !!

     Highlights arbitrary terms.

     <http://johannburkard.de/blog/programming/javascript/highlight-javascript-text-higlighting-jquery-plugin.html>

     MIT license.

     Johann Burkard
     <http://johannburkard.de>
     <mailto:jb@eaio.com>

     */
    var defaults = {
            className: 'highlighted'
        },
        options = {};

    jQuery.fn.highlight = function (pat, option) {

        options = jQuery.extend(options, defaults, option)

        function innerHighlight(node, pat) {
            var skip = 0;

            if (node.nodeType == 3) {
                var pos = node.data.toUpperCase().indexOf(pat);
                if (pos >= 0) {
                    var spannode = document.createElement('span');
                    spannode.className = options.className;
                    var middlebit = node.splitText(pos);
                    var endbit = middlebit.splitText(pat.length);
                    var middleclone = middlebit.cloneNode(true);
                    spannode.appendChild(middleclone);
                    middlebit.parentNode.replaceChild(spannode, middlebit);
                    skip = 1;
                }
            }
            else if (node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
                for (var i = 0; i < node.childNodes.length; ++i) {
                    i += innerHighlight(node.childNodes[i], pat);
                }
            }
            return skip;
        }

        return this.each(function () {
            innerHighlight(this, pat.toUpperCase());
        });
    };

    jQuery.fn.removeHighlight = function () {
        function newNormalize(node) {
            for (var i = 0, children = node.childNodes, nodeCount = children.length; i < nodeCount; i++) {
                var child = children[i];
                if (child.nodeType == 1) {
                    newNormalize(child);
                    continue;
                }
                if (child.nodeType != 3) {
                    continue;
                }
                var next = child.nextSibling;
                if (next == null || next.nodeType != 3) {
                    continue;
                }
                var combined_text = child.nodeValue + next.nodeValue;
                new_node = node.ownerDocument.createTextNode(combined_text);
                node.insertBefore(new_node, child);
                node.removeChild(child);
                node.removeChild(next);
                i--;
                nodeCount--;
            }
        }

        return this.find("span." + options.className).each(function () {
            var thisParent = this.parentNode;
            thisParent.replaceChild(this.firstChild, this);
            newNormalize(thisParent);
        }).end();
    };

}(jQuery, window, document));

var waitForFinalEvent = (function () {
    var timers = {};
    return function (callback, ms, uniqueId) {
        if (!uniqueId) {
            uniqueId = "Don't call this twice without a uniqueId";
        }
        if (timers[uniqueId]) {
            clearTimeout(timers[uniqueId]);
        }

        timers[uniqueId] = setTimeout(callback, ms);
    };
})();


WPV_Toolset.Utils._strip_scripts = function (data) {

    if (!data) return '';

    if (_.isString(data) === false) return '';

    data = data.replace(/&lt;/g, "|-lt-|");
    data = data.replace(/&gt;/g, "|-gt-|");
    data = data.replace(/&(\w+);/g, "&amp;$1;"); // Preserve entities (rafael.v)

    var out = data.replace(/<.*?script.*?>.*?<\/.*?script.*?>/igm, "");
    out = out.replace(/&lt;/g, "<");
    out = out.replace(/&gt;/g, ">");
    out = out.replace(/&amp;(\w+);/g, "&$1;"); // Preserve entities (rafael.v)
    out = out.replace(/\|-lt-\|/g, '&lt;');
    out = out.replace(/\|-gt-\|/g, '&gt;');
    return out;
};

if (!String.prototype.trim) {
    (function () {
        // Make sure we trim BOM and NBSP
        var rtrim = /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g;
        String.prototype.trim = function () {
            return this.replace(rtrim, '');
        };
    })();
}


/**
 * Strip known tags and escape the rest of the string.
 *
 * Warning! Since underscore.js 1.2.2 the _.escape() method got dumber and
 * now it double-escapes HTML entities (read: https://github.com/jashkenas/underscore/issues/350).
 * If you want to avoid double-escaping, you can do it by:
 *
 *     WPV_Toolset.Utils._strip_tags_and_preserve_text(_.unescape(text))
 *
 * Note: Although the "_" prefix suggests this function is private, it's also used elsewhere.
 *
 * @param {string} text
 * @returns {string}
 * @since unknown
 */
WPV_Toolset.Utils._strip_tags_and_preserve_text = function (text) {
    var rex = /<\/?(a|abbr|acronym|address|applet|area|article|aside|audio|b|base|basefont|bdi|bdo|bgsound|big|blink|blockquote|body|br|button|canvas|caption|center|cite|code|col|colgroup|data|datalist|dd|del|details|dfn|dir|div|dl|dt|em|embed|fieldset|figcaption|figure|font|footer|form|frame|frameset|h1|h2|h3|h4|h5|h6|head|header|hgroup|hr|html|i|iframe|img|input|ins|isindex|kbd|keygen|label|legend|li|link|listing|main|map|mark|marquee|menu|menuitem|meta|meter|nav|nobr|noframes|noscript|object|ol|optgroup|option|output|p|param|plaintext|pre|progress|q|rp|rt|ruby|s|samp|script|section|select|small|source|spacer|span|strike|strong|style|sub|summary|sup|table|tbody|td|textarea|tfoot|th|thead|time|title|tr|track|tt|u|ul|var|video|wbr|xmp)\b[^<>]*>/ig
    return _.escape(text.replace(rex, "")).trim();
};


/**
 * Setup the behaviour of browser when user tries to leave the page.
 *
 * When user tries to leave the page, check if confirmation is needed, and if so, displays a confirmation message and
 * runs custom action.
 *
 * WARNING: http://stackoverflow.com/a/37782307
 *
 * @param {function} checkIfConfirmationNeededCallback Should return true if confirmation should be shown (e.g. unsaved data).
 * @param {function|null} onBeforeUnloadCallback Will be called before showing the confirmation.
 * @param {string} confirmationMessage Confirmation message that should be shown by the browser.
 * @since unknown
 */
WPV_Toolset.Utils.setConfirmUnload = function (checkIfConfirmationNeededCallback, onBeforeUnloadCallback, confirmationMessage) {
    window.onbeforeunload = function (e) {
        if (checkIfConfirmationNeededCallback()) {

            if(_.isFunction(onBeforeUnloadCallback)) {
                onBeforeUnloadCallback();
            }

            // For IE and Firefox prior to version 4
            if (e) {
                e.returnValue = confirmationMessage;
            }
            return confirmationMessage;
        }
    };
};

if (typeof _ !== 'undefined' && typeof _.capitalize === 'undefined') {
    _.mixin({
        capitalize: function (string) {
            return string.charAt(0).toUpperCase() + string.substring(1).toLowerCase();
        }
    });
}


WPV_Toolset.Utils.has_shortcode = function (string) {
    var search = /\[(\[?)(\w*?\-*?\w*?)*?(?![\w-])([^\]\/]*(?:\/(?!\])[^\]\/]*)*?)(?:(\/)\]|\](?:([^\[]*(?:\[(?!\/\2\])[^\[]*)*)(\[\/\2\]))?)(\]?)/g;
    return decodeURIComponent(string).search(search) !== -1;
};


/**
 * Courtesy from: https://gist.github.com/alexey-bass/1115557
 * @param left
 * @param right
 * @returns int
 */
WPV_Toolset.Utils.versionCompare = function (left, right) {
    if (typeof left + typeof right != 'stringstring')
        return false;

    var a = left.split('.')
        , b = right.split('.')
        , i = 0, len = Math.max(a.length, b.length);

    for (; i < len; i++) {
        if ((a[i] && !b[i] && parseInt(a[i]) > 0) || (parseInt(a[i]) > parseInt(b[i]))) {
            return 1;
        } else if ((b[i] && !a[i] && parseInt(b[i]) > 0) || (parseInt(a[i]) < parseInt(b[i]))) {
            return -1;
        }
    }

    return 0;
};


/**
 * Get a query argument value from a URL.
 *
 * @param {string} name Argument name.
 * @param {string} [url] Source URL. Optional. If missing, current page URL will be used.
 * @returns {string|null}
 * @since 2.1
 * @link http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
 */
WPV_Toolset.Utils.getParameterByName = function (name, url) {

    if (!url) {
        url = window.location.href;
    }

    name = name.replace(/[\[\]]/g, "\\$&");

    var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)", "i");

    var results = regex.exec(url);

    if (!results) {
        return null;
    }

    if (!results[2]) {
        return '';
    }

    return decodeURIComponent(results[2].replace(/\+/g, " "));
};


/**
 * Add, update or remove query string argument.
 *
 * @param {string} key Argument name.
 * @param {string} [value] Argument value. Not supplying a value will remove the parameter, supplying one will
 *     add/update the argument.
 * @param {string} [url] If no URL is supplied, it will be grabbed from window.location
 * @returns {string}
 * @link http://stackoverflow.com/a/11654596/3191395
 */
WPV_Toolset.Utils.updateUrlQuery = function (key, value, url) {
    if (!url) {
        url = window.location.href;
    }
    var re = new RegExp("([?&])" + key + "=.*?(&|#|$)(.*)", "gi");
    var hash;

    if (re.test(url)) {
        if (typeof value !== 'undefined' && value !== null) {
            return url.replace(re, '$1' + key + "=" + value + '$2$3');
        } else {
            hash = url.split('#');
            url = hash[0].replace(re, '$1$3').replace(/(&|\?)$/, '');
            if (typeof hash[1] !== 'undefined' && hash[1] !== null) {
                url += '#' + hash[1];
            }
            return url;
        }
    } else {
        if (typeof value !== 'undefined' && value !== null) {
            var separator = url.indexOf('?') !== -1 ? '&' : '?';
            hash = url.split('#');
            url = hash[0] + separator + key + '=' + value;
            if (typeof hash[1] !== 'undefined' && hash[1] !== null) {
                url += '#' + hash[1];
            }
            return url;
        } else {
            return url;
        }
    }
};


/**
 * Tools for manipulating spinners in a native WP way.
 *
 * If WP changes the behaviour, we'll need only to update this object.
 *
 * @type {{create: WPV_Toolset.Utils.Spinner.create, show: WPV_Toolset.Utils.Spinner.show, hide: WPV_Toolset.Utils.Spinner.hide, find: WPV_Toolset.Utils.Spinner.find}}
 */
WPV_Toolset.Utils.Spinner = {
    create: function () {
        return jQuery('<span class="spinner"></span>');
    },
    show: function (spinner, show) {
        if (typeof(show) === 'undefined') {
            show = true;
        }

        if (!show) {
            WPV_Toolset.Utils.Spinner.hide(spinner);
            return;
        }

        spinner.addClass('is-active');
    },
    hide: function (spinner) {
        spinner.removeClass('is-active');
    },
    find: function (parentElement) {
        return parentElement.find('.spinner');
    }
};


/**
 * AJAX call helper.
 *
 * WIP, to be merged with WPV_Toolset.Utils.do_ajax_post().
 *
 * @type {{parseResponse: WPV_Toolset.Utils.Ajax.parseResponse, call: WPV_Toolset.Utils.Ajax.call}}
 * @since 2.1
 */
WPV_Toolset.Utils.Ajax = {

    /**
     * Ensure that response is always an object with the success property.
     *
     * If it's not, return a dummy object indicating a failure.
     *
     * @param response {*} Response from the AJAX call.
     * @returns {{success: boolean}} Sanitized response.
     *
     * @since 2.1
     */
    parseResponse: function (response) {
        if (typeof(response.success) === 'undefined') {
            console.log("parseResponse: no success value", response);
            return {success: false};
        } else {
            return response;
        }
    },


    /**
     * Perform an AJAX call on field definitions.
     *
     * @param {*} data AJAX call parameters. Needs to include 'action' and 'wpnonce'.
     * @param {function} successCallback Callback to be used after AJAX call is completed. It will get two parameters,
     *     the complete AJAX response and the 'data' element for convenience.
     * @param {function} [failCallback] Analogous to successCallback for the case of failure. If missing,
     *     successCallback will be used instead.
     *
     * @since 2.1
     */
    call: function (data, successCallback, failCallback) {

        if (typeof(failCallback) === 'undefined') {
            failCallback = successCallback;
        }

        jQuery.post({
            async: true,
            url: ajaxurl,
            data: data,

            success: function (originalResponse) {
                var response = WPV_Toolset.Utils.Ajax.parseResponse(originalResponse);

                if (response.success) {
                    successCallback(response, response.data || {});
                } else {
                    failCallback(response, response.data || {});
                }
            },

            error: function (ajaxContext) {
                console.log('Error:', ajaxContext.responseText);
                failCallback({success: false, data: {}}, {});
            }
        });

    }

};


WPV_Toolset.Utils._template = function (template, data, settings) {
    var options = _.defaults({}, settings, _.templateSettings),
        _template = null;

    if (WPV_Toolset.Utils.versionCompare(_.VERSION, '1.7') >= 0) {
        _template = _.template(template, options);
        return _template(data);
    } else {
        return _.template(template, data, options);
    }
};

// override dialog whenever toolset is active and take possession
/**
 * @todo This should not be here:
 * dialogs with specific classnames should get it directly.
 * We should not be pre-setting all jQuery UI dialogs dialogClass setting,
 * we are not good players here.
 */
if (jQuery && jQuery.ui && jQuery.ui.dialog) {
    jQuery.extend(jQuery.ui.dialog.prototype.options, {
        dialogClass: 'toolset-ui-dialog'
    });
}


if (typeof Toolset.add_qt_editor_buttons !== 'function') {
    Toolset.add_qt_editor_buttons = function (qt_instance, editor_instance) {
        var activeUrlEditor, html;
        QTags._buttonsInit();
        var editorInstance = {};
        editorInstance[qt_instance.id] = editor_instance;

        for (var button_name in qt_instance.theButtons) {
            if (qt_instance.theButtons.hasOwnProperty(button_name)) {
                qt_instance.theButtons[button_name].old_callback = qt_instance.theButtons[button_name].callback;
                if (qt_instance.theButtons[button_name].id == 'img') {
                    qt_instance.theButtons[button_name].callback = function (element, canvas, ed) {
                        var t = this,
                            id = jQuery(canvas).attr('id'),
                            selection = editorInstance[id].getSelection(),
                            e = "http://",
                            g = prompt(quicktagsL10n.enterImageURL, e),
                            f = prompt(quicktagsL10n.enterImageDescription, "");
                        t.tagStart = '<img src="' + g + '" alt="' + f + '" />';
                        selection = t.tagStart;
                        t.closeTag(element, ed);
                        editorInstance[id].replaceSelection(selection, 'end');
                        editorInstance[id].focus();
                    }
                } else if (qt_instance.theButtons[button_name].id == 'close') {

                } else if (qt_instance.theButtons[button_name].id == 'link') {
                    var t = this;
                    qt_instance.theButtons[button_name].callback =
                        function (b, c, d, e) {
                            activeUrlEditor = c;
                            var f, g = this;
                            return "undefined" != typeof wpLink ? void wpLink.open(d.id) : (e || (e = "http://"), void(g.isOpen(d) === !1 ? (f = prompt(quicktagsL10n.enterURL, e), f && (g.tagStart = '<a href="' + f + '">', a.TagButton.prototype.callback.call(g, b, c, d))) : a.TagButton.prototype.callback.call(g, b, c, d)))
                        };
                    jQuery('#wp-link-submit').off();
                    jQuery('#wp-link-submit').on('click', function (event) {
                        event.preventDefault();
                        if (wpLink.isMCE()) {
                            wpLink.mceUpdate();
                        } else {
                            var id = jQuery(activeUrlEditor).attr('id'),
                                selection = editorInstance[id].getSelection(),
                                inputs = {},
                                attrs, text, title, html;
                            inputs.wrap = jQuery('#wp-link-wrap');
                            inputs.backdrop = jQuery('#wp-link-backdrop');
                            if (jQuery('#link-target-checkbox').length > 0) {
                                // Backwards compatibility - before WordPress 4.2
                                inputs.text = jQuery('#link-title-field');
                                attrs = wpLink.getAttrs();
                                text = inputs.text.val();
                                if (!attrs.href) {
                                    return;
                                }
                                // Build HTML
                                html = '<a href="' + attrs.href + '"';
                                if (attrs.target) {
                                    html += ' target="' + attrs.target + '"';
                                }
                                if (text) {
                                    title = text.replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                                    html += ' title="' + title + '"';
                                }
                                html += '>';
                                html += text || selection;
                                html += '</a>';
                                t.tagStart = html;
                                selection = t.tagStart;
                            } else {
                                // WordPress 4.2+
                                inputs.text = jQuery('#wp-link-text');
                                attrs = wpLink.getAttrs();
                                text = inputs.text.val();
                                if (!attrs.href) {
                                    return;
                                }
                                // Build HTML
                                html = '<a href="' + attrs.href + '"';
                                if (attrs.target) {
                                    html += ' target="' + attrs.target + '"';
                                }
                                html += '>';
                                html += text || selection;
                                html += '</a>';
                                selection = html;
                            }
                            jQuery(document.body).removeClass('modal-open');
                            inputs.backdrop.hide();
                            inputs.wrap.hide();
                            jQuery(document).trigger('wplink-close', inputs.wrap);
                            editorInstance[id].replaceSelection(selection, 'end');
                            editorInstance[id].focus();
                            return false;
                        }
                    });
                } else {
                    qt_instance.theButtons[button_name].callback = function (element, canvas, ed) {
                        var id = jQuery(canvas).attr('id'),
                            t = this,
                            selection = editorInstance[id].getSelection();
                        if (selection.length > 0) {
                            if (!t.tagEnd) {
                                selection = selection + t.tagStart;
                            } else {
                                selection = t.tagStart + selection + t.tagEnd;
                            }
                        } else {
                            if (!t.tagEnd) {
                                selection = t.tagStart;
                            } else if (t.isOpen(ed) === false) {
                                selection = t.tagStart;
                                t.openTag(element, ed);
                            } else {
                                selection = t.tagEnd;
                                t.closeTag(element, ed);
                            }
                        }
                        editorInstance[id].replaceSelection(selection, 'end');
                        editorInstance[id].focus();
                    }
                }
            }
        }
    }
}
