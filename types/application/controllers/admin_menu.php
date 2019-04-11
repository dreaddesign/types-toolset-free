<?php

/**
 * Admin menu controller for Types.
 *
 * All Types pages, menus, submenus and whatnot need to be registered here. One of the main goals is to avoid
 * loading specific page controllers unless their page is actually being loaded. All page slugs in Types *must*
 * be defined here as constants PAGE_NAME_*.
 *
 * @since 2.0
 */
final class Types_Admin_Menu {

	/** Temporary slug compatible with the legacy code. */
	const MENU_SLUG = 'wpcf';


	// All (non-legacy) page slugs.
	const PAGE_NAME_FIELD_CONTROL = 'types-field-control';
	const PAGE_NAME_HELPER        = 'types-helper'; // hidden page
	const PAGE_NAME_DASHBOARD     = 'types-dashboard';


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



	private function __construct() {
		// Priority is hardcoded by filter documentation.
		add_filter( 'toolset_filter_register_menu_pages', array( $this, 'on_admin_menu' ), 10 );

		// Load Dashboard
		Types_Page_Dashboard::get_instance();
	}


	private function __clone() { }


	/**
	 * Add all Types submenus and jumpstart a specific page controller if needed.
	 *
	 * Toolset shared menu usage is described here:
	 * @link https://git.onthegosystems.com/toolset/toolset-common/wikis/toolset-shared-menu
	 *
	 * @param array $pages Array of menu item definitions.
	 * @return array Updated item definition array.
	 * @since 2.0
	 */
	public function on_admin_menu( $pages ) {
		// Add legacy pages
		$pages = wpcf_admin_toolset_register_menu_pages( $pages );

		$page_name = sanitize_text_field( wpcf_getget( 'page' ) );
		if( !empty( $page_name ) ) {
			$pages = $this->maybe_add_ondemand_submenu( $pages, $page_name );
		}
		return $pages;
	}


	/**
	 * Check if an on-demand submenu should be added, and jumpstart it's controller if needed.
	 *
	 * On-demand submenu means that the submenu isn't displayed normally, it appears only when its page is loaded.
	 *
	 * Note: All page controllers should inherit from Types_Page_Abstract.
	 *
	 * @param array $pages Array of menu item definitions.
	 * @param string $page_name
	 * @return array Updated item definition array.
	 * @since 2.0
	 */
	private function maybe_add_ondemand_submenu( $pages, $page_name ) {
		$page = null;
		switch( $page_name ) {
			case self::PAGE_NAME_FIELD_CONTROL:
				$page = Types_Page_Field_Control::get_instance();
				break;
			case self::PAGE_NAME_HELPER:
				Types_Page_Hidden_Helper::get_instance();
				break;
		}

		if( $page instanceof Types_Page_Abstract ) {

			// Jumpstart the page controller.
			try {
				$page->prepare();
			} catch( Exception $e ) {
				wp_die( $e->getMessage() );
			}

			$pages[ $page_name ] = array(
				'slug' => $page_name,
				'menu_title' => $page->get_title(),
				'page_title' => $page->get_title(),
				'callback' => $page->get_render_callback(),
				'load_hook' => $page->get_load_callback(),
				'capability' => $page->get_required_capability(),
				'contextual_help_hook' => array( Types_Asset_Help_Tab_Loader::get_instance(), 'add_help_tab' )
			);

			// todo we might need to handle adding URL parameters to submenu URLs in some standard way, it's common scenario for ondemand submenus

		}

		return $pages;
	}

}