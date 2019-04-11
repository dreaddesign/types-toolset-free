<?php

/**
 * Condition for the Toolset_Relationship_Query_V2.
 *
 * Provides a wpdb instance to all its subclasses.
 *
 * @since m2m
 */
abstract class Toolset_Relationship_Query_Condition implements IToolset_Relationship_Query_Condition {


	/**
	 * By default, there is nothing to join.
	 *
	 * @return string
	 */
	public function get_join_clause() {
		return '';
	}


	/**
	 * Get the alias of the post type set table that's joined to the query for a given role.
	 *
	 * @param IToolset_Relationship_Role_Parent_Child $role
	 *
	 * @return string
	 */
	protected function get_type_set_table_alias( $role ) {
		// We're using the standard aliases that are always joined in by default.
		switch( $role->get_name() ) {
			case Toolset_Relationship_Role::PARENT:
				return 'parent_types_table';
			case Toolset_Relationship_Role::CHILD:
				return 'child_types_table';
		}

		// This should never happen.
		throw new RuntimeException();
	}


}