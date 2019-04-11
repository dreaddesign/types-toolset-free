<?php

/**
 * Editor class for the Fusion Builder (Avada).
 *
 * Handles all the functionality needed to allow the Fusion Builder to work with Content Template editing.
 *
 * @since 2.5.0
 */

class Toolset_User_Editors_Editor_Avada
	extends Toolset_User_Editors_Editor_Abstract {

	const FUSION_BUILDER_OPTION_NAME = 'fusion_builder_status';
	const FUSION_BUILDER_OPTION_VALUE = 'active';

	protected $id = 'avada';
	protected $name = 'Fusion Builder';
	protected $option_name = '_toolset_user_editors_avada_template';

	protected $logo_class = 'dashicons-fusiona-logo';

	public function initialize() {
		if ( apply_filters( 'wpv_filter_is_native_editor_for_cts', false ) ) {
			add_action( 'edit_form_after_editor', array( $this, 'register_assets_for_backend_editor' ) );

			// register medium slug
			add_filter( 'fusion_builder_default_post_types', array( $this, 'support_medium' ) );
			add_filter( 'fusion_builder_allowed_post_types', array( $this, 'support_medium' ) );
		}

		add_action( 'edit_form_after_editor', array( $this, 'register_assets_for_avada_compatibility' ) );

		add_action( 'toolset_update_fusion_builder_post_meta', array( $this, 'update_fusion_builder_post_meta' ), 10, 2 );

		if (
			isset( $this->medium )
			&& $this->medium->get_id()
		) {
			$this->update_fusion_builder_post_meta( $this->medium->get_id(), 'ct_editor_choice' );
		}
	}

	public function update_fusion_builder_post_meta( $post_id, $key ) {
		if ( array_key_exists( $key, $_REQUEST ) ) {
			if ( $this->get_id() == sanitize_text_field( $_REQUEST[ $key ] ) ) {
				update_post_meta( $post_id, self::FUSION_BUILDER_OPTION_NAME, sanitize_text_field( self::FUSION_BUILDER_OPTION_VALUE ) );
			} else {
				delete_post_meta( $post_id, self::FUSION_BUILDER_OPTION_NAME );
			}
		}
	}

	public function required_plugin_active() {
		if ( ! apply_filters( 'toolset_is_views_available', false ) ) {
			return false;
		}

		if ( defined( 'FUSION_BUILDER_VERSION' ) ) {
			$this->name = __( 'Fusion Builder', 'fusion-builder' );
			return true;
		}

		return false;
	}

	public function run() {}

	public function register_assets_for_backend_editor() {
		do_action( 'toolset_enqueue_scripts', array( 'toolset-user-editors-avada-script' ) );
	}

	public function register_assets_for_avada_compatibility() {
		// The enqueueing of the style for Fusion Builder was moved outside the "CT editing" condition to also support
		// compatibility to the native post/page editor when Fusion Builder is used there too.
		do_action( 'toolset_enqueue_styles', array( 'toolset-user-editors-avada-editor-style' ) );
	}

	/**
	 * We need to register the slug of our Medium in Fusion Builder.
	 *
	 * @wp-filter fusion_builder_default_post_types
	 * @param $allowed_types
	 * @return array
	 */
	public function support_medium( $allowed_types ) {

		if ( ! in_array( 'view-template', $allowed_types ) ) {
			$allowed_types[] = 'view-template';
		}

		return $allowed_types;
	}

}
