<?php

/**
 * Adjusts the WP_Query functionality.
 *
 * Covers the need for querying by post relationship by adding a new query argument 'toolset_relationships'.
 *
 * Usage:
 *
 * $query = new WP_Query( array(
 *     ...,
 *     'toolset_relationships' => array(
 *         // ... relationship args
 *     )
 * );
 *
 * The relationship arguments can contain one or multiple conditions. Each condition is represented by
 * a nested associative array, but if there is only a single condition, it doesn't have to be nested.
 *
 * If multiple conditions are used, they're handled as conjunctions (AND).
 *
 * Each condition array has these elements:
 *
 * - 'role': 'parent'|'child'|'intermediary' - role of the queried post (the one that will be included in the results).
 *     Optional, default is 'child'.
 * - 'related_to': int|WP_Post - post to which the results are connected. Mandatory.
 * - 'relationship': string|string[] - relationship identified either by its slug or by a pair of the parent and child post types.
 *     The array variant can be used only for legacy relationships (or those migrated from the legacy implementation).
 *     The relationship slug variant will obviously work only if m2m is already enabled. Mandatory.
 * - 'role_to_query_by': 'parent'|'child'|'intermediary'|'other'. Role of the 'related_to' post in the relationship.
 *     'other' means the opposite of 'role' and can be used only if 'role' is 'parent' or 'child'.
 *     Optional, default is 'other'.
 *
 * Examples:
 *
 * // Single condition
 * $query = new WP_Query( array(
 *     ...,
 *     'toolset_relationships' => array(
 *         'role' => 'child',
 *         'related_to' => $parent_post,
 *         'relationship' => array( 'parent_type', 'post_type' )
 *     )
 * );
 *
 * // Multiple conditions
 * //
 * // Query posts that are children of $parent_post in the relationship between 'parent_type' and 'post_type'
 * // migrated from the legacy implementation and at the same time parents of $child_post
 * // in the relationship 'another_relationship' (because we used the slug, this will work only if
 * // m2m is enabled).
 * //
 * $query = new WP_Query( array(
 *     ...,
 *     'toolset_relationships' => array(
 *         array(
 *             'role' => 'child',
 *             'related_to' => $parent_post,
 *             'relationship' => array( 'parent_type', 'post_type' )
 *         ),
 *         array(
 *             'role' => 'parent',
 *             'related_to' => $child_post,
 *             'relationship' => 'another_relationship'
 *
 *     )
 * );
 *
 * @since 2.6.1
 */
abstract class Toolset_Wp_Query_Adjustments extends Toolset_Wpdb_User {


	// Must not be changed, third-party software depends on it.
	const RELATIONSHIP_QUERY_ARG = 'toolset_relationships';


	// The time when we store the toolset_relationships query argument during pre_get_posts.
	// It needs to happen late so that other query modifications (also meta_query ones) can happen before.
	const TIME_TO_STORE_RELATIONSHIPS_ARG = 10000;


	/**
	 * Initialize the query adjustments.
	 */
	public function initialize() {
		add_action( 'pre_get_posts', array( $this, 'check_custom_query_args' ), self::TIME_TO_STORE_RELATIONSHIPS_ARG );
	}


	/**
	 * Using the approach described here: http://www.danielauener.com/extend-wp_query-with-custom-parameters/
	 *
	 * @param $query
	 */
	public function check_custom_query_args( $query ) {
		if( ! $query instanceof WP_Query ) {
			// Something weird is happening.
			return;
		}

		if( array_key_exists( self::RELATIONSHIP_QUERY_ARG, $query->query_vars ) ) {
			$query->{self::RELATIONSHIP_QUERY_ARG} = $query->query_vars[ self::RELATIONSHIP_QUERY_ARG ];
		}
	}


	/**
	 * Get the table join manager object attached to the WP_Query instance or create and attach a new one.
	 *
	 * @param WP_Query $query
	 *
	 * @return Toolset_Wp_Query_Adjustments_Table_Join_Manager
	 */
	protected function get_table_join_manager( WP_Query $query ) {
		// This is a dirty hack but still cleanest considering we need to use this object
		// in different callbacks from WP_Query.
		$property_name = 'toolset_join_manager';
		if( ! property_exists( $query, $property_name ) ) {
			$query->{$property_name} = new Toolset_Wp_Query_Adjustments_Table_Join_Manager();
		}
		return $query->{$property_name};
	}


	/**
	 * Turn the relationship query argument into an array of conditions.
	 *
	 * @param array $query_args
	 *
	 * @return array
	 * @throws InvalidArgumentException
	 */
	protected function normalize_relationship_query_args( $query_args ) {
		$has_strings = $this->array_has_strings( $query_args );
		$has_arrays = $this->array_has_arrays( $query_args );

		if( $has_arrays && $has_strings ) {
			// Can't have both.
			throw new InvalidArgumentException( 'The toolset_relationship query argument contains mixed content (arrays and string values).' );
		}

		if( $has_strings ) {
			return array( $query_args );
		}

		return $query_args;
	}


	private function array_has_strings( $array ) {
		foreach( $array as $element ) {
			if( is_string( $element ) ) {
				return true;
			}
		}

		return false;
	}


	private function array_has_arrays( $array ) {
		foreach( $array as $key => $element ) {
			// The "relationship" element will not be considered as a nested condition.
			// If there is something weird going on, it will fail later on during the validation
			// of individual parameters.
			if( is_array( $element ) && 'relationship' !== $key ) {
				return true;
			}
		}

		return false;

	}

}