<?php

/**
 * Represents an association between two elements.
 *
 * @since m2m
 */
interface IToolset_Association {


	/**
	 * Unique identifier of the association.
	 *
	 * Depending on the implementation, this may be an association row ID, trid or anything else.
	 * The only guarantee is that each association's UID is unique.
	 *
	 * @return int|string
	 */
	public function get_uid();


	/**
	 * @return Toolset_Relationship_Definition
	 */
	public function get_definition();


	/**
	 * Tell if the association has custom fields.
	 *
	 * Note that this value is based on field definitions, not on the actual values in the database.
	 *
	 * @return bool
	 */
	public function has_fields();


	/**
	 * Check if the association has particular custom field.
	 *
	 * Note that this value is based on field definitions, not on the actual values in the database.
	 *
	 * @param string|Toolset_Field_Definition $field_source Field definition or slug.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function has_field( $field_source );


	/**
	 * Get all association field instances.
	 *
	 * @return Toolset_Field_Instance[]
	 * @since m2m
	 */
	public function get_fields();


	/**
	 * Get a particular association field instance.
	 *
	 * @param string|Toolset_Field_Definition $field_source Field definition or slug.
	 *
	 * @return Toolset_Field_Instance
	 * @throws InvalidArgumentException
	 */
	public function get_field( $field_source );


	/**
	 * Get an association element.
	 *
	 * Instantiates an element from its ID if that hasn't been done yet.
	 *
	 * @param IToolset_Relationship_Role $element_role
	 *
	 * @return Toolset_Element|null Null can be returned for the intermediary role, if there is no
	 *     intermediary post.
	 *
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function get_element( $element_role );


	/**
	 * Get an ID of the association element.
	 *
	 * Note that if WPML is active and the element is translated, this will return the ID of the
	 * translation.
	 *
	 * @param IToolset_Relationship_Role $element_role
	 *
	 * @return int
	 */
	public function get_element_id( $element_role );


	/**
	 * Check that the element role is valid.
	 *
	 * @param string $element_role
	 *
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public static function validate_element_role( $element_role );


	/**
	 * Shortcut to the relationship driver.
	 *
	 * @return Toolset_Relationship_Driver_Base
	 */
	public function get_driver();


	/**
	 * Get the ID of the intermediary post with association fields.
	 *
	 * Use with consideration.
	 *
	 * @return int Post ID or zero if no post exists.
	 * @since 2.5.8
	 */
	public function get_intermediary_id();


	/**
	 * Check whether an intermediary post exists for this association.
	 *
	 * @return bool
	 * @since 2.5.10
	 */
	public function has_intermediary_post();
}