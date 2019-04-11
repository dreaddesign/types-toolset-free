<?php

/**
 * Filter the potential posts association query by the author of the option.
 *
 * Each Toolset individual plugin can extend this filter to add its own API filters, using the filter_by_plugin method.
 *
 * @since m2m
 */
class Toolset_Potential_Association_Query_Filter_Posts_Author
	implements Toolset_Potential_Association_Query_Filter_Interface {
	
	/**
	 * Maybe filter the list of available posts to connect to a given post by their post author.
	 *
	 * Free method for individual Toolset plugins to subclass and implement.
	 *
	 * @param mixed $force_author The original value is a boolean but it might be filtered to become an integer or a string.
	 *
	 * @return mixed
	 *
	 * @since m2m
	 */
	protected function filter_by_plugin( $force_author ) {
		return $force_author;
	}
	
	/**
	 * Maybe filter the list of available posts to connect to a given post by their post author.
	 *
	 * Decides whether a filter by post author needs to be set by cascading a series of filters:
	 * - toolset_force_author_in_related_post
	 *
	 * Those filters should return either a post author ID or the keyword '$current', which is a placeholder
	 * for the currently logged in user; in case no user is logged in, we force empty query results.
	 *
	 * Note that individual Toolset plugins can include their own filters by subclassing this one
	 * and including just a filter_by_plugin method containing their API filters chain.
	 *
	 * @param array $query_arguments The potential association query arguments.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function filter( array $query_arguments ) {
		$force_author = false;
		
		$force_author = $this->filter_by_plugin( $force_author );
		
		/**
		 * Force a post author on all Toolset interfaces to set a related post.
		 *
		 * @since m2m
		 */
		$force_author = apply_filters(
			'toolset_force_author_in_related_post',
			$force_author
		);
		
		if ( false === $force_author ) {
			return $query_arguments;
		}
		
		if ( '$current' === $force_author ) {
			$force_author = get_current_user_id();
		}
		$force_author = (int) $force_author;
		
		if ( ! array_key_exists( 'wp_query_override', $query_arguments ) ) {
			$query_arguments['wp_query_override'] = array();
		}
		
		// Setting an author ID with zero value means offer no options
		// (also happens when setting $current and not logged in user exists).
		if ( 0 === $force_author ) {
			$query_arguments['wp_query_override']['post__in'] = array( '0' );
		} else {
			$query_arguments['wp_query_override']['author'] = $force_author;
		}

		return $query_arguments;
	}
	
}