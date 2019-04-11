<?php

/**
 * Class Toolset_Relationship_Origin_Repeatable_Group
 *
 * Relationship, which was created by adding a new repeatable group
 *
 * @since m2m
 */
class Toolset_Relationship_Origin_Repeatable_Group implements IToolset_Relationship_Origin {
	const ORIGIN_KEYWORD = 'repeatable_group';

	/**
	 * Returns the origin keyword (which will also be stored in the database)
	 * @return string
	 */
	public function get_origin_keyword() {
		return self::ORIGIN_KEYWORD;
	}

	/**
	 * @return bool
	 */
	public function show_on_page_relationships() {
		// DO NOT show this relationship on the relationship overview page
		return false;
	}

	public function show_on_post_edit_screen() {
		// DO NOT show this relationship on the post edit screen
		return false;
	}
}