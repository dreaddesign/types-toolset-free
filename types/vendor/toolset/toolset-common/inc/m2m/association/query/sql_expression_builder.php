<?php

/**
 * Builds the MySQL expression for the association query.
 *
 * @since 2.5.10
 */
class Toolset_Association_Query_Sql_Expression_Builder {


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;


	/**
	 * Toolset_Relationship_Query_Sql_Expression_Builder constructor.
	 *
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 */
	public function __construct(
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null
	) {
		$this->table_name = ( null === $table_name_di ? new Toolset_Relationship_Table_Name() : $table_name_di );

		$this->database_operations = (
		null === $database_operations_di
			? new Toolset_Relationship_Database_Operations()
			: $database_operations_di
		);

		$this->join_manager = $join_manager;
	}


	/**
	 * Build a complete MySQL query from the conditions.
	 *
	 * @param IToolset_Association_Query_Condition $root_condition
	 * @param int $offset
	 * @param int $limit
	 * @param IToolset_Association_Query_Orderby $orderby
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 * @param bool $need_found_rows
	 * @param IToolset_Association_Query_Result_Transformation $result_transformation
	 *
	 * @return string
	 */
	public function build(
		IToolset_Association_Query_Condition $root_condition,
		$offset,
		$limit,
		IToolset_Association_Query_Orderby $orderby,
		IToolset_Association_Query_Element_Selector $element_selector,
		$need_found_rows,
		IToolset_Association_Query_Result_Transformation $result_transformation
	) {

		$associations_table = $this->table_name->association_table();

		// Before building JOIN clauses, allow the ORDERBY builder also to add its own.
		$orderby->register_joins();
		// Same for the element selector. Otherwise the initialization would run
		// from inside of $this->join_manager->get_join_clause() which is too late.
		$element_selector->initialize();

		// Conditions can either use the JOIN manager object to share JOINed tables
		// or handle it entirely on their own. We need to get results from both sources here.
		//
		// Timing is extra important here: First, we get the JOIN clauses, so that the conditions
		// can create table aliases that are later used when building the WHERE clauses.
		//
		// Then come ORDER BY clauses.
		//
		// Then we ask the result transformation object to talk to the element selector,
		// and tell it which elements it will need in the select clause. This will
		// influence the following step as well and can't be done later.
		//
		// Then we collect the JOIN clauses from the join manager object, which may have been used also
		// when building the WHERE or ORDER BY clauses. This also includes JOINs coming from
		// the element selector.
		//
		// Finally, we already know what we're going to need in the results and we can
		// obtain the optimized select clauses from the element selector.
		$join_clause = $root_condition->get_join_clause();
		$where_clause = $root_condition->get_where_clause();
		$orderby_clause = $orderby->get_orderby_clause();
		$result_transformation->request_element_selection( $element_selector );
		$join_clause = $this->join_manager->get_join_clause( $element_selector ) . ' ' . ' ' . $join_clause;
		$select_elements = $element_selector->get_select_clauses();
		// End of the timing-critical part.

		$sql_found_rows = ( $need_found_rows ? 'SQL_CALC_FOUND_ROWS' : '' );
		if( ! empty( $orderby_clause ) ) {
			$orderby_clause = "ORDER BY $orderby_clause";
		}

		$limit = (int) $limit;
		$offset = (int) $offset;

		$maybe_distinct = $element_selector->maybe_get_distinct_modifier();

		// Make sure we glue the pieces together well and leave no extra comma at the end
		// in case $select_elements is empty.
		$final_select_elements = array();
		$select_elements_trimmed = trim( $select_elements );
		if( ! empty( $select_elements_trimmed ) ) {
			$final_select_elements[] = $select_elements;
		}
		$final_select_elements = implode( ', ' . PHP_EOL, $final_select_elements );

		// We rely on all the moving parts which are supposed to have provided properly escaped strings.
		$query = "
			SELECT {$maybe_distinct}
				{$sql_found_rows} 		
				{$final_select_elements}				
			FROM {$associations_table} AS associations {$join_clause} 
			WHERE {$where_clause}
			{$orderby_clause}			
			LIMIT {$limit} 
			OFFSET {$offset}";

		return $query;
	}


}