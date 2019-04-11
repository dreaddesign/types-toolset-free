var ToolsetCommon = ToolsetCommon || {};

/**
 * Bootstrao Grid module.
 *
 * Provides a Grid button on selected editors that allows inserting a Bootstrap grid HTML structure.
 *
 * @since 2.3.3
 */


ToolsetCommon.BootstrapCssComponentsGrids = function( $ ) {

    var self = this;
	
	/**
     * Init the Bootstrap grid dialog.
	 *
	 * @return selc;
     *
     * @since 2.3.3
     */
	
	self.initDialogs = function() {
		
		if ( ! $( '#js-toolset-bootstrap-grid-dialog-container' ).length ) {
			$( 'body' ).append( '<div id="js-toolset-bootstrap-grid-dialog-container" class="toolset-shortcode-gui-dialog-container"></div>' );
		}
		
		self.bootstrapGridDialog = $( "#js-toolset-bootstrap-grid-dialog-container" )
			.html( Toolset_CssComponent_Grids.dialog.content )
			.dialog({
                autoOpen: false,
                modal: true,
                title: Toolset_CssComponent_Grids.dialog.title,
                minWidth: 550,
                draggable: false,
                resizable: false,
                position: {
                    my: "center top+50",
                    at: "center top",
                    of: window,
                    collision: "none"
                },
                show: {
                    effect: "blind",
                    duration: 800
                },
                create: function( event, ui ) {
                    
                },
                open: function( event, ui ) {
                    $( 'body' ).addClass( 'modal-open' );
					self.restoreBootstrapGridDialogOptions();
                },
                close: function( event, ui ) {
                    $( 'body' ).removeClass( 'modal-open' );
                },
                buttons: [
                    {
                        class: 'button-secondary toolset-shortcode-gui-dialog-button-close',
                        text: Toolset_CssComponent_Grids.dialog.cancel,
                        click: function () {
                            $( this ).dialog( "close" );
                        }
                    },
                    {
                        class: 'toolset-shortcode-gui-dialog-button-align-right button-primary',
                        text: Toolset_CssComponent_Grids.dialog.insert,
                        click: function () {
                            self.insertBootstrapGrid();
                        }
                    }
                ]
            });
		
		return self;
		
	};

    /**
     * Open the Bootstrap grid dialog.
     *
     * @since 2.3.3
     */

    $( document ).on( 'click', '.js-toolset-bootstrap-grid-in-toolbar', function( e ) {
        e.preventDefault();
        window.wpcfActiveEditor = $( this ).data( 'editor' );
        self.bootstrapGridDialog.dialog( 'open' );
    });

    /**
     * Insert the bootstrap grid into the relevant editor.
     *
     * @since 2.3.3
	 *
	 * @todo Check how to integrate the highlighting in the icl_insert mechanism
     */

    self.insertBootstrapGrid = function() {
        var $grid = self.getBootstrapGrid();
		self.bootstrapGridDialog.dialog( 'close' );
		window.icl_editor.insert( $grid );
    };

    /**
     * Restore the bootstrap grid dialog to defaults on close.
     *
     * @since 2.3.3
     */

    self.restoreBootstrapGridDialogOptions = function() {
        var $defaultGridType = $( document ).find( 'ul.js-toolset-bootstrap-grid-type figure' ).first(),
            $defaultRadio = $defaultGridType.closest( 'li' ).find( 'input[name="grid_type"]' );

        $( document )
			.find( 'ul.js-toolset-bootstrap-grid-type figure' )
				.each( function () {
					$( this ).removeClass( 'selected' );
				});

        $defaultGridType.addClass( 'selected' );
        $defaultRadio.trigger( 'click' );
    };

    /**
     * Get the bootstrap grid given the dialog settings.
	 *
	 * @return string Grid HTML structure.
     *
     * @since 2.3.2
     */

    self.getBootstrapGrid = function() {
        var output = '';
		
        output += '<div class="row">\n';

        switch( $( 'input[name="grid_type"]:checked' ).val() ) {
            case 'two-even':
                output += '\t<div class="col-sm-6">Cell 1</div>\n';
                output += '\t<div class="col-sm-6">Cell 2</div>\n';
                break;
            case 'two-uneven':
                output += '\t<div class="col-sm-8">Cell 1</div>\n';
                output += '\t<div class="col-sm-4">Cell 2</div>\n';
                break;
            case 'three-even':
                output += '\t<div class="col-sm-4">Cell 1</div>\n';
                output += '\t<div class="col-sm-4">Cell 2</div>\n';
                output += '\t<div class="col-sm-4">Cell 3</div>\n';
                break;
            case 'three-uneven':
                output += '\t<div class="col-sm-3">Cell 1</div>\n';
                output += '\t<div class="col-sm-6">Cell 2</div>\n';
                output += '\t<div class="col-sm-3">Cell 3</div>\n';
                break;
            case 'four-even':
                output += '\t<div class="col-sm-3">Cell 1</div>\n';
                output += '\t<div class="col-sm-3">Cell 2</div>\n';
                output += '\t<div class="col-sm-3">Cell 3</div>\n';
                output += '\t<div class="col-sm-3">Cell 4</div>\n';
                break;
            case 'six-even':
                output += '\t<div class="col-sm-2">Cell 1</div>\n';
                output += '\t<div class="col-sm-2">Cell 2</div>\n';
                output += '\t<div class="col-sm-2">Cell 3</div>\n';
                output += '\t<div class="col-sm-2">Cell 4</div>\n';
                output += '\t<div class="col-sm-2">Cell 5</div>\n';
                output += '\t<div class="col-sm-2">Cell 6</div>\n';
                break;
            default:
                output += '\t<div class="col-sm-6">Cell 1</div>\n';
                output += '\t<div class="col-sm-6">Cell 2</div>\n';
        }

        output += '</div>';

        return output;
    };
	
	/**
	 * Manage the Bootstrap dialog options interaction.
	 *
	 * @since 2.3.3
	 */

    $( document ).on( 'click', '.js-toolset-bootstrap-grid-type figure', function( e ) {
        var $figure = $( this ),
            $radio = $figure.closest( 'li' ).find( 'input[name="grid_type"]' );

        $( document )
			.find( 'ul.js-toolset-bootstrap-grid-type figure' )
				.each( function () {
					$( this ).removeClass( 'selected' );
				});

        $figure.addClass( 'selected' );
        $radio.trigger( 'click' );
    });
	
	/** 
	 * Add the Bootstrap grid button to selected editors.
	 *
	 * @param string The ID of the editor being initialized.
	 * @return self;
	 *
	 * @since 2.3.3
	 */
	
	self.maybe_add_editor_button = function( editorId ) {
		var editor = $( '#' + editorId ),
			button = '<button class="button button-secondary js-toolset-bootstrap-grid-in-toolbar" data-editor="' + editorId + '"><i class="icon-bootstrap-original-logo ont-icon-18"></i>' + Toolset_CssComponent_Grids.button.label + '</button>',
			appendGridButtonIfMissing = function( toolbarList, toolbarButton ) {
				if ( toolbarList.find( '.js-toolset-bootstrap-grid-in-toolbar' ).length === 0 ) {
					toolbarList.append( toolbarButton );
				}
			};
		
		switch ( editorId ) {
			case 'wpv_filter_meta_html_content':
                // Views Filter Editor
				var toolbarButton = $( '<li>' + button + '</li>' ),
					toolbarList = editor
						.closest( '.js-code-editor' )
						.find( '.js-code-editor-toolbar > ul.js-wpv-filter-edit-toolbar' );
				appendGridButtonIfMissing( toolbarList, toolbarButton );
				break;
			case 'wpv_layout_meta_html_content':
			case 'wpv_content':
				// Views Loop Output Editor and Filter and Loop Output Integration Editor
				var toolbarButton = $( '<li>' + button + '</li>' ),
					toolbarList = editor
						.closest( '.js-code-editor' )
							.find( '.js-code-editor-toolbar > ul' );
				appendGridButtonIfMissing( toolbarList, toolbarButton );
				break;
			case 'visual-editor-html-editor':
				// Layouts visual editor cell HTML
				var toolbarButton = $( '<li>' + button + '</li>' ),
					toolbarList = editor
						.closest( '#js-visual-editor-codemirror' )
							.find( '.js-code-editor-toolbar > ul' );
				appendGridButtonIfMissing( toolbarList, toolbarButton );
				break;
			case 'cred_association_form_content':
				// CRED association forms editor
				var toolbarButton = $( button ),
					toolbarList = editor
						.closest( '#association_form_content' )
							.find( '.js-cred-content-editor-toolbar' );
				appendGridButtonIfMissing( toolbarList, toolbarButton );
				break;
			case 'content':
				// CRED main editors
				if (
					$( 'body' ).hasClass( 'post-type-cred-form' ) 
					|| $( 'body' ).hasClass( 'post-type-cred-user-form' ) 
				) {
					var toolbarButton = $( button ),
						toolbarList = editor
							.closest( '.wp-editor-wrap' )
								.find( '.wp-media-buttons' );
					appendGridButtonIfMissing( toolbarList, toolbarButton );
				}
				break;
			default:
				// Views inline CT editors
				// Layouts CT cells editors
				if ( editorId.indexOf( 'wpv-ct-inline-editor-' ) === 0 ) {
					var toolbarButton = $( '<li>' + button + '</li>' ),
						toolbarList = editor
							.closest( '.js-wpv-ct-inline-edit' )
								.find( '.js-code-editor-toolbar > ul' );
					appendGridButtonIfMissing( toolbarList, toolbarButton );
				}
				break;
		}
		
		return self;
	};
	
	/**
	 * Init the Bootstrap grid hooks.
	 *
	 * @return self;
	 *
	 * @since 2.3.3
	 */
	
	self.initHooks = function() {
		
		Toolset.hooks.addAction( 'toolset_text_editor_CodeMirror_init', function( editorId ) {
            if ( editorId ) { 
                self.maybe_add_editor_button( editorId );
            }
        });
        
        Toolset.hooks.addAction( 'toolset_text_editor_CodeMirror_init_only_buttons', function( editorId ) {
            if ( editorId ) { 
                self.maybe_add_editor_button( editorId );
            }
        });
		
		return self;
	};
	
	/**
	 * Init this module.
	 *
	 * @since 2.3.3
	 */
	
	self.init = function() {
		self.initDialogs()
			.initHooks();
	};
	
	self.init();

};

jQuery( document ).ready( function( $ ) {
    new ToolsetCommon.BootstrapCssComponentsGrids( $ );
});