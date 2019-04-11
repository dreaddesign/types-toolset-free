<?php

/**
 * Class Toolset_Relationship_Origin_Wizard
 *
 * Relationship, which was created by using the relationship wizard
 *
 * @since m2m
 */
class Toolset_Relationship_Origin_Wizard implements IToolset_Relationship_Origin {
	const ORIGIN_KEYWORD = 'wizard';

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
		// show this relationship on the relationship overview page
		return true;
	}

	public function show_on_post_edit_screen() {
		// show this relationship on the post edit screen
		return true;
	}
}