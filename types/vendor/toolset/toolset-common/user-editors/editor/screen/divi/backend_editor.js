/**
 * Backend script for the native post editor when editing Content Templates using Divi Builder.
 *
 * @summary Content Template editor manager for Divi Builder using the native editor compatibility.
 *
 * @since 2.5.0
 * @requires jquery.js
 * @requires underscore.js
 */

/* global toolset_user_editors_native */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.DiviEditor = function( $ ) {

	var self = this;

    self.init = function() {
		$( '#et_pb_toggle_builder' ).remove();

        /**
         * When Content Templates are edited using either the native post editor or a page builder, WordPress keeps autosave
         * data in the browser's local storage even though revisions are off for this post type. In order to prevent relevant
         * notifications to appear, we need to catch the heartbeat event and remove the needed data that will be used to
         * create an autosave entry in the database.
         */
        $( document ).on( 'heartbeat-send.bb-heartbeat', function( event, heartbeatData ){
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
	ToolsetCommon.UserEditor.DiviEditorInstance = new ToolsetCommon.UserEditor.DiviEditor( $ );
});