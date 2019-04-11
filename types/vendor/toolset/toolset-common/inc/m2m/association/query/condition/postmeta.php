<?php

/**
 * Query condition by a postmeta value of a selected element role.
 *
 * Note: Using this will immediately exclude all non-post elements.
 *
 * @since 2.6.1
 */
class Toolset_Association_Query_Condition_Postmeta extends Toolset_Association_Query_Condition {


	/** @var string */
	private $meta_key;


	/** @var string  */
	private $meta_value;


	/** @var string */
	private $comparison_operator;


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var Toolset_Association_Query_Table_Join_Manager */
	private $join_manager;


	/**
	 * Toolset_Association_Query_Condition_Postmeta constructor.
	 *
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $comparison_operator
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$meta_key,
		$meta_value,
		$comparison_operator,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		if(
			! is_string( $meta_key ) || empty( $meta_key )
			|| ! is_string( $meta_value ) || empty( $meta_value )
			|| ! in_array( $comparison_operator, Toolset_Query_Comparison_Operator::all() )
		) {
			throw new InvalidArgumentException();
		}

		$this->meta_key = $meta_key;
		$this->meta_value = $meta_value;
		$this->comparison_operator = $comparison_operator;
		$this->for_role = $for_role;
		$this->join_manager = $join_manager;
	}

	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$postmeta = $this->join_manager->wp_postmeta( $this->for_role, $this->meta_key );
		$meta_value = esc_sql( $this->meta_value );

		return "$postmeta $this->comparison_operator '$meta_value'";
	}

}