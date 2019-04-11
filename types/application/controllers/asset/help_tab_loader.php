<?php

/**
 * Manages adding help tabs to admin pages.
 *
 * Usage: Set array( Types_Asset_Help_Tab_Loader::get_instance, 'add_help_tab' ) to 'contextual_help_hook' in
 * the shared Toolset menu item configuration and then extend the get_help_config() method to return a valid
 * help tab configuration for the needed page name.
 *
 * @since 2.0
 */
final class Types_Asset_Help_Tab_Loader {


	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() { }

	private function __clone() { }


	/**
	 * Add help tabs to current screen.
	 *
	 * Used as a hook for 'contextual_help_hook' in the shared Toolset menu.
	 *
	 * @since 2.0
	 */
	public function add_help_tab() {

		$screen = get_current_screen();

		if ( is_null( $screen ) ) {
			return;
		}

		$current_page = sanitize_text_field( wpcf_getget( 'page', null ) );
		if ( null == $current_page ) {
			return;
		}

		$help_content = $this->get_help_content( $current_page );
		if( null == $help_content ) {
			return;
		}

		$args = array(
			'title' => wpcf_getarr( $help_content, 'title' ),
			'id' => 'wpcf',
			'content' => wpcf_getarr( $help_content, 'content' ),
			'callback' => false,
		);

		$screen->add_help_tab( $args );

		$this->add_need_help_tab();

	}


	/**
	 * Need Help section for a bit advertising.
	 *
	 * @since 2.0
	 */
	private function add_need_help_tab() {

		$args = array(
			'title' => __( 'Need More Help?', 'wpcf' ),
			'id' => 'custom_fields_group-need-help',
			'content' => wpcf_admin_help( 'need-more-help' ),
			'callback' => false,
		);

		$screen = get_current_screen();
		$screen->add_help_tab( $args );

	}


	/**
	 * Generate the configuration for help tab.
	 *
	 * The configuration needs to contain three keys:
	 * - title: Title of the tab.
	 * - template: Name of the Twig template (assuming the 'help' namespace is available)
	 * - context: Context object for Twig.
	 *
	 * @param string $page_name Name of current page.
	 * @return array|null Help tab configuration array or null when no help tab should be displayed.
	 * @since 2.0
	 */
	private function get_help_config( $page_name ) {

		switch( $page_name ) {
			case Types_Admin_Menu::PAGE_NAME_FIELD_CONTROL:
				return Types_Page_Field_Control::get_instance()->get_help_config();

			default:
				return null;
		}
	}


	/**
	 * Render help tab content from its configuration.
	 *
	 * @param string $page_name Name of current page.
	 * @return array|null Null when no help tab should be displayed, or an array with keys 'title' and 'content'.
	 * @since 2.0
	 */
	private function get_help_content( $page_name ) {

		$config = $this->get_help_config( $page_name );
		if( null == $config ) {
			return null;
		}

		$twig = $this->get_twig();
		
		return array(
			'title' => wpcf_getarr( $config, 'title' ),
			'content' => $twig->render( wpcf_getarr( $config, 'template' ), wpcf_getarr( $config, 'context' ) )
		);
	}


	/** @var Twig_Environment|null */
	private $twig = null;


	/**
	 * @return Twig_Environment Initialized Twig environment object for help tab content rendering.
	 * @throws Twig_Error_Loader
	 * @since 2.0
	 */
	private function get_twig() {
		if( null == $this->twig ) {
			$loader = new Twig_Loader_Filesystem();
			$loader->addPath( TYPES_ABSPATH . '/application/views/help', 'help' );
			$this->twig = new Twig_Environment( $loader );
		}
		return $this->twig;
	}

}