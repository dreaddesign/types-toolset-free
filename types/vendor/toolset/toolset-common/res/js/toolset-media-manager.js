/**
 * API and helper functions for media buttons over custom editors and inputs.
 *
 * @since m2m
 * @package Toolset
 * @todo add support for input media buttons, with audio/video/image/file restrictions
 */

var Toolset = Toolset || {};

var Toolset = Toolset  || {};
if ( typeof Toolset.mediaManager === "undefined" ) {
    Toolset.mediaManager = {};
}

Toolset.mediaManager.Class = function( $ ) {
	
	var self = this;
	
	self.wpMediaEditor = wp.media.editor;
	
	self.editorSelector = '.js-toolset-editor-media-manager';
	self.inputSelector  = '.js-toolset-input-media-manager';
	
	self.instances = {
		editor: {},
		input:  {}
	};
	
	self.getPostId = function( $mediaButton ) {
		var referredId = $mediaButton.data( 'postid' ),
			postId = 0;
		
		if ( referredId ) {
			postId = parseInt( referredId ) || 0;
		}
		
		return postId;
	};
	
	self.setTarget = function( $mediaButton ) {
		var targetId = $mediaButton.data( 'target' );
		
		if ( targetId ) {
			window.wpcfActiveEditor = targetId;
		}
	};
	
	self.getInstanceKey = function( postId ) {
		return 'toolsetMediaFor' + postId;
	};
	
	self.setEditorMediaManagerEvents = function( instanceKey ) {
		// Watch changes in wp-includes/js/media-editor.js
		var workflow = self.instances.editor[ instanceKey ];
		
		self.instances.editor[ instanceKey ].on( 'insert', function() {
			
			var state = self.instances.editor[ instanceKey ].state(),
				selection = state.get( 'selection' );
				
			if ( ! selection ) {
				return;
			}

			$.when.apply( $, selection.map( function( attachment ) {
				var display = state.display( attachment ).toJSON();
				return self.wpMediaEditor.send.attachment( display, attachment.toJSON() );
			}, self.wpMediaEditor ) ).done( function() {
				icl_editor.insert( _.toArray( arguments ).join('\n\n') );
			});
		});

		workflow.state( 'gallery-edit' ).on( 'update', function( selection ) {
			icl_editor.insert( wp.media.gallery.shortcode( selection ).string() );
		}, self.wpMediaEditor );

		workflow.state( 'playlist-edit' ).on( 'update', function( selection ) {
			icl_editor.insert( wp.media.playlist.shortcode( selection ).string() );
		}, self.wpMediaEditor );

		workflow.state( 'video-playlist-edit' ).on( 'update', function( selection ) {
			icl_editor.insert( wp.media.playlist.shortcode( selection ).string() );
		}, self.wpMediaEditor );

		workflow.state( 'embed' ).on( 'select', function() {
			var state = workflow.state(),
				type = state.get( 'type' ),
				embed = state.props.toJSON();

			embed.url = embed.url || '';

			if ( 'link' === type ) {
				_.defaults( embed, {
					linkText: embed.url,
					linkUrl: embed.url
				});

				self.wpMediaEditor.send.link( embed ).done( function( resp ) {
					icl_editor.insert( resp );
				});

			} else if ( 'image' === type ) {
				_.defaults( embed, {
					title:   embed.url,
					linkUrl: '',
					align:   'none',
					link:    'none'
				});

				if ( 'none' === embed.link ) {
					embed.linkUrl = '';
				} else if ( 'file' === embed.link ) {
					embed.linkUrl = embed.url;
				}

				icl_editor.insert( wp.media.string.image( embed ) );
			}
		}, self.wpMediaEditor );
	};
	
	$( document ).on( 'click', self.editorSelector, function( e ) {
		e.preventDefault();
		var $mediaButton = $( this ),
			postId = self.getPostId( $mediaButton ),
			instanceKey = self.getInstanceKey( postId );
		
		self.setTarget( $mediaButton );
		
		if ( _.has( self.instances.editor, instanceKey ) ) {
			self.instances.editor[ instanceKey ].open();
			return;
		}
		
		wp.media.model.settings.post.id = postId;
		
		self.instances.editor[ instanceKey ] = wp.media({
			className: 'media-frame js-toolset-media-frame',
			frame: 'post'
		});
		
		self.setEditorMediaManagerEvents( instanceKey );
		
		self.instances.editor[ instanceKey ].open();
		
	});
	
};

jQuery( document ).ready( function( $ ) {
    Toolset.mediaManager.main = new Toolset.mediaManager.Class( $ );
});