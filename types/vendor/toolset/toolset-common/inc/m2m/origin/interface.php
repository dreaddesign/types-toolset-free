<?php

/**
 * Interface IToolset_Relationship_Origin
 *
 * @since m2m
 */
interface IToolset_Relationship_Origin {

	/**
	 * Returns the origin keyword (which will also be stored in the database)
	 * @return string
	 */
	public function get_origin_keyword();

	/**
	 * Should the relationship be shown on the relationships overview page (Toolset->Relationships)
	 * @return bool
	 */
	public function show_on_page_relationships();

	/**
	 * Should the relationship be shown on post edit screens
	 * @return bool
	 */
	public function show_on_post_edit_screen();
}