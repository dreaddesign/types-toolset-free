<?php

/**
 * Query by searching a text in elements of a given role.
 *
 * Note: This currently supports only posts, but in the future, it should be domain-agnostic.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Search extends Toolset_Association_Query_Condition {


	/** @var string */
	private $search_string;


	/** @var bool */
	private $is_exact_search;


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;


	/** @var wpdb */
	private $wpdb;


	/**
	 * Toolset_Association_Query_Condition_Search constructor.
	 *
	 * @param string $search_string
	 * @param bool $is_exact_search
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param wpdb|null $wpdb_di
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$search_string,
		$is_exact_search,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		wpdb $wpdb_di = null
	) {
		if( ! is_string( $search_string ) || empty( $search_string ) ) {
			throw new InvalidArgumentException( 'Invalid search string ' . $search_string );
		}
		$this->search_string = $search_string;
		$this->join_manager = $join_manager;
		$this->is_exact_search = (bool) $is_exact_search;
		$this->for_role = $for_role;

		global $wpdb;
		$this->wpdb = ( null === $wpdb_di ? $wpdb : $wpdb_di );
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$wp_posts = $this->join_manager->wp_posts( $this->for_role );
		$search_string = esc_sql( $this->get_sanitized_search_string() );

		return "{$wp_posts}.post_title LIKE '{$search_string}' 
			OR {$wp_posts}.post_excerpt LIKE '{$search_string}' 
			OR {$wp_posts}.post_content LIKE '{$search_string}'";
	}


	/**
	 * Get a string prepared for using in the query.
	 *
	 * @return string
	 */
	private function get_sanitized_search_string() {
		$s = stripslashes( $this->search_string );
		$s = str_replace( array( "\r", "\n", "\t" ), '', $s );
		if( ! $this->is_exact_search ) {
			$s = '%' . $this->wpdb->esc_like( $s ) . '%';
		}
		return $s;
	}
}