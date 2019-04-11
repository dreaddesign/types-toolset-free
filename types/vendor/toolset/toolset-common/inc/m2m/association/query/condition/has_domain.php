<?php

/**
 * Query associations by the domain of selected role.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Has_Domain extends Toolset_Association_Query_Condition {


	/** @var Toolset_Association_Query_Table_Join_Manager  */
	private $join_manager;


	/** @var IToolset_Relationship_Role_Parent_Child */
	private $for_role;


	/** @var string */
	private $domain;


	/** @var null|Toolset_Relationship_Database_Operations */
	private $database_operations;


	/**
	 * Toolset_Association_Query_Condition_Has_Active_Relationship constructor.
	 *
	 * @param string $domain
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 */
	public function __construct(
		$domain,
		IToolset_Relationship_Role_Parent_Child $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Operations $database_operations_di = null
	) {
		if( ! in_array( $domain, Toolset_Element_Domain::all(), true ) ) {
			throw new InvalidArgumentException();
		}

		$this->domain = $domain;
		$this->for_role = $for_role;
		$this->join_manager = $join_manager;
		$this->database_operations = ( null === $database_operations_di ? new Toolset_Relationship_Database_Operations() : $database_operations_di );
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$relationships_table_alias = $this->join_manager->relationships();
		$domain_column = $this->database_operations->role_to_column(
			$this->for_role,
			Toolset_Relationship_Database_Operations::COLUMN_DOMAIN
		);

		return sprintf(
			"%s.%s = '%s'",
			$relationships_table_alias,
			$domain_column,
			esc_sql( $this->domain )
		);
	}


	/**
	 * @return string The element domain set on this condition.
	 * @since 2.5.10
	 */
	public function get_domain() {
		return $this->domain;
	}


	public function get_for_role() {
		return $this->for_role;
	}
}