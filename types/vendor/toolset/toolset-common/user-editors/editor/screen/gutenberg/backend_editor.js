/**
 * Backend script to be used when editing Content Templates using Gutenberg Editor.
 *
 * @summary Content Template editor manager for Gutenberg Editor.
 *
 * @since 2.6.9
 * @requires jquery.js
 * @requires underscore.js
 */

/* global toolset_user_editors_native */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.GutenbergEditor = function( $ ) {

	var self = this;

	self.init = function() {
		// The following two lines are a fix for the Gutenberg issue where the admin notices are hidden behind the Editor
		// toolbar. They can be removed when https://github.com/WordPress/gutenberg/issues/3395 is fixed.
		$( '.toolset-notice-wp' ).appendTo( '.components-notice-list' );
		$( '.components-notice-list' ).css( 'position', 'initial' );
	};
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.UserEditor.GutenbergEditorInstance = new ToolsetCommon.UserEditor.GutenbergEditor( $ );
});