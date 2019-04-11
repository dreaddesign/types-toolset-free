<?php

/**
 * Condition to query associations that don't belong to a relationship.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Exclude_Relationship extends Toolset_Association_Query_Condition_Relationship_Id {


	/**
	 * Returns condition operator
	 *
	 * @return string
	 * @since m2m
	 */
	protected function get_operator() {
		return '!=';
	}

}
