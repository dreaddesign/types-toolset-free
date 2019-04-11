<?php
/**
 * Class Toolset_Controller_Admin_Notices
 * Controls all Toolset related admin notices
 *
 * @since 2.3.0 First release of Toolset_Admin_Notice_Dismissible
 *              All containing properties and methods without since tag are part of the initial release
 */
class Toolset_Controller_Admin_Notices {
	protected $current_screen;

	protected $tpl_path;

	protected $is_types_active;
	protected $is_views_active;
	protected $is_layouts_active;
	protected $is_cred_active;
	protected $is_access_active;
	protected $is_tbt_active;
	protected $is_tbt_inactive;

	protected $constants;

	public function __construct( Toolset_Constants $constants = null ) {
		if ( null === $constants ) {
			$constants = new Toolset_Constants();
		}
		$this->constants = $constants;

		add_action( 'after_setup_theme', array( $this, 'init' ), 1000 );
	}

	public function init() {
		$this->tpl_path = TOOLSET_COMMON_PATH . '/templates/admin/notice';

		$condition = new Toolset_Condition_Plugin_Types_Active();
		$this->is_types_active = $condition->is_met();

		$condition = new Toolset_Condition_Plugin_Views_Active();
		$this->is_views_active = $condition->is_met();

		$condition = new Toolset_Condition_Plugin_Layouts_Active();
		$this->is_layouts_active = $condition->is_met();

		$condition = new Toolset_Condition_Plugin_Cred_Active();
		$this->is_cred_active = $condition->is_met();

		$condition = new Toolset_Condition_Plugin_Access_Active();
		$this->is_access_active = $condition->is_met();

		$condition = new Toolset_Condition_Theme_Toolset_Based_Active();
		$this->is_tbt_active = $condition->is_met();

		$condition = new Toolset_Condition_Theme_Toolset_Based_Inactive();
		$this->is_tbt_inactive = $condition->is_met();

		add_action('current_screen', array( $this, 'init_screens') );
	}

	/**
	 * Initialize where to show the notices
	 * Fired on hook 'current_screen'
	 */
	public function init_screens() {
		if( ! function_exists( 'get_current_screen' ) ) {
			// loaded to early
			return;
		}

		$this->current_screen = get_current_screen();

		$this->screen_any();
		$this->screen_toolset_dashboard();
		$this->screens_toolset_toplevel_pages();
		$this->screen_wordpress_dashboard();
		$this->screen_wordpress_plugins();
		$this->screen_wordpress_themes();
	}

	/**
	 * Notices for any page on the admin screen
	 */
	protected function screen_any() {
		if( ! is_admin() ) {
			return;
		}

		// If Types is active and any of the Commercial Plugins is active, but site is not registered,
		// then display a notice to force user to register Toolset.
		if ( $this->is_types_active && $this->is_commercial_active_but_not_registered() ) {
			$this->commercial_plugin_installed_but_not_registered();
		}
	}

	protected function is_commercial_active_but_not_registered( $abort_for_development_sites = true ) {
		$repository_id = 'toolset';

		if( $abort_for_development_sites && $this->is_development_environment() ) {
			return false;
		}

		if( class_exists( 'WP_Installer' )
		    && ! WP_Installer()->repository_has_valid_subscription( $repository_id )
		    && (
			    $this->is_views_active
			    || $this->is_access_active
			    || $this->is_cred_active
			    || $this->is_layouts_active
		    )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get current screen id
	 * @return bool
	 */
	protected function get_current_screen_id() {
		if( is_object( $this->current_screen ) and property_exists( $this->current_screen, 'id' ) ) {
			return $this->current_screen->id;
		}

		return false;
	}

	/**
	 * Notices for all Toolset toplevel pages
	 */
	protected function screens_toolset_toplevel_pages() {
		if( ! $current_screen_id = $this->get_current_screen_id() ) {
			// no screen id
			return;
		}

		if(
			$current_screen_id == 'toolset_page_wpcf-cpt'                // Post Types
			|| $current_screen_id == 'toolset_page_wpcf-ctt'                // Taxonomies
			|| $current_screen_id == 'toolset_page_wpcf-cf'                 // Post Fields
			|| $current_screen_id == 'toolset_page_wpcf-termmeta-listing'   // Term Fields
			|| $current_screen_id == 'toolset_page_wpcf-um'                 // User Fields
			|| $current_screen_id == 'toolset_page_types_access'            // Access Control
			|| $current_screen_id == 'toolset_page_views'                   // Views
			|| $current_screen_id == 'toolset_page_view-templates'          // Content Templates
			|| $current_screen_id == 'toolset_page_view-archives'           // WordPress Archives
			|| $current_screen_id == 'toolset_page_CRED_Forms'              // Post Forms
			|| $current_screen_id == 'toolset_page_CRED_User_Forms'         // User Forms
			|| $current_screen_id == 'toolset_page_dd_layouts'              // Layouts
			|| $current_screen_id == 'toolset_page_dd_layout_CSS_JS'        // Layouts CSS JS
			|| $current_screen_id == 'toplevel_page_toolset-dashboard'      // Dashboard
			|| $current_screen_id == 'toolset_page_types-custom-fields'     // m2m Custom Fields
			|| $current_screen_id == 'toolset_page_types-relationships'     // m2m Relationships
			// || $current_screen_id == 'toolset_page_toolset-settings'        // Toolset Settings
			// || $current_screen_id == 'toolset_page_toolset-export-import'   // Toolset Settings

		) {
			$this->notices_compilation_introduction();
			$this->notice_wpml_version_doesnt_support_m2m();
		}
	}

	/**
	 * Notices for the themes screen
	 */
	protected function screen_wordpress_themes() {
		if( $this->get_current_screen_id() != 'themes' ) {
			return;
		}

		$this->notices_compilation_introduction();
	}

	/**
	 * Notices for the plugins screen
	 */
	protected function screen_wordpress_plugins() {
		if( $this->get_current_screen_id() != 'plugins' ) {
			return;
		}

		if( $this->is_tbt_active ) {
			// active Toolset Based Theme
			$notice = new Toolset_Admin_Notice_Dismissible( 'tbt-active-dashboard' );
			$notice->set_content( $this->tpl_path . '/toolset-based-themes/active/plugin.phtml' );
			Toolset_Admin_Notices_Manager::add_notice( $notice );
			return;
		}

		if( $this->is_tbt_inactive ) {
			// inactive Toolset Based Theme
			$notice = new Toolset_Admin_Notice_Dismissible( 'tbt-inactive-dashboard' );
			$notice->set_content( $this->tpl_path . '/toolset-based-themes/inactive/plugin.phtml' );
			Toolset_Admin_Notices_Manager::add_notice( $notice );
			return;
		}

		$this->notices_compilation_introduction();
	}

	/**
	 * Notices for the Toolset Dashboard Page
	 */
	protected function screen_wordpress_dashboard() {
		if( $this->get_current_screen_id() != 'dashboard' ) {
			return;
		}

		$this->notices_compilation_introduction();
		$this->notice_wpml_version_doesnt_support_m2m();
	}

	/**
	 * Notices for the Toolset Dashboard Page
	 */
	protected function screen_toolset_dashboard() {
		if( $this->get_current_screen_id() != 'toplevel_page_toolset-dashboard' ) {
			return;
		}

		if( $this->is_tbt_active ) {
			// active Toolset Based Theme
			$notice = new Toolset_Admin_Notice_Dismissible( 'tbt-active-dashboard' );
			$notice->set_content( $this->tpl_path . '/toolset-based-themes/active/dashboard.phtml' );
			Toolset_Admin_Notices_Manager::add_notice( $notice );
			return;
		}

		if( $this->is_tbt_inactive ) {
			// inactive Toolset Based Theme
			$notice = new Toolset_Admin_Notice_Dismissible( 'tbt-inactive-dashboard' );
			$notice->set_content( $this->tpl_path . '/toolset-based-themes/inactive/dashboard.phtml' );
			Toolset_Admin_Notices_Manager::add_notice( $notice );
			return;
		}

		$this->notice_wpml_version_doesnt_support_m2m();

		// no Toolset Based Theme
		$this->notices_compilation_introduction();
	}

	/**
	 * These are our "Toolset Introduction Messages"
	 * for further information see toolsetcommon-136
	 */
	protected function notices_compilation_introduction() {
		if( $this->only_types_active() ) {
			// notice: theme has native layout support
			$this->notice_theme_works_best_with_toolset();

			return;
		}

		// commercial plugin active + theme we have an integration plugin for
		$this->integration_run_installer();
	}

	/**
	 * True if only types is active
	 * False if one commercial toolset plugin is active
	 *
	 * @return bool
	 */
	protected function only_types_active() {
		if( $this->is_views_active || $this->is_layouts_active || $this->is_access_active || $this->is_cred_active ) {
			return false;
		}

		return true;
	}

	/**
	 * @return Toolset_Admin_Notice_Dismissible
	 */
	protected function notice_theme_works_best_with_toolset() {
		$notice = new Toolset_Admin_Notice_Dismissible( 'theme-works-best-with-toolset' );
		$notice->set_content( $this->tpl_path . '/only-types-installed/layouts-support-native.phtml' );
		$notice->add_condition( new Toolset_Condition_Theme_Layouts_Support_Native_Available() );
		Toolset_Admin_Notices_Manager::add_notice( $notice );
	}

	/**
	 *
	 * Display a Toolset_Admin_Notice_Undismissible notice if the user has not registered this site.
	 *
	 * @return Toolset_Admin_Notice_Undismissible
	 *
	 */
	protected function commercial_plugin_installed_but_not_registered() {
		$notice = new Toolset_Admin_Notice_Undismissible( 'commercial-plugin-installed-not-registered', '', $this->constants );
		$notice->set_content( $this->tpl_path . '/commercial-plugin-installed/commercial-plugin-installed-but-not-registered.phtml' );
		Toolset_Admin_Notices_Manager::add_notice( $notice );

		return $notice;
	}



	/**
	 * @return Toolset_Admin_Notice_Dismissible
	 */
	protected function integration_run_installer() {
		$is_integration_plugin_active = new Toolset_Condition_Theme_Layouts_Support_Plugin_Active();

		if( ! $is_integration_plugin_active->is_met() ) {
			// no theme itegration plugin active
			return;
		}

		$theme_slug = sanitize_title( $is_integration_plugin_active->get_supported_theme_name() );

		$notice = new Toolset_Admin_Notice_Dismissible( 'integration-run-installer-for-' . $theme_slug );
		$notice->set_content( $this->tpl_path . '/installer/integration-run-installer.phtml' );
		$notice->add_condition( new Toolset_Condition_Plugin_Layouts_No_Items() );
		$notice->add_condition( new Toolset_Condition_Theme_Layouts_Support_Theme_Active() );
		Toolset_Admin_Notices_Manager::add_notice( $notice );

		return $notice;
	}

	/**
	 * @return Toolset_Admin_Notice_Dismissible
	 */
	protected function plugin_encrypted_no_valid_theme() {
		$notice = new Toolset_Admin_Notice_Dismissible( 'plugin-encrypted-no-valid-theme' );
		$notice->set_content( $this->tpl_path . '/toolset-based-themes/plugin-encrypted-no-valid-theme.phtml' );
		$notice->add_condition( new Toolset_Condition_Plugin_Encrypted_No_Valid_Theme() );
		Toolset_Admin_Notices_Manager::add_notice( $notice );

		return $notice;
	}

	/**
	 * Check if the current site is a development environment site or not.
	 *
	 * @return bool It return true if the site is a development environment, false in any other case.
	 */
	protected function is_development_environment() {
		$popular_tlds = array(
			'com',
			'org',
			'net',
			'edu',
			'de',
			'es',
			'fr',
			'se',
			'it',
			'uk',
			'jp',
			'pl',
			'hu',
			'cz',
			'dk',
			'nl',
			'au',
			'il',
			'co',
			'cl',
			'ar',
			'br',
			'mx',
			'ie',
			'io',
			'gr',
		);

		$stop_words = array(
			'staging',
			'dev',
			'develop',
			'local',
			'localhost',
		);

		$home_url = get_home_url();

		$broken_down_home_url = explode( ".", parse_url( $home_url, PHP_URL_HOST ) );

		foreach ( $stop_words as $stop_word ) {
			if ( preg_grep ( '/' . $stop_word . '/', $broken_down_home_url ) ) {
				return true;
			}
		}

		$tld = end( $broken_down_home_url );

		if ( ! in_array( $tld, $popular_tlds ) ) {
			return true;
		}

		return false;
	}


	protected function notice_wpml_version_doesnt_support_m2m() {
		$notice = new Toolset_Admin_Notice_Required_Action(
				'toolset-wpml-version-doesnt-support-m2m',
				sprintf(
					__( 'Post relationships in Toolset require WPML %s or newer to work properly with post translations. Please upgrade WPML.', 'wpcf' ),
					sanitize_text_field( Toolset_Relationship_Controller::MINIMAL_WPML_VERSION )
				)
		);
		$notice->add_condition( new Toolset_Condition_Plugin_Wpml_Doesnt_Support_M2m() );
		Toolset_Admin_Notices_Manager::add_notice( $notice );
	}

}
