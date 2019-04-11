<?php

/**
 * Represents a post type.
 *
 * "Post type" has an abstract meaning here, it may be one that's registered in WordPress by a third-party or by Types,
 * it can be a built-in one, or it can be one _defined_ by Types but not registered in WordPress at the time. Details are,
 * of course, described in classes that implement this.
 *
 * @since m2m
 */
interface IToolset_Post_Type {


	/**
	 * Get the post type slug.
	 *
	 * @return string
	 */
	public function get_slug();


	/**
	 * @return bool Is the post type defined by Types?
	 */
	public function is_from_types();


	/**
	 * @return bool Is the post type currently registered in WordPress?
	 */
	public function is_registered();


	/**
	 * @return bool Is this a built-in post type?
	 *
	 * Note that even built-in post types may have Types definitions.
	 */
	public function is_builtin();


	/**
	 * @return WP_Post_Type|null The underlying WP core object if the post type is registered, null otherwise.
	 */
	public function get_wp_object();


	/**
	 * @return bool True if the post type is used as an intermediary post type for a relationship.
	 */
	public function is_intermediary();


	/**
	 * @return bool True if the post type is used as a repeating field group.
	 */
	public function is_repeating_field_group();


	/**
	 * @return bool True if the post type has a special purpose and shouldn't be used elsewhere.
	 */
	public function has_special_purpose();


	/**
	 * Get one of the post type labels.
	 *
	 * @param string $label_name One of the values from Toolset_Post_Type_Labels.
	 *
	 * @return string Label value. If it's not defined, the "name" label is used.
	 *     If even that one is empty, it returns the post slug.
	 */
	public function get_label( $label_name = Toolset_Post_Type_Labels::NAME );


	/**
	 * @return bool Corresponds with WP_Post_Type::$public.
	 */
	public function is_public();


	/**
	 * Check whether the post type accepts an universal field group.
	 *
	 * In some situations, we need to limit generic field groups assigned to "all post types" and exclude them from
	 * certain specific post types.
	 *
	 * This method defines that behaviour.
	 *
	 * It needs to be called for generic field groups assigned to everything. For special field groups or
	 * field groups with specific post type assignments, it is not mandatory to use this.
	 *
	 * @param Toolset_Field_Group $field_group
	 *
	 * @return bool
	 */
	public function allows_field_group( Toolset_Field_Group $field_group );


	/**
	 * Check if the post type is translatable by WPML.
	 *
	 * @return bool
	 * @since 2.5.10
	 */
	public function is_translatable();


	/**
	 * Check if the post type is translatable and has the "display as translated" mode.
	 *
	 * @return bool
	 * @since 2.5.10
	 */
	public function is_in_display_as_translated_mode();


	/**
	 * Check if the post type can be used in a relationship.
	 *
	 * @return Toolset_Result
	 * @since 2.5.10
	 */
	public function can_be_used_in_relationship();


	/**
	 * Check if the post type is already used in an existing relationship.
	 *
	 * Needs m2m.
	 *
	 * @return bool
	 * @since 2.5.11
	 */
	public function is_involved_in_relationship();
}
