<?php

/**
 * Builds the MySQL for the relationship query out of IToolset_Relationship_Query_Condition instances.
 *
 * @since 2.5.4
 */
class Toolset_Relationship_Query_Sql_Expression_Builder {


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/**
	 * Toolset_Relationship_Query_Sql_Expression_Builder constructor.
	 *
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 */
	public function __construct(
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null
	) {
		$this->table_name = ( null === $table_name_di ? new Toolset_Relationship_Table_Name() : $table_name_di );
		$this->database_operations = ( null === $database_operations_di ? new Toolset_Relationship_Database_Operations() : $database_operations_di );
	}


	/**
	 * Build a complete MySQL query from the conditions.
	 *
	 * Also make sure that the query results will be easily recognizable by Toolset_Relationship_Definition_Translator.
	 *
	 * @param IToolset_Relationship_Query_Condition $root_condition
	 * @param bool $need_found_rows
	 * @return string
	 * @since 2.5.8 Can calculate found rows.
	 */
	public function build( IToolset_Relationship_Query_Condition $root_condition, $need_found_rows ) {

		$relationship_table = $this->table_name->relationship_table();
		$type_set_table = $this->table_name->type_set_table();

		// This is a bit more complex because we need to get results in a format that the
		// definition translator will accept.
		$select_clause = $this->database_operations->get_standard_relationships_select_clause();
		$where_clause = $root_condition->get_where_clause();
		$join_clause = implode( ' ', array(
			$this->database_operations->get_standard_relationships_join_clause( $type_set_table ),
			$root_condition->get_join_clause()
		) );
		$group_by_clause = $this->database_operations->get_standards_relationship_group_by_clause();
		$sql_found_rows = ( $need_found_rows ? 'SQL_CALC_FOUND_ROWS' : '' );

		return "SELECT {$sql_found_rows} {$select_clause} 
			FROM {$relationship_table} AS relationships {$join_clause} 
			WHERE {$where_clause} GROUP BY {$group_by_clause}";

	}

}