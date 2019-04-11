<?php

/**
 * Chains multiple IToolset_Query_Condition with AND.
 *
 * @since m2m
 */
class Toolset_Query_Condition_And extends Toolset_Query_Condition_Operator {


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {

		if( empty( $this->conditions ) ) {
			return '1 = 1';
		}

		$where_clauses = array();

		foreach( $this->conditions as $condition ) {
			$where_clauses[] = $condition->get_where_clause();
		}

		return ' ( ' . implode( ' ) AND ( ', $where_clauses ) . ' ) ';

	}


	/**
	 * @inheritdoc
	 *
	 * @param IToolset_Query_Condition[] $conditions
	 *
	 * @return Toolset_Query_Condition_And
	 */
	protected function instantiate_self( $conditions ) {
		return new self( $conditions );
	}


	public function get_inner_conditions() {
		return $this->conditions;
	}
}