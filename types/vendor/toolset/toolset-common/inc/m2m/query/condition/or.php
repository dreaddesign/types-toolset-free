<?php

/**
 * Chains multiple IToolset_Query_Condition with OR.
 *
 * @since m2m
 */
class Toolset_Query_Condition_Or extends Toolset_Query_Condition_Operator {


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {

		if( empty( $this->conditions ) ) {
			return '1 = 1';
		}

		$clauses = array();
		foreach( $this->conditions as $condition ) {
			$clauses[] = $condition->get_where_clause();
		}

		$or_clause = ' ( ' . implode( ' ) OR ( ', $clauses ) . ' ) ';

		return $or_clause;
	}


	/**
	 * @inheritdoc
	 * @param IToolset_Query_Condition[] $conditions
	 *
	 * @return IToolset_Query_Condition
	 */
	protected function instantiate_self( $conditions ) {
		return new self( $conditions );
	}
}