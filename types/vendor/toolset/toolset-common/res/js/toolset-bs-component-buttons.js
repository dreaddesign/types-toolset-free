var ToolsetCommon = ToolsetCommon || {};


ToolsetCommon.BootstrapCssComponentsQuickTags = function($){
    
    var self = this,
    $bootstrap_components = Toolset_CssComponent.DDL_CSS_JS.available_components,
    $bootstrap_css = Toolset_CssComponent.DDL_CSS_JS.available_css,
    $other = Toolset_CssComponent.DDL_CSS_JS.other,
    $instance = null;
    
    
    
    self.init = function(){
        
        $instance = null;
        
        Toolset.hooks.addAction( 'toolset_text_editor_CodeMirror_init', function( get_instance ) {
            if(get_instance){ 
                $instance = get_instance;
                self.add_bootstrap_components_buttons($instance);
            }
        });
    };

    self.wrap_codemirror_buttons = function($instance,$buttons){

        var toolbar_div = jQuery("#wp-"+$instance+"-editor-container .quicktags-toolbar");
        if(toolbar_div.length === 0){

            var views_toolbar = jQuery("#qt_"+$instance+"_toolbar");
            if(views_toolbar !== 0){
                toolbar_div = views_toolbar;
            }
        }

        toolbar_div.append('<div class="bs-quicktags-toolbar code-editor-toolbar" id="codemirror-buttons-for-'+$instance+'">'+$buttons+'</div>');
        jQuery("#"+$instance).before('<div id="pop_'+$instance+'" class="pop pop_top_margin pop_right_margin pop_hidden"><a href="#" class="pop_close" data-pop_id="pop_'+$instance+'"><i class="glyphicon glyphicon-remove"></i></a><p class="pop_msg_p">'+Toolset_CssComponent.DDL_CSS_JS.codemirror_pop_message+'<br><br><label><input type="checkbox" id="hide_pop_'+$instance+'" name="hide_tooltip" value="hide_pop"> Dont show this tip again</label></p></div>');
        
        _.defer(function(){
            if(Toolset_CssComponent.DDL_CSS_JS.show_bs_buttons_cm_status === "no"){
                jQuery('#codemirror-buttons-for-'+$instance).hide();
                jQuery("#qt_"+$instance+"_bs_component_show_hide_button").val(Toolset_CssComponent.DDL_CSS_JS.button_toggle_show);
            } else {
                jQuery("#qt_"+$instance+"_bs_component_show_hide_button").val(Toolset_CssComponent.DDL_CSS_JS.button_toggle_hide);
            }
        });
            
        ToolsetCommon.BSComponentsEventsHandler.editor_notification_handler($instance);
    };
    
    self.generate_codemirror_bs_buttons = function(instance){
        
        var codemirror_buttons = '';
        // bs components
        codemirror_buttons += '<span class="toolset_qt_btn_group_labels toolset_qt_btn_group_labels_style">'+Toolset_CssComponent.DDL_CSS_JS.group_label_bs_components+'</span>';
        codemirror_buttons += '<ul class="js-wpv-filter-edit-toolbar" >';
        
        jQuery.each( $bootstrap_components, function( index, value ){
            codemirror_buttons +='<li class="js-editor-addon-button-wrapper js-editor-bs-button-wrapper">';
            codemirror_buttons +='<button type="button" class="button-secondary js-code-editor-toolbar-button js-codemirror-bs-component-button bs-'+index+'-button" data-bs_category="available_components" data-cm_instance="'+instance+'" data-bs_key="'+index+'" title="'+value.name+'" onclick="ToolsetCommon.BSComponentsEventsHandler.openBSDialog(this);">';
            codemirror_buttons +='<i class="'+value.button_icon+' bs-'+index+'-icon"></i>';
            codemirror_buttons +='</li>';
            
        });
        codemirror_buttons +='</ul>';
        
        // bs css
        codemirror_buttons += '<span class="toolset_qt_btn_group_labels toolset_qt_btn_group_labels_style">'+Toolset_CssComponent.DDL_CSS_JS.group_label_bs_css+'</span>';
        codemirror_buttons += '<ul class="js-wpv-filter-edit-toolbar">';
        
        jQuery.each( $bootstrap_css, function( index, value ){
            
            codemirror_buttons +='<li class="js-editor-addon-button-wrapper js-editor-bs-button-wrapper">';
            codemirror_buttons +='<button type="button" class="button-secondary js-code-editor-toolbar-button js-codemirror-bs-component-button bs-'+index+'-button" data-bs_category="available_css" data-cm_instance="'+instance+'" data-bs_key="'+index+'" title="'+value.name+'" onclick="ToolsetCommon.BSComponentsEventsHandler.openBSDialog(this);">';
            codemirror_buttons +='<i class="'+value.button_icon+' bs-'+index+'-icon"></i>';
            codemirror_buttons +='</li>';
            
        });
        codemirror_buttons +='</ul>';
        
        // other buttons
        if(typeof $other === 'object' && _.keys($other).length > 0 ){
            codemirror_buttons += '<span class="toolset_qt_btn_group_labels toolset_qt_btn_group_labels_style">'+Toolset_CssComponent.DDL_CSS_JS.group_label_other+'</span>';
        
            codemirror_buttons += '<ul class="js-wpv-filter-edit-toolbar">';

            jQuery.each( $other, function( index, value ){

                codemirror_buttons +='<li class="js-editor-addon-button-wrapper js-editor-bs-button-wrapper">';
                codemirror_buttons +='<button type="button" class="button-secondary js-code-editor-toolbar-button js-codemirror-bs-component-button bs-'+index+'-button" data-bs_category="other" data-cm_instance="'+instance+'" data-bs_key="'+index+'" title="'+value.name+'"  onclick="ToolsetCommon.BSComponentsEventsHandler.openBSDialog(this);">';
                codemirror_buttons +='<i class="'+value.button_icon+' bs-'+index+'-icon"></i>';
                codemirror_buttons +='</li>';

            });
            codemirror_buttons +='</ul>';
        }
        
        return codemirror_buttons;

    };

    self.add_bootstrap_components_buttons = function( instance ) {
        if( typeof $bootstrap_components !== 'object' ){
            return;
        }
		
		if ( _.isUndefined( QTags.getInstance( instance ) ) ) {
			return;
		}

        if ( typeof( QTags.getInstance( instance ).getButton( 'bs_component_show_hide_button' ) ) === 'undefined'  ) {
            QTags.addButton(
                'bs_component_show_hide_button',
                Toolset_CssComponent.DDL_CSS_JS.button_toggle_show,
                self.bs_components_toggle_buttons,
                '',
                'b',
                Toolset_CssComponent.DDL_CSS_JS.button_toggle_show,
                140,
                instance
            );
        }
    };

    self.bs_components_toggle_buttons = function( instance ) {
        var button_id = instance.id;
        var regex = /qt_(.*)_bs_component_show_hide_button/i;
        var matches = button_id.match(regex);
        Toolset.hooks.doAction( 'bs_components_toggle_buttons', matches[1] );
    };

    jQuery( document ).on( 'quicktags-init', function( event, editor ) {
        if ( typeof( editor.getButton( 'bs_component_show_hide_button' ) ) !== 'undefined'  ) {
            $instance = editor.id;
            var $my_buttons = self.generate_codemirror_bs_buttons( $instance );
            self.wrap_codemirror_buttons( $instance, $my_buttons );
        }
    });
    

    self.init();
};
