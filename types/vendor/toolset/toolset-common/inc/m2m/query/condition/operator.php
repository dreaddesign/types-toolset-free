<?php

/**
 * Abstract condition for implementing operators in the MySQL query.
 *
 * @since 2.5.4
 */
abstract class Toolset_Query_Condition_Operator
	implements IToolset_Relationship_Query_Condition, IToolset_Association_Query_Condition
{


	/** @var IToolset_Query_Condition[] */
	protected $conditions = array();


	/**
	 * Toolset_Query_Condition_Operator constructor.
	 *
	 * @param IToolset_Query_Condition[]|array $conditions If a nested array of conditions
	 *     is provided, it will be handled as a nested $op ($op is the operation):
	 *     ( $condition1 ) $op ( ( $condition2_1 ) $op ( $condition2_2 ) ) $op ...etc.
	 */
	public function __construct( $conditions ) {
		$this->add_conditions( $conditions );
	}


	/**
	 * @param IToolset_Query_Condition[]|array $conditions
	 */
	private function add_conditions( $conditions ) {
		foreach( $conditions as $condition ) {
			if( $condition instanceof IToolset_Query_Condition ) {
				$this->conditions[] = $condition;
			} elseif( is_array( $condition ) ) {
				if( count( $condition ) === 1 ) {
					// single condition inside an array - it doesn't have to be nested in another condition
					$this->add_conditions( $condition );
				} else {
					$this->conditions[] = $this->instantiate_self( $condition );
				}
			} else {
				throw new InvalidArgumentException();
			}
		}
	}


	/**
	 * Just joins the join clauses from nested conditions
	 * @return string
	 */
	public function get_join_clause() {
		$join_clauses = '';

		foreach( $this->conditions as $condition ) {
			$join_clauses .= ' ' . $condition->get_join_clause() . ' ';
		}

		return $join_clauses;
	}


	/**
	 * Return an instance of self with provided conditions.
	 *
	 * Used for nesting when a nested array of conditions is passed to the constructor.
	 *
	 * @param IToolset_Relationship_Query_Condition[] $conditions
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	abstract protected function instantiate_self( $conditions );

}