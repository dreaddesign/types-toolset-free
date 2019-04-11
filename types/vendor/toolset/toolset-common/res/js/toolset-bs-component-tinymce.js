var ToolsetCommon = ToolsetCommon || {};


ToolsetCommon.BootstrapCssComponentsTinyMCE = function($){
    self.init = function(){
        self.generate_all_tinymce_buttons();

    };

    self.generate_all_tinymce_buttons = function(){
        /* Register the buttons */
        tinymce.create('tinymce.plugins.BSComponentButtons', {
            init : function(ed, url) {

                /**
                 * Inserts toogle button for Bootstrap buttons
                 */
                ed.addButton( 'css_components_toolbar_toggle', {
                    icon: ' icon-bootstrap-original-logo ont-icon-25 css-components-toggle-icon',
                    tooltip: Toolset_CssComponent.DDL_CSS_JS.toggle_button_tooltip,
                    onclick: function() {

                         var $labels = jQuery('.toolset_qt_btn_group_labels_'+ed.id),
                         $container = $labels.closest('div.mce-toolbar');
                         $container.toggle();
                         ToolsetCommon.BSComponentsEventsHandler.update_tinyMCE_toggle_status($container);

                    }
                });


                /**
                 * Insert all other buttons
                 */
                var $bootstrap_components = Toolset_CssComponent.DDL_CSS_JS.available_components;
                var $bootstrap_css = Toolset_CssComponent.DDL_CSS_JS.available_css;
                var $other = Toolset_CssComponent.DDL_CSS_JS.other;

                jQuery.each( $bootstrap_components, function( index, value ){

                    ed.addButton( 'css_components_'+index+'_button', {
                        icon: ' '+value.button_icon+' '+value.button_icon_size,
                        tooltip: value.name,
                        onclick: function() {

                            Toolset.hooks.doAction( 'bs_components_open_dialog', {
                                name: value.name,
                                description: value.description,
                                url: value.url,
                                button_icon: value.button_icon,
                                dialog_icon_size: value.dialog_icon_size,
                                bs_category: 'available_components',
                                bs_component_key: index,
                                editor_instance: ed.id,
                                buttons_type: 'tinymce'
                            });

                        },
                        'class' : "toolset-components-buttons"
                    });

                });

                jQuery.each( $bootstrap_css, function( index, value ){

                    ed.addButton( 'css_'+index+'_button', {
                        icon: ' '+value.button_icon+' '+value.button_icon_size,
                        tooltip: value.name,
                        onclick: function() {

                            Toolset.hooks.doAction( 'bs_components_open_dialog', {
                                name: value.name,
                                description: value.description,
                                url: value.url,
                                button_icon: value.button_icon,
                                dialog_icon_size: value.dialog_icon_size,
                                bs_category: 'available_css',
                                bs_component_key: index,
                                editor_instance: ed.id,
                                buttons_type: 'tinymce'
                            });

                        },
                        'class' : "toolset-components-buttons"
                    });

                });

                jQuery.each( $other, function( index, value ){

                    ed.addButton( 'other_'+index+'_button', {
                        icon: ' '+value.button_icon+' '+value.button_icon_size,
                        tooltip: value.name,
                        onclick: function() {

                            Toolset.hooks.doAction( 'bs_components_open_dialog', {
                                name: value.name,
                                description: value.description,
                                url: value.url,
                                button_icon: value.button_icon,
                                dialog_icon_size: value.dialog_icon_size,
                                bs_category: 'other',
                                bs_component_key: index,
                                editor_instance: ed.id,
                                buttons_type: 'tinymce'
                            });

                        },
                        'class' : "toolset-components-buttons"
                    });

                });

                _.defer(function(){
                    Toolset.hooks.doAction( 'bs_components_tinyMCE_divider', ed.id );
                });

            },
            createControl : function(n, cm) {
                return null;
            },
        });
        /* Start the buttons */
        tinymce.PluginManager.add( 'bs_component_buttons_script', tinymce.plugins.BSComponentButtons );
    };

    self.init();
}

(function() {
    new ToolsetCommon.BootstrapCssComponentsTinyMCE($);
});