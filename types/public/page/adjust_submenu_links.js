var Types = Types || {};

(function($) {

    Types.page = Types.page || {};

    /**
     * Utility for adding URL parameters to WordPress admin menus, which is currently not supported by WordPress.
     * 
     * Usage: 
     *      Types.page.menuLinkAdjuster.addMenuParams(params, selector);
     *      
     *      - param can be either a single object {key: 'param', value: 'value'} or an array of such objects.
     *      - selector, if provided, needs to select the a tag in a menu item. If omitted, it defaults to currently active menu item.
     *  
     * If the params object is provided via wp_localize_script in Types.l10n.paramsToAddToSubmenu, it will be applied
     * to the currently selected menu item automatically (this yet needs to be tested).
     * 
     * @since 2.0
     */ 
    Types.page.menuLinkAdjuster = new function() {

        var self = this;

        self.addQueryArg = function (url, param, value) {
            var a = document.createElement('a'), regex = /(?:\?|&amp;|&)+([^=]+)(?:=([^&]*))*/g;
            var match, str = []; a.href = url; param = encodeURIComponent(param);
            while (match = regex.exec(a.search))
                if (param != match[1]) str.push(match[1]+(match[2]?"="+match[2]:""));
            str.push(param+(value?"="+ encodeURIComponent(value):""));
            a.search = str.join("&");
            return a.href;
        };
        
        
        self.getParamsToAdd = function() {
            if(_.has(Types, 'l10n') && _.has(Types.l10n, 'paramsToAddToSubmenu')) {
                return Types.l10n['paramsToAddToSubmenu'];
            } else {
                return null;
            }
        };


        /**
         * Add menu parameters to the selected a tag.
         * 
         * @param {{key:string,value:string}|[{key:string,value:string}]} paramsToAdd
         * @param {string|undefined} selector
         * @since 2.0
         */
        self.addMenuParams = function(paramsToAdd, selector) {

            if(typeof(selector) == 'undefined') {
                selector = self.currentSubmenuSelector;
            }

            if(!_.isArray(paramsToAdd)) {
                paramsToAdd = [paramsToAdd];
            }
            
            var menuLink = $(selector);
            var url = menuLink.attr('href');
            _.each(paramsToAdd, function(paramToAdd) {
                url = self.addQueryArg(url, paramToAdd.key, paramToAdd.value);
            });
            menuLink.attr('href', url);
        };


        self.currentSubmenuSelector = '.wp-has-current-submenu li.current a';

        
        self.autorun = function() {
            var paramsFromL10n = self.getParamsToAdd();
            if(_.isArray(paramsFromL10n)) {
                self.addMenuParams(paramsFromL10n);
            }
        };


        self.autorun();
        
    };


})(jQuery);
