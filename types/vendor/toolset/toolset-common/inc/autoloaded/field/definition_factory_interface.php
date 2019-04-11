<?php

/**
 * Interface for field definitions factory.
 *
 * @since 2.3
 */
interface Toolset_Field_Definition_Factory_Interface {

	/**
	 * Load an existing field definition.
	 *
	 * For now, we're using legacy code to read fields from the options table.
	 *
	 * Note that field definitions for fields not currently managed by Types may be loaded as well.
	 *
	 * @param string $field_key Key used to store the field configuration in options, or field slug (which should be
	 * equal to the key).
	 *
	 * @return null|Toolset_Field_Definition Field definition or null if it can't be loaded.
	 */
	 public function load_field_definition( $field_key );


	/**
	 * This method is to be used only for bringing existing fields under Types control.
	 *
	 * At this point it is assumed that there doesn't exist any field definition for given meta_key.
	 * See Toolset_Field_Utils::start_managing_field() for details.
	 *
	 * Maybe the usage could be wider, but that is not yet clear from the legacy code. The behaviour is slightly
	 * different for meta_keys with the wpcf- prefix from the ones without it. More details in the code.
	 *
	 * The field will be created as a text field.
	 *
	 * @param string $meta_key Field meta key.
	 *
	 * @return string|false New field slug on success, false otherwise.
	 * @since 2.0
	 */
	 public function create_field_definition_for_existing_fields( $meta_key );


	/**
	 * Removes a single field definition from the storage of existing instances.
	 *
	 * It also completely clears the cache of the (legacy) wpcf_admin_fields_get_fields.
	 * Note that this method is public only temporarily and that this is not a mere cache clearing.
	 *
	 * @param string|null $field_slug If null, the cache will be emptied completely.
	 */
	public function clear_definition_storage( $field_slug = null );


	/**
	 * Determine if there exists any Types field definition (within the domain) that uses this key.
	 *
	 * @param string $meta_key
	 * @param string [$return='boolean'] For 'boolean', the method simply returns true/false answer, for 'definition'
	 *     it returns either the field definition instance or null if no such one exists.
	 *
	 * @return bool|Toolset_Field_Definition|null
	 * @since 1.9
	 */
	public function meta_key_belongs_to_types_field( $meta_key, $return = 'boolean' );


	/**
	 * @return Toolset_Field_Group_Factory
	 * @since 2.0
	 */
	public function get_group_factory();


	/**
	 * @return Toolset_Field_Definition_Generic[] Definitions of all generic fields that exist in the database within
	 *     current domain.
	 */
	public function load_generic_field_definitions();


	/**
	 * @return Toolset_Field_Definition_Abstract[] All field definitions (generic and Types-controlled).
	 */
	public function load_all_definitions();


	/**
	 * Reorder an array of field definitions.
	 *
	 * @param Toolset_Field_Definition_Abstract[] $definitions
	 * @param string $orderby 'name'|'slug'|'is_under_types_control'|'field_type'
	 * @param string $order 'asc'|'desc'
	 *
	 * @return Toolset_Field_Definition_Abstract[] Reordered array.
	 */
	public function order_definitions( $definitions, $orderby = 'name', $order = 'asc' );


	/**
	 * Compare function for ordering by name in order_definitions().
	 *
	 * @param $first Toolset_Field_Definition_Abstract
	 * @param $second Toolset_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definitions_by_name( $first, $second );


	/**
	 * Compare function for ordering by slug in order_definitions().
	 *
	 * @param $first Toolset_Field_Definition_Abstract
	 * @param $second Toolset_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definitions_by_slug( $first, $second );


	/**
	 * Compare function for ordering by the Types control status in order_definitions().
	 *
	 * @param $first Toolset_Field_Definition_Abstract
	 * @param $second Toolset_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definition_by_types_control( $first, $second );


	/**
	 * Compare function for ordering by field type in order_definitions().
	 *
	 * @param $first Toolset_Field_Definition_Abstract
	 * @param $second Toolset_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definitions_by_field_type( $first, $second );


	/**
	 * Query field definitions.
	 *
	 * @param array $args Following arguments are recognized:
	 *
	 *     - filter: What field definitions should be retrieved: 'types'|'generic'|'all'
	 *     - orderby: 'name'|'slug'|'is_under_types_control'|'field_type'
	 *     - order: 'asc'|'desc'
	 *     - search: String for fulltext search.
	 *     - field_type: string|array Field type slug(s). Allowed only for Types fields.
	 *     - group_id: int Field group ID where this field belongs to. Allowed only for Types fields.
	 *     - group_slug: string Slug of an existing firld group where this field belongs to. If defined, overrides
	 *           the group_id argument. Allowed only for Types fields.
	 *
	 * @return Toolset_Field_Definition_Abstract[] Field definitions that match query arguments.
	 *
	 * @since 1.9
	 */
	public function query_definitions( $args );


	/**
	 * Permanently delete field definition.
	 *
	 * That means:
	 * - remove it from all field groups,
	 * - delete field data from the database (sic!) and
	 * - delete the definition itself.
	 *
	 * After calling this method, the field definition object passed as parameter should never be used again.
	 *
	 * @param Toolset_Field_Definition_Abstract $field_definiton
	 *
	 * @return bool
	 */
	public function delete_definition( Toolset_Field_Definition_Abstract $field_definiton );


	/**
	 * Update existing field definition.
	 *
	 * @param Toolset_Field_Definition_Abstract $field_definition
	 *
	 * @throws InvalidArgumentException
	 * @return bool True when the update was successful, false otherwise.
	 * @since 2.0
	 */
	public function update_definition( Toolset_Field_Definition_Abstract $field_definition );


	/**
	 * Temporary workaround to access field definitions on a very deep level.
	 *
	 * @param $field_slug
	 * @param $definition_array
	 * @deprecated Do not use, it will be removed.
	 * @since 2.1
	 */
	public function set_field_definition_workaround( $field_slug, $definition_array );

	/**
	 * Temporary workaround to access field definitions on a very deep level.
	 * Do not use, it will be removed.
	 *
	 * @return string
	 * @deprecated Do not use, it will be removed.
	 * @since 2.1
	 */
	public function get_option_name_workaround();
}
