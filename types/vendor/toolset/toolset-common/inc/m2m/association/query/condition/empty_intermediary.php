<?php

/**
 * Condition to query associations without intermediary post. Needed when fields are added and association have to be updated.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Empty_Intermediary extends Toolset_Association_Query_Condition {


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return 'associations.intermediary_id = 0';
	}
}
