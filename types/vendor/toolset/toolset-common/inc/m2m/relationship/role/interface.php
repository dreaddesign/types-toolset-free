<?php

/**
 * Represents a role that one element can take in a relationship.
 *
 * @since m2m
 */
interface IToolset_Relationship_Role {

	/**
	 * Role name.
	 *
	 * @return string
	 */
	public function get_name();


	/**
	 * Preferably check instanceof IToolset_Relationship_Role_Parent_Child.
	 * @return bool
	 */
	public function is_parent_child();


	/**
	 * Convert this to a role name string.
	 *
	 * @return string
	 */
	public function __toString();

}