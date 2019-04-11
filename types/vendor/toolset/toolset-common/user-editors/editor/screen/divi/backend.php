<?php

class Toolset_User_Editors_Editor_Screen_Divi_Backend
	extends Toolset_User_Editors_Editor_Screen_Abstract {

	/**
	 * @var Toolset_Constants
	 */
	protected $constants;

	/**
	 * Toolset_User_Editors_Editor_Screen_Divi_Backend constructor.
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		$this->constants = $constants
			? $constants
			: new Toolset_Constants();

		$this->constants->define( 'DIVI_SCREEN_ID', 'divi' );
	}

	public function initialize() {
		add_action( 'init', array( $this, 'register_assets' ), 50 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ), 50 );

		add_filter( 'toolset_filter_toolset_registered_user_editors', array( $this, 'register_user_editor' ) );
		add_filter( 'wpv_filter_wpv_layout_template_extra_attributes', array( $this, 'layout_template_attribute' ), 10, 3 );

		add_action( 'wpv_action_wpv_ct_inline_user_editor_buttons', array( $this, 'register_inline_editor_action_buttons' ) );

		add_action( 'toolset_set_layout_template_user_editor_divi', array( $this, 'update_divi_builder_post_meta' ) );
		add_action( 'toolset_set_layout_template_user_editor_basic', array( $this, 'update_divi_builder_post_meta' ) );

		if ( $this->divi_tries_to_load_layouts_or_templates() ) {
			add_filter( 'et_pb_show_all_layouts_built_for_post_type', array( $this, 'make_view_template_similar_to_page_for_layouts_or_templates' ) );
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
			'toolset-user-editors-divi-style',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/divi/backend.css',
			array(),
			TOOLSET_COMMON_VERSION
		);

		// Native post editor screen assets

		$toolset_assets_manager->register_script(
			'toolset-user-editors-divi-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/divi/backend_editor.js',
			array( 'jquery' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		// Content Template as inline object assets

		$toolset_assets_manager->register_script(
			'toolset-user-editors-divi-layout-template-script',
			TOOLSET_COMMON_URL . '/user-editors/editor/screen/divi/backend_layout_template.js',
			array( 'jquery', 'views-layout-template-js', 'underscore' ),
			TOOLSET_COMMON_VERSION,
			true
		);

		$divi_layout_template_i18n = array(
			'template_editor_url' => admin_url( 'admin.php?page=ct-editor' ),
			'template_overlay' => array(
				'title' => sprintf( __( 'You created this template using %1$s', 'wpv-views' ), $this->editor->get_name() ),
				'button' => sprintf( __( 'Edit with %1$s', 'wpv-views' ), $this->editor->get_name() ),
				'discard' => sprintf( __( 'Stop using %1$s for this Content Template', 'wpv-views' ), $this->editor->get_name() ),
			),
		);

		$toolset_assets_manager->localize_script(
			'toolset-user-editors-divi-layout-template-script',
			'toolset_user_editors_divi_layout_template_i18n',
			$divi_layout_template_i18n
		);
	}

	public function admin_enqueue_assets() {
		if ( $this->is_views_or_wpa_edit_page() ) {
			do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-divi-layout-template-script' ) );
		}
	}

	public function action_enqueue_assets() {
		do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-divi-style' ) );
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
		$content_template_has_divi = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'DIVI_SCREEN_ID' ) );
		?>
		<button
			class="button button-secondary toolset-ct-button-logo js-wpv-ct-apply-user-editor js-wpv-ct-apply-user-editor-<?php echo esc_attr( $this->editor->get_id() ); ?>  <?php echo $this->editor->get_logo_class(); ?>"
			data-editor="<?php echo esc_attr( $this->editor->get_id() ); ?>"
            title="<?php echo __( 'Edit with', 'wpv-views' ) . ' ' . $this->editor->get_name() ?>"
			<?php disabled( $content_template_has_divi ); ?>
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
		$content_template_has_divi = ( get_post_meta( $content_template->ID, '_toolset_user_editors_editor_choice', true ) == $this->constants->constant( 'DIVI_SCREEN_ID' ) );
		if ( $content_template_has_divi ) {
			$attributes['builder'] = $this->editor->get_id();
		}
		return $attributes;
	}

	public function update_divi_builder_post_meta() {
		if (
			isset( $_POST['ct_id'] )
			&& $_POST['ct_id']
		) {
			do_action( 'toolset_update_divi_builder_post_meta', $_POST['ct_id'], 'editor' );
		}
	}

	/**
	 * Divi saves it's layouts and templates for the 'page' post type. In order to fetch those layouts or templates for
	 * "view-template" post type, we need to do it the same way Divi does it for the 'post' and 'project' post type.
	 * When the current post type is 'post', Divi replaces that with an array containing the current post type and the
	 * 'page' post type. So for the case of 'view-template', we are also adding the 'page' post type to the returned value.
	 *
	 * @param $post_types
	 *
	 * @return array       The post types to fetch layouts or template for.
	 */
	public function make_view_template_similar_to_page_for_layouts_or_templates( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}
		$post_types[] = 'page';

		return $post_types;
	}

	/**
	 * Detect whether or not Divi performs an AJAX call to load layouts or templates from its library.
	 *
	 * @return bool
	 */
	public function divi_tries_to_load_layouts_or_templates() {
		if (
			! defined( 'DOING_AJAX' )
			|| ! DOING_AJAX
			|| ! isset( $_POST['action'] )
		) {
			return false;
		}

		if (
			'et_pb_show_all_layouts' === $_POST['action']
			&& isset( $_POST['et_layouts_built_for_post_type'] )
			&& 'view-template' === $_POST['et_layouts_built_for_post_type']
		) {
			return true;
		}

		if (
			'et_pb_get_saved_templates' === $_POST['action']
			&& isset( $_POST['et_post_type'] )
			&& 'view-template' === $_POST['et_post_type']
		) {
			return true;
		}

		return false;
	}
}
