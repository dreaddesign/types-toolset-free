/**
 * Backend script for the Content Template basic editor, which happens to be a Codemirror editor,
 * as loop elements in Views and WordPress Archives Loop output sections.
 * This initializes the third-party user editor buttons and moves them to the main Codemirror editor toolbar,
 * as first class buttons.
 *
 * @summary Inline Content Template basic editor manager for third party editors compatibility,.
 *
 * @since 2.3.0
 * @requires jquery.js
 * @requires underscore.js
 */

/* global wpv_inline_templates_i18n, toolset_user_editors_basic_layout_template_i18n */

var WPViews = WPViews || {};

if( typeof _ !== 'undefined' && _.templateSettings )
{
    _.templateSettings = {
        escape: /\{\{([^\}]+?)\}\}(?!\})/g,
        evaluate: /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
    };
}

WPViews.ViewEditScreenUserEditorBasic = function( $ ) {
	
	var self = this;
	
	self.selector = '.js-wpv-ct-listing';
    self.template_selector = '#js-wpv-layout-template-saving-overlay-template';
    self.overlayContainer = _.template( jQuery( self.template_selector ).html() );
    self.i18n_data = {
        title: toolset_user_editors_basic_layout_template_i18n.template_overlay.title,
    };
	
	self.initBasicEditors = function() {
		$( self.selector ).each( function() {
			self
				.initBasicEditor( $( this ) )
				.initUserEditorButtons( $( this ) );
		});
		return self;
	};
	
	self.initBasicEditor = function( item ) {
		if ( 
			item.hasClass( 'js-wpv-ct-listing-user-editor-inited' ) 
			|| item.find( '.CodeMirror' ).length == 0
		) {
			// This has been inited before, or it is rendered closed
			return self;
		}
		var attributes = item.data( 'attributes' );
		_.defaults( attributes, { builder: 'basic' } );
		if ( attributes.builder == 'basic' ) {
			item.addClass( 'js-wpv-ct-listing-user-editor-inited' );
			item.find( '.js-wpv-layout-template-overlay' ).remove();
			item.find( '.js-wpv-ct-apply-user-editor:not(.js-wpv-ct-apply-user-editor-basic)' ).prop( 'disabled', false );
			// Autoresize setting
			if ( 
				wpv_inline_templates_i18n.settings.codemirror_autoresize == 'true' 
				|| wpv_inline_templates_i18n.settings.codemirror_autoresize == '1' 
			) {
				item.find( '.CodeMirror' ).css( 'height', 'auto' );
				item.find( '.CodeMirror-scroll' ).css( {'overflow-y':'hidden', 'overflow-x':'auto', 'min-height':'15em'} );
			} else {
				item.find( '.CodeMirror' ).css( 'height', '300px' );
				item.find( '.CodeMirror-scroll' ).css( {'overflow':'scroll !important', 'min-height':'none'} );
			}
		}
		return self;
	};
	
	self.initUserEditorButtons = function( item ) {
		if ( 
			item.hasClass( 'js-wpv-ct-listing-user-editor-buttons-inited' ) 
			|| item.find( '.CodeMirror' ).length == 0
		) {
			return self;
		}
		var itemUserEditorButtons = item.find( '.js-wpv-inline-content-template-user-editor-buttons .js-wpv-ct-apply-user-editor' );
		if ( itemUserEditorButtons.length != 0 ) {
			var toolbar = item.find( '.js-code-editor-toolbar ul' ),
				toolbarItem = $( '<li style="float:right"></li>' ).appendTo( toolbar );
			_.each( itemUserEditorButtons, function( element, index, list ) {
				toolbarItem.append( $( element ) );
			});
		}
		item.addClass( 'js-wpv-ct-listing-user-editor-buttons-inited' )
		return self;
	};
	
	self.applyLoadingOverlay = function( item ) {
		item.find( '.js-wpv-ct-apply-user-editor' ).prop( 'disabled', true );
		item.removeClass( 'js-wpv-ct-listing-user-editor-inited' );
		item.find( '.js-wpv-layout-template-overlay' ).remove();
        item.prepend( self.overlayContainer( self.i18n_data ) );
		item.find( '.CodeMirror' ).css( { 'height' : '0px'} );
	};
	
	self.setInlineContentTemplateEvents = function( templateId ) {
		self
			.initBasicEditor( $( '.js-wpv-ct-listing-' + templateId ) )
			.initUserEditorButtons( $( '.js-wpv-ct-listing-' + templateId ) );
	};
	
	// @note This is firing only when adding a new inline CT for a loop.
	// Do not expect this to happen on pageload when a loop already has one CT which renders open
	// Because this is fired earlier in the document.ready chain, and we never get here.
	// That is why we init on init too.
	$( document ).on( 'js_event_wpv_ct_inline_editor_inited', function( event, templateId ) {
		self
			.initBasicEditor( $( '.js-wpv-ct-listing-' + templateId ) )
			.initUserEditorButtons( $( '.js-wpv-ct-listing-' + templateId ) );
	});
	
	self.setUserEditorToBasic = function( ctId ) {
		var item = $( '.js-wpv-ct-listing-' + ctId, '.js-wpv-inline-content-template-listing' ),
			attributes = item.data( 'attributes' );
		
		attributes.builder = 'basic';
		item.data( 'attributes', attributes );
		
		if ( item.find( '.CodeMirror' ).length == 0 ) {
			item.find( '.js-wpv-content-template-open' ).trigger( 'click' );
		} else {
			self.initBasicEditor( item );
		}
	};
	
	$( document ).on( 'click', '.js-wpv-ct-apply-user-editor', function( e ) {
		e.preventDefault();
		
		var thiz = $( this ),
			editor = thiz.data( 'editor' ),
			item = thiz.closest( self.selector ),
			ctId = item.data( 'id' ),
			data = {
				action:		'toolset_set_layout_template_user_editor',
				ct_id:		ctId,
				editor:		editor,
				wpnonce:	toolset_user_editors_basic_layout_template_i18n.wpnonce
			};
		
		self.applyLoadingOverlay( item );
		
		$.ajax({
			type:		"POST",
			dataType:	"json",
			url:		ajaxurl,
			data:		data,
			success:	function( response ) {
				if ( response.success ) {
					Toolset.hooks.doAction( 'toolset-action-toolset-set-user-editor-to-' + editor, ctId );
				}
			},
			error:		function ( ajaxContext ) {
				//console.log( "Error: ", ajaxContext.responseText );
			},
			complete:	function() {
				
			}
		});		
		
	});
	
	self.initHooks = function() {
		Toolset.hooks.addAction( 'wpv-action-wpv-set-inline-content-template-events', self.setInlineContentTemplateEvents );
		Toolset.hooks.addAction( 'toolset-action-toolset-set-user-editor-to-basic', self.setUserEditorToBasic );
		return self;
	};
	
	self.init = function() {
		self.initBasicEditors()
			.initHooks();
		
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
	WPViews.ViewEditScreenUserEditorBasicInstance = new WPViews.ViewEditScreenUserEditorBasic( $ );
});