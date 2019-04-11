/**
 * Backend script for the native post editor when editing Content Templates.
 *
 * @summary Content Template editor manager for native editor compatibility.
 *
 * @since 2.5.0
 * @requires jquery.js
 * @requires underscore.js
 */

/* global toolset_user_editors_native */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.NativeEditor = function( $ ) {

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

    /**
	 * When Content Templates are edited using either the native post editor or a page builder, WordPress keeps autosave
	 * data in the browser's local storage even though revisions are off for this post type. In order to prevent relevant
	 * notifications to appear, we need to manually remove them from there.
     */
	self.removeCtAutosaveDataFromSessionstorage = function() {
		var data,
			autosave_data_property,
			prefix = 'wp-autosave';

		autosave_data_property = self.findPropertyByPrefix( sessionStorage, prefix )
		if ( typeof autosave_data_property != 'undefined' ) {
			data = JSON.parse( autosave_data_property.value );

			for ( var property in data ) {
				if ( data.hasOwnProperty( property ) ) {
					if ( data[ property ].post_type == 'view-template' ) {
						delete data[ property ];
					}
				}
			}

			data = JSON.stringify( data );

			sessionStorage.setItem( autosave_data_property.key, data );
		}
	}

	self.removeUnnecessaryNativeEditorElements = function() {
		$( '.misc-pub-section.misc-pub-post-status, .misc-pub-section.misc-pub-visibility, .edit-timestamp, .page-title-action, .submitdelete.deletion, fieldset.metabox-prefs' ).remove();
	}

	self.init = function() {
		Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-gui-before-do-action', self.secureShortcodeFromSanitization );

        Toolset.hooks.addFilter( 'wpv-filter-wpv-shortcodes-transform-format', self.secureShortcodeFromSanitization );

		// Here the new Types shortcodes gets sanitized.
		Toolset.hooks.addFilter( 'toolset-filter-get-crafted-shortcode', self.secureShortcodeFromSanitization, 11 );

		self.removeCtAutosaveDataFromSessionstorage();

		self.removeUnnecessaryNativeEditorElements();

        /**
         * When Content Templates are edited using either the native post editor or a page builder, WordPress keeps autosave
         * data in the browser's local storage even though revisions are off for this post type. In order to prevent relevant
         * notifications to appear, we need to catch the heartbeat event and remove the needed data that will be used to
		 * create an autosave entry in the database.
         */
		$( document ).on( 'heartbeat-send.native', function( event, heartbeatData ){
			if(
				typeof( heartbeatData.wp_autosave ) != 'undefined'
				&& typeof( heartbeatData.wp_autosave.post_type ) != 'undefined'
				&& heartbeatData.wp_autosave.post_type == 'view-template'
			) {
				delete heartbeatData.wp_autosave;
			}
		});
	};
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.UserEditor.NativeEditorInstance = new ToolsetCommon.UserEditor.NativeEditor( $ );
});