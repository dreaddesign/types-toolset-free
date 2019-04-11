var ToolsetCommon = ToolsetCommon || {};

ToolsetCommon.ToolsetSettings = function( $ ) {

	var self = this;
	
	self.overlay_container = $("<div class='toolset-setting-overlay js-toolset-setting-overlay'><div class='toolset-transparency'></div><i class='icon-lock fa fa-lock'></i></div>");

    self.init = function() {
		self.handle_options_changes();
		self.bootstrap_version_change_handler();
		self.update_event_triggered();
		self.update_event_completed();
		self.update_event_failed();
		self.check_selected_bootstrap_version( toolset_settings_texts.toolset_bootstrap_version_filter, toolset_settings_texts.toolset_bootstrap_version_selected );
		self.trigger_tab_switch_after_load();
    };


    self.handle_options_changes = function(){
		$( document ).on( 'click', '.js-toolset-nav-tab', function( e ) {
			e.preventDefault();
			var thiz = $( this ),
				target = thiz.data( 'target' ),
				current = $( '.js-toolset-nav-tab.nav-tab-active' ).data( 'target' );
			if ( ! thiz.hasClass( 'nav-tab-active' ) ) {
				$( '.js-toolset-nav-tab.nav-tab-active' ).removeClass( 'nav-tab-active' );
				$( '.js-toolset-tabbed-section-item-' + current ).fadeOut( 'fast', function() {
					$( '.js-toolset-tabbed-section-item' ).removeClass( 'toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item' );
					thiz.addClass( 'nav-tab-active' );
					$( '.js-toolset-tabbed-section-item-' + target ).fadeIn( 'fast', function() {
						$( this ).addClass( 'toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item' );
						thiz.trigger( 'toolsetSettings:afterTabSwitch', target );
					});
				});
			}
		});
	};

	/**
	 * Triggers an after tab switch event for active tab, so listeners can render stuff that's waiting for such event.
	 *
	 * Example when this is needed: tab opened in another browser tab.
	 * After load is so that listeners have time to register on document ready.
	 */
	self.trigger_tab_switch_after_load = function(){
		$( window ).load(function(){
			var active_tab = $('.js-toolset-nav-tab.nav-tab-active').data( 'target' );
			$('.js-toolset-nav-tab').trigger( 'toolsetSettings:afterTabSwitch', active_tab );
		})
	};

	/**
	 * --------------------
	 * Bootstrap
	 * --------------------
	 */

	self.bootstrap_version_state = ( $('.js-toolset-bootstrap-version:checked').length > 0 ) ? $('.js-toolset-bootstrap-version:checked').val() : false;

	self.bootstrap_version_change_handler = function(){
		$( '.js-toolset-bootstrap-version' ).on( 'change', function( ) {
			self.bootstrap_version_options_debounce_update();
			self.check_selected_bootstrap_version( toolset_settings_texts.toolset_bootstrap_version_filter, $('.js-toolset-bootstrap-version:checked').val() )
		});
	};


	self.check_selected_bootstrap_version = function( filter_version, db_version ){


		$('.js-tolset-option-'+toolset_settings_texts.toolset_bootstrap_version_filter.replace('.','')).append('<div id="js-different-bs-versions" style="display:none;" class="notice inline notice-warning notice-alt"><p>'+toolset_settings_texts.toolset_theme_loads_own_bs+'</p></div>')

		if( filter_version !== db_version && $.inArray(db_version,['','98','99']) === -1 ){
			$('#js-different-bs-versions').show();
		} else {
			$('#js-different-bs-versions').hide();
		}

	};

	self.save_bootstrap_version_options = function() {
		if ( self.bootstrap_version_state != $( '.js-toolset-bootstrap-version:checked' ).val() ) {
			var data = {
				action: 'toolset_update_bootstrap_version_status',
				status: $('.js-toolset-bootstrap-version:checked').val(),
				wpnonce: $('#toolset_bootstrap_version_nonce').val()
			};

			$.ajax({
				type: "POST",
				dataType: "json",
				url: ajaxurl,
				data: data,
				success: function( response ) {
					if ( response.success ) {
						self.bootstrap_version_state = $('.js-toolset-bootstrap-version:checked').val();
						$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
					} else {
						$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
					}
				},
				error: function( ajaxContext ) {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
				},
				complete: function() {

				}
			});
		}
	};

	self.bootstrap_version_options_debounce_update = _.debounce( self.save_bootstrap_version_options, 1000 );

	
	/**
	* --------------------
	* WordPress Admin Bar options
	* --------------------
	*/
	
	self.admin_bar_state = $( '#toolset-admin-bar-settings :input' ).serialize();
	
	$( '.js-toolset-admin-bar-options' ).on( 'change', function() {
		self.admin_bar_options_debounce_update();
	});
	
	self.save_admin_bar_options = function() {
		if ( self.admin_bar_state != $( '.js-toolset-admin-bar-settings :input' ).serialize() ) {
			var data = {
				action: 'toolset_update_toolset_admin_bar_options',
				frontend: $( '#js-toolset-admin-bar-menu' ).prop( 'checked' ),
				backend: $('.js-toolset-shortcodes-generator:checked').val(),
				wpnonce: $('#toolset_admin_bar_settings_nonce').val()
			};
			$( document ).trigger( 'js-toolset-event-update-setting-section-triggered' );
			$.ajax({
				type: "POST",
				dataType: "json",
				url: ajaxurl,
				data: data,
				success: function( response ) {
					if ( response.success ) {
						self.admin_bar_state = $( '.js-toolset-admin-bar-settings :input' ).serialize();
						$( document ).trigger( 'js-toolset-event-update-setting-section-completed' );
					} else {
						$( document ).trigger( 'js-toolset-event-update-setting-section-failed', [ response.data ] );
					}
				},
				error: function( ajaxContext ) {
					$( document ).trigger( 'js-toolset-event-update-setting-section-failed' );
				},
				complete: function() {
					
				}
			});
		}
	};
	
	self.admin_bar_options_debounce_update = _.debounce( self.save_admin_bar_options, 1000 );

	self.update_event_triggered = function(){
		$( document ).on( 'js-toolset-event-update-setting-section-triggered', function( event ) {
			$( '#js-toolset-ajax-saving-messages' )
				.html( toolset_settings_texts.autosave_saving )
				.show();
		});
	};

	self.update_event_completed = function(){
		$( document ).on( 'js-toolset-event-update-setting-section-completed', function( event ) {
			$( '#js-toolset-ajax-saving-messages' )
				.html( toolset_settings_texts.autosave_saved )
				.addClass( 'toolset-ajax-saving-messages-success' )
				.show();
			setTimeout( function () {
				$( '#js-toolset-ajax-saving-messages' ).removeClass( 'toolset-ajax-saving-messages-success' );
			}, 1000 );
		});
	};

	self.update_event_failed = function(){
		$( document ).on( 'js-toolset-event-update-setting-section-failed', function( event, data ) {
			var message = ( typeof data === 'undefined' || _.has( data, "message" ) ) ? toolset_settings_texts.autosave_failed : data.message;
			$( '#js-toolset-ajax-saving-messages' )
				.html( message )
				.addClass( 'toolset-ajax-saving-messages-fail' );
			$( '.js-toolset-tabbed-section-item' )
				.css( { 'position': 'relative' } )
				.prepend( self.overlay_container );
		});
	};

	
	self.init();

};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.settings = new ToolsetCommon.ToolsetSettings( $ );
});