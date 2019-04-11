<?php

/**
 * Main Types controller.
 *
 * Determines if we're in admin or front-end mode or if an AJAX call is being performed. Handles tasks that are common
 * to all three modes, if there are any.
 *
 * @since 2.0
 */
final class Types_Main {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function initialize() {
		self::get_instance();
	}

	private function __clone() { }


	private function __construct() {

		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ), 10 );
		add_action( 'init', array( $this, 'on_init' ) );

	}



	/**
	 * Determine in which mode we are and initialize the right dedicated controller.
	 *
	 * @since 2.0
	 */
	public function on_init() {
		if( is_admin() ) {
			if( defined( 'DOING_AJAX' ) ) {
				$this->mode = self::MODE_AJAX;
				Types_Ajax::initialize();
			} else {
				$this->mode = self::MODE_ADMIN;
				Types_Admin::initialize();
			}
		} else {
			$this->mode = self::MODE_FRONTEND;
			Types_Frontend::initialize();
		}
	}


	/**
	 * @var string One of the MODE_* constants.
	 */
	private $mode = self::MODE_UNDEFINED;

	const MODE_UNDEFINED = '';
	const MODE_AJAX = 'ajax';
	const MODE_ADMIN = 'admin';
	const MODE_FRONTEND = 'frontend';

	/**
	 * Get current plugin mode.
	 *
	 * Possible values are:
	 * - MODE_UNDEFINED before the main controller initialization is completed
	 * - MODE_AJAX when doing an AJAX request
	 * - MODE_ADMIN when showing a WP admin page
	 * - MODE_FRONTEND when rendering a frontend page
	 *
	 * @return string
	 * @since 2.1
	 */
	public function get_plugin_mode() {
		return $this->mode;
	}


	/**
	 * Set current plugin mode.
	 *
	 * @param string $new_mode the new plugin mode
	 * @return bool TRUE if set is succesfully done, FALSE otherwise
	 * @since 2.2
	 */
	public function set_plugin_mode( $new_mode = self::MODE_UNDEFINED ) {
		if ( !in_array( $new_mode, array( self::MODE_UNDEFINED, self::MODE_AJAX, self::MODE_ADMIN, self::MODE_FRONTEND ) ) ){
			return false;
		}
		$this->mode = $new_mode;
		return true;
	}


	/**
	 * Determine whether a WP admin page is being loaded.
	 *
	 * Note that the behaviour differs from the native is_admin() which will return true also for AJAX requests.
	 *
	 * @return bool
	 * @since 2.1
	 */
	public function is_admin() {
		return ( $this->get_plugin_mode() == self::MODE_ADMIN );
	}


	/**
	 * Early loading actions.
	 *
	 * @since 2.0
	 */
	public function after_setup_theme() {

		// Initialize the Toolset Common library
		Toolset_Common_Bootstrap::get_instance();

		$this->setup_autoloader();

		// If an AJAX callback handler needs other assets, it should initialize the asset manager by itself.
		if( $this->get_plugin_mode() != self::MODE_AJAX ) {
			Types_Assets::get_instance()->initialize_scripts_and_styles();
		}

		// Handle embedded plugin mode
		Types_Embedded::initialize();

		Types_Api::initialize();

		Types_Interop_Mediator::initialize();
	}


	private function setup_autoloader() {

		// It is possible to regenerate the classmap with Zend framework.
		//
		// See the "recreate_classmap.sh" script in the plugin root directory.
		$classmap = include( TYPES_ABSPATH . '/application/autoload_classmap.php' );

		// Use Toolset_Common_Autoloader
		do_action( 'toolset_register_classmap', $classmap );

	}


	/**
	 * In some cases, it may not be clear what legacy files are includes and what aren't.
	 *
	 * This method should make sure all is covered (add files when needed). Use only when necessary.
	 *
	 * @since 2.0
	 */
	public function require_legacy_functions() {
		require_once WPCF_INC_ABSPATH . '/fields.php';
	}

}
