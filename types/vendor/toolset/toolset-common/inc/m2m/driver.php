<?php

/**
 * Native Toolset relationship driver.
 *
 * @since m2m
 */
class Toolset_Relationship_Driver extends Toolset_Relationship_Driver_Base {


	const DA_INTERMEDIARY_POST_TYPE = 'intermediary_post_type';

	/** @var null|Toolset_Field_Definition[] */
	private $association_field_definitions = null;


	/**
	 * Create new native association in the database.
	 *
	 * @param int|Toolset_Element|WP_Post $parent_source
	 * @param int|Toolset_Element|WP_Post $child_source
	 * @param array $args Association arguments:
	 *     - 'intermediary_id': ID of the intermediary post; defaults to zero.
	 *
	 * @return IToolset_Association|Toolset_Result ID of the new association on success or a result information with an
	 *     error.
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function create_association( $parent_source, $child_source, $args = array() ) {

		$relationship_definition = $this->get_relationship_definition();

		// This will throw when the elements don't exist
		$parent = $this->get_element_factory()->get_element( $relationship_definition->get_parent_domain(), $parent_source );
		$child = $this->get_element_factory()->get_element( $relationship_definition->get_child_domain(), $child_source );

		// We need to make sure the association is allowed.
		$potential_association_query = $this->get_potential_association_query_factory()->create(
			$this->get_relationship_definition(),
			new Toolset_Relationship_Role_Child(),
			$parent
		);

		$can_associate_check = $potential_association_query->check_single_element( $child );
		if ( $can_associate_check->is_error() ) {
			return $can_associate_check;
		}

		$intermediary_id = (int) toolset_getarr( $args, 'intermediary_id', 0 );

		// Create intermediary post if doesn't exist.
		if ( 0 === $intermediary_id ) {
			$association_helper = new Toolset_Association_Intermediary_Post_Persistence( $relationship_definition );
			$intermediary_id = (int) $association_helper->create_intermediary_post( $parent->get_id(), $child->get_id() );
		}

		try {
			$association = $this->association_factory->create(
				$relationship_definition,
				$parent->get_id(),
				$child->get_id(),
				$intermediary_id
			);

			$updated_association = $this->association_persistence->insert_association( $association );
		} catch( Exception $e ) {
			return new Toolset_Result(
				false,
				sprintf(
					__( 'An error occurred when creating an association: %s', 'wpcf' ),
					$e->getMessage()
				)
			);
		}

		// Get the association instance (in the best language available)
		$instantiate_association = (bool) toolset_getarr( $args, 'instantiate', false );
		if ( $instantiate_association ) {
			return $updated_association;
		} else {
			return new Toolset_Result( true, __( 'Association created', 'wpcf' ) );
		}

	}


	/**
	 * Get the slug of the indermediary post type that holds association fields.
	 *
	 * @return string|null Post type slug or null if undefined/invalid.
	 * @since m2m
	 */
	public function get_intermediary_post_type() {
		$post_type_slug = $this->get_setup( self::DA_INTERMEDIARY_POST_TYPE );
		if ( ! is_string( $post_type_slug ) || empty( $post_type_slug ) ) {
			return null;
		}

		// todo check that it actually exists

		return $post_type_slug;
	}


	/**
	 * @return IToolset_Post_Type_From_Types|null
	 */
	public function get_intermediary_post_type_object() {
		$post_type_slug = $this->get_intermediary_post_type();

		if( null === $post_type_slug ) {
			return null;
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return Toolset_Post_Type_Repository::get_instance()->get( $post_type_slug );
	}


	/**
	 * Create a new intermediary post type if it doesn't exist yet.
	 *
	 * @param null|string $new_slug_candidate Use this post slug if possible.
	 * @param boolean     $is_public If the intermediary post type is public.
	 * @return string Post type slug.
	 */
	public function create_intermediary_post_type( $new_slug_candidate = null, $is_public = false ) {
		$post_type_repository = Toolset_Post_Type_Repository::get_instance();

		$post_type_slug = $this->get_intermediary_post_type();
		if( null !== $post_type_slug && $post_type_repository->has( $post_type_slug ) ) {
			/** @noinspection PhpParamsInspection */
			$this->set_intermediary_post_type( $post_type_repository->get( $post_type_slug ), $is_public );
			return $post_type_slug;
		}

		$naming_helper = Toolset_Naming_Helper::get_instance();

		$names = array(
			'slug' => ( null === $new_slug_candidate ? $this->get_relationship_slug() : $new_slug_candidate ),
			'label_name' => sprintf(
				__( '%s Intermediary Posts', 'wpcf' ),
				$this->get_relationship_definition()->get_display_name_plural()
			),
			'label_singular_name' => sprintf(
				__( '%s Intermediary Post', 'wpcf' ),
				$this->get_relationship_definition()->get_display_name_singular()
			)
		);

		$filtered_names = apply_filters( 'toolset_new_intermediary_post_type_names', $names );

		$names = wp_parse_args( $filtered_names, $names );
		$post_type_slug = $naming_helper->generate_unique_post_type_slug( $names['slug'] );

		$post_type = $post_type_repository->create(
			$post_type_slug, $names['label_name'], $names['label_singular_name']
		);

		$this->set_intermediary_post_type( $post_type, $is_public );

		$post_type->set_is_public( $is_public );
		$post_type_repository->save( $post_type );

		return $post_type_slug;
	}


	/**
	 * Set the intermediary post type for a relationship.
	 *
	 * Also update the "is_intermediary" flag in the new and previous type (if they exist).
	 *
	 * @param IToolset_Post_Type_From_Types|null $post_type Post type or null to unlink an intermediary post type.
	 * @param boolean                            $is_public If the intermediary post type is public.
	 */
	public function set_intermediary_post_type( IToolset_Post_Type_From_Types $post_type = null, $is_public = false ) {

		$post_type_repository = Toolset_Post_Type_Repository::get_instance();

		if ( null === $post_type ) {
			$new_post_type_slug = '';
		} else {
			$new_post_type_slug = $post_type->get_slug();
		}

		$previous_post_type = $this->get_intermediary_post_type_object();
		if( null !== $previous_post_type && $previous_post_type->get_slug() !== $new_post_type_slug ) {
			$previous_post_type->unset_as_intermediary();
			$post_type_repository->save( $previous_post_type );
		}

		if( null !== $post_type ) {
			$post_type->set_as_intermediary( $is_public );
			$post_type_repository->save( $post_type );
		}

		$this->set_setup_argument( self::DA_INTERMEDIARY_POST_TYPE, $new_post_type_slug );
	}


	/**
	 * @inheritdoc
	 *
	 * @return Toolset_Field_Definition[]
	 * @since m2m
	 */
	public function get_field_definitions() {

		if ( null === $this->association_field_definitions ) {

			$intermediary_post_type = $this->get_intermediary_post_type();

			if ( null == $intermediary_post_type ) {
				$this->association_field_definitions = array();
			} else {
				$this->association_field_definitions = Toolset_Field_Utils::get_field_definitions_for_post_type( $intermediary_post_type );
			}
		}

		return $this->association_field_definitions;
	}


	/**
	 * @inheritdoc
	 *
	 * In the context of native Toolset relationships, the association fields are translatable when the intermediary
	 * post type is translatable.
	 *
	 * @return bool
	 */
	public function has_translatable_fields() {
		return $this->has_field_definitions() && Toolset_Wpml_Utils::is_post_type_translatable( $this->get_intermediary_post_type() );
	}


	/**
	 * Delete an association from the database.
	 *
	 * Also delete an intermediary post if it exists.
	 *
	 * @param Toolset_Association|IToolset_Association $association
	 *
	 * @return Toolset_Result
	 *
	 * @deprecated Use Toolset_Association_Persistence::delete_association() instead.
	 * @since m2m
	 */
	public function delete_association( $association ) {
		return $this->association_persistence->delete_association( $association );
	}

}
