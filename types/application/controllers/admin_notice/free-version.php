<?php

/**
 * Class Types_Admin_Notices_Free_Version
 *
 * Controls all admin notices which are only relevant for the free version of Types
 *
 * @since 2.3
 */
class Types_Admin_Notices_Free_Version extends Toolset_Controller_Admin_Notices {

	const NOTICE_TYPES_3_0 = 'types-3-0-features';
	const NOTICE_TYPES_3_1 = 'types-3-1-features';

	/**
	 * Types_Admin_Notices_Free_Version constructor.
	 * (same as parent, but as we use $this to call the action it's important to overwrite it)
	 *
	 * @param Toolset_Constants|null $constants
	 */
	public function __construct( Toolset_Constants $constants = null ) {
		if ( null === $constants ) {
			$constants = new Toolset_Constants();
		}
		$this->constants = $constants;

		add_action( 'init', array( $this, 'init' ), 1000 );
	}

	/**
	 * Init notices by screen
	 */
	public function init_screens() {
		if( ! function_exists( 'get_current_screen' ) ) {
			// loaded to early
			return;
		}

		$this->current_screen = get_current_screen();

		$this->screen_wordpress_dashboard();
	}

	/**
	 * Notices for the Wordpress Dashboard Page
	 */
	protected function screen_wordpress_dashboard() {
		if( $this->get_current_screen_id() != 'dashboard' ) {
			return;
		}

		$this->new_features_of_paid_types();
	}

	/**
	 * New feature of paid types
	 * Will only show notices if only types is active
	 */
	private function new_features_of_paid_types() {
		if( ! $this->only_types_active() ) {
			// not only types active
			return;
		}

		// new features of paid types (here should only be the newest version,
		// otherwise new clients or old on new installations see all release notices)
		$this->notice_types_release_3_0();
	}

	/**
	 * Notice about Types 3.0 features
	 *
	 * @return bool|Toolset_Admin_Notice_Dismissible
	 */
	private function notice_types_release_3_0() {
		$notice = new Toolset_Admin_Notice_Dismissible( self::NOTICE_TYPES_3_0, '', $this->constants );
		$notice->set_similar_notices_key( Toolset_Admin_Notices_Manager::SIMILAR_NOTICES_FREE_PLUGIN_SHOWS_PAID_FEATURES );
		$notice->set_content( TYPES_ABSPATH . '/application/views/admin-notices/free-version/types-3-0.phtml' );
		Toolset_Admin_Notices_Manager::add_notice( $notice );

		return $notice;
	}

	/**
	 * Notice about Types 3.1 features
	 *
	 * NOT USED YET - ADDED FOR TESTING AND KEPT IT FOR NEXT RELEASE
	 *
	 * @return bool|Toolset_Admin_Notice_Dismissible
	 */
	private function notice_types_release_3_1() {
		$notice = new Toolset_Admin_Notice_Dismissible( self::NOTICE_TYPES_3_1, '', $this->constants );
		$notice->set_similar_notices_key( Toolset_Admin_Notices_Manager::SIMILAR_NOTICES_FREE_PLUGIN_SHOWS_PAID_FEATURES );
		$notice->set_content( TYPES_ABSPATH . '/application/views/admin-notices/free-version/types-3-1.phtml' );
		Toolset_Admin_Notices_Manager::add_notice( $notice );

		return $notice;
	}
}