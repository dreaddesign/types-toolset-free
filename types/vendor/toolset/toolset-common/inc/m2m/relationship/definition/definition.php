<?php

/**
 * Relationship definition.
 *
 * Besides acting as a data model, it also intermediates the relationship driver.
 *
 * Here is the only code that understands the relationship definition array (specifically, read_definition_array()
 * and get_definition_array()), which should never ever be used or accessed anywhere else (except loading and
 * saving in the factory class).
 *
 * All instances of this class should be managed exclusively by methods of the
 * Toolset_Relationship_Definition_Repository class.
 *
 * @since m2m
 */
class Toolset_Relationship_Definition implements IToolset_Relationship_Definition {


	// Relationship properties.
	//
	// Invariant: This data is to be kept sanitized at all times.
	// Even private methods should use getters instead of accessing properties directly.
	// Read getters' description for more detailed information.

	/** @var int */
	private $row_id;

	/** @var string */
	private $slug;

	/** @var string */
	private $display_name_plural;

	/** @var string */
	private $display_name_singular;

	/** @var string */
	private $driver_name;

	/** @var Toolset_Relationship_Element_Type */
	private $parent_type;

	/** @var Toolset_Relationship_Element_Type */
	private $child_type;

	/** @var int */
	private $parent_type_set_id;

	/** @var int */
	private $child_type_set_id;

	/** @var Toolset_Relationship_Cardinality  */
	private $cardinality;

	/** @var array Driver-specific configuration. To be used only for driver initialization. */
	private $driver_setup;

	/** @var bool */
	private $is_distinct;

	/** @var null|Toolset_Relationship_Scope */
	private $scope;

	/** @var  IToolset_Relationship_Origin */
	private $origin;

	/** @var string[] */
	private $role_names;

	/** @var string[] */
	private $role_labels_singular;

	/** @var string[] */
	private $role_labels_plural;

	/** @var string[] */
	private $role_aliases;

	/** @var bool */
	private $is_legacy_support_needed;

	/** @var bool */
	private $is_active;

	/**
	 * @var string|null Determines whether this relationship is an ownership and its direction.
	 *
	 * - 'parent': Delete children when the parent is deleted.
	 * - 'child': Delete parents when the child is deleted.
	 * - null: Not an ownership.
	 *
	 * This will be taken into account only for one-to-many relationships.
	 */
	private $ownership;


	// Definition array keys
	const DA_SLUG = 'slug';
	const DA_DISPLAY_NAME_PLURAL = 'display_name_plural';
	const DA_DISPLAY_NAME_SINGULAR = 'display_name_singular';
	const DA_DRIVER = 'driver';
	const DA_PARENT_TYPE = 'parent';
	const DA_CHILD_TYPE = 'child';
	const DA_PARENT_TYPE_SET_ID = 'parent_type_set_id';
	const DA_CHILD_TYPE_SET_ID = 'child_type_set_id';
	const DA_CARDINALITY = 'cardinality';
	const DA_DRIVER_SETUP = 'driver_setup';
	const DA_OWNERSHIP = 'ownership';
	const DA_IS_DISTINCT = 'is_distinct';
	const DA_SCOPE = 'scope';
	const DA_ROLE_NAMES = 'role_names';
	const DA_ROLE_LABELS_SINGULAR = 'role_labels_singular';
	const DA_ROLE_LABELS_PLURAL = 'role_labels_plural';
	const DA_NEEDS_LEGACY_SUPPORT = 'needs_legacy_support';
	const DA_IS_ACTIVE = 'is_active';
	const DA_ORIGIN = 'origin';
	const DA_ROW_ID = 'row_id';

	// Supported relationship driver names.
	// At the moment, only the native Toolset relationships are supported.
	const DRIVER_NATIVE = 'toolset';

	const OWNER_IS_PARENT = 'parent';
	const OWNER_IS_CHILD = 'child';


	/**
	 * Default plural role names
	 *
	 * @var string[]
	 * @since m2m
	 */
	private $default_role_labels_plural = array(
		Toolset_Relationship_Role::PARENT => 'Parents',
		Toolset_Relationship_Role::CHILD => 'Children',
	);

	/**
	 * Default singular role names
	 *
	 * @var string[]
	 * @since m2m
	 */
	private $default_role_labels_singular = array(
		Toolset_Relationship_Role::PARENT => 'Parent',
		Toolset_Relationship_Role::CHILD => 'Child',
	);

	/** @var Toolset_Potential_Association_Query_Factory */
	private $potential_association_query_factory;


	/**
	 * Toolset_Relationship_Definition constructor.
	 *
	 * @param array $definition_array Valid definition array.
	 * @param Toolset_Potential_Association_Query_Factory|null $potential_association_query_factory_di
	 *
	 * @since m2m
	 */
	public function __construct( $definition_array, Toolset_Potential_Association_Query_Factory $potential_association_query_factory_di = null ) {
		$this->read_definition_array( $definition_array );

		$this->potential_association_query_factory = ( null === $potential_association_query_factory_di ? new Toolset_Potential_Association_Query_Factory() : $potential_association_query_factory_di );
	}


	/**
	 * Sanitize and fill properties with data from the definition array.
	 *
	 * @param array $definition_array
	 * @throws InvalidArgumentException when invalid data is provided.
	 * @since m2m
	 */
	private function read_definition_array( $definition_array ) {

		$this->slug = sanitize_title( toolset_getarr( $definition_array, self::DA_SLUG ) );

		$this->row_id = (int) toolset_getarr( $definition_array, self::DA_ROW_ID );

		if( empty( $this->slug ) ) {
			throw new InvalidArgumentException( 'Invalid or missing slug.' );
		}

		// Use slug as a default display name value.
		$this->set_display_name( toolset_getarr( $definition_array, self::DA_DISPLAY_NAME_PLURAL, $this->slug ) );
		$this->set_display_name_singular( toolset_getarr( $definition_array, self::DA_DISPLAY_NAME_SINGULAR, $this->slug ) );

		$this->driver_name = toolset_getarr( $definition_array, self::DA_DRIVER, null, array( self::DRIVER_NATIVE ) );
		if( null == $this->driver_name ) {
			throw new InvalidArgumentException( 'Unsupported relationship driver' );
		}

		// All we know is that it's an array that will be passed to the driver when instantiating it.
		// From that point it's driver's business.
		$this->driver_setup = toolset_ensarr( toolset_getarr( $definition_array, self::DA_DRIVER_SETUP, null ) );

		$this->parent_type = new Toolset_Relationship_Element_Type( toolset_getarr( $definition_array, self::DA_PARENT_TYPE ) );

		$this->child_type = new Toolset_Relationship_Element_Type( toolset_getarr( $definition_array, self::DA_CHILD_TYPE ) );

		// Defaults to "infinity"
		$cardinality_da = toolset_getarr(
			$definition_array,
			self::DA_CARDINALITY,
			array(
				Toolset_Relationship_Role::PARENT => Toolset_Relationship_Cardinality::INFINITY,
				Toolset_Relationship_Role::CHILD => Toolset_Relationship_Cardinality::INFINITY
			)
		);

		$this->cardinality = new Toolset_Relationship_Cardinality( $cardinality_da );

		$this->ownership = toolset_getarr(
			$definition_array,
			self::DA_OWNERSHIP,
			null,
			array( self::OWNER_IS_CHILD, self::OWNER_IS_PARENT, null )
		);

		// The owner can be only one in the relationship
		if( null != $this->ownership ) {
			$owner_cardinality_limit = $this->get_cardinality()->get_limit( $this->ownership, Toolset_Relationship_Cardinality::MAX );
			if ( 1 != $owner_cardinality_limit ) {
				$this->ownership = null;
			}
		}

		$this->is_distinct = (bool) toolset_getarr( $definition_array, self::DA_IS_DISTINCT, false );

		try {
			$scope_data = toolset_getarr( $definition_array, self::DA_SCOPE, null );
			if( null != $scope_data ) {
				$this->scope = new Toolset_Relationship_Scope( $scope_data, $this );
			}
		} catch( Exception $e ) {
			// Can't read scope data, default to no scope.
		}

		$role_types_and_sanitize = array(
			self::DA_ROLE_NAMES => 'sanitize_title',
			self::DA_ROLE_LABELS_SINGULAR => 'sanitize_text_field',
			self::DA_ROLE_LABELS_PLURAL => 'sanitize_text_field'
		);
		foreach ( $role_types_and_sanitize as $role_type => $sanitize) {
			$role_names_definition = toolset_getarr( $definition_array, $role_type );

			// Workaround to support PHP 5.3 to 7.*:
			// - can't do $this->$role_type[ $role ] = ... because it's ambiguous in PHP 7.0
			// - can't do ($this->$role_type)[ $role ] = ... because PHP 5.3 considers it a syntax error
			$role_names = array();

			foreach( Toolset_Relationship_Role::all_role_names() as $role ) {
				// For each existing role, we will have a key with a custom name slug that should be recognized
				// in shortcodes, etc. Default value is also the role name.
				$role_names[ $role ] = $sanitize(
					toolset_getarr( $role_names_definition, $role, $this->get_default_role_name( $role, $role_type ), 'is_string' )
				);
			}
			$this->$role_type = $role_names;
		}

		$this->is_legacy_support_needed = (bool) toolset_getarr( $definition_array, self::DA_NEEDS_LEGACY_SUPPORT, false );

		$this->is_active( toolset_getarr( $definition_array, self::DA_IS_ACTIVE, true ) );

		$this->set_origin( toolset_getarr( $definition_array, self::DA_ORIGIN, Toolset_Relationship_Origin_Wizard::ORIGIN_KEYWORD ) );

		$this->parent_type_set_id = (int) toolset_getarr( $definition_array, self::DA_PARENT_TYPE_SET_ID );
		$this->child_type_set_id = (int) toolset_getarr( $definition_array, self::DA_CHILD_TYPE_SET_ID );
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_slug() { return $this->slug; }


	/**
	 * Update the relationship slug.
	 *
	 * The usage of this method is strictly limited to the m2m API, always change the slug via
	 * Toolset_Relationship_Definition_Repository::change_definition_slug().
	 *
	 * At the very least, it is assumed that the new slug value is validated via Toolset_Relationship_Slug_Validator.
	 *
	 * @param string $new_slug
	 *
	 * @since m2m
	 */
	public function set_slug( $new_slug ) {
		$this->slug = sanitize_title( $new_slug );
	}

	/**
	 * @inheritdoc
	 *
	 * @return string
	 * @since m2m
	 */
	public function get_display_name() { return $this->get_display_name_plural(); }


	/**
	 * @inheritdoc
	 *
	 * @param string $display_name
	 * @since m2m
	 */
	public function set_display_name( $display_name ) {
		$this->display_name_plural = sanitize_text_field( $display_name );
	}


    /**
     * Synonymous to get_display_name().
     *
     * @return string
     * @since m2m
     */
	public function get_display_name_plural() { return $this->display_name_plural; }


    /**
     * Get the singular display name of the relationship.
     *
     * @return string
     * @since m2m
     */
	public function get_display_name_singular() { return $this->display_name_singular; }


	/**
	 * Update the relationship singular display name.
	 *
	 * @param string $display_name
	 * @since m2m
	 */
	public function set_display_name_singular( $display_name ) {
		$this->display_name_singular = sanitize_text_field( $display_name );
	}



	/**
	 * Get the name of the relationship driver.
	 *
	 * Allowed values are the DEFINITION_* constants.
	 *
	 * @return string
	 * @since m2m
	 */
	private function get_driver_name() { return $this->driver_name; }


	/**
	 * Get the parent entity type definition.
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public function get_parent_type() { return $this->parent_type; }


	/**
	 * Get the child entity type definition.
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public function get_child_type() { return $this->child_type; }


	public function get_parent_domain() {
		$type = $this->get_parent_type();
		return $type->get_domain();
	}


	public function get_child_domain() {
		$type = $this->get_child_type();
		return $type->get_domain();
	}


	/**
	 * @param string|IToolset_Relationship_Role $element_role
	 *
	 * @return string
	 */
	public function get_domain( $element_role ) {
		return $this->get_element_type( $element_role )->get_domain();
	}



	/**
	 * Get a relationship entity type.
	 *
	 * @param string|IToolset_Relationship_Role $element_role
	 *
	 * @return Toolset_Relationship_Element_Type
	 * @since m2m
	 */
	public function get_element_type( $element_role ) {
		switch( $this->role_to_role_name( $element_role ) ) {
			case Toolset_Relationship_Role::CHILD:
				return $this->get_child_type();
			case Toolset_Relationship_Role::PARENT:
				return $this->get_parent_type();
			case Toolset_Relationship_Role::INTERMEDIARY:
				return Toolset_Relationship_Element_Type::build_for_post_type(
					$this->get_intermediary_post_type()
				);
			default:
				throw new InvalidArgumentException();
		}
	}


	/**
	 * Get an set_id that references type slugs in the toolset_type_sets table for a given role.
	 * Obviously, never use this outside of m2m API.
	 *
	 * @param IToolset_Relationship_Role_Parent_Child|string $element_role
	 *
	 * @return int Set ID or zero if the type set is not persisted yet.
	 */
	public function get_element_type_set_id( $element_role ) {
		switch( $this->role_to_role_name( $element_role ) ) {
			case Toolset_Relationship_Role::PARENT:
				return $this->parent_type_set_id;
			case Toolset_Relationship_Role::CHILD:
				return $this->child_type_set_id;
			default:
				throw new InvalidArgumentException();
		}
	}


	/**
	 * Set type of a relationship role (parent or child).
	 *
	 * Must not be used outside m2m API.
	 *
	 * @param string|IToolset_Relationship_Role $element_role
	 * @param Toolset_Relationship_Element_Type $type
	 * @since m2m
	 */
	public function set_element_type( $element_role, Toolset_Relationship_Element_Type $type ) {
		switch( $this->role_to_role_name( $element_role ) ) {
			case Toolset_Relationship_Role::PARENT:
				$this->parent_type = $type;
				break;
			case Toolset_Relationship_Role::CHILD:
				$this->child_type = $type;
				break;
			default:
				throw new InvalidArgumentException();
		}
	}


	/**
	 * Determine if there are posts on the given side of the relationship.
	 *
	 * @param string|IToolset_Relationship_Role $element_role
	 * @return bool
	 * @since m2m
	 */
	public function is_post( $element_role ) {
		return ( Toolset_Element_Domain::POSTS == $this->get_element_type( $element_role )->get_domain() );
	}


	/**
	 * Build a definition array for persisting the definition.
	 *
	 * @return array
	 * @since m2m
	 */
	public function get_definition_array() {

		return array(
			self::DA_SLUG => $this->get_slug(),
			self::DA_DRIVER => $this->get_driver_name(),
			self::DA_DRIVER_SETUP => $this->get_driver()->get_setup(),
			self::DA_PARENT_TYPE => $this->get_parent_type()->get_definition_array(),
			self::DA_CHILD_TYPE => $this->get_child_type()->get_definition_array(),
			self::DA_CARDINALITY => $this->get_cardinality()->get_definition_array(),
			self::DA_OWNERSHIP => $this->get_owner(),
			self::DA_IS_DISTINCT => $this->is_distinct(),
			self::DA_SCOPE => ( $this->has_scope() ? $this->get_scope()->get_scope_data() : null ),
			self::DA_ROLE_NAMES => $this->get_role_names(),
			self::DA_ROLE_LABELS_SINGULAR => $this->get_role_labels_singular(),
			self::DA_ROLE_LABELS_PLURAL => $this->get_role_labels_plural(),
			self::DA_DISPLAY_NAME_PLURAL => $this->get_display_name_plural(),
			self::DA_DISPLAY_NAME_SINGULAR => $this->get_display_name_singular()
		);
	}


	/** @var Toolset_Relationship_Driver_Base|null */
	private $driver = null;


	/**
	 * Get the relationship driver. Initialize it if called for the first time.
	 *
	 * @return Toolset_Relationship_Driver
	 * @since m2m
	 */
	public function get_driver() {

		if( null === $this->driver ) {
			switch( $this->get_driver_name() ) {
				case self::DRIVER_NATIVE:
					$this->driver = new Toolset_Relationship_Driver( $this, $this->driver_setup );
					break;
				default:
					// fail miserably
					//
					// But really - this should never happen because we have a validation
					// in read_definition_array().
					throw new RuntimeException( 'Unsupported relationship driver.' );
			}
		}

		return $this->driver;
	}


	public function get_cardinality() {
		return $this->cardinality;
	}


	/**
	 * Update the relationship cardinality.
	 *
	 * @param Toolset_Relationship_Cardinality $value
	 * @throws InvalidArgumentException
	 * @since m2m
	 */
	public function set_cardinality( $value ) {
		if( ! $value instanceof Toolset_Relationship_Cardinality ) {
			throw new InvalidArgumentException();
		}

		$this->cardinality = $value;
	}


	/**
	 * Check if this relationship has some association fields defined.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function has_association_field_definitions() {
		return $this->get_driver()->has_field_definitions();
	}


	/**
	 * Get definitions of association fields.
	 *
	 * @return Toolset_Field_Definition[]
	 * @since m2m
	 */
	public function get_association_field_definitions() {
		return $this->get_driver()->get_field_definitions();
	}


	/**
	 * Get the intermediary post type, if it exists.
	 *
	 * Note that its existence doesn't necessarily mean that there are association fields.
	 *
	 * @return null|string
	 * @since m2m
	 */
	public function get_intermediary_post_type() {
		$driver = $this->get_driver();
		if( ! $driver instanceof Toolset_Relationship_Driver ) {
			return null;
		}

		return $driver->get_intermediary_post_type();
	}


	public function is_ownership() {
		return ( null != $this->ownership );
	}


	public function get_owner() {
		if( in_array( $this->ownership, array( self::OWNER_IS_CHILD, self::OWNER_IS_PARENT ) ) ) {
			return $this->ownership;
		}

		return 'none';
	}


	/**
	 * @param IToolset_Element|IToolset_Element[] $parent_or_elements
	 * @param IToolset_Element|null $child
	 *
	 * @return bool
	 * @throws InvalidArgumentException
	 * @since m2m
	 * @since 2.5.7 Deprecated.
	 * @deprecated Use IToolset_Potential_Association_Query::check_single_element() instead.
	 */
	public function can_associate( $parent_or_elements, $child = null ) {

		_doing_it_wrong(
			__FUNCTION__,
			'Toolset_Relationship_Definition::can_associate() is replaced by IToolset_Potential_Association_Query::check_single_element()',
			'2.5.7'
		);

		if( $parent_or_elements instanceof Toolset_Element ) {
			$elements = array(
				Toolset_Relationship_Role::PARENT => $parent_or_elements,
				Toolset_Relationship_Role::CHILD => $child
			);
		} elseif( is_array( $parent_or_elements ) ) {
			$elements = $parent_or_elements;
		} else {
			throw new InvalidArgumentException( 'Invalid argument - wrong element types in can_associate().');
		}

		$potential_association = $this->potential_association_query_factory->create(
			$this,
			new Toolset_Relationship_Role_Child(),
			$elements[ Toolset_Relationship_Role::PARENT ]
		);

		$result = $potential_association->check_single_element( $elements[ Toolset_Relationship_Role::CHILD ] );

		if( ! $result->is_success() ) {
			return false;
		}

		return true;
	}


	/**
	 * Creates an association of this relationship between two elements.
	 *
	 * So far, only native relationships are supported. In their case, an intermediary post is created automatically,
	 * if the relationship requires it.
	 *
	 * @param int|WP_Post|Toolset_Element $parent Parent element (of matching domain, type and other conditions)
	 * @param int|WP_Post|Toolset_Element $child Child element (of matching domain, type and other conditions)
	 *
	 * @return Toolset_Result|IToolset_Association The newly created association or a negative Toolset_Result when it could not have been created.
	 * @throws RuntimeException when the association cannot be created because of a known reason. The exception would
	 *     contain a displayable error message.
	 * @throws InvalidArgumentException when the method is used improperly.
	 *
	 * @since m2m
	 */
	public function create_association( $parent, $child ) {

		$driver = $this->get_driver();
		if( ! $driver instanceof Toolset_Relationship_Driver ) {
			throw new RuntimeException( 'Not implemented!' );
		}

		$association = Toolset_Relationship_Database_Operations::create_association( $this, $parent, $child, 0 );

		return $association;
	}


	/**
	 * Determine or set whether the relationship is distinct, which means that only one association between
	 * each two elements can exist.
	 *
	 * @param null|bool $new_value If a boolean value is provided, it will be set.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function is_distinct( $new_value = null ) {
		if( null !== $new_value ) {
			$this->is_distinct = (bool) $new_value;
		}
		return $this->is_distinct;
	}


	/**
	 * Determine whether this relationship has a scope defined.
	 *
	 * @return bool
	 */
	public function has_scope() {
		return ( $this->scope instanceof Toolset_Relationship_Scope );
	}


	/**
	 * @return null|Toolset_Relationship_Scope
	 */
	public function get_scope() {
		return $this->scope;
	}


	/** @var null|bool Cache for is_translatable(). */
	private $is_translatable = null;


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
	public function is_translatable() {
		if( null === $this->is_translatable ) {

			$this->is_translatable = (
				$this->get_parent_type()->is_translatable()
				|| $this->get_child_type()->is_translatable()
				|| $this->get_driver()->has_translatable_fields()
			);
		}

		return $this->is_translatable;
	}


	/**
	 * Get a custom role name that should be recognized in shortcodes instead of parent, child, etc.
	 *
	 * @param string|IToolset_Relationship_Role $element_role One of the Toolset_Relationship_Role values.
	 * @return string Custom role name.
	 * @since m2m
	 */
	public function get_role_name( $element_role ) {
		$element_role = $this->role_to_role_name( $element_role );
		if( ! Toolset_Relationship_Role::is_valid( $element_role ) ) {
			throw new InvalidArgumentException();
		}

		return $this->role_names[ $element_role ];
	}


	/**
	 * Get a custom role singular name that should be recognized in shortcodes instead of parent, child, etc.
	 *
	 * @param string|IToolset_Relationship_Role $element_role One of the Toolset_Relationship_Role values.
	 * @return string Custom role name.
	 * @since m2m
	 */
	public function get_role_label_singular( $element_role ) {
		$element_role = $this->role_to_role_name( $element_role );
		if( ! Toolset_Relationship_Role::is_valid( $element_role ) ) {
			throw new InvalidArgumentException();
		}

		return isset( $this->role_labels_singular[ $element_role ] )
			? $this->role_labels_singular[ $element_role ]
			: $this->default_role_labels_singular[ $element_role ];
	}


	/**
	 * Get a custom role plural name that should be recognized in shortcodes instead of parent, child, etc.
	 *
	 * @param string|IToolset_Relationship_Role $element_role One of the Toolset_Relationship_Role values.
	 * @return string Custom role name.
	 * @since m2m
	 */
	public function get_role_label_plural( $element_role ) {
		$element_role = $this->role_to_role_name( $element_role );
		if( ! Toolset_Relationship_Role::is_valid( $element_role ) ) {
			throw new InvalidArgumentException();
		}

		return isset( $this->role_labels_plural[ $element_role ] )
			? $this->role_labels_plural[ $element_role ]
			: $this->default_role_labels_plural[ $element_role ];
	}

	/**
	 * Get all custom role names as an associative array.
	 *
	 * @return string[string]
	 * @since m2m
	 */
	public function get_role_names() { return $this->role_names; }


	/**
	 * Get all custom role singular names as an associative array.
	 *
	 * @return string[string]
	 * @since m2m
	 */
	public function get_role_labels_singular() {
		return $this->role_labels_singular;
	}


	/**
	 * Get all custom role plural names as an associative array.
	 *
	 * @return string[string]
	 * @since m2m
	 */
	public function get_role_labels_plural() {
		return $this->role_labels_plural;
	}

	/**
	 * Determine the default custom name for a role.
	 *
	 * Note: In the future, this might take into account the types of related elements as well as the
	 * slug of the intermediary post type, if one exists.
	 *
	 * @param string $role
	 * @param string $role_type Grammatical type: plural or singular
	 *
	 * @return string
	 * @since m2m
	 */
	private function get_default_role_name( $role, $role_type = self::DA_ROLE_NAMES ) {
		if( in_array( $role, Toolset_Relationship_Role::parent_child_role_names() ) ) {
			switch ( $role_type ) {
				case self::DA_ROLE_LABELS_SINGULAR:
					return $this->default_role_labels_singular[ $role ];
				case self::DA_ROLE_LABELS_PLURAL:
					return $this->default_role_labels_plural[ $role ];
				default:
					return $role;
			}
		} elseif( Toolset_Relationship_Role::INTERMEDIARY === $role ) {
			return 'association';
		}

		throw new InvalidArgumentException();
	}


	/**
	 * Lists default aliases by role type
	 *
	 * @return array
	 * @since m2m
	 */
	public function get_default_labels() {
		$aliases = array();
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role ) {
			$aliases[ $role ] = array(
				'name' => $role,
			);
			foreach ( array( self::DA_ROLE_LABELS_SINGULAR, self::DA_ROLE_LABELS_PLURAL ) as $role_type ) {
				$aliases[ $role ][ $role_type ] = $this->get_default_role_name( $role, $role_type );
			}
		}
		return $aliases;
	}

	/**
	 * Update a custom role name.
	 *
	 * The name will be sanitized and the value actually saved will be returned.
	 *
	 * @param string|IToolset_Relationship_Role $element_role One of the Toolset_Relationship_Role values.
	 * @param string $custom_name Custom name for the role.
	 *
	 * @return string Sanitized custom name
	 * @since m2m
	 */
	public function set_role_name( $element_role, $custom_name ) {
		$role_name = $this->role_to_role_name( $element_role );
		if( ! Toolset_Relationship_Role::is_valid( $role_name ) ) {
			throw new InvalidArgumentException();
		}

		$sanitized_custom_name = sanitize_title( $custom_name );
		$this->role_names[ $role_name ] = $sanitized_custom_name;

		return $sanitized_custom_name;
	}


	/**
	 * Update a custom role singular name.
	 *
	 * The name will be sanitized and the value actually saved will be returned.
	 *
	 * @param string|IToolset_Relationship_Role $element_role One of the Toolset_Relationship_Role values.
	 * @param string $custom_name Custom name for the role.
	 *
	 * @return string Sanitized custom name
	 * @since m2m
	 */
	public function set_role_label_singular( $element_role, $custom_name ) {
		$role_name = $this->role_to_role_name( $element_role );
		if( ! Toolset_Relationship_Role::is_valid( $role_name ) ) {
			throw new InvalidArgumentException();
		}

		$sanitized_custom_name = sanitize_text_field( $custom_name );
		$this->role_labels_singular[ $role_name ] = $sanitized_custom_name;

		return $sanitized_custom_name;
	}


	/**
	 * Update a custom role plural name.
	 *
	 * The name will be sanitized and the value actually saved will be returned.
	 *
	 * @param string|IToolset_Relationship_Role $element_role One of the Toolset_Relationship_Role values.
	 * @param string $custom_name Custom name for the role.
	 *
	 * @return string Sanitized custom name
	 * @since m2m
	 */
	public function set_role_label_plural( $element_role, $custom_name ) {
		$role_name = $this->role_to_role_name( $element_role );
		if( ! Toolset_Relationship_Role::is_valid( $role_name ) ) {
			throw new InvalidArgumentException();
		}

		$sanitized_custom_name = sanitize_text_field( $custom_name );
		$this->role_labels_plural[ $role_name ] = $sanitized_custom_name;

		return $sanitized_custom_name;
	}


	/**
	 * If the relationship was migrated from the legacy post relationships, we need to
	 * provide backward compatibility for it.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function needs_legacy_support() {
		return $this->is_legacy_support_needed;
	}


	/**
	 * Set the status of legacy support requirement.
	 *
	 * This MUST NOT be used anywhere except the migration procedure.
	 *
	 * @param bool $is_legacy_support_needed
	 * @since m2m
	 */
	public function set_legacy_support_requirement( $is_legacy_support_needed ) {
		$this->is_legacy_support_needed = $is_legacy_support_needed;
	}


	/**
	 * Defines whether the relationship is active on the site (whether it should be taken into account at all).
	 *
	 * @param null|bool $value
	 *
	 * @return bool
	 */
	public function is_active( $value = null ) {
		if( null !== $value && is_bool( $value ) ) {
			$this->is_active = (bool) $value;
		}

		return $this->is_active;
	}


	private function role_to_role_name( $role ) {
		if( $role instanceof IToolset_Relationship_Role ) {
			return $role->get_name();
		}

		return $role;
	}

	/**
	 * @return IToolset_Relationship_Origin
	 * @since m2m
	 */
	public function get_origin() {
		return $this->origin;
	}


	/**
	 * Set origin
	 * Can be set by using the origin keyword or the class
	 *
	 * @param IToolset_Relationship_Origin|string  $origin
	 *
	 * @since m2m
	 */
	public function set_origin( $origin ) {
		if( ! $origin instanceof IToolset_Relationship_Origin ) {
			switch( $origin ) {
				case Toolset_Relationship_Origin_Repeatable_Group::ORIGIN_KEYWORD:
					$origin = new Toolset_Relationship_Origin_Repeatable_Group();
					break;
				case Toolset_Relationship_Origin_Post_Reference_Field::ORIGIN_KEYWORD:
					$origin = new Toolset_Relationship_Origin_Post_Reference_Field();
					break;
				default:
					$origin = new Toolset_Relationship_Origin_Wizard();
			}
		}

		$this->origin = $origin;
	}


	/**
	 * Get an ID of the database row where this relationship definition is stored.
	 *
	 * @return int Careful: This can be zero if no ID is available (relationship is not saved yet).
	 */
	public function get_row_id() {
		return $this->row_id;
	}


	/**
	 * Return the number of existing associations belonging to the relationships
	 *
	 * @param string|IToolset_Relationship_Role $role Role.
	 * @return int
	 * @since m2m
	 */
	public function get_max_associations( $role ) {
		$db_operations = Toolset_Relationship_Database_Operations::get_instance();
		$role_name = $this->role_to_role_name( $role );
		return $db_operations->count_max_associations( $this->get_row_id(), $role_name );
	}


	/**
	 * Set default role aliases
	 *
	 * @since m2m
	 */
	public function set_default_role_labels() {
		foreach ( Toolset_Relationship_Role::parent_child_role_names() as $role_name ) {
			$this->set_role_name( $role_name, $role_name );
			$this->set_role_label_singular( $role_name, $this->default_role_labels_singular[ $role_name ] );
			$this->set_role_label_plural( $role_name, $this->default_role_labels_plural[ $role_name ] );
		}
	}
}
