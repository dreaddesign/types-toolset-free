<?php

/**
 * Query associations by the origin value of a relationship they belong to.
 *
 * @since m2m
 */
class Toolset_Association_Query_Condition_Relationship_Origin extends Toolset_Association_Query_Condition {

	/** @var Toolset_Association_Query_Table_Join_Manager  */
	private $join_manager;


	/** @var bool */
	private $expected_value;


	/**
	 * Toolset_Association_Query_Condition_Has_Active_Relationship constructor.
	 *
	 * @param bool $expected_value
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 */
	public function __construct( $expected_value, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		$this->expected_value = $expected_value;
		$this->join_manager = $join_manager;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$relationships_table = $this->join_manager->relationships();

		return sprintf(
			'%s.origin = %d',
			$relationships_table,
			esc_sql( $this->expected_value )
		);
	}
}
