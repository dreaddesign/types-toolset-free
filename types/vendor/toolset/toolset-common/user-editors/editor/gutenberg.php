<?php
/**
 * Editor class for the Divi Builder.
 *
 * Handles all the functionality needed to allow the Divi Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Gutenberg
	extends Toolset_User_Editors_Editor_Abstract {

	protected $id = 'gutenberg';
	protected $name = 'Gutenberg';

	public function initialize() {
		add_action( 'init', array( $this, 'add_support_for_ct_edit_by_gutenberg_editor' ), 9 );
	}

	public function required_plugin_active() {
		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if (
			defined( 'GUTENBERG_VERSION' )
			|| defined( 'GUTENBERG_DEVELOPMENT_MODE' )
		) {
			$this->name = __( 'Gutenberg', 'wpv-views' );
			return true;
		}

		return false;
	}

	public function run() {}

	public function add_support_for_ct_edit_by_gutenberg_editor() {
		add_filter( 'register_post_type_args', array( $this, 'make_ct_editable_by_gutenberg_editor' ), 10, 2 );
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
	public function make_ct_editable_by_gutenberg_editor( $args, $name ) {
		if ( 'view-template' === $name ) {
			$args['show_in_rest'] = true;
		}
		return $args;
	}

}