<?php

/**
 * Adjust the WP_Query functionality for legacy post relationships.
 *
 * This assumes m2m is *not* enabled.
 *
 * See the superclass for details.
 *
 * @since 2.6.1
 */
class Toolset_Wp_Query_Adjustments_Legacy_Relationships extends Toolset_Wp_Query_Adjustments {

	/**
	 * Call to register required hook.
	 */
	public function initialize() {
		add_action( 'pre_get_posts', array( $this, 'convert_toolset_relationships_argument_to_meta_query' ) );
	}

	/**
	 * Will convert 'toolset_relationship' argument to 'meta_query' of WP_Query
	 *
	 * @action pre_get_posts
	 *
	 * @param $query
	 */
	public function convert_toolset_relationships_argument_to_meta_query( $query ) {
		if( ! $query instanceof WP_Query ) {
			// Something weird is happening.
			return;
		}

		if( ! array_key_exists( self::RELATIONSHIP_QUERY_ARG, $query->query_vars ) ) {
			// 'toolset_relationship' argument is not used
			return;
		}

		// get meta_query
		$meta_query = $this->get_meta_query_by_toolset_argument( $query->query_vars[ self::RELATIONSHIP_QUERY_ARG ] );

		if( ! empty( $meta_query ) ) {
			if( ! isset( $query->query_vars['meta_query'] ) ) {
				// no meta_query yet, open collection
				$query->query_vars['meta_query'] = array();
			}
			// add our converted 'toolset_relationship' argument to the WP_Query
			$query->query_vars['meta_query'][] = $meta_query;
		};

		// important to unset our argument, otherwise each get_posts call will append the same conditions again
		unset( $query->query_vars[ self::RELATIONSHIP_QUERY_ARG ] );
	}

	/**
	 * Get all conditions of 'toolset_relationships' and store them to $this->meta_query_conditions.
	 *
	 * @param $toolset_relationships The 'toolset_relationships' argument
	 *
	 * @return array
	 */
	private function get_meta_query_by_toolset_argument( $toolset_relationships ) {
		if( ! is_array( reset( $toolset_relationships ) ) ) {
			// normalise array
			$toolset_relationships = array( $toolset_relationships );
		}

		$meta_query = array();

		foreach( $toolset_relationships as $condition ) {
			if( ! $condition = $this->valid_query_array( $condition ) ){
				// slug of relationships is not supported by legacy
				continue;
			}

			// normalise related_to value to be an integer
			$related_to = $condition['related_to'] instanceof WP_Post
				? (int) $condition['related_to']->ID
				: (int) $condition['related_to'];

			$meta_query[] = array(
				'key' => '_wpcf_belongs_'.reset( $condition['relationship'] ).'_id',
				'value' => $related_to,
				'compare' => '=',
			);
		}

		if( ! empty( $meta_query ) ) {
			$meta_query['relation'] = 'AND';
		}

		return $meta_query;
	}

	/**
	 * Validation of a condition array. For legacy we have a more strict validation
	 * than for m2m as a bunch of features is not available for legacy relationships
	 *
	 * @param $condition
	 *
	 * @return bool
	 */
	private function valid_query_array( $condition ) {
		if( ! isset( $condition['related_to'] ) ) {
			// required
			throw new InvalidArgumentException( "WP_Query > 'toolset_relationships' requires 'related_to' key." );
		}

		if( ( ! is_numeric( $condition['related_to'] ) || strpos( $condition['related_to'], '.' ) !== false )
		    && ! $condition['related_to'] instanceof WP_Post ) // no WP_Post
			{
			// no integer NOR object of WP_Post
			throw new InvalidArgumentException( "WP_Query > 'toolset_relationships' > 'related_to' must be a post_ID or a WP_Post object." );
		}

		if( ! isset( $condition['relationship'] ) // not set at all
		    || ! is_array( $condition[ 'relationship' ] ) // no array, which is not possible for legacy relationship
		    || ! is_string( reset( $condition[ 'relationship' ] ) )  // first array element is no string -> not valid
		) {
			throw new InvalidArgumentException( "WP_Query > 'toolset_relationships' > 'relationship' must be an array( 'parent_slug', 'child_slug' )." );
		}

		if( ! isset( $condition['role'] ) && ! isset( $condition['role_to_query_by'] ) ) {
			// default value
			$condition['role'] = 'child';
		}

		if( isset( $condition['role'] ) && $condition['role'] != 'child' ) {
			// only child role is possible for legacy
			throw new InvalidArgumentException( "WP_Query > 'toolset_relationships' > 'role' invalid value. You can only use 'child' for legacy relationships." );
		}

		if( isset( $condition['role_to_query_by'] ) && ( $condition['role_to_query_by'] == 'child' || $condition['role_to_query_by'] == 'intermediary' ) ) {
			// not possible to query by child or intermediary
			throw new InvalidArgumentException( "WP_Query > 'toolset_relationships' > 'role_to_query_by' invalid value. You can only use 'parent' for legacy relationships." );
		}

		// all fine
		return $condition;
	}
}