<?php

/**
 * Condition that the relationship has at least one active post type in a given role (or another domain than posts).
 *
 * Note that if polymorphic relationships are introduced, a relationship with a mix of inactive and active post types
 * in one role will pass the condition but it will not have the information about the inactive types. That may become
 * an issue when editing relationships (however, there a query is not used, but relationships are loaded directly).
 *
 * @since 2.5.4
 */
class Toolset_Relationship_Query_Condition_Has_Active_Types extends Toolset_Relationship_Query_Condition {


	/** @var bool */
	private $only_active_types;


	/** @var IToolset_Relationship_Role_Parent_Child */
	protected $for_role;


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/** @var Toolset_Post_Type_Query_Factory */
	private $post_type_query_factory;


	/**
	 * Toolset_Relationship_Query_Condition_Has_Active_Types constructor.
	 *
	 * @param bool $only_active_types
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Post_Type_Query_Factory|null $post_type_query_factory_di
	 */
	public function __construct(
		$only_active_types,
		IToolset_Relationship_Role_Parent_Child $for_role,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Post_Type_Query_Factory $post_type_query_factory_di = null
	) {
		if( ! is_bool( $only_active_types ) ) {
			throw new InvalidArgumentException();
		}

		$this->only_active_types = $only_active_types;
		$this->for_role = $for_role;

		$this->database_operations = (
			null === $database_operations_di
				? new Toolset_Relationship_Database_Operations()
				: $database_operations_di
		);

		$this->post_type_query_factory = (
			null === $post_type_query_factory_di
				? new Toolset_Post_Type_Query_Factory()
				: $post_type_query_factory_di
		);
	}

	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {

		if( ! $this->only_active_types ) {
			return '1 = 1';
		}

		$active_post_types = $this->get_active_post_type_slugs();
		$type_set_table_alias = $this->get_type_set_table_alias( $this->for_role );
		$domain_column_name = $this->database_operations->role_to_column(
			$this->for_role,
			Toolset_Relationship_Database_Operations::COLUMN_DOMAIN
		);

		$query = sprintf(
			"( 
				relationships.{$domain_column_name} != '%s' 
				OR {$type_set_table_alias}.type IN ( '" . implode( "', '", esc_sql( $active_post_types ) ) . "' ) 
			)",
			Toolset_Element_Domain::POSTS
		);

		return $query;
	}


	/**
	 * @return string[] Slugs of active post types.
	 */
	private function get_active_post_type_slugs() {

		$post_type_query = $this->post_type_query_factory->create( array(
			Toolset_Post_Type_Query::IS_REGISTERED => true,
			Toolset_Post_Type_Query::RETURN_TYPE => 'slug',
			Toolset_Post_Type_Query::HAS_SPECIAL_PURPOSE => null
		) );

		$active_post_types = $post_type_query->get_results();

		return $active_post_types;
	}
}