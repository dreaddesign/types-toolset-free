<?php

/**
 * Main controller for Toolset Common tasks in the backend.
 *
 * This class has to be loaded only after the autoloader is initialized.
 *
 * @since 2.5.7
 */
class Toolset_Admin_Controller {


	public function initialize() {
		$this->load_whip();
		$this->init_page_extensions();
	}


	/**
	 * Show a customized WHIP notice for PHP 5.2 users.
	 */
	private function load_whip() {
		if ( 'index.php' !== $GLOBALS['pagenow'] && current_user_can( 'manage_options' ) ) {
			return;
		}

		require_once TOOLSET_COMMON_PATH . '/lib/whip/src/facades/wordpress.php';

		add_filter( 'whip_hosting_page_url_wordpress', '__return_true' );
		whip_wp_check_versions( array( 'php' => '>=5.3' ) );
	}


	private function init_page_extensions() {

	}

}