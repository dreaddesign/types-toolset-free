var ToolsetCommon = ToolsetCommon || {};

ToolsetCommon.BSComponentsEventsHandler = function($){

    var self = this, dialog = null;

    self.init = function(){


        Toolset.hooks.addAction( 'bs_components_toggle_buttons', function( instance ){
            self.toggle_codemirror_buttons(instance);
        });

        Toolset.hooks.addAction( 'bs_components_open_dialog', function( object ){
            self.bootstrap_info_dialog(object);
        });

        Toolset.hooks.addAction( 'bs_components_tinyMCE_divider', function( instance ){
            self.tinyMCE_divider(instance);
        });

    };


    self.bootstrap_info_dialog = function(object ){

        dialog = new DDLayout.DialogView({
            title:  object.name,
            modal:true,
            resizable: false,
            draggable: false,
            position: {my: "center", at: "center", of: window},
            width: 250,
            selector: '#ddl-bootstrap-info-dialog-tpl',
            template_object: {
                description: object.description,
                dialog_icon_size: object.dialog_icon_size,
                url: object.url,
                title: object.name,
                icon: object.button_icon,
                bs_component_category: object.bs_category,
                bs_component_key: object.bs_component_key,
                editor_instance: object.editor_instance,
                buttons_type: object.buttons_type
            }
        });

        dialog.$el.on('ddldialogclose', function (event) {
            dialog.remove();
        });

        dialog.dialog_open();

    };


    self.tinyMCE_divider = function($instance){

        //_.defer(function(){
            if(jQuery(".toolset_bs_cm_labels_"+$instance).length === 0 ){
                jQuery('#wp-'+$instance+'-editor-container [aria-label="Glyphicons"]').before('<span class="toolset_qt_btn_group_labels_style toolset_qt_btn_group_labels_'+$instance+' toolset_bs_cm_labels_'+$instance+'">'+Toolset_CssComponent.DDL_CSS_JS.group_label_bs_components+'</span>');
                jQuery('#wp-'+$instance+'-editor-container [aria-label="Grid system"]').before('<span class="toolset_qt_btn_group_labels_style toolset_qt_btn_group_labels_'+$instance+' toolset_bs_cm_labels_'+$instance+'">'+Toolset_CssComponent.DDL_CSS_JS.group_label_bs_css+'</span>');

                if(typeof Toolset_CssComponent.DDL_CSS_JS['other'] === 'object' && _.keys(Toolset_CssComponent.DDL_CSS_JS['other']).length > 0 ){
                    jQuery('#wp-'+$instance+'-editor-container [aria-label="Responsive utilities"]').after('<span class="toolset_qt_btn_group_labels_style toolset_qt_btn_group_labels_'+$instance+' toolset_bs_cm_labels_'+$instance+'">'+Toolset_CssComponent.DDL_CSS_JS.group_label_other+'</span>');
                }

                var $pop_location = jQuery('.toolset_qt_btn_group_labels_'+$instance).closest('div.mce-toolbar');
                $pop_location.after('<div id="pop_'+$instance+'" class="pop pop_top_margin pop_right_margin_big pop_hidden"><a href="#" class="pop_close" data-instance="'+$instance+'" data-pop_id="pop_tinymce"><i class="glyphicon glyphicon-remove"></i></a><p class="pop_msg_p">'+Toolset_CssComponent.DDL_CSS_JS.tinymce_pop_message+'<br><br><label><input type="checkbox" id="hide_pop_tinymce" name="hide_tooltip" value="hide_pop"> Dont show this tip again</label></p></div>');

            }


            var $labels = jQuery('.toolset_qt_btn_group_labels_'+$instance), $container = $labels.closest('div.mce-toolbar');
            if(Toolset_CssComponent.DDL_CSS_JS.show_bs_buttons_tinymce_status === "yes"){
                $container.show();
            } else {
                $container.hide();
            }

            ToolsetCommon.BSComponentsEventsHandler.editor_notification_handler($instance);

        //});

    };


    self.toggle_codemirror_buttons = function($instance, $other){

        jQuery('#codemirror-buttons-for-'+$instance).toggle();

        if(jQuery('#codemirror-buttons-for-'+$instance).is(":hidden") === true){
            jQuery("#qt_"+$instance+"_bs_component_show_hide_button").val(Toolset_CssComponent.DDL_CSS_JS.button_toggle_show);
            self.update_db_option('show_buttons_cm_status',false);
            Toolset_CssComponent.DDL_CSS_JS.show_bs_buttons_cm_status = "no";
        } else {
            jQuery("#qt_"+$instance+"_bs_component_show_hide_button").val(Toolset_CssComponent.DDL_CSS_JS.button_toggle_hide);
            self.update_db_option('show_buttons_cm_status',true);
            Toolset_CssComponent.DDL_CSS_JS.show_bs_buttons_cm_status = "yes";
        }

    };

    self.update_db_option = function(option, value){
        var data = {
			'action': 'toolset_bs_update_option',
            'option': option,
			'value': value
		};
		jQuery.post(ajaxurl, data);
    };


    ToolsetCommon.BSComponentsEventsHandler.update_tinyMCE_toggle_status = function ($container){
        var hide_tinyMCE_buttons = jQuery($container).is(":hidden");
        var tinyMCE_status = (hide_tinyMCE_buttons) ? false : true;
        Toolset_CssComponent.DDL_CSS_JS.show_bs_buttons_tinymce_status = (tinyMCE_status) ? 'yes' : 'no';
        self.update_db_option('show_buttons_tinymce_status',tinyMCE_status);
    };


    ToolsetCommon.BSComponentsEventsHandler.openBSDialog = function(button_data){
        var bs_cat = jQuery(button_data).data('bs_category');
        var bs_key = jQuery(button_data).data('bs_key');
        var cm_instance = jQuery(button_data).data('cm_instance');

        if( Toolset_CssComponent && Toolset_CssComponent.DDL_CSS_JS ) {
            try {
                Toolset.hooks.doAction('bs_components_open_dialog', {
                    name: Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].name,
                    description: Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].description,
                    url: Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].url,
                    button_icon: Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].button_icon,
                    dialog_icon_size: Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].dialog_icon_size,
                    bs_category: bs_cat,
                    bs_component_key: bs_key,
                    buttons_type: 'codemirror',
                    editor_instance: cm_instance
                });
            } catch (e) {
                console.log('A property is missing for: ', bs_cat, bs_key, e.message);
            }
        }

    };

    ToolsetCommon.BSComponentsEventsHandler.editor_notification = function (button_data){

        var bs_cat = jQuery(button_data).data('bs_category');
        var bs_key = jQuery(button_data).data('bs_key');
        var bs_editor_instance = jQuery(button_data).data('editor_instance');
        var bs_buttons_type = jQuery(button_data).data('buttons_type');

        dialog.remove();

        if(Toolset_CssComponent.DDL_CSS_JS['hide_editor_pop_msg'] === "no"){

            if(bs_buttons_type === 'tinymce'){
                jQuery('#pop_'+bs_editor_instance).show();
                jQuery(".bs_pop_element_name_tinymce").text(Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].name);
            } else if(bs_buttons_type === 'codemirror'){
                jQuery('#pop_'+bs_editor_instance).show();
                jQuery(".bs_pop_element_name_codemirror").text(Toolset_CssComponent.DDL_CSS_JS[bs_cat][bs_key].name);
            }

        }

    };

    ToolsetCommon.BSComponentsEventsHandler.editor_notification_handler = function (instance){


        jQuery( ".pop_close" ).click(function(event) {
            event.preventDefault();

            // update option, do not show message again
            if(jQuery(this).data('pop_id') === 'pop_tinymce'){
                if(jQuery("#hide_pop_tinymce").prop('checked')){
                    self.update_db_option('hide_pop_msg',true);
                    Toolset_CssComponent.DDL_CSS_JS['hide_editor_pop_msg'] = "yes";
                }
                jQuery('#pop_'+jQuery(this).data('instance')).hide();
            } else {
                jQuery("#"+jQuery(this).data('pop_id')).hide();
                if(jQuery("#hide_pop_"+instance).prop('checked')){
                    self.update_db_option('hide_pop_msg',true);
                    Toolset_CssComponent.DDL_CSS_JS['hide_editor_pop_msg'] = "yes";
                }
            }

        });


    };

    self.init();
};


jQuery( document ).ready(function() {

    if (typeof QTags !== 'undefined') {
        new ToolsetCommon.BootstrapCssComponentsQuickTags($);

        _.defer(function(){
            if( Toolset_CssComponent.DDL_CSS_JS.current_screen ==='post' ){

                if(jQuery(".quicktags-toolbar").parent('.wp-editor-container').length > 0){
                    jQuery.each( jQuery(".quicktags-toolbar").parent('.wp-editor-container'), function( key, value ) {
                        if(value.id !== 'undefined'){
                            var instance = value.id.replace("wp-", "");
                            instance = instance.replace("-editor-container","");
                            Toolset.hooks.doAction('toolset_text_editor_CodeMirror_init', instance);
                        }

                    });
                }

            }
        })
    }

    new ToolsetCommon.BSComponentsEventsHandler($);
});
