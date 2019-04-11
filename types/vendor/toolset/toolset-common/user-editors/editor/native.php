<?php

/**
 * Editor class for the Native post editor.
 *
 * Handles all the functionality needed to allow the Native post editor to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Native
	extends Toolset_User_Editors_Editor_Abstract {

	protected $id = 'native';
	protected $name = 'Native editor';
	protected $option_name = '_toolset_user_editors_native';

	public function initialize() {
		if ( $this->is_native_editor_for_cts() ) {
			add_action( 'init', array( $this, 'add_support_for_ct_edit_by_native_editor' ), 9 );

			add_action( 'current_screen', array( $this, 'show_notice_to_get_back_to_toolset_ct_editor' ) );

			add_action( 'add_meta_boxes_view-template', array( $this, 'remove_native_editor_meta_boxes' ) );

			add_action( 'init', array( $this, 'register_assets_for_backend_editor' ), 51 );

			add_filter( 'wpv_filter_is_native_editor_for_cts', array( $this, 'is_native_editor_for_cts' ) );

			add_filter( 'toolset_filter_force_shortcode_generator_display', array( $this, 'force_shortcode_generator_display' ) );

			add_filter( 'post_updated_messages', array( $this, 'adjust_post_updated_messages' ) );
		}
	}

	public function required_plugin_active() {

		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		return true;
	}

	public function run() {}

	public function register_assets_for_backend_editor() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-native-script' ) );
	}


	/**
	 * Verify that the current page and URL parameters qualify for the editing of Content Templates using the
	 * native post editor.
	 *
	 * @param  null|int $post_id The current post ("view-template" post) ID.
	 *
	 * @return bool
	 *
	 * @since 2.5.0
	 */
	public function is_native_editor_for_cts() {
		global $pagenow;

		$action = wpv_getget( 'action', null );
		$action = null === $action ? wpv_getpost( 'action', null ) : $action;

		$post_id = (int) wpv_getget( 'post', 0 );
		$post_id = ( 0 === $post_id ? (int) wpv_getpost( 'post_ID', 0 ) : $post_id );

		$post = get_post( $post_id );
		if (
			'post.php' === $pagenow
			&& ( 'edit' === $action || 'editpost' == $action )
			&& null !== $post
			&& 'view-template' === $post->post_type
		) {
			return true;
		}

		return false;
	}

	public function add_support_for_ct_edit_by_native_editor() {
		add_filter( 'register_post_type_args', array( $this, 'make_ct_editable_by_native_editor' ), 10, 2 );
	}

	/**
	 * For the "view-template" custom post type to be editable by the native post editor, we need to temporarily set
	 * the "show_ui" argument that is used during the custom post type registration to true.
	 *
	 * @param  array  $args The arguments of the custom post type for its registration.
	 * @param  string $name The name of the custom post type to be registered.
	 *
	 * @return mixed        The filtered arguments.
	 *
	 * @since 2.5.0
	 */
	public function make_ct_editable_by_native_editor( $args, $name ) {
		if ( 'view-template' === $name ) {
			$args['show_ui'] = true;
		}
		return $args;
	}

	/**
	 * The "view-template" custom post type supports "author" and "slug" by design. In the native post editor,
	 * when editing a Content Template, we need to hide the meta-boxes.
	 *
	 * @since 2.5.0
	 */
	public function remove_native_editor_meta_boxes() {
		remove_meta_box( 'authordiv', 'view-template', 'normal' );
		remove_meta_box( 'slugdiv', 'view-template', 'normal' );
	}

	/**
	 * Show a notice on the top of the native post editor with a link to return back to the Toolset Content Template editor.
	 *
	 * @since 2.5.0
	 */
	public function show_notice_to_get_back_to_toolset_ct_editor() {
		$ct_id = (int) wpv_getget( 'post', 0 );

		$notice = new Toolset_Admin_Notice_Success( 'return-to-toolset-ct-editor-page-notice' );

		$notice_content = sprintf(
			__( 'Done editing here? Return to the %1$sToolset Content Template editor%2$s.', 'wpv-views' ),
			'<a href="' . admin_url( 'admin.php?page=ct-editor&ct_id=' . $ct_id ) . '">',
			'</a>'
		);

		Toolset_Admin_Notices_Manager::add_notice( $notice, $notice_content );
	}

	public function force_shortcode_generator_display( $register_section ) {
		return true;
	}

	public function adjust_post_updated_messages( $messages ) {
		$messages['post'][1] = __( 'Content Template updated.', 'wpv-views' );
		return $messages;
	}
}
