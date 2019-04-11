<?php

/**
 * Translate the association data between the IToolset_Association model and a database row.
 *
 * @since 2.5.9
 */
class Toolset_Association_Translator {

	/** @var Toolset_Relationship_Definition_Repository|null */
	private $_definition_repository;

	/** @var Toolset_Association_Factory|null */
	private $_association_factory;

	/** @var null|Toolset_Element_Factory */
	private $_element_factory;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/**
	 * Toolset_Association_Translator constructor.
	 *
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository_di
	 * @param Toolset_Association_Factory|null $association_factory_di
	 * @param Toolset_Element_Factory|null $element_factory_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		Toolset_Relationship_Definition_Repository $definition_repository_di = null,
		Toolset_Association_Factory $association_factory_di = null,
		Toolset_Element_Factory $element_factory_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		$this->_definition_repository = $definition_repository_di;
		$this->_association_factory = $association_factory_di;
		$this->wpml_service = ( null === $wpml_service_di ? Toolset_WPML_Compatibility::get_instance() : $wpml_service_di );
		$this->_element_factory = $element_factory_di;
	}


	private function get_definition_repository() {
		if( null === $this->_definition_repository ) {
			$this->_definition_repository = Toolset_Relationship_Definition_Repository::get_instance();
		}

		return $this->_definition_repository;
	}


	private function get_association_factory() {
		if( null === $this->_association_factory ) {
			$this->_association_factory = new Toolset_Association_Factory();
		}

		return $this->_association_factory;
	}



	/** @noinspection PhpDocRedundantThrowsInspection */
	/**
	 * @param object $database_row Object returned from the wpdb->get_results() query.
	 * @param null|array $id_column_map Allows for overriding the names of the columns used to
	 *    access element IDs. If not null, this must contain a map of columns for all three roles.
	 *
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @return IToolset_Association
	 */
	public function from_database_row( $database_row, $id_column_map = null ) {
		$relationship_definition = $this->get_definition_repository()->get_definition_by_row_id( $database_row->relationship_id );

		if( null === $id_column_map ) {
			$id_column_map = array(
				Toolset_Relationship_Role::PARENT => 'parent_id',
				Toolset_Relationship_Role::CHILD => 'child_id',
				Toolset_Relationship_Role::INTERMEDIARY => 'intermediary_id',
			);
		}

		return $this->get_association_factory()->create(
			$relationship_definition,
			(int) $database_row->{$id_column_map[ Toolset_Relationship_Role::PARENT ]},
			(int) $database_row->{$id_column_map[ Toolset_Relationship_Role::CHILD ]},
			(int) $database_row->{$id_column_map[ Toolset_Relationship_Role::INTERMEDIARY ]},
			(int) $database_row->id
		);
	}


	/**
	 * Translate a database row to an association instance if element translations are available.
	 *
	 * @param object $database_row
	 * @param array $id_column_map Nested associative array with:
	 *     role --> language code --> name of the column with the element ID.
	 *     In the database row, the IDs may be zero for translated (non-default language) parent
	 *     or child or any intermediary posts.
	 *
	 * @return IToolset_Association
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public function from_translated_database_row( $database_row, $id_column_map ) {
		$relationship_definition = $this->get_definition_repository()->get_definition_by_row_id( $database_row->relationship_id );

		$association = $this->get_association_factory()->create(
			$relationship_definition,
			$this->get_element_in_all_languages( $database_row, $id_column_map, $relationship_definition, new Toolset_Relationship_Role_Parent() ),
			$this->get_element_in_all_languages( $database_row, $id_column_map, $relationship_definition, new Toolset_Relationship_Role_Child() ),
			$this->get_element_in_all_languages( $database_row, $id_column_map, $relationship_definition, new Toolset_Relationship_Role_Intermediary() ),
			(int) $database_row->id
		);

		return $association;
	}


	/**
	 * Get an element which contains all the available language information.
	 *
	 * @param $database_row
	 * @param array $id_column_map Map as described in from_translated_database_row().
	 * @param IToolset_Relationship_Definition $relationship_definition
	 * @param IToolset_Relationship_Role $for_role
	 *
	 * @return IToolset_Element|IToolset_Post|int Zero can be returned if there is no
	 *     intermediary post at all.
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function get_element_in_all_languages(
		$database_row,
		$id_column_map,
		IToolset_Relationship_Definition $relationship_definition,
		IToolset_Relationship_Role $for_role
	) {
		$element_ids = array();
		foreach( $id_column_map[ $for_role->get_name() ] as $language => $column ) {
			$element_id = (int) $database_row->{$column};
			if( 0 !== $element_id ) {
				$element_ids[ $language ] = $element_id;
			}
		}

		if( empty( $element_ids ) ) {
			// This can happen for an intermediary post - no element to instantiate, and
			// the association will survive.
			return 0;
		}

		$element = $this->get_element_factory()->get_element(
			$relationship_definition->get_element_type( $for_role )->get_domain(),
			$element_ids
		);

		return $element;
	}


	/**
	 * @param IToolset_Association $association
	 * @throws RuntimeException
	 * @return array Database row as an associative array.
	 */
	public function to_database_row( IToolset_Association $association ) {
		$row = array(
			'relationship_id' => $association->get_definition()->get_row_id(),
			'parent_id' => $this->get_default_language_element_id( $association, new Toolset_Relationship_Role_Parent() ),
			'child_id' => $this->get_default_language_element_id( $association, new Toolset_Relationship_Role_Child() ),
			'intermediary_id' => $this->get_default_language_element_id( $association, new Toolset_Relationship_Role_Intermediary() ),
		);

		return $row;
	}


	/**
	 * Obtain an element ID from the association for the purpose of saving it into database.
	 *
	 * If WPML is active, load the elements and make sure we're getting the ID
	 * of the default language version of the element.
	 *
	 * @param IToolset_Association $association
	 * @param IToolset_Relationship_Role $role
	 * @throws RuntimeException
	 *
	 * @return int
	 */
	private function get_default_language_element_id(
		IToolset_Association $association,
		IToolset_Relationship_Role $role
	) {
		if( ! $this->wpml_service->is_wpml_active_and_configured() ) {
			return $association->get_element_id( $role );
		}

		$element = $association->get_element( $role );

		if( null === $element ) {
			// Intermediary post.
			return 0;
		}

		$translation = $element->translate( $this->wpml_service->get_default_language(), true );

		if( null === $translation ) {
			throw new RuntimeException(
				'The default language version of an element involved in an association is missing.'
			);
		}

		return $translation->get_id();
	}


	/**
	 * @return string[] Column formats for columns as returned by to_database_row().
	 */
	public function get_database_row_formats() {
		return array( '%d', '%d', '%d', '%d' );
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
}