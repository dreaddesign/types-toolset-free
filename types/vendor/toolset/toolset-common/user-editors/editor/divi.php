<?php
/**
 * Editor class for the Divi Builder.
 *
 * Handles all the functionality needed to allow the Divi Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Divi
	extends Toolset_User_Editors_Editor_Abstract {

	const DIVI_BUILDER_OPTION_NAME = '_et_pb_use_builder';
	const DIVI_BUILDER_OPTION_VALUE = 'on';

	protected $id = 'divi';
	protected $name = 'Divi Builder';
	protected $option_name = '_toolset_user_editors_divi_template';

	protected $logo_class = 'toolset-divi-logo-for-ct-button';

	public function initialize() {
		if ( apply_filters( 'wpv_filter_is_native_editor_for_cts', false ) ) {
			add_action( 'edit_form_after_editor', array( $this, 'register_assets_for_backend_editor' ) );

			// register medium slug
			add_filter( 'et_builder_post_types', array( $this, 'support_medium' ) );
			add_filter( 'et_builder_module_post_types', array( $this, 'support_medium' ) );
		}

		add_action( 'toolset_update_divi_builder_post_meta', array( $this, 'update_divi_builder_post_meta' ), 10, 2 );

		add_action( 'wp_loaded', array( $this, 'add_filter_for_divi_modules_for_cts' ) );

		add_filter( 'get_post_metadata', array( $this, 'maybe_post_uses_divi_built_ct' ), 10, 4 );

		if (
			isset( $this->medium )
			&& $this->medium->get_id()
		) {
			$this->update_divi_builder_post_meta( $this->medium->get_id(), 'ct_editor_choice' );
		}
	}

	public function add_filter_for_divi_modules_for_cts() {
		add_filter( 'et_builder_module_post_types', array( $this, 'support_medium' ) );
	}

	public function update_divi_builder_post_meta( $post_id, $key ) {
		if ( array_key_exists( $key, $_REQUEST ) ) {
			if ( $this->get_id() == sanitize_text_field( $_REQUEST[ $key ] ) ) {
				update_post_meta( $post_id, self::DIVI_BUILDER_OPTION_NAME, sanitize_text_field( self::DIVI_BUILDER_OPTION_VALUE ) );
			} else {
				delete_post_meta( $post_id, self::DIVI_BUILDER_OPTION_NAME );
			}
		}
	}

	public function required_plugin_active() {
		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if (
			defined( 'ET_BUILDER_THEME' )
			|| defined( 'ET_BUILDER_PLUGIN_VERSION' )
		) {
			$this->name = __( 'Divi Builder', 'wpv-views' );
			return true;
		}

		return false;
	}

	public function run() {}

	public function register_assets_for_backend_editor() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-divi-script' ) );
	}

	/**
	 * We need to register the slug of our Medium in Divi Builder.
	 *
	 * @wp-filter et_builder_post_types
	 * @param $allowed_types
	 * @return array
	 */
	public function support_medium( $allowed_types ) {
		if ( ! in_array( 'view-template', $allowed_types ) ) {
			$allowed_types[] = 'view-template';
		}

		return $allowed_types;
	}

	/**
	 * Hijack the "get_post_meta( $post_id, '_et_pb_use_builder', true )" call that checks if the post with ID equals to
	 * $post_id is built with Divi builder. The hijacking relates to checking on posts/pages that use content templates
	 * built with Divi. In this case, the post will be identified as one that uses Divi builder.
	 *
	 * @param  string $meta_value The value of the meta.
	 * @param  int    $post_id    The current post ID.
	 * @param  string $meta_key   The key of the meta.
	 * @param  bool   $single     Whether to return a single value.
	 * @return mixed
	 */
	public function maybe_post_uses_divi_built_ct( $meta_value, $post_id, $meta_key, $single ) {
		if ( $meta_key === self::DIVI_BUILDER_OPTION_NAME ) {
			$ct_id = get_post_meta( $post_id, '_views_template', true );
			if ( $ct_id ) {
				$meta_value = get_post_meta( $ct_id, self::DIVI_BUILDER_OPTION_NAME, true );
			}
		}

		return $meta_value;
	}
}
