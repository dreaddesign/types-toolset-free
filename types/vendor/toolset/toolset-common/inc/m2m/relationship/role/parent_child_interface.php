<?php

/**
 * Represents a parent or a child (not intermediary) role of an element in a relationship.
 *
 * @since m2m
 */
interface IToolset_Relationship_Role_Parent_Child extends IToolset_Relationship_Role {

	/**
	 * @return IToolset_Relationship_Role_Parent_Child The opposite role.
	 */
	public function other();

}