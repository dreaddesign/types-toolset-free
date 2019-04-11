var ToolsetTypes = ToolsetTypes || {};

ToolsetTypes.SettingsScreen = function( $ ) {
	
	var self = this;
	
	/**
	* Images
	*/
	
	$( document ).on( 'click', '.js-wpcf-settings-clear-cache-images', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpcf-settings-clear-cache-images-container' ),
		spinnerContainer = $( '<div class="toolset-spinner ajax-loader">' ).appendTo( thiz_container ).show();
		thiz.prop('disabled', true );
		self.save_settings_section( 'wpcf_settings_clear_cache_images', 'all' )
			.done( function( response ) {
				if ( response.success ) {
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.prop('disabled', false );
			});
	});
	
	$( document ).on( 'click', '.js-wpcf-settings-clear-cache-images-outdated', function() {
		var thiz = $( this ),
		thiz_container = thiz.closest( '.js-wpcf-settings-clear-cache-images-container' ),
		spinnerContainer = $( '<div class="toolset-spinner ajax-loader">' ).appendTo( thiz_container ).show();
		thiz.prop('disabled', true );
		self.save_settings_section( 'wpcf_settings_clear_cache_images', 'outdated' )
			.done( function( response ) {
				if ( response.success ) {
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			})
			.always( function() {
				spinnerContainer.remove();
				thiz.prop('disabled', false );
			});
	});
	
	self.wpcf_image_state = $( '.js-toolset-wpcf-image-settings input, .js-toolset-wpcf-image-settings select' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-wpcf-image-settings input, .js-toolset-wpcf-image-settings select', function() {
		if ( self.wpcf_image_state != $( '.js-toolset-wpcf-image-settings input, .js-toolset-wpcf-image-settings select' ).serialize() ) {
			self.wpcf_image_options_debounce_update();
		}
	});
	
	self.save_wpcf_image_options = function() {
		var data = $( '.js-toolset-wpcf-image-settings input, .js-toolset-wpcf-image-settings select' ).serialize();
		self.save_settings_section( 'wpcf_settings_save_image_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.wpcf_image_state = $( '.js-toolset-wpcf-image-settings input, .js-toolset-wpcf-image-settings select' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.wpcf_image_options_debounce_update = _.debounce( self.save_wpcf_image_options, 1000 );
	
	/**
	* Help box
	*/
	
	self.wpcf_help_box_state = $( '.js-toolset-wpcf-help-box-settings input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-wpcf-help-box-settings input', function() {
		if ( self.wpcf_help_box_state != $( '.js-toolset-wpcf-help-box-settings input' ).serialize() ) {
			self.wpcf_help_box_options_debounce_update();
		}
	});
	
	self.save_wpcf_help_box_options = function() {
		var data = $( '.js-toolset-wpcf-help-box-settings input' ).serialize();
		self.save_settings_section( 'wpcf_settings_save_help_box_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.wpcf_help_box_state = $( '.js-toolset-wpcf-help-box-settings input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.wpcf_help_box_options_debounce_update = _.debounce( self.save_wpcf_help_box_options, 1000 );
	
	/**
	* Custom field metabox
	*/
	
	self.wpcf_custom_field_metabox_state = $( '.js-toolset-wpcf-custom-field-metabox-settings input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-wpcf-custom-field-metabox-settings input', function() {
		if ( self.wpcf_custom_field_metabox_state != $( '.js-toolset-wpcf-custom-field-metabox-settings input' ).serialize() ) {
			self.wpcf_custom_field_metabox_options_debounce_update();
		}
	});
	
	self.save_wpcf_custom_field_metabox_options = function() {
		var data = $( '.js-toolset-wpcf-custom-field-metabox-settings input' ).serialize();
		self.save_settings_section( 'wpcf_settings_save_custom_field_metabox_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.wpcf_custom_field_metabox_state = $( '.js-toolset-wpcf-custom-field-metabox-settings input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.wpcf_custom_field_metabox_options_debounce_update = _.debounce( self.save_wpcf_custom_field_metabox_options, 1000 );
	
	/**
	* Unfiltered HTML
	*/
	
	self.wpcf_unfiltered_html_state = $( '.js-toolset-wpcf-unfiltered-html-settings input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-wpcf-unfiltered-html-settings input', function() {
		if ( self.wpcf_unfiltered_html_state !=  $( '.js-toolset-wpcf-unfiltered-html-settings input' ).serialize() ) {
			self.wpcf_unfiltered_html_options_debounce_update();
		}
	});
	
	self.save_wpcf_unfiltered_html_options = function() {
		var data = $( '.js-toolset-wpcf-unfiltered-html-settings input' ).serialize();
		self.save_settings_section( 'wpcf_settings_save_unfiltered_html_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.wpcf_unfiltered_html_state = $( '.js-toolset-wpcf-unfiltered-html-settings input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.wpcf_unfiltered_html_options_debounce_update = _.debounce( self.save_wpcf_unfiltered_html_options, 1000 );
	
	/**
	* WPML
	*/
	
	self.wpcf_wpml_state = $( '.js-toolset-wpml-wpcf input' ).serialize();
	
	$( document ).on( 'change', '.js-toolset-wpml-wpcf input', function() {
		if ( self.wpcf_wpml_state !=  $( '.js-toolset-wpml-wpcf input' ).serialize() ) {
			self.wpcf_wpml_options_debounce_update();
		}
	});
	
	self.save_wpcf_wpml_options = function() {
		var data = $( '.js-toolset-wpml-wpcf input' ).serialize();
		self.save_settings_section( 'wpcf_settings_save_wpml_settings', data )
			.done( function( response ) {
				if ( response.success ) {
					self.wpcf_wpml_state = $( '.js-toolset-wpml-wpcf input' ).serialize();
					$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
				} else {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
				}
			})
			.fail( function( ajaxContext ) {
				$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
			});
		
	};
	
	self.wpcf_wpml_options_debounce_update = _.debounce( self.save_wpcf_wpml_options, 1000 );
	
	/**
	* Helper method for saving settings
	*/
	
	self.save_settings_section = function( save_action, save_data ) {
		var data = {
			action:			save_action,
			settings:		save_data,
			wpnonce:		wpcf_settings_i18n.wpcf_settings_nonce
		};
		$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
		return $.ajax({
			url:		ajaxurl,
			data:		data,
			type:		"POST",
			dataType:	"json"
		});
	};
	
	self.init = function() {
		
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    ToolsetTypes.settings_screen = new ToolsetTypes.SettingsScreen( $ );
});