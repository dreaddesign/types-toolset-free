var ToolsetCommon = ToolsetCommon || {};

ToolsetCommon.ToolsetExportImport = function( $ ) {

	var self = this;

    self.init = function() {
		
    };
	
	self.control_hidden_section = function( selector ) {
		var target = selector.data( 'target' );
		if ( selector.prop( 'checked' ) ) {
			$( '.js-toolset-control-hidden-setting-target-' + target ).fadeIn( 'fast' );
		} else {
			$( '.js-toolset-control-hidden-setting-target-' + target ).fadeOut( 'fast' );
		}
	};
	
	$( document ).on( 'change', '.js-toolset-control-hidden-setting', function() {
		self.control_hidden_section( $( this ) );
	});
	
	$( document ).on( 'click', '.js-toolset-nav-tab', function( e ) {
		e.preventDefault();
		var thiz = $( this ),
		target = thiz.data( 'target' ),
		current = $( '.js-toolset-nav-tab.nav-tab-active' ).data( 'target' );
		if ( ! thiz.hasClass( 'nav-tab-active' ) ) {
			$( '.js-toolset-nav-tab' ).removeClass( 'nav-tab-active' );
			$( '.js-toolset-tabbed-section-item-' + current ).fadeOut( 'fast', function() {
				$( '.js-toolset-tabbed-section-item' ).removeClass( 'toolset-tabbed-section-current-item' );
				thiz.addClass( 'nav-tab-active' );
				$( '.js-toolset-tabbed-section-item-' + target ).fadeIn( 'fast', function() {
					$( this ).addClass( 'toolset-tabbed-section-current-item' );
				});
			});
		}
	});

    self.init();

};

jQuery( document ).ready( function( $ ) {
	ToolsetCommon.export_import = new ToolsetCommon.ToolsetExportImport( $ );
});