<?php

/**
 * Translates between a database row from the toolset_relationships table and a relationship definition.
 *
 * This is the only place when such process is supposed to take place.
 *
 * Never use this class outside of the m2m API.
 *
 * @since 2.5.2
 */
class Toolset_Relationship_Definition_Translator {


	private $definition_factory;


	public function __construct( Toolset_Relationship_Definition_Factory $definition_factory_di = null ) {
		$this->definition_factory = (
			null === $definition_factory_di
				? new Toolset_Relationship_Definition_Factory()
				: $definition_factory_di
		);
	}


	/**
	 * Convert a relationship definition into a database row
	 *
	 * @param Toolset_Relationship_Definition $definition
	 * @return array
	 * @since m2m
	 */
	public function to_database_row( $definition ) {

		$defintion_array = $definition->get_definition_array();

		$row = array(
			'slug' => $definition->get_slug(),
			'display_name_plural' => $definition->get_display_name(),
			'display_name_singular' => $definition->get_display_name_singular(),
			'driver' => $defintion_array[ Toolset_Relationship_Definition::DA_DRIVER ],
			'parent_domain' => $definition->get_parent_type()->get_domain(),
			'parent_types' => $definition->get_element_type_set_id( Toolset_Relationship_Role::PARENT ),
			'child_domain' => $definition->get_child_type()->get_domain(),
			'child_types' => $definition->get_element_type_set_id( Toolset_Relationship_Role::CHILD ),
			'intermediary_type' => $definition->get_driver()->get_setup( Toolset_Relationship_Driver::DA_INTERMEDIARY_POST_TYPE, '' ),
			'ownership' => ( null == $definition->get_owner() ? 'none' : $definition->get_owner() ),
			'cardinality_parent_max' => $definition->get_cardinality()->get_parent( Toolset_Relationship_Cardinality::MAX ),
			'cardinality_parent_min' => $definition->get_cardinality()->get_parent( Toolset_Relationship_Cardinality::MIN ),
			'cardinality_child_max' => $definition->get_cardinality()->get_child( Toolset_Relationship_Cardinality::MAX ),
			'cardinality_child_min' => $definition->get_cardinality()->get_child( Toolset_Relationship_Cardinality::MIN ),
			'is_distinct' => ( $definition->is_distinct() ? 1 : 0 ),
			'scope' => maybe_serialize( $definition->has_scope() ? $definition->get_scope()->get_scope_data() : '' ),
			'origin' => $definition->get_origin()->get_origin_keyword(),
			'role_name_parent' => $definition->get_role_name( Toolset_Relationship_Role::PARENT ),
			'role_name_child' => $definition->get_role_name( Toolset_Relationship_Role::CHILD ),
			'role_name_intermediary' => $definition->get_role_name( Toolset_Relationship_Role::INTERMEDIARY ),
			'role_label_parent_singular' => $definition->get_role_label_singular( Toolset_Relationship_Role::PARENT ),
			'role_label_child_singular' => $definition->get_role_label_singular( Toolset_Relationship_Role::CHILD ),
			'role_label_parent_plural' => $definition->get_role_label_plural( Toolset_Relationship_Role::PARENT ),
			'role_label_child_plural' => $definition->get_role_label_plural( Toolset_Relationship_Role::CHILD ),
			'needs_legacy_support' => ( $definition->needs_legacy_support() ? 1 : 0 ),
			'is_active' => ( $definition->is_active() ? 1 : 0 ),
		);

		return $row;
	}


	/**
	 * Convert a row from the relationships table into a relationship definition array.
	 *
	 * @param $row
	 * @return array
	 * @since m2m
	 */
	private function from_database_row_to_definition_array( $row ) {

		$definition_array = array(
			Toolset_Relationship_Definition::DA_ROW_ID => $row->id,
			Toolset_Relationship_Definition::DA_SLUG => $row->slug,
			Toolset_Relationship_Definition::DA_DISPLAY_NAME_PLURAL => $row->display_name_plural,
			Toolset_Relationship_Definition::DA_DISPLAY_NAME_SINGULAR => $row->display_name_singular,
			Toolset_Relationship_Definition::DA_DRIVER => $row->driver,
			Toolset_Relationship_Definition::DA_PARENT_TYPE => array(
				Toolset_Relationship_Element_Type::DA_DOMAIN => $row->parent_domain,
				Toolset_Relationship_Element_Type::DA_TYPES => explode( Toolset_Relationship_Database_Operations::GROUP_CONCAT_DELIMITER, $row->parent_types ),
			),
			Toolset_Relationship_Definition::DA_CHILD_TYPE => array(
				Toolset_Relationship_Element_Type::DA_DOMAIN => $row->child_domain,
				Toolset_Relationship_Element_Type::DA_TYPES => explode( Toolset_Relationship_Database_Operations::GROUP_CONCAT_DELIMITER, $row->child_types ),
			),
			Toolset_Relationship_Definition::DA_PARENT_TYPE_SET_ID => (int) $row->parent_types_set_id,
			Toolset_Relationship_Definition::DA_CHILD_TYPE_SET_ID => (int) $row->child_types_set_id,
			Toolset_Relationship_Definition::DA_CARDINALITY => array(
				Toolset_Relationship_Role::PARENT => array(
					Toolset_Relationship_Cardinality::MAX => (int) $row->cardinality_parent_max,
					Toolset_Relationship_Cardinality::MIN => (int) $row->cardinality_parent_min
				),
				Toolset_Relationship_Role::CHILD => array(
					Toolset_Relationship_Cardinality::MAX => (int) $row->cardinality_child_max,
					Toolset_Relationship_Cardinality::MIN => (int) $row->cardinality_child_min
				),
			),
			Toolset_Relationship_Definition::DA_DRIVER_SETUP => array(
				Toolset_Relationship_Driver::DA_INTERMEDIARY_POST_TYPE => $row->intermediary_type
			),
			Toolset_Relationship_Definition::DA_OWNERSHIP => $row->ownership,
			Toolset_Relationship_Definition::DA_IS_DISTINCT => (bool) $row->is_distinct,
			Toolset_Relationship_Definition::DA_SCOPE => maybe_unserialize( $row->scope ),
			Toolset_Relationship_Definition::DA_ROLE_NAMES => array(
				Toolset_Relationship_Role::PARENT => $row->role_name_parent,
				Toolset_Relationship_Role::CHILD => $row->role_name_child,
				Toolset_Relationship_Role::INTERMEDIARY => $row->role_name_intermediary,
			),
			Toolset_Relationship_Definition::DA_ROLE_LABELS_SINGULAR => array(
				Toolset_Relationship_Role::PARENT => $row->role_label_parent_singular,
				Toolset_Relationship_Role::CHILD => $row->role_label_child_singular,
			),
			Toolset_Relationship_Definition::DA_ROLE_LABELS_PLURAL => array(
				Toolset_Relationship_Role::PARENT => $row->role_label_parent_plural,
				Toolset_Relationship_Role::CHILD => $row->role_label_child_plural,
			),
			Toolset_Relationship_Definition::DA_NEEDS_LEGACY_SUPPORT => (bool) $row->needs_legacy_support,
			Toolset_Relationship_Definition::DA_IS_ACTIVE => (bool) $row->is_active,
			Toolset_Relationship_Definition::DA_ORIGIN => maybe_unserialize( $row->origin ),
		);

		return $definition_array;
	}


	/**
	 * Load a single relationship definition from a definition array.
	 *
	 * @param object $database_row
	 * @return null|Toolset_Relationship_Definition The relationship definition or null if it was not
	 *     possible to load it (which means that the definition array was invalid).
	 * @since m2m
	 */
	public function from_database_row( $database_row ) {
		$definition_array = $this->from_database_row_to_definition_array( $database_row );
		$definition = $this->definition_factory->create( $definition_array );
		return $definition;
	}


	/**
	 * Get an array of formats for $wpdb when working with the database row generated by this class.
	 *
	 * @return string[]
	 */
	public function get_database_row_formats() {
		return array(
			'%s', // slug
			'%s', // display_name_plural
			'%s', // display_name_singular
			'%s', // driver
			'%s', // parent_domain
			'%d', // parent_types
			'%s', // child_domain
			'%d', // child_types
			'%s', // intermediary_type
			'%d', // ownership
			'%d', // cardinality_parent_max
			'%d', // cardinality_parent_min
			'%d', // cardinality_child_max
			'%d', // cardinality_child_min
			'%d', // is_distinct
			'%s', // scope
			'%s', // origin
			'%s', // role_name_parent
			'%s', // role_name_child
			'%s', // role_name_intermediary
			'%s', // role_name_parent_singular
			'%s', // role_name_child_singular
			'%s', // role_name_parent_plural
			'%s', // role_name_child_plural
			'%d', // needs_legacy_support
			'%d', // is_active
		);
	}


}
