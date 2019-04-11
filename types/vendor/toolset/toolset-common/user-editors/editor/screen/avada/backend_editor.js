/**
 * Backend script for the native post editor when editing Content Templates using Fusion Builder (Avada).
 *
 * @summary Content Template editor manager for Fusion Builder (Avada) using the native editor compatibility.
 *
 * @since 2.5.0
 * @requires jquery.js
 * @requires underscore.js
 */

/* global toolset_user_editors_native */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.AvadaEditor = function( $ ) {

	var self = this;

	self.init = function() {
		$( '#fusion_toggle_builder' ).remove();
	};
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.UserEditor.AvadaEditorInstance = new ToolsetCommon.UserEditor.AvadaEditor( $ );
});