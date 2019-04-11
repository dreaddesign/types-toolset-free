<?php

/**
 * Query associations by the is_active value of a relationship they belong to.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Has_Active_Relationship extends Toolset_Association_Query_Condition_Relationship_Flag {


	/**
	 * @inheritdoc
	 * @return string
	 */
	protected function get_flag_name() {
		return 'is_active';
	}
}