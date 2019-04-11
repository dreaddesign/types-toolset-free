<?php

namespace OTGS\Toolset\Common\MaintenanceMode;

/**
 * Control (enable or disable) the WordPress maintenance mode.
 *
 * @package OTGS\Toolset\Common\MaintenanceMode
 * @since 2.6.8
 */
class Controller {


	private function get_maintenance_file_path() {
		return ABSPATH . '/.maintenance';
	}


	/**
	 * Note that an existing file doesn't mean the maintenance mode is active - it can also be expired.
	 *
	 * @return bool
	 */
	public function maintenance_file_exists() {
		return file_exists( $this->get_maintenance_file_path() );
	}


	/**
	 * Create the content of the .maintenance file.
	 *
	 * @param bool $allow_backend Allow access to backend - wp-admin/ and wp-login.php, but not AJAX calls.
	 * @param bool $allow_ajax  Allow AJAX calls.
	 * @param bool $for_infinity If true, the maintenance mode will never expire. Otherwise, it will expire
	 *     based on WordPress core logic (10min).
	 *
	 * @return bool|string Content of the .maintenance file or false on failure.
	 */
	private function build_maintenance_file( $allow_backend = true, $allow_ajax = false, $for_infinity = false ) {
		$template = \Toolset_Output_Template_Repository::get_instance()->get( \Toolset_Output_Template_Repository::MAINTENANCE_FILE );

		$context = array(
			'allow_backend' => $allow_backend,
			'allow_ajax' => $allow_ajax,
			'upgrading_time' => ( $for_infinity ? 'time()' : time() )
		);

		try {
			$output = \Toolset_Renderer::get_instance()->render( $template, $context, false );
		} catch ( \Exception $e ) {
			return false;
		}

		return $output;
	}


	/**
	 * Enable the maintenance mode.
	 *
	 * Note: If it's already active, the method will fail.
	 *
	 * @param bool $allow_backend Allow access to backend - wp-admin/ and wp-login.php, but not AJAX calls.
	 * @param bool $allow_ajax  Allow AJAX calls.
	 * @param bool $for_infinity If true, the maintenance mode will never expire. Otherwise, it will expire
	 *     based on WordPress core logic (10min).
	 *
	 * @return \Toolset_Result
	 */
	public function enable( $allow_backend = true, $allow_ajax = false, $for_infinity = false ) {
		if( $this->maintenance_file_exists() ) {
			return new \Toolset_Result( false, __( 'Maintenance mode is already active (.maintenance file present).', 'wpcf' ) );
		}

		$content = $this->build_maintenance_file( $allow_backend, $allow_ajax, $for_infinity );

		if( false === $content ) {
			return new \Toolset_Result( false, __( 'Unable to process the template for the ".maintenance" file.', 'wpcf' ) );
		}

		$result = file_put_contents( $this->get_maintenance_file_path(), $content );

		if( false === $result ) {
			return new \Toolset_Result( false, __( 'Couldn\'t write to the ".maintenance" file.', 'wpcf' ) );
		}

		return new \Toolset_Result( true, __( 'Maintenance mode enabled.', 'wpcf' ) );
	}


	/**
	 * Disable the maintenance mode.
	 *
	 * Note: If it's not enabled, the method will fail.
	 *
	 * @return \Toolset_Result
	 */
	public function disable() {
		if( ! $this->maintenance_file_exists() ) {
			return new \Toolset_Result( false, __( 'Maintenance mode is already disabled.', 'wpcf' ) );
		}

		$result = unlink( $this->get_maintenance_file_path() );

		if( false === $result ) {
			return new \Toolset_Result( false, __( 'Unable to delete the ".maintenance" file.', 'wpcf' ) );
		}

		return new \Toolset_Result( true, __( 'Maintenance mode disabled.', 'wpcf' ) );
	}

}