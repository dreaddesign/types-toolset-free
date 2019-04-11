<?php
if ( file_exists( dirname(__FILE__) . '/editor-addon-generic.class.php') && !class_exists( 'WPV_Editor_addon', false )  ) {

    require_once( dirname(__FILE__) . '/editor-addon-generic.class.php' );

    class WPV_Editor_addon extends Editor_addon_generic {
	
		static $footer_dialogs = '';
		static $footer_dialogs_types = array();
		static $footer_dialogs_added = false;
			
		public function __construct( $name, $button_text, $plugin_js_url, $media_button_image = '', $print_button = true, $icon_class = '' ) {
			parent::__construct( $name, $button_text, $plugin_js_url, $media_button_image, $print_button, $icon_class );
		}

		/**
		* get_fields_list
		*
		* This is used in the Loop Wizard, so watch out
		*
		*/
			
		function get_fields_list() {
			return apply_filters( 'toolset_editor_addon_post_fields_list', $this->items );
		}
		
		function add_form_button( $context, $text_area = '', $standard_v = true, $add_views = false, $codemirror_button = false ) {
			return;
		}
		
		/**
		 * Backwards compatibility: Views < 2.3.0 might call this.
		 *
		 * @until 2.5.0
		 */
		function render_shortcodes_wrapper_dialogs() {
			return;
		}
		
		/**
		 * Backwards compatibility: Views < 2.3.0 might call this.
		 *
		 * @until 2.5.0
		 */
		function add_fields_views_button() {
			return;
		}

        /**
         *
         * Sort menus (and menu content) in an alphabetical order
         *
         * Still, keep Basic and Taxonomy on the top and Other Fields at the bottom
         *
         * @param array $menu menu reference
         */
		function sort_menus( $menus ) {
			// keep main references if set (not set on every screen)
			$menu_temp = array();
			$menu_names = array(
				__( 'WPML', 'wpv-views' ),
				__( 'User View', 'wpv-views' ),
				__( 'Taxonomy View', 'wpv-views' ),
				__( 'Post View', 'wpv-views' ),
				__( 'View', 'wpv-views' ),
				__( 'Post field', 'wpv-views' ),
				__( 'User basic data', 'wpv-views' ),
				__( 'Content Template', 'wpv-views' ),
				__( 'Taxonomy', 'wpv-views' ),
				__( 'Basic', 'wpv-views' )
			);
				
			$menus_sorted_first = array();
			$menus_sorted_last = array();
			$menus_sorted = array();

			$menus_on_top = array(
				__( 'Basic', 'wpv-views' ),
				__( 'Taxonomy', 'wpv-views' ),
				__( 'Content Template', 'wpv-views' ),
				__( 'User basic data', 'wpv-views' )
			);

			$menus_on_bottom = array(
				__( 'Post field', 'wpv-views' ),
				__( 'View', 'wpv-views' ),
				__( 'Post View', 'wpv-views' ),
				__( 'Taxonomy View', 'wpv-views' ),
				__( 'User View', 'wpv-views' ),
				__( 'WPML', 'wpv-views' )
			);

			$menus_keys = array_keys( $menus );

			foreach ( $menus_keys as $mk ) {
				if ( in_array( $mk, $menus_on_top ) ) {
					$menus_sorted_first[$mk] = $menus[$mk];
					unset( $menus[$mk] );
				} else if ( in_array( $mk, $menus_on_bottom ) ) {
					$menus_sorted_last[$mk] = $menus[$mk];
					unset( $menus[$mk] );
				}
			}

			$menus_sorted = array_merge( $menus_sorted_first, $menus, $menus_sorted_last );

			return $menus_sorted;
				
		 
		}

    }

    /**
     * Renders JS for inserting shortcode from thickbox popup to editor.
     *
     * @param type $shortcode
	 * maybe DEPRECATED ???
     */
    if ( ! function_exists('editor_admin_popup_insert_shortcode_js') ) {
        function editor_admin_popup_insert_shortcode_js( $shortcode ) { // Types now uses ColorBox, it's not used in Views anymore. Maybe DEPRECATED
            ?>
            <script type="text/javascript">
                //<![CDATA[
                // Close popup
                window.parent.jQuery('#TB_closeWindowButton').trigger('click');
                // Check if there is custom handler
                if (window.parent.wpcfFieldsEditorCallback_redirect) {
                    eval(window.parent.wpcfFieldsEditorCallback_redirect['function'] + '(\'<?php echo esc_js( $shortcode ); ?>\', window.parent.wpcfFieldsEditorCallback_redirect[\'params\'])');
                } else {
                    // Use default handler
                    window.parent.icl_editor.insert('<?php echo $shortcode; ?>');
                }
                //]]>
            </script>
            <?php
        }
    }

}

