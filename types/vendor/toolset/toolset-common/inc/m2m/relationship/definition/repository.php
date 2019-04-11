<?php

/**
 * Factory class for relationship definitions.
 *
 * Use as a singleton in production code.
 *
 * All relationship definitions are stored in a form of definition arrays in a single option.
 * When this class is instantiated, they will be all loaded at once.
 *
 * After making changes to relationship definitions, those must be persisted by calling save_definitions().
 *
 * TODO Lot of things here can be optimized now that we store definitions in their own table.
 *
 * @since m2m
 */
class Toolset_Relationship_Definition_Repository {


	/** @var null|Toolset_Relationship_Definition_Repository */
	private static $instance = null;


	/**
	 * @return Toolset_Relationship_Definition_Repository
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->load_definitions();
		}
		return self::$instance;
	}


	/** @var Toolset_Relationship_Database_Operations|null */
	private $_database_operations;

	/** @var Toolset_Relationship_Definition_Persistence */
	private $_definition_persistence;

	/** @var Toolset_Relationship_Definition_Translator */
	private $definition_translator;


	public function __construct(
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Definition_Persistence $definition_persistence_di = null,
		Toolset_Relationship_Definition_Translator $definition_translator_di = null
	) {
		$this->_database_operations = $database_operations_di;
		$this->_definition_persistence = $definition_persistence_di;

		$this->definition_translator = (
		null === $definition_translator_di
			? new Toolset_Relationship_Definition_Translator()
			: $definition_translator_di
		);
	}


	/** @var Toolset_Relationship_Definition[] Managed relationship instances. */
	private $definitions;


	/**
	 * Load relationship definitions.
	 *
	 * Never use from outside the class, except testing.
	 *
	 * @since m2m
	 */
	public function load_definitions() {

		$rows = $this->get_database_operations()->load_all_relationships();

		$this->definitions = array();
		foreach( $rows as $row ) {

			$definition = $this->definition_translator->from_database_row( $row );

			if( null != $definition ) {
				$this->add_to_cache( $definition );
			}
		}
	}


	/**
	 * Insert a definition into the array of managed ones.
	 *
	 * @param $definition Toolset_Relationship_Definition
	 * @since m2m
	 */
	private function add_to_cache( $definition ) {
		// We can rely on this, the slug never changes.
		$this->definitions[ $definition->get_slug() ] = $definition;
	}


	/**
	 * Remove a definition from the array of managed ones.
	 *
	 * If it isn't there already, it does nothing.
	 *
	 * @param $definition IToolset_Relationship_Definition|string Definition itself or its slug.
	 *
	 * @param bool $do_cleanup true to delete related associations,
	 *     intermediary post type and the intermediary post field group, if they exist.
	 *
	 * @return Toolset_Result_Set
	 * @since m2m
	 */
	public function remove_definition( $definition, $do_cleanup = true ) {
		/**@var Toolset_Result */
		$toolset_results = array();
		if( ! $definition instanceof Toolset_Relationship_Definition ) {
			if( ! is_string( $definition ) || ! $this->definition_exists( $definition ) ) {
				throw new InvalidArgumentException( 'Relationship definition doesn\'t exist.' );
			}

			$definition = $this->get_definition( $definition );
		}

		$slug = $definition->get_slug();

		do_action( 'toolset_before_delete_relationship', $slug );

		// fixme: abstract this away
		if( $do_cleanup ) {
			// delete associations of relationship
			$toolset_results[] = $this->get_database_operations()->delete_associations_by_relationship( $definition->get_row_id() );

			$intermediary_post_type = $definition->get_intermediary_post_type();
			if( null !== $intermediary_post_type ) {
				$post_type_repository = Toolset_Post_Type_Repository::get_instance();
				$intermediary_post_type = $post_type_repository->get( $intermediary_post_type );
				if( $intermediary_post_type instanceof IToolset_Post_Type_From_Types ) {
					$group_factory = Toolset_Field_Group_Post_Factory::get_instance();
					$groups = $group_factory->get_groups_by_post_type( $intermediary_post_type->get_slug() );
					foreach( $groups as $group ) {
						wp_delete_post( $group->get_id() );
					}

					$post_type_repository->delete( $intermediary_post_type );
				}
			}
		}
		unset( $this->definitions[ $slug ] );
		$this->get_definition_persistence()->delete_definition( $definition );

		$toolset_results[] = new Toolset_Result( true, sprintf( __( 'Relationship "%s" has been deleted.', 'wpcf' ), $slug ) );

		// No "after_delete_relationship" action as long as we have to save_relationships() manually. This can change in the future.

		return new Toolset_Result_Set( $toolset_results );
	}


	/**
	 * Get all relationship definitions.
	 *
	 * @return IToolset_Relationship_Definition[]
	 */
	public function get_definitions() {
		return $this->definitions;
	}


	/**
	 * Determine if a relationship definition with a given slug exists.
	 *
	 * @param string $slug
	 * @return bool
	 * @since m2m
	 */
	public function definition_exists( $slug ) {
		return array_key_exists( $slug, $this->definitions );
	}


	/**
	 * Get a relationship definition with given slug.
	 *
	 * @param string $slug
	 * @return null|IToolset_Relationship_Definition
	 * @since m2m
	 */
	public function get_definition( $slug ) {
		return ( $this->definition_exists( $slug ) ? $this->definitions[ $slug ] : null );
	}


	/**
	 * Get a relationship definition with a given row ID.
	 *
	 * @param int $row_id
	 *
	 * @return null|Toolset_Relationship_Definition
	 */
	public function get_definition_by_row_id( $row_id ) {
		foreach( $this->definitions as $definition ) {
			if( (int) $row_id === (int) $definition->get_row_id() ) {
				return $definition;
			}
		}

		return null;
	}


	/**
	 * Create a new definition, persist it in the database and start managing it.
	 *
	 * @param string $slug Valid (sanitized) relationship slug.
	 * @param Toolset_Relationship_Element_Type $parent Parent entity type.
	 * @param Toolset_Relationship_Element_Type $child Child entity type.
	 *
	 * @param bool $allow_slug_adjustment
	 *
	 * @return IToolset_Relationship_Definition
	 * @since m2m
	 * @since 2.5.5 persists the relationship in the database.
	 */
	public function create_definition( $slug, $parent, $child, $allow_slug_adjustment = true ) {
		if( $slug != sanitize_title( $slug ) ) {
			throw new InvalidArgumentException( 'Poorly sanitized relationship definition slug.' );
		}
		if( ! $parent instanceof Toolset_Relationship_Element_Type ) {
			throw new InvalidArgumentException( 'Invalid parent entity type.' );
		}
		if( ! $child instanceof Toolset_Relationship_Element_Type ) {
			throw new InvalidArgumentException( 'Invalid child entity type.' );
		}
		if( $this->definition_exists( $slug ) ) {
			// If we're allowed to adjust the slug, we'll generate an unique one.
			if( $allow_slug_adjustment ) {
				$naming_helper = Toolset_Naming_Helper::get_instance();
				$slug = $naming_helper->generate_unique_slug( $slug, null, Toolset_Naming_Helper::DOMAIN_RELATIONSHIPS );
			} else {
				throw new InvalidArgumentException( 'Definition slug already taken.' );
			}
		}

		$definition_array = array(
			Toolset_Relationship_Definition::DA_SLUG => $slug,
			Toolset_Relationship_Definition::DA_DRIVER => Toolset_Relationship_Definition::DRIVER_NATIVE,
			Toolset_Relationship_Definition::DA_PARENT_TYPE => $parent->get_definition_array(),
			Toolset_Relationship_Definition::DA_CHILD_TYPE => $child->get_definition_array(),
			Toolset_Relationship_Definition::DA_IS_ACTIVE => true
		);

		$new_definition = new Toolset_Relationship_Definition( $definition_array );

		// The definition will be augmented when inserting (with IDs)
		$persisted_definition = $this->get_definition_persistence()->insert_definition( $new_definition );

		$this->add_to_cache( $persisted_definition );

		return $persisted_definition;
	}

	/**
	 * Creates a definition for the Post Reference Field
	 *
	 * @param $field_slug
	 * @param $field_group_slug
	 * @param $post_reference_type
	 * @param $parent
	 * @param $child
	 *
	 * @return IToolset_Relationship_Definition
	 * @since m2m
	 */
	public function create_definition_post_reference_field( $field_slug, $field_group_slug, $post_reference_type, $parent, $child ) {
		return $this->create_definition(
			$field_slug,
			$parent,
			$child,
			false
		);
	}


	/**
	 * Persist all relationship definitions in the database.
	 *
	 * @deprecated Use persist_definition() only on the relationship that has been changed.
	 * @since m2m
	 */
	public function save_definitions() {
		foreach( $this->definitions as $definition ) {
			$this->persist_definition( $definition );
		}
	}

	/**
	 * Update a single relationship definition.
	 *
	 * @param Toolset_Relationship_Definition $relationship_definition
	 * @since 2.5.2
	 */
	public function persist_definition( Toolset_Relationship_Definition $relationship_definition ) {
		$this->get_definition_persistence()->persist_definition( $relationship_definition );
	}


	/**
	 * Look for a relationship between posts that was migrated from the legacy post relationships.
	 *
	 * @param $parent_post_type
	 * @param $child_post_type
	 *
	 * @return IToolset_Relationship_Definition|null Relationship definition or null if none exists.
	 * @since m2m
	 *
	 * todo This can be optimized greatly by extending Toolset_Relationship_Query
	 */
	public function get_legacy_definition( $parent_post_type, $child_post_type ) {

		$query = new Toolset_Relationship_Query(
			array(
				Toolset_Relationship_Query::QUERY_IS_LEGACY => true,
				Toolset_Relationship_Query::QUERY_HAS_TYPE => array(
					'domain' => Toolset_Field_Utils::DOMAIN_POSTS,
					'type' => $parent_post_type
				)
			)
		);

		$result_candidates = $query->get_results();

		// Find the specific match. There should be only one.
		foreach( $result_candidates as $relationship_definition ) {
			$candidate_parent_types = $relationship_definition->get_parent_type()->get_types();
			$candidate_parent_type = array_pop( $candidate_parent_types );

			$candidate_child_types = $relationship_definition->get_child_type()->get_types();
			$candidate_child_type = array_pop( $candidate_child_types );

			if( $candidate_parent_type === $parent_post_type && $candidate_child_type === $child_post_type ) {
				return $relationship_definition;
			}
		}

		return null;
	}


	/**
	 * Rename the relationship definition slug properly.
	 *
	 * Ensure that:
	 * - the database integrity is maintained
	 * - the cache in this repository is updated
	 *
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param string $new_slug
	 *
	 * @return Toolset_Result
	 *
	 * @since m2m
	 */
	public function change_definition_slug( $relationship_definition, $new_slug ) {
		if( ! $relationship_definition instanceof Toolset_Relationship_Definition ) {
			throw new InvalidArgumentException();
		}

		$slug_validator = new Toolset_Relationship_Slug_Validator( $new_slug, $relationship_definition );

		$slug_validation_result = $slug_validator->validate();
		if( $slug_validation_result->is_error() ) {
			return $slug_validation_result;
		}

		// Update the definition instance
		$previous_slug = $relationship_definition->get_slug();
		$relationship_definition->set_slug( $new_slug );

		// Remove old definition from cache
		unset( $this->definitions[ $previous_slug ] );

		// Add updated definition to cache
		$this->add_to_cache( $relationship_definition );

		// Store changes to db
		$this->persist_definition( $relationship_definition );

		return new Toolset_Result(
			true,
			sprintf(
				__( 'Relationship slug was successfully renamed from "%s" to "%s".', 'wpcf' ),
				$previous_slug,
				$new_slug
			)
		);
	}


	private function get_database_operations() {
		if( null === $this->_database_operations ) {
			$this->_database_operations = new Toolset_Relationship_Database_Operations();
		}

		return $this->_database_operations;
	}


	private function get_definition_persistence() {
		if( null === $this->_definition_persistence ) {
			$this->_definition_persistence = new Toolset_Relationship_Definition_Persistence();
		}

		return $this->_definition_persistence;
	}

}
