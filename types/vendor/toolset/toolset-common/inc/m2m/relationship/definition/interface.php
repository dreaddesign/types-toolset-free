<?php

/**
 * Interface of the relationship definition.
 *
 * @since m2m
 */
interface IToolset_Relationship_Definition {

	/**
	 * Get the relationship slug.
	 *
	 * This value is unique and cannot change (except in special cases to be handled by a database transformation).
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_slug();


	/**
	 * Get the display name of the relationship.
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_display_name();


	/**
	 * Update the relationship (plural) display name.
	 *
	 * @param string $display_name
	 *
	 * @since m2m
	 */
	public function set_display_name( $display_name );


	/**
	 * Synonymous to get_display_name().
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_display_name_plural();


	/**
	 * Get the singular display name of the relationship.
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_display_name_singular();


	/**
	 * Update the relationship singular display name.
	 *
	 * @param string $display_name
	 *
	 * @since m2m
	 */
	public function set_display_name_singular( $display_name );


	/**
	 * Get the parent entity type definition.
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public function get_parent_type();


	/**
	 * Get the child entity type definition.
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public function get_child_type();


	public function get_parent_domain();


	public function get_child_domain();


	public function get_domain( $element_role );


	/**
	 * Get a type of elements that can take a role in the relationship.
	 *
	 * @param string|IToolset_Relationship_Role $element_role
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public function get_element_type( $element_role );


	/**
	 * Set a type of elements that can take a role in the relationship.
	 *
	 * Use with caution. Without further adjustments, this can cause a database inconsistency.
	 *
	 * @param Toolset_Relationship_Element_Type $element_type
	 * @param IToolset_Relationship_Role_Parent_Child|string $role
	 *
	 * @return void
	 * @since 2.5.6
	 */
	public function set_element_type( $role, Toolset_Relationship_Element_Type $element_type );


	/**
	 * Determine if there are posts on the given side of the relationship.
	 *
	 * @param string|IToolset_Relationship_Role $element_role
	 *
	 * @return bool
	 * @since m2m
	 */
	public function is_post( $element_role );


	/**
	 * @return Toolset_Relationship_Cardinality
	 */
	public function get_cardinality();


	/**
	 * Update the relationship cardinality.
	 *
	 * @param Toolset_Relationship_Cardinality $value
	 *
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function set_cardinality( $value );


	/**
	 * Check if this relationship has some association fields defined.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function has_association_field_definitions();


	/**
	 * Get definitions of association fields.
	 *
	 * @return Toolset_Field_Definition[]
	 * @since m2m
	 */
	public function get_association_field_definitions();


	/**
	 * Creates an association of this relationship between two elements.
	 *
	 * So far, only native relationships are supported. In their case, an intermediary post is created automatically,
	 * if the relationship requires it.
	 *
	 * @param int|WP_Post|IToolset_Element $parent Parent element (of matching domain, type and other conditions)
	 * @param int|WP_Post|IToolset_Element $child Child element (of matching domain, type and other conditions)
	 *
	 * @return Toolset_Result|IToolset_Association The newly created association or a negative Toolset_Result when it could not have been created.
	 * @throws RuntimeException when the association cannot be created because of a known reason. The exception would
	 *     contain a displayable error message.
	 * @throws InvalidArgumentException when the method is used improperly.
	 *
	 * @since m2m
	 */
	public function create_association( $parent, $child );


	/**
	 * Determine or set whether the relationship is distinct, which means that only one association between
	 * each two elements can exist.
	 *
	 * @param null|bool $new_value If a boolean value is provided, it will be set.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function is_distinct( $new_value = null );


	/**
	 * Determine whether this relationship involves translatable elements.
	 *
	 * That includes possible parent and child types as well as association fields.
	 *
	 * Note that the value is cached for performance reasons and it may apply a lot of WPML filters on the first time.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function is_translatable();


	/**
	 * Get a custom role name that should be recognized in shortcodes instead of parent, child, etc.
	 *
	 * @param string|IToolset_Relationship_Role $role One of the Toolset_Relationship_Role values.
	 *
	 * @return string Custom role name.
	 * @since m2m
	 */
	public function get_role_name( $role );


	/**
	 * Get all custom role names as an associative array.
	 *
	 * @return string[string]
	 * @since m2m
	 */
	public function get_role_names();


	/**
	 * Update a custom role name.
	 *
	 * The name will be sanitized and the value actually saved will be returned.
	 *
	 * @param string|IToolset_Relationship_Role $role One of the Toolset_Relationship_Role values.
	 * @param string $custom_name Custom name for the role.
	 *
	 * @return string Sanitized custom name
	 * @since m2m
	 */
	public function set_role_name( $role, $custom_name );


	/**
	 * If the relationship was migrated from the legacy post relationships, we need to
	 * provide backward compatibility for it.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function needs_legacy_support();


	/**
	 * Defines whether the relationship is active on the site (whether it should be taken into account at all).
	 *
	 * @param null|bool $value
	 *
	 * @return bool
	 */
	public function is_active( $value = null );


	/**
	 * @return IToolset_Relationship_Origin
	 * @since m2m
	 */
	public function get_origin();


	/**
	 * Set origin
	 * Can be set by using the origin keyword or the class
	 *
	 * @param IToolset_Relationship_Origin|string  $origin
	 * @return void
	 * @since m2m
	 */
	public function set_origin( $origin );


	/**
	 * @return int
	 */
	public function get_row_id();


	/**
	 * Return the number of existing associations belonging to the relationships
	 *
	 * @param string|IToolset_Relationship_Role $role Role.
	 * @return int
	 * @since m2m
	 */
	public function get_max_associations( $role );


	/**
	 * Get the intermediary post type, if it exists.
	 *
	 * Note that its existence doesn't necessarily mean that there are association fields.
	 *
	 * @return null|string
	 */
	public function get_intermediary_post_type();
}
