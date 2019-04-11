<?php

class Toolset_User_Editors_Editor_Screen_Native_Backend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * Toolset_User_Editors_Editor_Screen_Native_Backend constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();

		$this->constants->define( 'NATIVE_SCREEN_ID', 'native' );
	}

	public function initialize() {

		add_action( 'init', array( $this, 'register_assets' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 50 );

		add_filter( 'toolset_filter_toolset_registered_user_editors', array( $this, 'register_user_editor' ) );
		add_filter( 'wpv_filter_wpv_layout_template_extra_attributes', array( $this, 'layout_template_attribute' ), 10, 3 );

		/**
		 * If we need to enable the Native Editor button in the CT editor, the TOOLSET_SHOW_NATIVE_EDITOR_BUTTON_FOR_CT
		 * needs to be defined.
		 */
		if ( $this->constants->defined( 'TOOLSET_SHOW_NATIVE_EDITOR_BUTTON_FOR_CT' ) ) {
			add_action( 'wpv_action_wpv_ct_inline_user_editor_buttons', array( $this, 'register_inline_editor_action_buttons' ) );
		}

	}

	public function is_active() {
		if ( ! $this->set_medium_as_post() ) {
			return false;
		}

		$this->action();

		return true;
	}

	private function action() {
		add_action( 'admin_enqueue_scripts', array( $this, 'action_enqueue_assets' ) );
		$this->medium->set_html_editor_backend( array( $this, 'html_output' ) );
		$this->medium->page_reload_after_backend_save();
	}

	public function register_assets() {

		$toolset_assets_manager = Toolset_Assets_Manager::getInstance();

		// Content Template own edit screen assets

		$toolset_assets_manager->register_style(
			'toolset-user-editors-native-style',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/native/backend.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		// Native post editor screen assets

		$toolset_assets_manager->register_script(
			'toolset-user-editors-native-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/native/backend_editor.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		// Content Template as inline object assets

		$toolset_assets_manager->register_script(
			'toolset-user-editors-native-layout-template-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/native/backend_layout_template.js',
			array( 'jquery', 'views-layout-template-js', 'underscore' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$native_layout_template_i18n = array(
			'template_editor_url' => admin_url( 'admin.php?page=ct-editor' ),
			'template_overlay' => array(
				'title' => sprintf( __( 'This Content Template uses %1$s', 'wpv-views' ), $this->editor->get_name() ),
				'button' => sprintf( __( 'Edit with %1$s', 'wpv-views' ), $this->editor->get_name() ),
				'discard' => sprintf( __( 'Stop using %1$s for this Content Template', 'wpv-views' ), $this->editor->get_name() ),
			),
		);
		$toolset_assets_manager->localize_script(
			'toolset-user-editors-native-layout-template-script',
			'toolset_user_editors_native_layout_template_i18n',
			$native_layout_template_i18n
		);
	}

	public function admin_enqueue_assets() {
		if ( $this->is_views_or_wpa_edit_page() ) {
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-native-layout-template-script' ) );
		}
	}

	public function action_enqueue_assets() {
		do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-native-style' ) );
	}

	private function set_medium_as_post() {
		$medium_id  = $this->medium->get_id();

		if ( ! $medium_id ) {
			return false;
		}

		$medium_post_object = get_post( $medium_id );
		if ( null === $medium_post_object ) {
			return false;
		}

		$this->post = $medium_post_object;

		return true;
	}

	public function register_user_editor( $editors ) {
		$editors[ $this->editor->get_id() ] = $this->editor->get_name();
		return $editors;
	}

	/**
	 * Content Template editor output.
	 *
	 * Displays the Native Editor message and button to fire it up.
	 *
	 * @since 2.5.0
	 */

	public function html_output() {

		if ( ! isset( $_GET['ct_id'] ) ) {
			return 'No valid content template id';
		}

		ob_start();
		include_once( dirname( __FILE__ ) . '/backend.phtml' );
		$output = ob_get_contents();
		ob_end_clean();

		$admin_url = admin_url( 'admin.php?page=ct-editor&ct_id=' . esc_attr( $_GET['ct_id'] ) );
		$output .= '<p>'
				   . sprintf(
					   __( '%1$sStop using %2$s for this Content Template%3$s', 'wpv-views' ),
					   '<a href="' . esc_url( $admin_url ) . '&ct_editor_choice=basic">',
					   $this->editor->get_name(),
					   '</a>'
				   )
				   . '</p>';

		return $output;
	}

	public function register_inline_editor_action_buttons( $content_template ) {
		$content_template_has_native = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'NATIVE_SCREEN_ID' ) );
		?>
		<button
			class="button button-secondary toolset-ct-button-logo js-wpv-ct-apply-user-editor js-wpv-ct-apply-user-editor-<?php echo esc_attr( $this->editor->get_id() ); ?>"
			data-editor="<?php echo esc_attr( $this->editor->get_id() ); ?>"
            title="<?php echo __( 'Edit with', 'wpv-views' ) . ' ' . $this->editor->get_name() ?>"
			<?php disabled( $content_template_has_native ); ?>
		>
			<?php echo esc_html( $this->editor->get_name() ); ?>
		</button>
		<?php
	}

	/**
	 * Set the builder used by a Content Template, if any.
	 *
	 * On a Content Template used inside a View or WPA loop output, we set which builder it is using
	 * so we can link to the CT edit page with the right builder instantiated.
	 *
	 * @since 2.5.0
	 */
	public function layout_template_attribute( $attributes, $content_template, $view_id ) {
		$content_template_has_native = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'NATIVE_SCREEN_ID' ) );
		if ( $content_template_has_native ) {
			$attributes['builder'] = $this->editor->get_id();
		}
		return $attributes;
	}
}
