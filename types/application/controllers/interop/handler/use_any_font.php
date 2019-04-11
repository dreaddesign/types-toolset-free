<?php

/**
 * Use Any Font interoperability handler.
 *
 * @link https://wordpress.org/plugins/use-any-font/
 *
 * @since 2.2.9
 */
class Types_Interop_Handler_Use_Any_Font implements Types_Interop_Handler_Interface {

	private static $instance;

	public static function initialize() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		// Not giving away the instance on purpose.

	}


	private function __clone() {
	}


	private function __construct() {
		$this->maybe_unregister_uaf_assets();
	}


	/**
	 * Fix a compatibility issue with conflicting JS assets.
	 *
	 * Implemented for Use Any Font 4.6.
	 *
	 * On Edit Post Type page, UAF enqueues its own instance of jQuery UI Validation plugin which overwrites the one
	 * from Types, with added additional validation rules.
	 *
	 * Turns out, UAF enqueues everything everywhere without care although it has only a single admin page.
	 * In general, it has very bad coding practices (unprefixed function names, etc.)
	 *
	 * Here, we do what UAF should do on its own: Check if we're on its settings page and if
	 * we're not, avoid loading its assets.
	 *
	 * @since 2.2.9
	 */
	function maybe_unregister_uaf_assets() {
		$main_controller = Types_Main::get_instance();
		$is_uaf_settings_page = (
			$main_controller->get_plugin_mode() === Types_Main::MODE_ADMIN && wpcf_getget( 'page' ) === 'uaf_settings_page'
		);

		if ( ! $is_uaf_settings_page ) {
			remove_action( 'admin_print_scripts', 'adminjslibs' );
			remove_action( 'admin_print_styles', 'adminCsslibs' );
		}
	}

}