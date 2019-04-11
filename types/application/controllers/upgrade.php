<?php

/**
 * Plugin upgrade controller.
 *
 * Compares current plugin version with a version number stored in the database, and performs upgrade routines if
 * necessary.
 *
 * Note: Filters to add upgrade routines are not provided on purpose, so all routines need to be defined here.
 * 
 * It works with version numbers, which are easier to compare and manipulate with. See convert_version_string_to_number()
 * for details.
 *
 * @since 2.1
 */
class Types_Upgrade {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	private function __construct() { }


	private function __clone() { }


	public static function initialize() {
		$instance = self::get_instance();
		$instance->check_upgrade();
	}


	// Legacy option names used to store version string.
	const TYPES_DATABASE_VERSION_OPTION_LEGACY1 = 'WPCF_VERSION';
	const TYPES_DATABASE_VERSION_OPTION_LEGACY2 = 'wpcf-version';

	/** Name of the option used to store version number. */
	const TYPES_DATABASE_VERSION_OPTION = 'types_database_version';


	/**
	 * Check if an upgrade is needed, and if yes, perform it.
	 *
	 * @since 2.1
	 */
	public function check_upgrade() {

		if( $this->is_upgrade_needed() ) {
			$this->do_upgrade();
		}

	}


	/**
	 * Returns true if an upgrade is needed.
	 *
	 * @return bool
	 * @since 2.1
	 */
	private function is_upgrade_needed() {
		return ( $this->get_database_version() < $this->get_plugin_version() );
	}


	/**
	 * Get current plugin version number.
	 * 
	 * @return int
	 * @since 2.1
	 */
	private function get_plugin_version() {
		return $this->convert_version_string_to_number( TYPES_VERSION );
	}


	/**
	 * Get number of the version stored in the database.
	 * 
	 * @return int
	 * @since 2.1
	 */
	private function get_database_version() {
		$version = (int) get_option( self::TYPES_DATABASE_VERSION_OPTION, 0 );

		if( 0 === $version ) {
			$version = get_option( self::TYPES_DATABASE_VERSION_OPTION_LEGACY1, 0 );

			if( 0 === $version ) {
				$version = get_option( self::TYPES_DATABASE_VERSION_OPTION_LEGACY2, 0 );
			}

			$version = $this->convert_version_string_to_number( $version );
		}

		return $version;
	}


	/**
	 * Transform a version string to a version number.
	 * 
	 * The version string looks like this: "major.minor[.maintenance[.revision]]". We expect that all parts have
	 * two digits at most.
	 * 
	 * Conversion to version number is done like this:
	 * $ver_num  = MAJOR      * 1000000
	 *           + MINOR        * 10000
	 *           + MAINTENANCE    * 100
	 *           + REVISION         * 1
	 *
	 * That means, for example "1.8.11.12" will be equal to:
	 *                          1000000
	 *                        +   80000
	 *                        +    1100
	 *                        +      12
	 *                        ---------
	 *                        = 1081112
     *
	 * @param string $version_string
	 * @return int
	 * @since 2.1
	 */
	private function convert_version_string_to_number( $version_string ) {

		if( 0 === $version_string ) {
			return 0;
		}
		
		$version_parts = explode( '.', $version_string );
		$multipliers = array( 1000000, 10000, 100, 1 );

		$version_part_count = count( $version_parts );
		$version = 0;
		for( $i = 0; $i < $version_part_count; ++$i ) {
			$version_part = (int) $version_parts[ $i ];
			$multiplier = $multipliers[ $i ];

			$version += $version_part * $multiplier;
		}

		return $version;
	}


	/**
	 * Update the version number stored in the database.
	 * 
	 * @param int $version_number
	 * @since 2.1 
	 */
	private function update_database_version( $version_number ) {
		if( is_numeric( $version_number ) ) {
			update_option( self::TYPES_DATABASE_VERSION_OPTION, (int) $version_number );
		}
	}


	/**
	 * Get an array of upgrade routines.
	 * 
	 * Each routine is defined as an associative array with two elements:
	 *     - 'version': int, which specifies the *target* version after the upgrade
	 *     - 'callback': callable
	 * 
	 * @return array
	 * @since 2.1
	 */
	private function get_upgrade_routines() {
		
		$upgrade_routines = array(
			array(
				'version' => 2010000,
				'callback' => array( $this, 'upgrade_db_to_2010000' )
			),
			array(
				'version' => 2021600,
				'callback' => array( $this, 'upgrade_db_to_2021600' )
			)
		);
		
		return $upgrade_routines;
	}


	/**
	 * Perform the upgrade by calling the appropriate upgrade routines and updating the version number in the database.
	 *
	 * @since  2.1 
	 */
	private function do_upgrade() {
		
		$from_version = $this->get_database_version();
		$upgrade_routines = $this->get_upgrade_routines();
		$target_version = $this->get_plugin_version();

		// Sort upgrade routines by their version.
		$routines_by_version = array();
		foreach( $upgrade_routines as $key => $row ) {
			$routines_by_version[ $key ] = $row['version'];
		}
		array_multisort( $routines_by_version, SORT_DESC, $upgrade_routines );

		// Run all the routines necessary
		foreach( $upgrade_routines as $routine ) {
			$upgrade_version = (int) wpcf_getarr( $routine, 'version' );
			
			if( $from_version < $upgrade_version && $upgrade_version <= $target_version ) {
				$callback = wpcf_getarr( $routine, 'callback' );
				if( is_callable( $callback ) ) {
					call_user_func( $callback );
				}
				$this->update_database_version( $upgrade_version );
			}
		}

		// Finally, update to current plugin version even if there are no other routines to run, so that
		// this method is not called every time by check_upgrade().
		$this->update_database_version( $target_version );
	}


	/**
	 * Upgrade database to 2010000 (Types 2.1) 
	 *
	 * Batch fix types-768 for all non-superadmin users.
	 */
	function upgrade_db_to_2010000() {

		$roles_manager = WPCF_Roles::getInstance();

		global $wpdb;

		// Will find users without the administrator roles but with one of the Types management roles.
		// A sign of the types-768 bug.
		$user_query = new WP_User_Query(
			array(
				'meta_query' => array(
					'relation' => 'AND',
					array(
						'key' => $wpdb->prefix . 'capabilities',
						'value' => '"administrator"',
						'compare' => 'NOT LIKE',
					),
					array(
						'key' => $wpdb->prefix . 'capabilities',
						'value' => '"wpcf_custom_post_type_view"',
						'compare' => 'LIKE',
					),
				)
			)
		);

		$users = $user_query->get_results();

		foreach( $users as $user ) {
			$roles_manager->clean_the_mess_in_nonadmin_user_caps( $user );
		}
	}
	
	/**
	 * Upgrade database to 2021600 (Types 2.2.16) 
	 *
	 * Fix types-1142 for non admins with an 'admin' username.
	 */
	function upgrade_db_to_2021600() {
		
		$roles_manager = WPCF_Roles::getInstance();
		$roles_manager->clean_the_mess_in_nonadmin_user_caps( 'admin' );
	}

}