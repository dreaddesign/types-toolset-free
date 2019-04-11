var ToolsetTypes = ToolsetTypes || {};

ToolsetTypes.ExportImportScreen = function( $ ) {
	
	var self = this;
	
	$( document ).on( 'change', '.js-types-import-method input', function() {
		var selected = $( '.js-types-import-method input:checked' ).val();
		$( '.js-types-import-method-extra' ).hide();
		$( '.js-types-import-method-extra-' + selected ).fadeIn( 'fast' );
	});
	
	self.init = function() {
		
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    ToolsetTypes.export_import_screen = new ToolsetTypes.ExportImportScreen( $ );
});