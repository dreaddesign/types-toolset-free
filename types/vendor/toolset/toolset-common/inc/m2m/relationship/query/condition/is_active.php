<?php

/**
 * Condition that a relationship is active.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Is_Active extends Toolset_Relationship_Query_Condition_Is_Boolean_Flag {


	protected function get_flag_column_name() {
		return 'is_active';
	}

}