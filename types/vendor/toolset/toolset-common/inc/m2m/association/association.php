<?php

/**
 * Translation-unaware m2m association between two elements.
 *
 * This can be used only when the multilingual mode is off/transitional
 *
 * Not to be used directly outside of the m2m API.
 *
 * @since m2m
 */
class Toolset_Association implements IToolset_Association {


	/**
	 * @var int[] IDs of elements indexed by role names, always complete.
	 *
	 * However, in case we deal with translatable elements, this will hold only the IDs
	 * of the default language versions, so these values can't be always used directly.
	 * get_element_id() will handle the translation if necessary.
	 *
	 * If the association has no intermediary post, zero will be stored as its ID.
	 */
	private $element_ids = array();


	/** @var Toolset_Relationship_Definition */
	private $relationship_definition;


	/**
	 * @var Toolset_Element[] Actual elements, loaded on demand. Use self::get_element() to obtain them.
	 */
	protected $elements = array();


	/**
	 * @var int Translation group ID.
	 */
	private $uid;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/** @var Toolset_Element_Factory */
	private $_element_factory;


	/** @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * Toolset_Association constructor.
	 *
	 * Note that no checks about elements with respect to the relationship definition are being performed here.
	 * The caller needs to ensure everything is valid (domains, types, other conditions). This is handled well in the
	 * association factory.
	 *
	 * It is assumed that the relationship definition uses the Toolset_Relationship_Driver driver.
	 *
	 * @param int $uid Unique association ID.
	 * @param Toolset_Relationship_Definition $relationship_definition
	 * @param array $element_sources Associative array with both element keys. Each item can be either an ID
	 *     or a matching Toolset_Element instance.
	 * @param int|Toolset_Post $intermediary_source Intermediary post with association fields or its ID. If a
	 *    Toolset_Post instance is provided, it must have the type matching with the relationship definition.
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @since m2m
	 */
	public function __construct(
		$uid,
		Toolset_Relationship_Definition $relationship_definition,
		$element_sources,
		$intermediary_source,
		Toolset_WPML_Compatibility $wpml_service_di = null,
		Toolset_Element_Factory $element_factory_di = null
	) {
		$this->wpml_service = ( null === $wpml_service_di ? Toolset_WPML_Compatibility::get_instance() : $wpml_service_di );
		$this->_element_factory = ( null === $element_factory_di ? new Toolset_Element_Factory( null, $wpml_service_di ) : $element_factory_di );

		if( ! Toolset_Utils::is_nonnegative_numeric( $uid ) ) {
			throw new InvalidArgumentException();
		}

		$this->relationship_definition = $relationship_definition;
		$this->uid = (int) $uid;

		foreach( Toolset_Relationship_Role::parent_child_role_names() as $element_role ) {
			$element_source = toolset_getarr( $element_sources, $element_role, null );
			$this->store_element( $element_source, $element_role );
		}

		$this->store_element( $intermediary_source, Toolset_Relationship_Role::INTERMEDIARY );
	}


	/**
	 * Understand the element source and store it properly without wasting too much performance.
	 *
	 * Sometimes, the association is getting only element IDs, other times, it will get existing
	 * IToolset_Element instances, or a mix thereof. If possible, we wait with instantiating the
	 * elements as long as possible, to reduce memory and performance requirements.
	 *
	 * However, if WPML is active and there's a risk of some of those IDs or elements being
	 * translated, we need to make sure that we store only element IDs in the default language.
	 * In that case, we will have to instantiate the element and translate it.
	 *
	 * Note: For historical reasons, this will also survive an array with a single item,
	 * which is the actual element source.
	 *
	 * @param int|string|IToolset_Element|array $element_source
	 * @param string $role_name
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function store_element( $element_source, $role_name ) {
		$is_intermediary = Toolset_Relationship_Role::INTERMEDIARY === $role_name;

		if( is_array( $element_source ) && 1 === count( $element_source ) ) {
			$element_source = array_pop( $element_source );
		}

		if(
			$is_intermediary
			&& is_numeric( $element_source )
			&& 0 === (int) $element_source
		) {
			// No intermediary post.
			$this->element_ids[ Toolset_Relationship_Role::INTERMEDIARY ] = 0;
			return;
		}

		if( $this->can_be_translated() ) {
			// There's a chance we might need a translation.
			try {
				if( $element_source instanceof IToolset_Element ) {
					$element = $element_source;
				} else {
					$element = $this->get_element_factory()->get_element(
						$this->get_definition()->get_domain( Toolset_Relationship_Role::role_from_name( $role_name ) ),
						(int) $element_source
					);
				}

				$element_id = $element->translate( $this->wpml_service->get_default_language() )->get_id();

			} catch( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
				// We'll survive only a missing intermediary post.
				if( ! $is_intermediary ) {
					throw $e;
				}

				$element = null;
				$element_id = 0;
			}
		} elseif( $element_source instanceof IToolset_Element ) {
			// No translations from now on.

			$element = $element_source;
			$element_id = $element_source->get_id();
		} elseif( Toolset_Utils::is_natural_numeric( $element_source ) ) {
			$element = null;
			$element_id = (int) $element_source;
		} else {
			throw new InvalidArgumentException( 'Invalid or missing element source.' );
		}

		$this->element_ids[ $role_name ] = $element_id;
		if( null !== $element ) {
			$this->elements[ $role_name ] = $element;
		}
	}


	private function can_be_translated() {
		return (
			$this->wpml_service->is_wpml_active_and_configured()
			&& $this->get_definition()->is_translatable()
		);
	}


	/**
	 * @return Toolset_Relationship_Definition
	 */
	public function get_definition() { return $this->relationship_definition; }


	/**
	 * Get domain of selected association element.
	 *
	 * @param string $element_role
	 *
	 * @return string Valid domain name as defined in Toolset_Field_Utils.
	 * @since m2m
	 */
	protected function get_element_domain( $element_role ) {
		$relationship_definition = $this->get_definition();
		$element_type = $relationship_definition->get_element_type( $element_role );
		return $element_type->get_domain();
	}


	/**
	 * Get an ID of an element in the associaton.
	 *
	 * @param string|IToolset_Relationship_Role $element_role Must be a valid role.
	 *
	 * @return int
	 * @since m2m
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_element_id( $element_role ) {
		$element_role_name = Toolset_Relationship_Role::name_from_role( $element_role );

		if( ! $this->can_be_translated() ) {
			return $this->element_ids[ $element_role_name ];
		}

		// We have to use the actual element because we might have to return
		// a translated ID.
		$element = $this->get_element( $element_role_name );

		if( null === $element ) {
			// Intermediary post which doesn't exist.
			return 0;
		}

		return $element->get_id();
	}


	/**
	 * Get an association element.
	 *
	 * Instantiates an element from its ID if that hasn't been done yet.
	 *
	 * @param string $element_role
	 *
	 * @return IToolset_Element|null
	 * @throws InvalidArgumentException
	 * @since m2m
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function get_element( $element_role ) {
		$element_role_name = Toolset_Relationship_Role::name_from_role( $element_role );

		if(
			Toolset_Relationship_Role::INTERMEDIARY === $element_role_name
			&& 0 === $this->element_ids[ Toolset_Relationship_Role::INTERMEDIARY ]
		) {
			// If we know that there is no intermediary post at all, we'll not even try.
			return null;
		}

		// Load the element if missing.
		if(
			! array_key_exists( $element_role_name, $this->elements )
			|| null === $this->elements[ $element_role_name ]
		) {
			$this->elements[ $element_role_name ] = $this->get_element_factory()->get_element(
				$this->get_element_domain( $element_role_name ),
				$this->get_element_id( $element_role_name )
			);
		}

		return $this->elements[ $element_role_name ];
	}


	/**
	 * Check that the element role is valid.
	 *
	 * @param string $element_role
	 *
	 * @throws InvalidArgumentException
	 * @since m2m
	 * @deprecated Use methods in Toolset_Relationship_Role instead.
	 */
	public static function validate_element_role( $element_role ) {
		if( ! in_array( $element_role, Toolset_Relationship_Role::parent_child_role_names() ) ) {
			throw new InvalidArgumentException( 'Invalid element key.' );
		}
	}


	/**
	 * Shortcut to the relationship driver.
	 *
	 * @return Toolset_Relationship_Driver_Base
	 */
	public function get_driver() {
		$relationship_definition = $this->get_definition();
		return $relationship_definition->get_driver();
	}


	/**
	 * Get the unique identifier for the association.
	 *
	 * An integer value indicates that it's an ID from the associations table.
	 *
	 * It may be zero for associations that are not persisted yet.
	 *
	 * @return int
	 * @since m2m
	 */
	public function get_uid() {
		return $this->uid;
	}


	/**
	 * Get the translation group ID of the association.
	 *
	 * @return int Translation group ID or zero if not supported.
	 * @deprecated Use get_uid() instead.
	 */
	public function get_trid() {
		return $this->uid;
	}



	/**
	 * @inheritdoc
	 * @return null|IToolset_Post
	 */
	protected function get_intermediary_post() {
		/** @var IToolset_Post|null $post */
		$post = $this->get_element( new Toolset_Relationship_Role_Intermediary() );

		return $post;
	}


	/**
	 * @inheritdoc
	 * @return bool|Toolset_Field_Instance[]
	 */
	public function get_fields() {
		if( ! $this->has_fields() ) {
			return false;
		}

		return $this->get_intermediary_post()->get_fields();
	}


	/**
	 * @inheritdoc
	 * @param string|Toolset_Field_Definition $field_source
	 * @return bool|Toolset_Field_Instance
	 */
	public function get_field( $field_source ) {
		if( ! $this->has_fields() ) {
			return false;
		}

		return $this->get_intermediary_post()->get_field( $field_source );
	}


	/**
	 * Get the ID of the intermediary post with association fields.
	 *
	 * Required for the [types] shortcode, but use with consideration.
	 *
	 * @return int Post ID or zero if no post exists.
	 * @since m2m
	 */
	public function get_intermediary_id() {
		if( ! $this->can_be_translated() ) {
			return $this->element_ids[ Toolset_Relationship_Role::INTERMEDIARY ];
		}

		// We have to go through the post object because it might be translated.
		$intermediary_post = $this->get_intermediary_post();
		if( null === $intermediary_post ) {
			return 0;
		}
		return $intermediary_post->get_id();
	}


	/**
	 * @return bool
	 * @since m2m
	 */
	public function has_intermediary_post() {
		return ( 0 !== $this->get_intermediary_id() );
	}


	/**
	 * @return Toolset_Element_Factory
	 */
	private function get_element_factory() {
		if( null === $this->_element_factory ) {
			$this->_element_factory = new Toolset_Element_Factory();
		}

		return $this->_element_factory;
	}


	/**
	 * @inheritdoc
	 *
	 * This needs to be called (internally) before accessing the intermediary post object.
	 *
	 * @return bool
	 * @since m2m
	 */
	public function has_fields() {
		$intermediary_post = $this->get_intermediary_post();

		if ( null === $intermediary_post ) {
			return false;
		}

		return ( $intermediary_post->get_field_count() > 0 );
	}


	/**
	 * @inheritdoc
	 *
	 * @param string|Toolset_Field_Definition $field_source
	 * @return bool
	 */
	public function has_field( $field_source ) {
		if( ! $this->has_fields() ) {
			return false;
		}

		return $this->get_intermediary_post()->has_field( $field_source );
	}

}
