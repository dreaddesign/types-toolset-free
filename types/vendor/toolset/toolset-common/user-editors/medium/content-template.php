<?php

class Toolset_User_Editors_Medium_Content_Template
	extends Toolset_User_Editors_Medium_Abstract {

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	protected $slug = 'view-template';
	protected $allowed_templates;
	protected $option_name_editor_choice = '_toolset_user_editors_editor_choice';

	/**
	 * Toolset_User_Editors_Medium_Content_Template constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();

		if( array_key_exists( 'ct_id', $_REQUEST ) )
			$this->id  = (int) $_REQUEST['ct_id'];

		if( $this->id && array_key_exists( 'ct_editor_choice', $_REQUEST ) )
			update_post_meta( $this->id, $this->option_name_editor_choice, sanitize_text_field( $_REQUEST['ct_editor_choice'] ) );

		add_filter( 'toolset_user_editors_backend_html_editor_select', array( $this, 'editor_selection' ) );
	}

	public function user_editor_choice() {
		if( $this->user_editor_choice !== null )
			return $this->user_editor_choice;

		if( ! $this->get_id() )
			return false;
		
		$content_template_id  = wpv_getget( 'ct_id' );

		if( $editor_choice = get_post_meta( $content_template_id, $this->option_name_editor_choice, true ) ) {
			$this->user_editor_choice = $editor_choice;
			return $editor_choice;
		} // backward compatibility (since Views Visual Comopser Beta we used 'wpv_ct_editor_choice')
		elseif ( $editor_choice = get_post_meta( $content_template_id, 'wpv_ct_editor_choice', true ) ) {
			$this->user_editor_choice = $editor_choice;
			update_post_meta( $content_template_id, $this->option_name_editor_choice, $editor_choice );
			delete_post_meta( $content_template_id, 'wpv_ct_editor_choice' );
			return $editor_choice;
		} elseif( get_post_type( $content_template_id ) == $this->slug ) {
			update_post_meta( $content_template_id, $this->option_name_editor_choice, 'basic' );
			return 'basic';
		}

		return false;
	}

	/**
	 * Register the list of allowed theme PHP templates based on the usage of the current Content Template.
	 *
	 * @since unknown
	 * @since 2.3.0 Covers the frontend PHP templates for Content Templates assigned to single pages.
	 */
	public function get_frontend_templates() {
		
		if( $this->allowed_templates !== null )
			return $this->allowed_templates;

		$content_template_usages = $this->get_usages();
		$theme_template_files    = (array) wp_get_theme()->get_files( 'php', 1, true );

		$wpv_options_patterns = array(
			'views_template_for_'         => array(
				'label'              => __( 'Single', 'wpv-views' ),
				'domain'             => 'post',
				'template_hierarchy' => array(
					'single-%NAME%.php',
					'single.php',
					'singular.php',
					'index.php'
				)
			),
			'views_template_archive_for_' => array(
				'label'              => __( 'Posts archive', 'wpv-views' ),
				'domain'             => 'post',
				'template_hierarchy' => array(
					'archive-%NAME%.php',
					'archive.php',
					'index.php'
				)
			),
			'views_template_loop_'        => array(
				'label'              => __( 'Taxonomy archive', 'wpv-views' ),
				'domain'             => 'taxonomy',
				'template_hierarchy' => array(
					'taxonomy-%NAME%.php',
					'taxonomy.php',
					'archive.php',
					'index.php'
				)
			),
			'view_loop_preview_post_type_'	=> array(
				'label'              => __( 'View loop', 'wpv-views' ),
				'domain'             => 'post',
				'template_hierarchy' => array(
					'single-%NAME%.php',
					'single.php',
					'singular.php',
					'index.php'
				)
			),
			'view_wpa_loop_preview_post_type_'	=> array(
				'label'              => __( 'WordPress Archive loop', 'wpv-views' ),
				'domain'             => 'post',
				'template_hierarchy' => array(
					'archive-%NAME%.php',
					'archive.php',
					'index.php'
				)
			),
			'view_wpa_loop_preview_taxonomy_'	=> array(
				'label'              => __( 'WordPress Archive loop', 'wpv-views' ),
				'domain'             => 'taxonomy',
				'template_hierarchy' => array(
					'taxonomy-%NAME%.php',
					'taxonomy.php',
					'archive.php',
					'index.php'
				)
			),
		);

		$this->allowed_templates = array();

		foreach( $content_template_usages as $usage => $ct_id ) {
			if ( 'views_template_for_page' == $usage ) {
				// Content Templates assigned to single pages demand a speial management,
				// since they are indeed single posts but with special templates in the 
				// native WordPress PHP templates hierarchy.
				// Note that there is no CT for the non-existing pages archive loop at all.
				$single_page_template_hierarchy = array(
					'page.php',
					'singular.php',
					'index.php'
				);
				$single_page_post_type_object = get_post_type_object( 'page' );
				if ( is_object( $single_page_post_type_object ) ) {
					foreach( $single_page_template_hierarchy as $template_file ) {
						if( array_key_exists( $template_file, $theme_template_files ) ) {
							$this->allowed_templates[] = array(
								'slug'              => 'page',
								'domain'            => 'post',
								'form-option-label' => $single_page_post_type_object->labels->name . ' [' . __( 'Single', 'wpv-views' ) . ']',
								'path'              => $theme_template_files[ $template_file ]
							);
							break;
						}
					}
				}
			} else {
				foreach( $wpv_options_patterns as $pattern => $settings ) {
					if( strpos( $usage, $pattern ) !== false ) {
						$type_name   = str_replace( $pattern, '', $usage );
						$type_object = $settings['domain'] == 'post'
							? get_post_type_object( $type_name )
							: get_taxonomy( $type_name );
						if ( is_object( $type_object ) ) {
							foreach( $settings['template_hierarchy'] as $template_file ) {
								$template_file = str_replace( '%NAME%', $type_object->name, $template_file );
								if( array_key_exists( $template_file, $theme_template_files ) ) {
									$this->allowed_templates[] = array(
										'slug'              => $type_object->name,
										'domain'            => $settings['domain'],
										'form-option-label' => $type_object->labels->name . ' [' . $settings['label'] . ']',
										'path'              => $theme_template_files[ $template_file ]
									);
									break;
								}
							}
						}
					}
				}
			}
		}
		
		// Make sure that the stored template path is in the allowed ones, or force it otherwise
		$allowed_paths = wp_list_pluck( $this->allowed_templates, 'path' );
		$current_template = get_post_meta( (int) $_GET['ct_id'], $this->manager->get_active_editor()->get_option_name(), true );
		
		if ( isset( $_GET['ct_id'] ) ) {
			if ( empty( $allowed_paths ) ) {
				$settings_to_store = array(
					'template_path' => false,
					'preview_domain' => 'post',
					'preview_slug' => ''
				);

				update_post_meta( (int) $_GET['ct_id'], $this->manager->get_active_editor()->get_option_name(), $settings_to_store );
			} else {
				if (
					! isset( $current_template['template_path'] ) 
					|| ! in_array( $current_template['template_path'], $allowed_paths ) 
				) {
					$slide_allowed_template = array_slice( $this->allowed_templates, 0, 1 );
					$first_allowed_template = array_shift( $slide_allowed_template );
					$settings_to_store = array(
						'template_path' => wp_slash( $first_allowed_template['path'] ),
						'preview_domain' => $first_allowed_template['domain'],
						'preview_slug' => $first_allowed_template['slug']
					);

				update_post_meta( (int) $_GET['ct_id'], $this->manager->get_active_editor()->get_option_name(), $settings_to_store );
				}
			}
		
		}

		return $this->allowed_templates;
	}

	private function get_usages() {
		$views_settings	= WPV_Settings::get_instance();
		$views_options	= $views_settings->get();
		$views_options	= array_filter( $views_options, array( $this, 'filter_templates_by_template_id' ) );
		
		if ( isset( $_GET['ct_id'] ) ) {
			
			if ( 
				isset( $_GET['preview_post_type'] ) 
				&& is_array( $_GET['preview_post_type'] ) 
				&& ! empty ( $_GET['preview_post_type'] )
			) {
				$preview_post_type = array_map( 'sanitize_text_field', $_GET['preview_post_type'] );
				foreach ( $preview_post_type as $prev_cpt ) {
					$views_options[ 'view_loop_preview_post_type_' . $prev_cpt ] = (int) $_GET['ct_id'];
				}
			}
			
			if ( 
				isset( $_GET['preview_post_type_archive'] ) 
				&& is_array( $_GET['preview_post_type_archive'] ) 
				&& ! empty ( $_GET['preview_post_type_archive'] )
			) {
				$preview_post_type_archive = array_map( 'sanitize_text_field', $_GET['preview_post_type_archive'] );
				foreach ( $preview_post_type_archive as $prev_cpt ) {
					$views_options[ 'view_wpa_loop_preview_post_type_' . $prev_cpt ] = (int) $_GET['ct_id'];
				}
			}
			
			if ( 
				isset( $_GET['preview_taxonomy_archive'] ) 
				&& is_array( $_GET['preview_taxonomy_archive'] ) 
				&& ! empty ( $_GET['preview_taxonomy_archive'] )
			) {
				$preview_taxonomy_archive = array_map( 'sanitize_text_field', $_GET['preview_taxonomy_archive'] );
				foreach ( $preview_taxonomy_archive as $prev_cpt ) {
					$views_options[ 'view_wpa_loop_preview_taxonomy_' . $prev_cpt ] = (int) $_GET['ct_id'];
				}
			}
			
			// @todo implement the rest of the Layout Loop usages
			
		}

		return $views_options;
	}

	private function filter_templates_by_template_id( $stored_value ) {
		if( ! isset( $_GET['ct_id'] ) )
			return false;

		return( (int) $stored_value == (int) $_GET['ct_id'] );
	}

	/**
	 * @param $content_function callable
	 */
	public function set_html_editor_backend( $content_function ) {
		add_filter( 'toolset_user_editors_backend_html_active_editor', $content_function );
	}


	public function editor_selection() {
		$control_editor_select = '';
		$editors = $this->manager->get_editors();

		if( count( $editors ) > 1 ) {
			$admin_url = admin_url( 'admin.php?page=ct-editor&ct_id='. (int) $_GET['ct_id'] );

			$editor_switch_buttons = array();

			foreach( $editors as $editor ) {
				if ( $editor->get_id() != $this->manager->get_active_editor()->get_id() ) {
					if (
						'native' == $editor->get_id()
						&& ! $this->constants->defined( 'TOOLSET_SHOW_NATIVE_EDITOR_BUTTON_FOR_CT' )
					) {
						continue;
					}
					$editor_switch_buttons[] = sprintf(
						'<a class="button button-secondary js-wpv-ct-apply-user-editor toolset-ct-button-logo %s" href="%s" title="%s">%s %s</a>',
						sanitize_html_class( $editor->get_logo_class() ),
						esc_url($admin_url . '&ct_editor_choice=' . $editor->get_id() ),
						esc_attr( __( 'Edit with', 'wpv-views' ) . ' ' . $editor->get_name() ),
						$editor->get_logo_image_svg() ? '<img src="' . esc_url( $this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/res/images/third-party/logos/' . $editor->get_logo_image_svg() ) . '" />' : '',
						esc_html( $editor->get_name() )
					);
				}
			}

			$control_editor_select .= '<div class="wpv-ct-control-switch-editor">';
			//$control_editor_select .= __( 'Select Editor: ', 'wpv-views' );
			$control_editor_select .= '<span class="wpv-content-template-user-editor-buttons js-wpv-content-template-user-editor-buttons" style="display:none">' . join( '', array_reverse( $editor_switch_buttons ) ) . '</span>';
			$control_editor_select .= '</div>';
		}

		return $control_editor_select;
	}

	public function page_reload_after_backend_save() {
		add_action( 'admin_print_footer_scripts', array( $this, '_action_page_reload_after_backend_save' ) );
	}
	
	/**
	* @todo refactor this, it should not happen this way. If after saving a section we need to reload, we set it on a caninical way.
	* ALso, this should NOT happen any time a CT setting is saved, just when a CT usage chnage is...
	*/
	
	public function _action_page_reload_after_backend_save() {
		echo "<script>jQuery( document ).on('ct_saved', function() { location.reload(); });</script>";
	}
}