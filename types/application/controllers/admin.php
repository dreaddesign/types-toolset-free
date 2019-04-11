<?php

/**
 * Main backend controller for Types.
 * 
 * @since 2.0
 */
final class Types_Admin {


	/**
	 * Initialize Types for backend. 
	 * 
	 * This is expected to be called during init.
	 * 
	 * @since 2.0
	 */
	public static function initialize() {
		new self();
	}
	
	
	private function __construct() {
		$this->on_init();
	}
	
	
	private function __clone() { }
	
	
	private function on_init() {

		Types_Upgrade::initialize();

		// Load Twig - this is a bit hacky way to do it, see Types_Twig_Autoloader class for explanation.
		Types_Twig_Autoloader::register();

		// Load menu - won't be loaded in embedded version.
		if( apply_filters( 'types_register_pages', true ) ) {
			Types_Admin_Menu::initialize();
		}

		$this->init_page_extensions();

		// admin notices
		Toolset_Singleton_Factory::get( 'Types_Admin_Notices_Free_Version' );
	}


	/**
	 * Add hooks for loading page extensions.
	 *
	 * @since 2.1
	 */
	private function init_page_extensions() {
		// extensions for post edit page
		add_action( 'load-post.php', array( 'Types_Page_Extension_Edit_Post', 'get_instance' ) );

		// extension for post type edit page
		add_action( 'load-toolset_page_wpcf-edit-type', array( 'Types_Page_Extension_Edit_Post_Type', 'get_instance' ) );

		// extension for post fields edit page
		add_action( 'load-toolset_page_wpcf-edit', array( 'Types_Page_Extension_Edit_Post_Fields', 'get_instance' ) );

		// settings
		add_action( 'load-toolset_page_toolset-settings', array( $this, 'init_settings' ) );
	}


	/**
	 * Initialize the extension for the Toolset Settings page.
	 * 
	 * @since 2.1
	 */
	public function init_settings() {
		$settings = new Types_Page_Extension_Settings();
		$settings->build();
	}

}