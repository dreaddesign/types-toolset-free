/**
 * Backend script for the Content Template basic editor, which happens to be a Codemirror editor,
 * in Content Templates edit pages.
 * This initializes the third-party user editor buttons and moves them to the main Codemirror editor toolbar,
 * as first class buttons.
 *
 * @summary Content Template basic editor manager for third party editors compatibility.
 *
 * @since 2.3.0
 * @requires jquery.js
 * @requires underscore.js
 */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.BasicBackend = function( $ ) {

	var self = this;
	
	self.initUserEditorButtons = function() {
		var userEditorButtons = $( '.js-wpv-content-template-user-editor-buttons .js-wpv-ct-apply-user-editor' );
		if ( userEditorButtons.length != 0 ) {
			var toolbar = ( '.js-wpv-content-section .js-code-editor-toolbar ul' ),
				toolbarItem = $( '<li style="float:right"></li>' ).appendTo( toolbar );
			_.each( userEditorButtons, function( element, index, list ) {
				toolbarItem.append( $( element ) );
			});
		}
		return self;
	};

    self.init = function() {
		self.initUserEditorButtons();
    };
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.UserEditor.BasicBackendInstance = new ToolsetCommon.UserEditor.BasicBackend( $ );
});