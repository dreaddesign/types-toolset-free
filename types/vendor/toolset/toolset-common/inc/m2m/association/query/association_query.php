<?php

/**
 * A class for querying associations and associated elements.
 *
 * Usage:
 *
 *     $query = new Toolset_Association_Query( $args );
 *     $results = $query->get_results();
 *
 * Notes:
 *
 *   - For now, it supports only the native associations (they're the only ones we have).
 *   - If you need to query by some parameters that are not supported, either create a feature request about it or
 *     submit a merge request rather than going around the query and touching the database directly.
 *
 * WARNING: This got deprecated and was turned into a complatibility layer for Toolset_Association_Query_V2.
 *
 * @since m2m
 * @deprecated Since 2.5.8. Use Toolset_Association_Query instead.
 */
class Toolset_Association_Query extends Toolset_Relationship_Query_Base {

	/** @var string One of the RETURN_* constants determining what kind of output should be provided. */
	private $return;

	/** @var bool */
	protected $dont_count_found_rows;

	const OPTION_USE_CACHED_RESULTS = 'use_cached_results';
	const OPTION_CACHE_RESULTS = 'cache_results';
	const OPTION_RETURN = 'return';
	const OPTION_DONT_COUNT_FOUND_ROWS = 'no_found_rows';
	const QUERY_OFFSET = 'offset';
	const QUERY_LIMIT = 'limit';
	const QUERY_SELECT_FIELDS = 'select_fields';
	const QUERY_RELATIONSHIP_SLUG = 'relationship_slug';
	const QUERY_INTERMEDIARY_ID = 'intermediary_id';
	const QUERY_RELATIONSHIP_ID = 'relationship_id';
	const QUERY_PARENT_ID = 'parent_id';
	const QUERY_CHILD_ID = 'child_id';
	const QUERY_PARENT_DOMAIN = 'parent_domain';
	const QUERY_PARENT_QUERY = 'parent_query';
	const QUERY_CHILD_DOMAIN = 'child_domain';
	const QUERY_CHILD_QUERY = 'child_query';
	const QUERY_LANGUAGE = 'language';
	const QUERY_HAS_TRASHED_POSTS = 'has_trashed_posts';
	const RETURN_ASSOCIATION_IDS = 'association_ids';
	const RETURN_ASSOCIATIONS = 'associations';
	const RETURN_PARENT_IDS = 'parent_ids';
	const RETURN_CHILD_IDS = 'child_ids';
	const RETURN_PARENTS = 'parents';
	const RETURN_CHILDREN = 'children';
	const LANGUAGE_ALL = 'all';
	const GROUP_CONCAT_SEPARATOR = ',';


	/**
	 * Parse query arguments, store them sanitized as options or in the $query_vars array.
	 *
	 * @param array $query
	 */
	protected function parse_query( $query ) {

		$this->use_cached_results = (bool) toolset_getarr( $query, self::OPTION_USE_CACHED_RESULTS, true );
		$this->cache_results = (bool) toolset_getarr( $query, self::OPTION_CACHE_RESULTS, true );
		$this->return = toolset_getarr( $query, self::OPTION_RETURN, self::RETURN_ASSOCIATIONS, $this->get_return_options() );
		$this->dont_count_found_rows = (bool) toolset_getarr( $query, self::OPTION_DONT_COUNT_FOUND_ROWS, false );

		// Default value of these needs to be null
		$this->parse_query_arg( $query, self::QUERY_RELATIONSHIP_SLUG, 'strval' );
		$this->parse_query_arg( $query, self::QUERY_RELATIONSHIP_ID, 'absint' );
		$this->parse_query_arg( $query, self::QUERY_PARENT_ID, 'absint' );
		$this->parse_query_arg( $query, self::QUERY_CHILD_ID, 'absint' );
		$this->parse_query_arg( $query, self::QUERY_INTERMEDIARY_ID, 'absint' );
		$this->parse_query_arg( $query, self::QUERY_LIMIT, 'absint' );
		$this->parse_query_arg( $query, self::QUERY_OFFSET, 'absint' );
		$this->parse_query_arg( $query, self::QUERY_SELECT_FIELDS, null, array() );
		$this->parse_query_arg( $query, self::QUERY_PARENT_DOMAIN, null, null, array( Toolset_Field_Utils::DOMAIN_POSTS ) );
		$this->parse_query_arg( $query, self::QUERY_PARENT_QUERY, null );
		$this->parse_query_arg( $query, self::QUERY_CHILD_DOMAIN, null, null, array( Toolset_Field_Utils::DOMAIN_POSTS ) );
		$this->parse_query_arg( $query, self::QUERY_CHILD_QUERY, null );
		$this->parse_query_arg( $query, self::QUERY_HAS_TRASHED_POSTS, 'boolval' );
	}


	/**
	 * Perform the query and get results.
	 *
	 * Depending on query arguments, the results may be cached.
	 *
	 * @return int[]|IToolset_Element[]|IToolset_Association[] Array of results, depending on query arguments.
	 */
	public function get_results() {
		$q = new Toolset_Association_Query_V2();

		if ( $this->has_query_var( self::QUERY_RELATIONSHIP_SLUG ) ) {
			$relationship_slug = $this->get_query_var( self::QUERY_RELATIONSHIP_SLUG );
			$q->add( $q->relationship_slug( $relationship_slug ) );
		}

		if ( $this->has_query_var( self::QUERY_RELATIONSHIP_ID ) ) {
			$relationship_id = $this->get_query_var( self::QUERY_RELATIONSHIP_ID );
			$q->add( $q->relationship_id( $relationship_id ) );
		}

		if ( $this->has_query_var( self::QUERY_INTERMEDIARY_ID ) ) {
			$intermediary_id = $this->get_query_var( self::QUERY_INTERMEDIARY_ID );
			$q->add( $q->intermediary_id( $intermediary_id ) );
		}

		if ( $this->has_query_var( self::QUERY_PARENT_ID ) ) {
			$q->add( $q->parent_id( $this->get_query_var( self::QUERY_PARENT_ID ) ) );
		}

		if ( $this->has_query_var( self::QUERY_HAS_TRASHED_POSTS ) ) {
			$include_trash = $this->get_query_var( self::QUERY_HAS_TRASHED_POSTS );
			if ( $include_trash ) {
				$q->add(
					$q->do_and(
						$q->element_status( 'any', new Toolset_Relationship_Role_Parent() ),
						$q->element_status( 'any', new Toolset_Relationship_Role_Child() )
					)
				);
			}
		}

		if ( $this->has_query_var( self::QUERY_CHILD_ID ) ) {
			$q->add( $q->child_id( $this->get_query_var( self::QUERY_CHILD_ID ) ) );
		}

		if ( $this->has_query_var( self::QUERY_PARENT_DOMAIN ) ) {
			$q->add( $q->has_domain( $this->get_query_var( self::QUERY_PARENT_DOMAIN ), new Toolset_Relationship_Role_Parent() ) );
		}

		if ( $this->has_query_var( self::QUERY_CHILD_DOMAIN ) ) {
			$q->add( $q->has_domain( $this->get_query_var( self::QUERY_CHILD_DOMAIN ), new Toolset_Relationship_Role_Child() ) );
		}

		if( $this->has_query_var( self::QUERY_PARENT_QUERY ) ) {
			$q->add( $q->wp_query( new Toolset_Relationship_Role_Parent(), $this->get_query_var( self::QUERY_PARENT_QUERY ), 'i_know_what_i_am_doing' ) );
		}

		if( $this->has_query_var( self::QUERY_CHILD_QUERY ) ) {
			$q->add( $q->wp_query( new Toolset_Relationship_Role_Child(), $this->get_query_var( self::QUERY_CHILD_QUERY ), 'i_know_what_i_am_doing' ) );
		}

		if( $this->need_row_count() ) {
			$q->need_found_rows();
		}

		if ( $this->has_query_var( self::QUERY_LIMIT ) ) {
			$q->limit( $this->get_query_var( self::QUERY_LIMIT ) );
			if ( $this->has_query_var( self::QUERY_OFFSET ) ) {
				$q->offset( $this->get_query_var( self::QUERY_OFFSET ) );
			}
		} else {
			// Toolset_Association_Query worked without a limit set by the user
			// Toolset_Association_Query_V2 requires that the user sets a limit, otherwise FATAL ERROR
			// For backward compatibility we're adding limit 1000 if no specific limit set by the user
			$q->limit( 1000 );
		}

		switch( $this->return ) {
			case self::RETURN_ASSOCIATION_IDS:
				$q->return_association_uids();
				break;
			case self::RETURN_ASSOCIATIONS:
				$q->return_association_instances();
				break;
			case self::RETURN_PARENT_IDS:
				$q->return_element_ids( new Toolset_Relationship_Role_Parent() );
				break;
			case self::RETURN_CHILD_IDS:
				$q->return_element_ids( new Toolset_Relationship_Role_Child() );
				break;
			case self::RETURN_PARENTS:
				$q->return_element_instances( new Toolset_Relationship_Role_Parent() );
				break;
			case self::RETURN_CHILDREN:
				$q->return_element_instances( new Toolset_Relationship_Role_Child() );
				break;
		}

		return $q->get_results();
	}



	protected function get_subject_name_for_cache() {
		return 'associations';
	}


	/**
	 * Build the MySQL statement for querying the data, depending on query variables.
	 *
	 * @return string MySQL query statement.
	 * @since m2m
	 */
	protected function build_sql_statement() {
		return '';
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_results_type() {
		return ARRAY_A;
	}


	/**
	 * @return string[] Possible values for the 'return' query argument.
	 */
	private function get_return_options() {
		return array(
			self::RETURN_ASSOCIATIONS,
			self::RETURN_ASSOCIATION_IDS,
			self::RETURN_CHILD_IDS,
			self::RETURN_CHILDREN,
			self::RETURN_PARENT_IDS,
			self::RETURN_PARENTS
		);
	}


	/**
	 * Determine if SQL_CALC_FOUND_ROWS should be part of the MySQL statement.
	 *
	 * @return bool
	 */
	private function need_row_count() {
		return ( ! $this->dont_count_found_rows && $this->has_query_var( self::QUERY_LIMIT ) );
	}


	/**
	 * Process raw output from $wpdb.
	 *
	 * @param array $rows
	 *
	 * @return array
	 */
	protected function postprocess_results( $rows ) {
		return array();
	}

}
