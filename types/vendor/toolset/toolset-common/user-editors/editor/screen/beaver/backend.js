/**
 * Backend script for the Content Template editor or Beaver Builder, in Content Templates edit pages.
 * This initializs the Beaver Buildr fronten editor launcher with the right theme PHP template based 
 * on the kind of preview selected depending on the CT usage.
 *
 * @summary Content Template editor manager for Beaver Builder compatibility.
 *
 * @since 2.3.0
 * @requires jquery.js
 * @requires underscore.js
 */

/* global toolset_user_editors_beaver */

var ToolsetCommon			= ToolsetCommon || {};
ToolsetCommon.UserEditor	= ToolsetCommon.UserEditor || {};

ToolsetCommon.UserEditor.BeaverBuilderBackend = function( $ ) {

	var self = this;
	
	self.builderLaunchButton = $( '.js-toolset-user-editors-beaver-backend .fl-launch-builder' );
	
	$( '#toolset-user-editors-beaver-template-file' ).on( 'change', function() {
		self.builderLaunchButton
			.addClass( 'button-secondary button-disabled' )
			.removeClass( 'button-primary' )
			.prop( 'disabled', true );
		$.ajax( {
			type:		'post',
			dataType:	'json',
			url:		ajaxurl,
			data:		{
				action:			'toolset_user_editors_beaver',
				post_id:		toolset_user_editors_beaver.mediumId,
				template_path:	this.value,
				preview_domain:	$( '#toolset-user-editors-beaver-template-file option:selected' ).data( 'preview-domain' ),
				preview_slug:	$( '#toolset-user-editors-beaver-template-file option:selected' ).data( 'preview-slug' ),
				nonce:			toolset_user_editors_beaver.nonce
			},
			complete:	function() {
				self.builderLaunchButton
					.removeClass( 'button-secondary button-disabled' )
					.addClass( 'button-primary' )
					.prop( 'disabled', false );
			}
		} );
	});

    self.init = function() {
		
    };
	
	self.init();
	
};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.UserEditor.BeaverBuilderBackendInstance = new ToolsetCommon.UserEditor.BeaverBuilderBackend( $ );
});