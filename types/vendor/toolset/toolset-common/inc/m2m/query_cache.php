<?php

/**
 * Skeleton of cache for relationship queries.
 *
 * So far it does nothing but it's already being called by Toolset_Relationship_Query.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Cache {

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
	 * Get a cached value.
	 *
	 * @param array $query Query arguments for Toolset_Relationship_Query
	 * @param string $subject Name of the subject (what cache to access).
	 *
	 * @return array|false An array of cached results for given arguments or false if not available.
	 */
	public function get( $query, $subject ) {
		return false;
	}


	/**
	 * Add a value to the cache.
	 *
	 * @param array $query Query arguments for Toolset_Relationship_Query.
	 * @param string $subject Name of the subject (what cache to access).
	 * @param array $results Results to be cached.
	 */
	public function set( $query, $subject, $results ) {
		// Do nothing
	}

}