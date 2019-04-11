/**
 * Backend script for the Visual Composer editor when editing Content Templates.
 *
 * @summary Content Template editor manager for isual Composer editor compatibility.
 *
 * @since 2.5.0
 * @requires jquery.js
 * @requires underscore.js
 */

/* global toolset_user_editors_visual_composer */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.VisualComposerEditor = function( $ ) {

	var self = this;

    /**
     * In Views 2.5.0, we introduced support for shortcodes using placeholders instead of brackets. The selected placeholder
     * for the left bracket "[" was chosen to be the "{!{" and the selected placeholder for the right bracket "]" was chosen
	 * to be the "}!}". This was done to allow the use of Toolset shortcodes inside the various page builder module fields.
     * Here, we are replacing the instances of brackets with placeholders to allow them to be used in the various page builder
	 * module fields.
	 **/
	self.secureShortcodeFromSanitization = function( shortcode_data ) {
        var shortcode_string;
		if ( typeof( shortcode_data ) === 'object' ) {
            shortcode_string = shortcode_data.shortcode;
		} else {
            shortcode_string = shortcode_data;
		}

		shortcode_string = shortcode_string.replace( /\[/g, '{!{' ).replace( /]/g, '}!}' );

		if ( -1 !== shortcode_string.indexOf( '[wpv-conditional' ) ) {
            shortcode_string.replace( /"/g, '\'' );
        };

        if ( typeof( shortcode_data ) === 'object' ) {
            shortcode_data.shortcode = shortcode_string;
        } else {
            shortcode_data = shortcode_string;
        }

		return shortcode_data;
	}

	self.findPropertyByPrefix = function(object, prefix) {
		for ( var property in object ) {
			if (
				object.hasOwnProperty( property )
				&& property.toString().startsWith( prefix )
			) {
				return { key: property, value: object[ property ] };
			}
		}
	}

    self.initializeVisualComposerBackendEditor = function() {
        $( window ).load( function() {
            /* no fullscreen, no vc save button */
            jQuery( '#vc_navbar .vc_save-backend, #vc_fullscreen-button' ).remove();

            /* show vc editor */
            vc.app.show();
            vc.app.status = 'shown';

            var viewsBasicTextarea 		 = jQuery( '#wpv_content' );
            var wordpressDefaultTextarea = jQuery( '#content' );

            var viewsCSSArea = jQuery( '#wpv_template_extra_css' );
            vc.post_settings_view.on( 'save', function() {
                if( this.saved_css_data != vc.$custom_css.val() ) {
                    viewsCSSArea.val( vc.$custom_css.val() );

                    WPViews.ct_edit_screen.vm.templateCssAccepted = function(){ return vc.$custom_css.val(); };
                    WPViews.ct_edit_screen.vm.propertyChangeByComparator( 'templateCss', _.isEqual );
                }
            } );

            /* Visual Composer fires the 'sync' event everytime something is changed */
            /* we use this to enable button 'Save all sections at once' if content has changed */
            vc.shortcodes.on( 'sync', function() {
                if( wordpressDefaultTextarea.val() != viewsBasicTextarea.val() ) {
                    viewsBasicTextarea.val( wordpressDefaultTextarea.val() );

                    WPViews.ct_edit_screen.vm.postContentAccepted = function(){ return wordpressDefaultTextarea.val() };
                    WPViews.ct_edit_screen.vm.propertyChangeByComparator( 'postContent', _.isEqual );
                }
            } );
        });
	}

	self.init = function() {
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-before-do-action', self.secureShortcodeFromSanitization );

        Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-transform-format', self.secureShortcodeFromSanitization );

        self.initializeVisualComposerBackendEditor();
	};
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.UserEditor.VisualComposerEditorInstance = new ToolsetCommon.UserEditor.VisualComposerEditor( $ );
});