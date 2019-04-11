<?php

if ( ! defined( 'WPT_EXPORT_IMPORT_SCREEN' ) ) {
    define( 'WPT_EXPORT_IMPORT_SCREEN', true );
}

/**
* Toolset_Export_Import_Screen
*
* Generic class for the shared export / import entry for the Toolset family.
*
* @since 1.9
*/

if ( ! class_exists( 'Toolset_Export_Import_Screen', false ) ) {

    /**
     * Class to show promotion message.
     *
     * @since 1.5
     * @access  public
     */
    class Toolset_Export_Import_Screen {
		
		public function __construct() {		
			
			add_filter( 'toolset_filter_register_common_page_slug', 	array( $this, 'register_export_import_page_slug' ) );
			
            add_action( 'admin_init',									array( $this, 'admin_init' ) );
			
			add_filter( 'toolset_filter_register_menu_pages', 			array( $this, 'register_export_import_page_in_menu' ), 65 );
			
        }
		
		public function register_export_import_page_slug( $slugs ) {
			if ( ! in_array( 'toolset-export-import', $slugs ) ) {
				$slugs[] = 'toolset-export-import';
			}
			return $slugs;
		}
		
		public function admin_init() {
			
		}
		
		public function register_export_import_page_in_menu( $pages ) {
			$pages[] = array(
				'slug'			=> 'toolset-export-import',
				'menu_title'	=> __( 'Export / Import', 'wpv-views' ),
				'page_title'	=> __( 'Export / Import', 'wpv-views' ),
				'callback'		=> array( $this, 'export_import_page' )
			);
			return $pages;
		}
		
		/**
		* Register sections as 
		* 'slug' => array(
		* 		'slug'		=> section slug,
		* 		'title'		=> section title,
		* 		'items'		=> array(
		* 							'slug'		=> item slug,
		* 							'title'		=> item title,
		* 							'content'	=> item content, as a string - optional,
		* 							'callback'	=> item callback to display the content - optional
		* 						)
		* )
		*/
		
		public function export_import_page() {
			/**
			* Ordering export / import sections by plugin:
			* 10: Toolset Types
			* 20: Toolset Views
			* 30: Toolset Layouts
			* 40: Toolset CRED
			* 50: Toolset Access
			*/
			$registered_sections = apply_filters( 'toolset_filter_register_export_import_section', array() );
			$registered_sections_slugs = array_keys( $registered_sections );
			$first_tab = ( count( $registered_sections_slugs ) > 0 ) ? $registered_sections_slugs[0] : '';
			$current_tab = ( isset( $_GET['tab'] ) && in_array( $_GET['tab'], $registered_sections_slugs ) ) ? sanitize_text_field( $_GET['tab'] ) : $first_tab;
			?>
			<div class="wrap">
				<h1><?php echo esc_html( __( 'Toolset Export / Import', 'wpv-views' ) ); ?></h1>
				<?php
				$export_import_menu = '';
				$export_import_content = '';
				foreach ( $registered_sections as $section_slug => $section_data ) {
					$menu_item_classname = array( 'js-toolset-nav-tab', 'toolset-nav-tab', 'nav-tab' );
					$content_item_classname = array( 'js-toolset-tabbed-section-item',  'toolset-tabbed-section-item ', 'toolset-tabbed-section-item-' . $section_slug, 'js-toolset-tabbed-section-item-' . $section_slug );
					if ( $section_slug == $current_tab ) {
						$menu_item_classname[] = 'nav-tab-active';
						$content_item_classname[] = 'toolset-tabbed-section-current-item';
					}
					$export_import_menu .=  sprintf(
						'<a class="%s" href="%s" title="%s" data-target="%s">%s%s</a>',
						esc_attr( implode( ' ', $menu_item_classname ) ),
						admin_url( 'admin.php?page=toolset-export-import&tab=' . $section_slug ),
						esc_attr( $section_data['title'] ),
						$section_slug,
						isset( $section_data['icon'] ) ? $section_data['icon'] : '',
						esc_html( $section_data['title'] )
					);
					
					$export_import_content .= '<div class="' . implode( ' ', $content_item_classname ) . '">';
					ob_start();
					if ( isset( $section_data['items'] ) ) {
						foreach ( $section_data['items'] as $item_slug => $item_data ) {
							$this->render_export_import_section( $item_data );
						}
					}
					$export_import_content .= ob_get_clean();
					$export_import_content .= '</div>';
				}
				?>
				<p class="toolset-tab-controls">
					<?php echo $export_import_menu; ?>
				</p>
				<?php echo $export_import_content; ?>
				<div class="toolset-debug-info-helper">
					<p>
					<?php
					echo __( 'Sometimes, our Customer Support personnel ask you to provide debug information. This information helps them give you quicker and better support.', 'wpv-views' );
					?>
					</p>
					<p>
					<?php
					echo sprintf(
						__( 'To get this information, go to %1$sToolset Debug Information%2$s.', 'wpv-views' ),
						'<a href="' . admin_url( 'admin.php?page=toolset-debug-information' ) . '">',
						'</a>'
					);
					?>
					</p>
				</div>
			</div>
			<?php
		}
		
		public function render_export_import_section( $item_data ) {
			include TOOLSET_COMMON_PATH . '/templates/toolset-export-import-section.tpl.php';
		}
		
	}
	
}