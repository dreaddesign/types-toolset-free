<?php

/**
 * Query associations by the fact whether the relationship they belong to was migrated from the legacy implementation or not.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Has_Legacy_Relationship extends Toolset_Association_Query_Condition_Relationship_Flag {


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_flag_name() {
		return 'needs_legacy_support';
	}
}