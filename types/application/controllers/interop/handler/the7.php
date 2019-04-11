<?php
/**
 * The7 theme interoperability handler.
 *
 * @since 2.2.16
 */
class Types_Interop_Handler_The7 implements Types_Interop_Handler_Interface {

	/** @var Types_Interop_Handler_The7 */
	private static $instance;


	/**
	 * Initialize the interop handler.
	 */
	public static function initialize() {

		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->add_hooks();
		}

		// Not giving away the instance on purpose.
	}


	/**
	 * Hook to the event of rendering the legacy editor code, which happens just before
	 * enqueuing assets.
	 */
	public function add_hooks() {
		add_action( 'types_leagacy_editor_callback_init', array( $this, 'remove_presscore_hooks' ) );
	}


	/**
	 * Fix a compatibility issue caused, simply put, by our own terrible mess.
	 *
	 * The issue was visible when activating The7 theme, Views and Types, on the Edit Content Template page (for example).
	 * When trying to add a Types field, the AJAX call to render the dialog markup has failed due to a fatal error.
	 * The result was that the dialog never opened and the user was left with the grey overlay and no error message.
	 *
	 * The fatal error was caused by the fact that the legacy code in Types, namely in embedded/includes/ajax/admin-header.php
	 * is running do_action( 'admin_enqueue_scripts', $hook_suffix ); outside of the context of the
	 * standard admin page loading mechanism.
	 *
	 * That causes problems in The7_Demo_Content_Admin::enqueue_scripts() (which is hooked even during this
	 * non-standard procedure): It accesses the global $tgmpa variable which is assumed to be
	 * an instance of The7_TGMPA but for some reason, it's never initialized.
	 *
	 * The workaround is also extremely ugly: We intercept the AJAX call that renders the dialog markup and check
	 * all admin_enqueue_scripts hooks. If its callback has a prefix coming from The7 theme, we'll remove the action.
	 *
	 * It is safe because we're targeting only a very specific scenario, where the theme's admin assets
	 * aren't needed at all.
	 *
	 * This can be removed when the dialog is finally refactored into something sensible.
	 *
	 * @since 2.2.16
	 */
	public function remove_presscore_hooks() {
		if ( toolset_getget( 'action' ) === 'wpcf_ajax' && toolset_getget( 'wpcf_action' ) === 'editor_callback' ) {
			global $wp_filter;
			/** @var WP_Hook $admin_enqueue_script_hooks */
			$admin_enqueue_script_hooks = toolset_getarr( $wp_filter, 'admin_enqueue_scripts', array() );

			foreach ( $admin_enqueue_script_hooks->callbacks as $priority => $callbacks_for_priority ) {
				foreach ( $callbacks_for_priority as $callback_id => $callback ) {
					$function = $callback['function'];

					$the7_string_prefix = 'presscore_';
					$is_the7_string_callback = ( is_string( $function ) && substr( $function, 0, strlen( $the7_string_prefix ) ) === $the7_string_prefix );

					$the7_class_prefix = 'The7_';
					$is_the7_class_callback = (
						is_array( $function )
						&& count( $function ) === 2
						&& substr( get_class( $function[0] ), 0, strlen( $the7_class_prefix ) ) === $the7_class_prefix
					);

					if ( $is_the7_class_callback || $is_the7_string_callback ) {
						remove_action( 'admin_enqueue_scripts', $function, $priority );
					}
				}
			}
		}
	}

}