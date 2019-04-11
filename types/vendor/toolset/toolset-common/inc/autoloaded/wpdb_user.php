<?php

/**
 * Class that uses $wpdb, can be extended to avoid repetition of the dependency injection.
 *
 * @since 2.5.10
 */
abstract class Toolset_Wpdb_User {


	/** @var wpdb */
	protected $wpdb;


	/**
	 * Toolset_Wpdb_User constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 */
	public function __construct( wpdb $wpdb_di = null ) {
		global $wpdb;

		if( null === $wpdb_di ) {
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}
	}

}