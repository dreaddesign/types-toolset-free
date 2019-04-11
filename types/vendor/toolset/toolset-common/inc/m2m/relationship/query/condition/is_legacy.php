<?php

/**
 * Condition that a relationship needs legacy support (because it was migrated
 * from the legacy implementation).
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Is_Legacy extends Toolset_Relationship_Query_Condition_Is_Boolean_Flag {


	protected function get_flag_column_name() {
		return 'needs_legacy_support';
	}
}