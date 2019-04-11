<?php

/**
 * Condition that a relationship has not a certain type in a certain relationship role.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Exclude_Type extends Toolset_Relationship_Query_Condition_Type {


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {
		$where = sprintf(
			"{$this->get_type_set_table_alias( $this->role )}.type != '%s' ",
			esc_sql( $this->type )
		);

		return $where;
	}

}
