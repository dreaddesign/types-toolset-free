<?php

/**
 * Condition to query associations by a specific intermediary post (row) ID.
 *
 * @since 2.6.7
 */
class Toolset_Association_Query_Condition_Has_Intermediary_Id extends Toolset_Association_Query_Condition {


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( ' ( associations.intermediary_id IS NOT NULL and associations.intermediary_id > 0 ) ' );
	}
}
