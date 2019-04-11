<?php

/**
 * Represents a parent role of an element in a relationship.
 *
 * @since m2m
 */
class Toolset_Relationship_Role_Parent
	extends Toolset_Relationship_Role_Abstract
	implements IToolset_Relationship_Role_Parent_Child
{

	/**
	 * Role name.
	 *
	 * @return string
	 */
	public function get_name() {
		return Toolset_Relationship_Role::PARENT;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_parent_child() {
		return true;
	}


	/**
	 * @inheritdoc
	 *
	 * @return IToolset_Relationship_Role_Parent_Child
	 */
	public function other() {
		return new Toolset_Relationship_Role_Child();
	}
}