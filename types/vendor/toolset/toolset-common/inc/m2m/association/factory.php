<?php

/**
 * Factory for instantiating IToolset_Association objects.
 *
 * This should not be used from outside
 * of the m2m API. Everything required for working with associations should be
 * implemented on IToolset_Relationship_Definition.
 *
 * @since 2.5.8
 */
class Toolset_Association_Factory {


	/** @var Toolset_Relationship_Definition_Repository */
	private $definition_repository;


	/** @var Toolset_Element_Factory|null */
	private $_element_factory;


	/** @var null|Toolset_WPML_Compatibility */
	private $_wpml_service;


	/**
	 * Toolset_Association_Factory constructor.
	 *
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository_di
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		Toolset_Relationship_Definition_Repository $definition_repository_di = null,
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->definition_repository = ( null === $definition_repository_di ? Toolset_Relationship_Definition_Repository::get_instance() : $definition_repository_di );
		$this->_element_factory = $element_factory_di;
		$this->_wpml_service = $wpml_service_di;
	}


	/**
	 * @param Toolset_Relationship_Definition $relationship
	 * @param int|IToolset_Element $parent_id
	 * @param int|IToolset_Element $child_id
	 * @param int|IToolset_Post $intermediary_id
	 * @param int $association_uid Can be zero for associations that are not stored in the database yet.
	 *
	 * @return IToolset_Association
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function create(
		Toolset_Relationship_Definition $relationship,
		$parent_id, $child_id, $intermediary_id, $association_uid = 0
	) {
		return new Toolset_Association(
			$association_uid,
			$relationship,
			array(
				Toolset_Relationship_Role::PARENT => $parent_id,
				Toolset_Relationship_Role::CHILD => $child_id
			),
			$intermediary_id,
			$this->_wpml_service,
			$this->_element_factory
		);
	}


	/**
	 * @param int $relationship_id
	 * @param int $parent_id
	 * @param int $child_id
	 * @param int $intermediary_id
	 * @param int $association_uid Can be zero for associations that are not stored in the database yet.
	 *
	 * @return IToolset_Association
	 * @throws RuntimeException Thrown if an invalid relationship slug is provided.
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function create_by_relationship_id(
		$relationship_id,
		$parent_id, $child_id, $intermediary_id, $association_uid = 0
	) {
		$relationship = $this->definition_repository->get_definition_by_row_id( $relationship_id );
		if( null === $relationship ) {
			throw new RuntimeException( 'Relationship doesn\'t exist.' );
		}

		return $this->create( $relationship, $parent_id, $child_id, $intermediary_id, $association_uid );
	}

}