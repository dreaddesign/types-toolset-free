<?php

/**
 * Divi interoperability handler.
 *
 * @since 2.2.7
 */
class Types_Interop_Handler_Divi implements Types_Interop_Handler_Interface {

	private static $instance;

	public static function initialize() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		// Not giving away the instance on purpose.

	}


	private function __clone() { }


	private function __construct() {

		// admin_enqueue_scripts is too late and init is too early ($typenow is not populated at that time).
		// Actually, we need to load with even earlier priority because Types already enqueues things
		// at load-post.php.
		add_action( 'load-post.php', array( $this, 'fix_validation_method_extension_dependency' ), 8 );
		add_action( 'load-post-new.php', array( $this, 'fix_validation_method_extension_dependency' ), 8 );

	}


	/**
	 * Fix a compatibility issue that happens on the Edit Post page.
	 *
	 * Implemented for Divi 3.0.29.
	 *
	 * Types is extending the jQuery UI Validation plugin with some additional validation rules,
	 * some of which are used for custom field validation.
	 *
	 * The actual problem is that Divi is including the jQuery UI Validation plugin with a different
	 * script handler, 'validation', which was overwriting the instance enqueued by Types, and thus
	 * removing the additional validation rules.
	 *
	 * By registering the script with additional validation rules with a dependency on the Divi's
	 * jQuery UI Validation plugin instance, we avoid this issue.
	 *
	 * A more straightforward solution might be changing the script handler to 'validation'
	 * but that is a way too generic name and might cause issues elsewhere.
	 *
	 * Here, we're copying the logic of Divi's et_pb_admin_scripts_styles() and
	 * re-register the script only if Divi is about to enqueue its jQuery UI Validation plugin.
	 *
	 * @since 2.2.7
	 */
	public function fix_validation_method_extension_dependency() {

		if ( ! function_exists( 'et_builder_get_builder_post_types' ) ) {
			return;
		}

		$post_types = et_builder_get_builder_post_types();

		global $typenow;

		if ( ! isset( $typenow ) || ! in_array( $typenow, $post_types ) ) {
			return;
		}

		// Now we know that Divi is about to enqueue its jQuery UI Validation plugin.

		wp_deregister_script( Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES );

		$asset_manager = Types_Asset_Manager::get_instance();

		wp_register_script(
			Types_Asset_Manager::SCRIPT_ADDITIONAL_VALIDATION_RULES,
			$asset_manager->get_additional_validation_script_url(),
			// add another dependency
			array( 'jquery', 'validation', Types_Asset_Manager::SCRIPT_JQUERY_UI_VALIDATION ),
			TYPES_VERSION
		);

	}

}