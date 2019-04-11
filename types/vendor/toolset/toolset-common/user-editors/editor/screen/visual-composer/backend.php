<?php

class Toolset_User_Editors_Editor_Screen_Visual_Composer_Backend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	private $post;
	public $editor;

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * Toolset_User_Editors_Editor_Screen_Visual_Composer_Backend constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();

		$this->constants->define( 'VC_SCREEN_ID', 'vc' );
	}

	public function initialize() {
	    $shortcode_transformer = new Toolset_Shortcode_Transformer();

		add_action( 'init',												array( $this, 'register_assets' ), 50 );
		add_action( 'admin_enqueue_scripts',							array( $this, 'admin_enqueue_assets' ), 50 );
		
		add_filter( 'toolset_filter_toolset_registered_user_editors',	array( $this, 'register_user_editor' ) );
		add_filter( 'wpv_filter_wpv_layout_template_extra_attributes',	array( $this, 'layout_template_attribute' ), 10, 3 );
		add_action( 'wpv_action_wpv_ct_inline_user_editor_buttons',		array( $this, 'register_inline_editor_action_buttons' ) );

		add_filter( 'wpcf_filter_wpcf_admin_get_current_edited_post', array( $this, 'get_current_ct_id_for_wpcf_admin' ), 11, 1 );

		add_action( 'wpv_action_wpv_save_item', array( $this, 'save_vc_custom_css' ) );

		add_filter( 'vc_btn_a_href', array( $shortcode_transformer, 'replace_shortcode_placeholders_with_brackets' ) );
		add_filter( 'vc_btn_a_href', 'do_shortcode' );

		add_filter( 'vc_btn_a_title', array( $shortcode_transformer, 'replace_shortcode_placeholders_with_brackets' ) );
		add_filter( 'vc_btn_a_title', 'do_shortcode' );

		add_filter( 'vc_raw_html_module_content', array( $shortcode_transformer, 'replace_shortcode_placeholders_with_brackets' ) );

		// Post edit page integration
		//add_action( 'edit_form_after_title',				array( $this, 'preventNested' ) );
	}

	public function is_active() {
		if( ! $this->set_medium_as_post() )
			return false;

		// check for functions used
		if(
			! function_exists( 'vc_user_access' )
			|| ! class_exists( 'Vc_Shortcodes_Manager' )
			|| ! method_exists( 'Vc_Manager', 'backendEditor' )
		)
			return false;

		// don't show VC if user role is not allowed to use the backend editor
		if( ! vc_user_access()->part( 'backend_editor' )->can()->get() ) {
			return false;
		}

		$this->action();
		return true;
	}

	private function action() {
		add_action( 'admin_init', array( $this, 'setup' ) );

		add_action( 'admin_print_scripts', array( &$this->editor, 'enqueueEditorScripts' ) );
		add_action( 'admin_print_scripts', array( $this, 'print_scripts' ) );
		add_action( 'admin_print_scripts', array( Vc_Shortcodes_Manager::getInstance(), 'buildShortcodesAssets' ), 1 );

		add_filter( 'toolset_filter_force_shortcode_generator_display', array( $this, 'force_shortcode_generator_display' ) );

		$this->medium->set_html_editor_backend( array( $this, 'html_output' ) );
	}

	/**
	 * Setup the editor
	 * called on action 'admin_init'
	 */
	public function setup() {
		// Disable WPBakery Page Builder (former Visual Composer) Frontend Editor
		vc_disable_frontend();

		// Get backend editor object through VC_Manager (vc di container)
		global $vc_manager;
		$this->editor = $vc_manager->backendEditor();

		// VC_Backend_Editor->render() registers all needed scripts
		// the "real" render came later in $this->html_output();
		$this->editor->render( $this->post->post_type );
	}


	private function set_medium_as_post() {
		$medium_id  = $this->medium->get_id();

		if( ! $medium_id )
			return false;

		$medium_post_object = get_post( $medium_id );
		if( $medium_post_object === null )
			return false;

		$this->post = $medium_post_object;

		return true;
	}
	
	public function register_assets() {
		
		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();
		
		// Content Template as inline object assets
		
		$toolset_assets_manager->register_script(
			'toolset-user-editors-vc-layout-template-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/visual-composer/backend_layout_template.js',
			array( 'jquery', 'views-layout-template-js', 'underscore' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$toolset_assets_manager->register_script(
			'toolset-user-editors-vc-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/visual-composer/backend_editor.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$toolset_assets_manager->register_style(
			'toolset-user-editors-vc-editor-style',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/visual-composer/backend_editor.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		$vc_layout_template_i18n = array(
			'template_editor_url'	=> admin_url( 'admin.php?page=ct-editor' ),
			'template_overlay'		=> array(
										'title'		=> sprintf( __( 'You created this template using %1$s', 'wpv-views' ), $this->editor->get_name() ),
										'button'	=> sprintf( __( 'Edit with %1$s', 'wpv-views' ), $this->editor->get_name() ),
										'discard'	=> sprintf( __( 'Stop using %1$s for this Content Template', 'wpv-views' ), $this->editor->get_name() )
									),
		);
		$toolset_assets_manager->localize_script( 
			'toolset-user-editors-vc-layout-template-script', 
			'toolset_user_editors_vc_layout_template_i18n', 
			$vc_layout_template_i18n 
		);
		
	}
	
	public function admin_enqueue_assets( $screen_id ) {
		$content_template_has_vc = ( get_post_meta( wpv_getget( 'ct_id' ), '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'VC_SCREEN_ID' ) );
		$ct_edit_page_screen_id = class_exists( 'WPV_Page_Slug' ) ? WPV_Page_Slug::CONTENT_TEMPLATES_EDIT_PAGE : 'toolset_page_ct-editor';

		if ( $this->is_views_or_wpa_edit_page() ) {
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-vc-layout-template-script' ) );
		}

		if (
				$content_template_has_vc
				&& $ct_edit_page_screen_id === $screen_id
		) {
			// We need to enqueue the following style and script on the Content Template edit page but only when the
			// template is built with WPBakery Page Builder (former Visual Composer).
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-vc-script' ) );
			do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-vc-editor-style' ) );
		}

	}

	public function html_output() {

		ob_start();

		include_once( dirname( __FILE__ ) . '/backend.phtml' );

		$output = ob_get_contents();

		ob_end_clean();

		$admin_url = admin_url( 'admin.php?page=ct-editor&ct_id=' . esc_attr( $_GET['ct_id'] ) );
		$output .= '<p>'
				   . sprintf(
						   __( '%1$sStop using %2$s for this Content Template%3$s', 'wpv-views' ),
						   '<a href="' . esc_url( $admin_url ) . '&ct_editor_choice=basic">',
                           $this->medium->get_manager()->get_active_editor()->get_name(),
						   '</a>'
				   )
				   . '</p>';

		return $output;
	}

	/**
	 * We need some custom scripts ( &styles )
	 * called on 'admin_print_scripts'
	 */
	public function print_scripts() {

		// disable the 100% and fixed vc editor navigation when scrolling down
		$output = '
		<style type="text/css">
			body.toolset_page_ct-editor .composer-switch {
				display:none;
			}
			body.toolset_page_ct-editor .wpv-settings-section,
			body.toolset_page_ct-editor .wpv-setting-container {
				max-width: 96% !important;
			}
			
			body.toolset_page_ct-editor .wpv-setting-container .wpv-settings-header {
				width: 15% !important;
			}
			
			.wpv-setting {
				width: 84%;
			}
			
			.wpv-mightlong-list li {
				min-width: 21%;
			}

			body.toolset_page_ct-editor .js-wpv-content-section .wpv-settings-header {
				display: block;
			}
			
			body.toolset_page_ct-editor .wpv-ct-control-switch-editor {
				padding-left: 105px;
			}
			
			body.toolset_page_ct-editor .js-wpv-content-section .wpv-setting {
				width: 100% !important;
			}
			
			.vc_subnav-fixed{
				position:relative !important;
				top:auto !important;
				left:auto !important;
				z-index: 1 !important;
				padding-left:0 !important;
			}
		</style>';

		// disable our backbone extension due to conflicts with vc (see util.js)
		$output .= "<script>var ToolsetDisableBackboneExtension = '1';</script>";
		echo preg_replace('/\v(?:[\v\h]+)/', '', $output );
	}
	
	public function register_user_editor( $editors ) {
		$editors[ $this->editor->get_id() ] = $this->editor->get_name();
		return $editors;
	}
	
	/**
	* Set the builder used by a Content Template, if any.
	*
	* On a Content Template used inside a View or WPA loop output, we set which builder it is using
	* so we can link to the CT edit page with the right builder instantiated.
	*
	* @since 2.3.0
	*/
	
	public function layout_template_attribute( $attributes, $content_template, $view_id ) {
		$content_template_has_vc = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'VC_SCREEN_ID' ) );
		if ( $content_template_has_vc ) {
			$attributes['builder'] = $this->editor->get_id();
		}
		return $attributes;
	}
	
	public function register_inline_editor_action_buttons( $content_template ) {
		$content_template_has_vc = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'VC_SCREEN_ID' ) );
		?>
		<button 
			class="button button-secondary toolset-ct-button-logo js-wpv-ct-apply-user-editor js-wpv-ct-apply-user-editor-<?php echo esc_attr( $this->editor->get_id() ); ?>"
			data-editor="<?php echo esc_attr( $this->editor->get_id() ); ?>"
            title="<?php echo __( 'Edit with', 'wpv-views' ) . ' ' . $this->editor->get_name() ?>"
			<?php disabled( $content_template_has_vc );?>
		>
            <img src="<?php echo $this->constants->constant( 'TOOLSET_COMMON_URL' ) . '/res/images/third-party/logos/' . $this->editor->get_logo_image_svg(); ?>" />
			<?php echo esc_html( $this->editor->get_name() ); ?>
		</button>
		<?php
	}

	public function force_shortcode_generator_display( $register_section ) {
		return true;
	}

	/**
	 * Get the current content template ID to append to the third-party shortcodes added in the Fields and Views dialog.
	 *
	 * @param   WP_POST   $current_post   The current post.
	 *
	 * @return  WP_Post   The filtered post.
	 *
	 * @since 2.5.1
	 */
	public function get_current_ct_id_for_wpcf_admin( $current_post ) {
		$ct_id = wpv_getget( 'ct_id' );

		if ( ! empty( $ct_id ) ) {
			$current_post = get_post( $ct_id );
		}

		return $current_post;
	}

	/**
	 * Save WPBakery Page Builder (former Visual Composer) Custom CSS upon content template save.
	 *
	 * @param   $content_template_id   The ID of the content template to save the custom CSS for.
	 *
	 * @since 2.5.1
	 */
	public function save_vc_custom_css( $content_template_id ) {
		$content_template_has_vc = ( get_post_meta( $content_template_id, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'VC_SCREEN_ID' ) );
		if ( $content_template_has_vc ) {
			foreach ( $_POST['properties'] as $property ) {
				if ( 'template_extra_css' === $property['name'] ) {
					update_post_meta( $content_template_id, '_wpb_post_custom_css', $property['value'] );
				}
			}
		}
	}
}
