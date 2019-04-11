<?php

/**
 * Represents an intermediary post role in a relationship.
 *
 * @since m2m
 */
class Toolset_Relationship_Role_Intermediary extends Toolset_Relationship_Role_Abstract {

	/**
	 * Role name.
	 *
	 * @return string
	 */
	public function get_name() {
		return Toolset_Relationship_Role::INTERMEDIARY;
	}


	/**
	 * @inheritdoc
	 * @return bool
	 */
	public function is_parent_child() {
		return false;
	}

}