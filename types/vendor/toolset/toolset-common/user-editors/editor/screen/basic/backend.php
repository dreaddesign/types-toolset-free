<?php

class Toolset_User_Editors_Editor_Screen_Basic_Backend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * Toolset_User_Editors_Editor_Screen_Basic_Backend constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();

		$this->constants->define( 'BASIC_SCREEN_ID', 'basic' );
	}

	public function initialize() {
		add_action( 'init', array( $this, 'register_assets' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 50 );

		add_filter( 'toolset_filter_toolset_registered_user_editors', array( $this, 'register_user_editor' ) );
		add_filter( 'wpv_filter_wpv_layout_template_extra_attributes', array( $this, 'layout_template_attribute' ), 10, 3 );

		add_action( 'wp_ajax_toolset_set_layout_template_user_editor', array( $this, 'set_layout_template_user_editor' ) );

		add_action( 'admin_footer', array( $this, 'load_template' ) );
	}

	public function is_active() {
		$this->action();
		return true;
	}

	private function action() {
		add_action( 'admin_enqueue_scripts', array( $this, 'action_assets' ) );
		$this->medium->set_html_editor_backend( array( $this, 'html_output' ) );
	}

	public function html_output() {

		if( ! isset( $_GET['ct_id'] ) )
			return 'No valid content template id';

		ob_start();
			include_once( dirname( __FILE__ ) . '/backend.phtml' );
			// Render HTML template for the Insert/Edit link native WP dialog.
			if ( ! class_exists( '_WP_Editors' ) ) {
				require( ABSPATH . WPINC . '/class-wp-editor.php' );
			}
			_WP_Editors::wp_link_dialog();
			$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}
	
	public function register_assets() {
		
		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();

		$toolset_assets_manager->register_style(
			'toolset-user-editors-basic-style',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/basic/backend.css',
			array(),
			TOOLSET_COMMON_VERSION
		);
		
		$toolset_assets_manager->register_script(
			'toolset-user-editors-basic-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/basic/backend.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);
		
		$toolset_assets_manager->register_script(
			'toolset-user-editors-basic-layout-template-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/basic/backend_layout_template.js',
			array( 'jquery', 'views-layout-template-js', 'underscore' ),
			TOOLSET_COMMON_VERSION,
			true
		);
		
		$basic_layout_template_i18n = array(
            'template_editor_url'	=> admin_url( 'admin.php?page=ct-editor' ),
			'template_overlay'		=> array(
										'title'		=> __( 'Saving...', 'wpv-views' )
									),
			'user_editors'			=> apply_filters( 'toolset_filter_toolset_registered_user_editors', array() ),
			'wpnonce'				=> wp_create_nonce( 'toolset_layout_template_user_editor_nonce' )
		);
		$toolset_assets_manager->localize_script( 
			'toolset-user-editors-basic-layout-template-script', 
			'toolset_user_editors_basic_layout_template_i18n', 
			$basic_layout_template_i18n 
		);
		
	}
	
	public function admin_enqueue_assets() {
		if ( $this->is_views_or_wpa_edit_page() ) {
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-basic-layout-template-script' ) );
			do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-basic-style' ) );
		}
	}
	
	public function action_assets() {
		
		do_action( 'toolset_enqueue_scripts',	array( 'toolset-user-editors-basic-script' ) );
		do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-basic-style' ) );
		
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
		$content_template_has_basic = ( in_array( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ), array( '', $this->constants->constant( 'BASIC_SCREEN_ID' ) ) ) );
		if ( $content_template_has_basic ) {
			$attributes['builder'] = $this->editor->get_id();
		}
		return $attributes;
	}
	
	public function set_layout_template_user_editor() {
		
		if ( ! current_user_can( 'manage_options' ) ) {
			$data = array(
				'type' => 'capability',
				'message' => __( 'You do not have permissions for that.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if ( 
			! isset( $_POST['wpnonce'] )
			|| ! wp_verify_nonce( $_POST['wpnonce'], 'toolset_layout_template_user_editor_nonce' )
		) {
			$data = array(
				'type' => 'nonce',
				'message' => __( 'Your security credentials have expired. Please reload the page to get new ones.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		if (
			! isset( $_POST['ct_id'] )
			|| ! is_numeric( $_POST['ct_id'] )
			|| intval( $_POST['ct_id'] ) < 1 
		) {
			$data = array(
				'type' => 'id',
				'message' => __( 'Wrong or missing ID.', 'wpv-views' )
			);
			wp_send_json_error( $data );
		}
		
		$ct_id = (int) $_POST['ct_id'];
		$editor = isset( $_POST['editor'] ) ? sanitize_text_field( $_POST['editor'] ) : $this->constants->constant( 'BASIC_SCREEN_ID' );
		update_post_meta( $ct_id, '_toolset_user_editors_editor_choice', $editor );

		do_action( 'toolset_set_layout_template_user_editor_' . $editor );
		
		wp_send_json_success();
	}

	public function load_template() {
		require_once $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/user-editors/editor/templates/inline-ct-overlay.tpl.php';
		require_once $this->constants->constant( 'TOOLSET_COMMON_PATH' ) . '/user-editors/editor/templates/inline-ct-saving-overlay.tpl.php';
	}
}