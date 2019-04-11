<?php

/**
 * Condition to filter results by element domain and type at the same time.
 *
 * Actually, this doesn't do anything but to tie those two together so that the association query
 * can perform some more advanced optimizations.
 *
 * @since m2m
 */
class Toolset_Association_Query_Condition_Has_Domain_And_Type extends Toolset_Association_Query_Condition {


	/** @var string */
	private $domain;


	/** @var string */
	private $type;


	/** @var IToolset_Association_Query_Condition */
	private $inner_condition;


	/**
	 * Toolset_Association_Query_Condition_Has_Type constructor.
	 *
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param string $domain
	 * @param string $type
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 * @param Toolset_Association_Query_Condition_Factory $condition_factory
	 */
	public function __construct(
		IToolset_Relationship_Role_Parent_Child $for_role,
		$domain,
		$type,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias,
		Toolset_Association_Query_Condition_Factory $condition_factory
	) {
		if(
			! in_array( $domain, Toolset_Element_Domain::all(), true )
			|| ! is_string( $type ) || empty( $type )
		) {
			throw new InvalidArgumentException();
		}

		$this->domain = $domain;
		$this->type = $type;

		$this->inner_condition = $condition_factory->do_and(
			array(
				$condition_factory->has_domain( $domain, $for_role, $join_manager ),
				$condition_factory->has_type( $type, $for_role, $join_manager, $unique_table_alias )
			)
		);
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return $this->inner_condition->get_where_clause();
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_join_clause() {
		return $this->inner_condition->get_join_clause();
	}


	/**
	 * @return string The element domain set in this condition.
	 */
	public function get_domain() {
		return $this->domain;
	}


	/**
	 * @return string The element type set in this condition.
	 */
	public function get_type() {
		return $this->type;
	}
}