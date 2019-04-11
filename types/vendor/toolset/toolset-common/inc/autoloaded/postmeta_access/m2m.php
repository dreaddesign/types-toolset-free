<?php

/**
 * Hijack add/update/get_post_meta native functions for legacy relationships converted to m2m.
 *
 * This assumes m2m is enabled.
 *
 * See Toolset_Postmeta_Access_Loader for details.
 *
 * @since m2m
 */
class Toolset_Postmeta_Access_M2m {

	const KEY_START = '_wpcf_belongs_';
	const KEY_START_LENGTH = 14;

	const KEY_END = '_id';
	const KEY_END_LENGTH = 3;

	private $legacy_relationships = null;

	private $array_filter_args = null;

	public function initialize() {
		$types_has_legacy_relationships_condition = new Toolset_Condition_Plugin_Types_Has_Legacy_Relationships();
		$types_has_legacy_relationships = $types_has_legacy_relationships_condition->is_met();

		if (
			! $types_has_legacy_relationships
			/**
			 * Force the initialization of this class.
			 *
			 * @since m2m
			 */
			&& ! apply_filters( 'toolset_force_legacy_relationships_meta_access', false )
		) {
			return;
		}

		/**
		 * Init the legacy postmeta access hooks, at init:11 so Types can register its post types at init:10.
		 *
		 * @since m2m
		 */
		add_action( 'init', array( $this, 'init' ), 11 );
	}

	/**
	 * Init the legacy postmeta access hooks.
	 *
	 * Note that this legacy layer is only available after init:11 since Types post types are registered at init:10.
	 *
	 * @since m2m
	 */
	public function init() {
		do_action( 'toolset_do_m2m_full_init' );

		// Cache the legacy relationships, and deactivate the hooks above if needed.
		$this->set_legacy_relationships();
		if ( ! empty( $this->legacy_relationships ) ) {
			// Hijack the native set/update/get posteta functions
			add_action( 'add_post_meta', array( $this, 'add_post_meta' ), 10, 3 );
			add_action( 'update_post_meta', array( $this, 'update_post_meta' ), 10, 4 );
			add_filter( 'get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
			add_filter( 'toolset_postmeta_access_m2m_get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
		}

		// Deactivate this class hijcking in case there is nothing to hijack
		add_action( 'toolset_deactivate_postmeta_access_m2m', array( $this, 'deactivate_postmeta_access_m2m' ) );
	}

	/**
	 * Transform a postmeta adding action into an association creation.
	 *
	 * @param $object_id int ID of the object metadata is for
	 * @param @meta_key string Metadata key
	 * @param $_meta_value mixed Metadata value
	 *
	 * @since m2m
	 */
	public function add_post_meta( $object_id, $meta_key, $_meta_value ) {
		// It has to run update_post_meta because the IPT could exist and needs to be deleted.
		$this->update_post_meta( null, $object_id, $meta_key, $_meta_value );
	}

	/**
	 * Transform a postmeta updating action into an association updating.
	 *
	 * @param $meta_id int ID of the metadata entry to update
	 * @param $object_id int ID of the object metadata is for
	 * @param @meta_key string Metadata key
	 * @param $_meta_value mixed Metadata value
	 *
	 * @since m2m
	 */
	public function update_post_meta( $meta_id, $object_id, $meta_key, $_meta_value ) {
		$parent_type = $this->get_parent_type_from_meta_key( $meta_key );
		if ( ! $parent_type ) {
			return;
		}

		if ( ! Toolset_Utils::is_nonnegative_numeric( $_meta_value ) ) {
			return;
		}

		$child_post_type = get_post_type( $object_id );
		$matching_relationships = $this->get_legacy_relationship( $parent_type, $child_post_type );

		if ( null === $matching_relationships ) {
			return;
		}

		$current_relationship = key( $matching_relationships );
		$relationship_definition = isset( $this->legacy_relationships[ $current_relationship ] )
			? $this->legacy_relationships[ $current_relationship ]['definition']
			: null;

		if ( null === $relationship_definition ) {
			return;
		}

		$current_association = $this->get_association( $current_relationship, $object_id );

		if ( $current_association instanceof Toolset_Association ) {
			if ( $_meta_value === $current_association->get_element_id( Toolset_Relationship_Role::PARENT ) ) {
				// There is an association already for those two items, so do nothing else.
				return;
			}
			$relationship_driver = $relationship_definition->get_driver();
			// Delete the previously existing association between child and any parent in the known relationship, if any.
			$relationship_driver->delete_association( $current_association );
		}

		// Create an association between child and parent in the known relationship.
		// It should be one2many, so no intermediary fields are required.
		try {
			$relationship_definition->create_association( $_meta_value, $object_id );
		} catch( Exception $e ) {

		}
	}

	/**
	 * Transform a postmeta getting action into an association get.
	 *
	 * @param $return ull|array|string The value to return
	 * @param $object_id int ID of the object metadata is for
	 * @param @meta_key string Metadata key
	 * @param $single bool Whether to return only the first value of the specified $meta_key
	 *
	 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @since m2m
	 */
	public function get_post_metadata( $return, $object_id, $meta_key, $single ) {
		$parent_type = $this->get_parent_type_from_meta_key( $meta_key );
		if ( ! $parent_type ) {
			return $return;
		}

		$child_post_type = get_post_type( $object_id );
		$matching_relationships = $this->get_legacy_relationship( $parent_type, $child_post_type );

		if ( null === $matching_relationships ) {
			return $return;
		}

		$current_relationship = key( $matching_relationships );
		$parent_id = $this->get_association_parent_id( $current_relationship, $object_id );

		if ( null === $parent_id ) {
			return $single
				? ''
				: array();
		}

		return $single
			? $parent_id
			: array( $parent_id );
	}

	/**
	 * Remove the hooks to postmeta functions, on demand.
	 * Fired here when after getting legacy relationships, there is none.
	 *
	 * @since m2m
	 */
	public function deactivate_postmeta_access_m2m() {
		remove_action( 'add_post_meta', array( $this, 'add_post_meta' ), 10, 3 );
		remove_action( 'update_post_meta', array( $this, 'update_post_meta' ), 10, 4 );
		remove_filter( 'get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
		remove_filter( 'toolset_postmeta_access_m2m_get_post_metadata', array( $this, 'get_post_metadata' ), 10, 4 );
	}

	/**
	 * Check whether a given postmeta key matches the _wpcf_belongs_{slug}_id structure,
	 * and return the parent post type slug if available.
	 *
	 * @param $meta_key string The meta key
	 *
	 * @return bool|string False when the meta key does not match the pattern, the parent type slug otherwise.
	 *
	 * @since m2m
	 */
	private function get_parent_type_from_meta_key( $meta_key ) {
		if ( substr( $meta_key, 0, self::KEY_START_LENGTH ) !== self::KEY_START ) {
			return false;
		}

		if ( substr( $meta_key, - self::KEY_END_LENGTH ) !== self::KEY_END ) {
			return false;
		}

		$parent_type = substr( $meta_key, self::KEY_START_LENGTH );
		$parent_type = substr( $parent_type, 0, - self::KEY_END_LENGTH );
		return $parent_type;
	}

	/**
	 * Cache the existing legacy relationships on this site.
	 *
	 * @note It deactivates the hijacking of postmeta functions if no legacy relationships are found.
	 *
	 * @since m2m
	 */
	private function set_legacy_relationships() {
		if ( null !== $this->legacy_relationships ) {
			return;
		}

		$relationship_query = new Toolset_Relationship_Query_V2();
		$definitions = $relationship_query
			->add( $relationship_query->is_legacy( true ) )
			->get_results();


		if ( empty( $definitions ) ) {
			$this->legacy_relationships = array();
		}

		foreach ( $definitions as $definition ) {
			$this->legacy_relationships[ $definition->get_slug() ] = array(
				'parent' => $definition->get_parent_type()->get_types(),
				'child' => $definition->get_child_type()->get_types(),
				'definition' => $definition
			);
		}
	}

	/**
	 * Get a legacy relationship by its parent and child post types.
	 *
	 * @param $parent_type string The parent post type slug
	 * @param $child_type string The child post type slug
	 *
	 * @return null|array The list of matching legacy relationships, or null otherwise.
	 *
	 * @since m2m
	 */
	private function get_legacy_relationship( $parent_type, $child_type ) {
		if ( empty( $this->legacy_relationships ) ) {
			return null;
		}

		$this->array_filter_args = array(
			'parent' => $parent_type,
			'child' => $child_type
		);

		$matching_relationships = array_filter( $this->legacy_relationships, array( $this, 'filter_by_roles' ) );

		$this->array_filter_args = null;

		if ( empty( $matching_relationships ) ) {
			return null;
		}

		return $matching_relationships;
	}

	/**
	 * Helper to filter an array of relationships by its parent and child post types.
	 *
	 * @param $relationship array
	 *
	 * @return bool
	 *
	 * @since m2m
	 */
	private function filter_by_roles( $relationship ) {
		return (
			in_array( $this->array_filter_args['parent'], $relationship['parent'] )
			&& in_array( $this->array_filter_args['child'], $relationship['child'] )
		);
	}

	/**
	 * Get a specific association given a child, and a relationship.
	 * Works because legacy relationships are one-to-many, hence the child can only have one associated parent.
	 *
	 * @param $elationship_slug string The relationship slug
	 * @param $child_id int The child ID
	 *
	 * @return null|Toolset_Association
	 *
	 * @since m2m
	 */
	private function get_association( $relationshup_slug, $child_id ) {
		$association_query = new Toolset_Association_Query_V2();
		$associations = $association_query
			->add( $association_query->relationship_slug( $relationshup_slug ) )
			->limit( 1 )
			->add( $association_query->element_id_and_domain( $child_id, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() ) )
			->get_results();

		if ( empty( $associations ) ) {
			return null;
		}
		return reset( $associations );
	}

	/**
	 * Get a specific association parent ID given a child, and a relationship.
	 * Works because legacy relationships are one-to-many, hence the child can only have one associated parent.
	 *
	 * @param $elationship_slug string The relationship slug
	 * @param $child_id int The child ID
	 *
	 * @return null|int
	 *
	 * @since m2m
	 */
	private function get_association_parent_id( $relationshup_slug, $child_id ) {
		$association_query = new Toolset_Association_Query_V2();
		$associations_ids = $association_query
			->add( $association_query->relationship_slug( $relationshup_slug ) )
			->return_element_ids( new Toolset_Relationship_Role_Parent() )
			->limit( 1 )
			->add( $association_query->element_id_and_domain( $child_id, Toolset_Element_Domain::POSTS, new Toolset_Relationship_Role_Child() ) )
			->get_results();

		if ( empty( $associations_ids ) ) {
			return null;
		}
		return reset( $associations_ids );
	}

}
