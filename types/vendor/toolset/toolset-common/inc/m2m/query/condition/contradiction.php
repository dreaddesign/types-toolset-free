<?php

/**
 * A condition that is always false.
 *
 * It can be useful in situations where we need to make sure that the query will produce no results
 * (e.g. querying for something that clearly isn't there).
 *
 * @since 2.5.8
 */
class Toolset_Query_Condition_Contradiction
	implements IToolset_Relationship_Query_Condition, IToolset_Association_Query_Condition
{


	public function get_join_clause() {
		return '';
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return ' 1 = 0 ';
	}
}