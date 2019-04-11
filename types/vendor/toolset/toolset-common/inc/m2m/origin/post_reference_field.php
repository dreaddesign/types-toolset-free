<?php

/**
 * Class Toolset_Relationship_Origin_Post_Reference_Field
 *
 * Relationship, which was created by adding a post reference field
 * (So far same rules as Toolset_Relationship_Origin_Repeatable_Group)
 *
 * @since m2m
 */
class Toolset_Relationship_Origin_Post_Reference_Field extends Toolset_Relationship_Origin_Repeatable_Group {
	const ORIGIN_KEYWORD = 'post_reference_field';

	/**
	 * Returns the origin keyword (which will also be stored in the database)
	 * @return string
	 */
	public function get_origin_keyword() {
		return self::ORIGIN_KEYWORD;
	}
}