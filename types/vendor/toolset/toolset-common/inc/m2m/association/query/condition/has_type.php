<?php

/**
 * Condition to query associations by a type (not domain) of elements in the given role.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Has_Type extends Toolset_Association_Query_Condition {


	/** @var IToolset_Relationship_Role_Parent_Child */
	private $for_role;


	/** @var string */
	private $type;


	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_Relationship_Database_Unique_Table_Alias */
	private $unique_table_alias;


	/** @var string|null This needs to be set during get_join_clauses(). */
	private $type_set_table_alias;


	/**
	 * Toolset_Association_Query_Condition_Has_Type constructor.
	 *
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param string $type
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		IToolset_Relationship_Role_Parent_Child $for_role,
		$type,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null
	) {
		if( ! is_string( $type ) || empty( $type ) ) {
			throw new InvalidArgumentException();
		}

		$this->for_role = $for_role;
		$this->type = $type;
		$this->join_manager = $join_manager;
		$this->unique_table_alias = $unique_table_alias;
		$this->database_operations = ( null === $database_operations_di ? new Toolset_Relationship_Database_Operations() : $database_operations_di );
		$this->table_name = ( null === $table_name_di ? new Toolset_Relationship_Table_Name() : $table_name_di );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_join_clause() {
		$relationships_table = $this->join_manager->relationships();
		$type_set_column = $this->database_operations->role_to_column(
			$this->for_role->get_name(),
			Toolset_Relationship_Database_Operations::COLUMN_TYPES
		);
		$type_set_table = $this->table_name->type_set_table();
		$type_set_table_alias = $this->unique_table_alias->generate( $type_set_table, true );

		$this->type_set_table_alias = $type_set_table_alias;

		return " JOIN $type_set_table AS $type_set_table_alias ON ( $type_set_table_alias.set_id = $relationships_table.$type_set_column ) ";
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( "%s.type = '%s'", $this->type_set_table_alias, esc_sql( $this->type ) );
	}

}