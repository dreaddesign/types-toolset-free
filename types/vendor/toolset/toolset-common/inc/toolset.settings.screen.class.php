<?php

if ( ! defined( 'WPT_SETTINGS_SCREEN' ) ) {
    define( 'WPT_SETTINGS_SCREEN', true );
}

/**
* Toolset_Settings_Screen
*
* Generic class for the shared settings entry for the Toolset family.
*
* @since 1.9
*/

if ( ! class_exists( 'Toolset_Settings_Screen', false ) ) {

    class Toolset_Settings_Screen {

		function __construct() {
			
			add_filter( 'toolset_filter_register_common_page_slug', 	array( $this, 'register_settings_page_slug' ) );
			
            add_action( 'admin_init',									array( $this, 'admin_init' ) );
			
			add_filter( 'toolset_filter_register_menu_pages',			array( $this, 'register_settings_page_in_menu' ), 60 );
			
			add_action( 'init', 										array( $this, 'init' ) );
			
		}
		
		public function register_settings_page_slug( $slugs ) {
			if ( ! in_array( 'toolset-settings', $slugs ) ) {
				$slugs[] = 'toolset-settings';
			}
			return $slugs;
		}
		
		public function admin_init() {
			
		}
		
		public function register_settings_page_in_menu( $pages ) {
			$pages[] = array(
				'slug'			=> 'toolset-settings',
				'menu_title'	=> __( 'Settings', 'wpv-views' ),
				'page_title'	=> __( 'Settings', 'wpv-views' ),
				'callback'		=> array( $this, 'settings_page' )
			);
			return $pages;
		}

		function init() {
			// Admin bar settings
			add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'toolset_admin_bar_settings' ), 10, 2 );
			add_action( 'wp_ajax_toolset_update_toolset_admin_bar_options',			array( $this, 'toolset_update_toolset_admin_bar_options' ) );
			add_filter( 'toolset_filter_force_unset_shortcode_generator_option',	array( $this, 'force_unset_shortcode_generator_option_to_disable' ), 99 );

			add_filter( 'toolset_filter_toolset_register_settings_general_section',	array( $this, 'toolset_bootstrap_options' ), 30 );
			add_action( 'wp_ajax_toolset_update_bootstrap_version_status',							array( $this, 'toolset_update_bootstrap_version_status' ) );

		}


		function settings_page() {

			$settings = Toolset_Settings::get_instance();
			// Which tab is selected?
			// First tab by default: general
			$current_tab = 'general';
			
			$registered_sections = array( 
				'general'			=> array(
					'slug'	=> 'general',
					'title'	=> __( 'General', 'wpv-views' )
				), 		
			);
			$registered_sections = apply_filters( 'toolset_filter_toolset_register_settings_section', $registered_sections );

			if ( 
				isset( $_GET['tab'] ) 
				&& isset( $registered_sections[ $_GET['tab'] ] )
			) {
				$current_tab = sanitize_text_field( $_GET['tab'] );
			}
			?>

			<div class="wrap">
				<h1><?php _e( 'Toolset Settings', 'wpv-views' ) ?></h1>
				<span id="js-toolset-ajax-saving-messages" class="toolset-ajax-saving-messages js-toolset-ajax-saving-messages"></span>
				<?php
				$settings_menu = '';
				$settings_content = '';
				foreach ( $registered_sections as $section_slug => $section_data ) {
					$content_item_items = apply_filters( "toolset_filter_toolset_register_settings_{$section_slug}_section", array(), $settings );
					$menu_item_classname = array( 'js-toolset-nav-tab', 'toolset-nav-tab', 'nav-tab' );
					$content_item_classname = array( 'js-toolset-tabbed-section-item',  'toolset-tabbed-section-item ', 'toolset-tabbed-section-item-' . $section_slug, 'js-toolset-tabbed-section-item-' . $section_slug );
					if ( $section_slug == $current_tab ) {
						$menu_item_classname[] = 'nav-tab-active';
						$content_item_classname[] = 'toolset-tabbed-section-current-item js-toolset-tabbed-section-current-item';
					}
					$settings_menu .= sprintf(
						'<a class="%s" href="%s" title="%s" data-target="%s">%s%s</a>',
						esc_attr( implode( ' ', $menu_item_classname ) ),
						admin_url( 'admin.php?page=toolset-settings&tab=' . $section_slug ),
						esc_attr( $section_data['title'] ),
						$section_slug,
						isset( $section_data['icon'] ) ? $section_data['icon'] : '',
						esc_html( $section_data['title'] )
					);
					
					$settings_content .= '<div class="' . implode( ' ', $content_item_classname ) . '">';
					ob_start();
					foreach ( $content_item_items as $item_slug => $item_data ) {
						$this->render_setting_section( $item_data );
					}
					$settings_content .= ob_get_clean();
					$settings_content .= '</div>';
				}
				?>
				<p class="toolset-tab-controls">
					<?php echo $settings_menu; ?>
				</p>
				<?php echo $settings_content; ?>
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
		
		public function render_setting_section( $item_data ) {
			include TOOLSET_COMMON_PATH . '/templates/toolset-setting-section.tpl.php';
		}
		
		function toolset_admin_bar_settings( $sections, $toolset_options ) {
			$toolset_admin_bar_menu_show = ( isset( $toolset_options['show_admin_bar_shortcut'] ) && $toolset_options['show_admin_bar_shortcut'] == 'off' ) ? false : true;
			$toolset_shortcodes_generator = ( isset( $toolset_options['shortcodes_generator'] ) && in_array( $toolset_options['shortcodes_generator'], array( 'unset', 'disable', 'editor', 'always' ) ) ) ? $toolset_options['shortcodes_generator'] : 'unset';
			$section_content = '';
			ob_start();
			?>
			<h3><a name="shortcodes-settings" href="#"></a><?php echo __( 'Toolset shortcodes menu in the admin bar', 'wpv-views' ); ?></h3>
			<div class="toolset-advanced-setting">
				<p>
					<?php _e( "Toolset can display an admin bar menu in the backend to let you generate Toolset shortcodes in any page that you need them.", 'wpv-views' ); ?>
				</p>
				<ul class="js-shortcode-generator-form">
					<?php
						// Can be 'unset', 'disable', 'editor' or 'always'
						if ( $toolset_shortcodes_generator == 'unset' ) {
							$toolset_shortcodes_generator = apply_filters( 'toolset_filter_force_unset_shortcode_generator_option', $toolset_shortcodes_generator );
						}
						$shortcodes_generator_options = array(
							array(
								'label' =>  __( 'Disable the Toolset shortcodes menu in the admin bar', 'wpv-views' ),
								'value' => 'disable'
							),
							array(
								'label' => __( 'Show the Toolset shortcodes menu in the admin bar only when editing content', 'wpv-views' ),
								'value' => 'editor'
							),
							array(
								'label' => __( 'Show the Toolset shortcodes menu in the admin bar in all the admin pages', 'wpv-views' ),
								'value' => 'always'
							)
						);
						foreach( $shortcodes_generator_options as $option ) {
							printf(
								'<li><label><input type="radio" name="wpv-shortcodes-generator" class="js-toolset-shortcodes-generator js-toolset-admin-bar-options" value="%s" %s autocomplete="off" />%s</label></li>',
								$option['value'],
								checked( $option['value'] == $toolset_shortcodes_generator, true, false ),
								$option['label']
							);

						}

					?>
				</ul>
			</div>
			<h3><a name="design-with-toolset-settings" href="#"></a><?php echo __( 'Design with Toolset', 'wpv-views' ); ?></h3>
			<div class="toolset-advanced-setting">
				<p>
					<?php _e( "Toolset can display an admin bar menu in the frontend to let you create or edit Views and Content Templates related to the current page.", 'wpv-views' ); ?>
				</p>
				<p>
					<label>
						<input type="checkbox" name="wpv-toolset-admin-bar-menu" id="js-toolset-admin-bar-menu" class="js-toolset-admin-bar-menu js-toolset-admin-bar-options" value="1" <?php checked( $toolset_admin_bar_menu_show ); ?> autocomplete="off" />
						<?php _e( "Enable the Design with Toolset menu in the frontend", 'wpv-views' ); ?>
					</label>
				</p>
			</div>
			<?php
			wp_nonce_field( 'toolset_admin_bar_settings_nonce', 'toolset_admin_bar_settings_nonce' );
			?>
			<?php
			$section_content = ob_get_clean();
			
			$sections['admin-bar-settings'] = array(
				'slug'		=> 'admin-bar-settings',
				'title'		=> __( 'Admin bar options', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}
		
		function toolset_update_toolset_admin_bar_options() {
			$toolset_options = Toolset_Settings::get_instance();
			if ( ! current_user_can( 'manage_options' ) ) {
				$data = array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			if ( 
				! isset( $_POST["wpnonce"] )
				|| ! wp_verify_nonce( $_POST["wpnonce"], 'toolset_admin_bar_settings_nonce' ) 
			) {
				$data = array(
					'type' => 'nonce',
					'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			$frontend			= ( isset( $_POST['frontend'] ) ) ? sanitize_text_field( $_POST['frontend'] ) : 'true';
			$backend			= ( isset( $_POST['backend'] ) && in_array( $_POST['backend'], array( 'disable', 'editor', 'always' ) ) ) ? sanitize_text_field( $_POST['backend'] ) : null;
			if ( null != $backend ) {
				$toolset_options['shortcodes_generator'] = $backend;
			}
			$toolset_options['show_admin_bar_shortcut'] = ( $frontend == 'true' ) ? 'on' : 'off';
			$toolset_options->save();
			wp_send_json_success();
		}
		
		public function force_unset_shortcode_generator_option_to_disable( $state ) {
			if ( $state == 'unset' ) {
				$state = 'disable';
			}
			return $state;
		}



		/**
		 * Bootstrap - settings and saving
		 */

		function toolset_bootstrap_options( $sections ) {
			$is_disabled = '';
			$disabled_message = '';

			$settings = Toolset_Settings::get_instance();
			ob_start();
			?>
			<ul class="js-bootstrap-version-form">
				<?php

				$version_options = array(


					array(
						'label' => __( 'The theme or another plugin is already loading Bootstrap 2.0', 'wpv-views' ),
						'value' => 2
					),
					array(
						'label' => __( 'The theme or another plugin is already loading Bootstrap 3.0', 'wpv-views' ),
						'value' => 3
					),
					array(
						'label' =>  __( 'Toolset should load Bootstrap 3.0', 'wpv-views' ),
						'value' => '3.toolset'
					),
					array(
						'label' => __( 'This site is not using Bootstrap CSS', 'wpv-views' ),
						'value' => -1
					)
				);

				foreach( $version_options as $option ) {

					printf(
						'<li><label class="js-tolset-option-%s"><input type="radio" name="wpv-bootstrap-version" class="js-toolset-bootstrap-version" value="%s" %s %s autocomplete="off" />%s</label></li>',
						str_replace(".","",$option['value']),
						$option['value'],
						checked( $option['value'], $settings->toolset_bootstrap_version, false ),
						disabled( $is_disabled, true, false ),
						$option['label']
					);

				}

				?>
			</ul>

			<?php echo $disabled_message; ?>
			<p>
				<?php
				echo sprintf(
					__( 'Get more details in the <a href="%1$s" title="%2$s">documentation page</a>.', 'wpv-views' ),
					'https://toolset.com/documentation/user-guides/view-layouts-101/' ,
					esc_attr( __( 'Documentation on the Bootstrap Layouts', 'wpv-views' ) )
				);
				?>
			</p>
			<?php
			wp_nonce_field( 'toolset_bootstrap_version_nonce', 'toolset_bootstrap_version_nonce' );
			?>
			<?php
			$section_content = ob_get_clean();

			$sections['bootstrap-settings'] = array(
				'slug'		=> 'bootstrap-settings',
				'title'		=> __( 'Bootstrap loading', 'wpv-views' ),
				'content'	=> $section_content
			);
			return $sections;
		}

		/**
		 * Update the Views Bootrstap version
		 *
		 * $_POST:
		 * 	wpnonce:	wpv_bootstrap_version_nonce
		 * 	status:		1|2|3|3.toolset|-1
		 */

		function toolset_update_bootstrap_version_status() {
			$settings = Toolset_Settings::get_instance();
			if ( ! current_user_can( 'manage_options' ) ) {
				$data = array(
					'type' => 'capability',
					'message' => __( 'You do not have permissions for that.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}
			if (
				! isset( $_POST["wpnonce"] )
				|| ! wp_verify_nonce( $_POST["wpnonce"], 'toolset_bootstrap_version_nonce' )
			) {
				$data = array(
					'type' => 'nonce',
					'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
				);
				wp_send_json_error( $data );
			}

			$status = in_array($_POST['status'], array( 1, 2, 3, '3.toolset', -1 ));
			if ( null != $status ) {
				$settings->toolset_bootstrap_version = $_POST['status'];
				$settings->save();
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
			wp_send_json_success();
		}



	}

}
