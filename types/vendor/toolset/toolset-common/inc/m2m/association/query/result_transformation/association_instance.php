<?php

/**
 * Transform association query results into instances of IToolset_Association.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Result_Transformation_Association_Instance
	implements IToolset_Association_Query_Result_Transformation {


	/** @var Toolset_Association_Translator */
	private $association_translator;


	private $wpml_service;


	/**
	 * Toolset_Association_Query_Result_Transformation_Association_Instance constructor.
	 *
	 * @param Toolset_Association_Translator|null $association_translator_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service
	 * @param Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default|null $is_current_language_default_di
	 */
	public function __construct(
		Toolset_Association_Translator $association_translator_di = null,
		Toolset_WPML_Compatibility $wpml_service = null,
		Toolset_Condition_Plugin_Wpml_Is_Current_Language_Default $is_current_language_default_di = null
	) {
		$this->wpml_service = ( null === $wpml_service ? Toolset_WPML_Compatibility::get_instance() : $wpml_service );
		$this->association_translator = ( null === $association_translator_di ? new Toolset_Association_Translator() : $association_translator_di );
	}


	/**
	 * @inheritdoc
	 *
	 * @param object $database_row
	 *
	 * @return IToolset_Association
	 */
	public function transform( $database_row, IToolset_Association_Query_Element_Selector $element_selector ) {

		try {
			if (
				$this->wpml_service->is_wpml_active_and_configured()
				&& $this->wpml_service->get_current_language() !== $this->wpml_service->get_default_language()
			) {
				// There's a chance of having element translations among the results. Let's try.
				return $this->transform_with_wpml( $database_row, $element_selector );
			}

			return $this->transform_without_wpml( $database_row, $element_selector );
		} catch( Toolset_Element_Exception_Element_Doesnt_Exist $e ) {
			return null;
		}
	}


	/**
	 * Transform the database row to an association instance if we know that there are no
	 * element translations involved.
	 *
	 * @param object $database_row
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 * @return IToolset_Association
	 */
	private function transform_without_wpml( $database_row, IToolset_Association_Query_Element_Selector $element_selector ) {
		$id_column_map = array();
		foreach( Toolset_Relationship_Role::all() as $role ) {
			$id_column_map[ $role->get_name() ] = $element_selector->get_element_id_alias( $role );
		}

		$association = $this->association_translator->from_database_row( $database_row, $id_column_map );

		return $association;

	}


	/**
	 * Transform the database row to an association instance if there may be element translations.
	 *
	 * @param object $database_row
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return IToolset_Association
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	private function transform_with_wpml( $database_row, IToolset_Association_Query_Element_Selector $element_selector ) {

		// The map must contain: role --> language code --> name of the column with element ID.
		$id_column_map = array();

		foreach( Toolset_Relationship_Role::all() as $role ) {

			$default_language = $this->wpml_service->get_default_language();
			$default_language_element_id_alias = $element_selector->get_element_id_alias( $role, false );

			if ( $element_selector->has_element_id_translated( $role ) ) {
				$current_language = $this->wpml_service->get_current_language();
				$current_language_element_id_alias = $element_selector->get_element_id_alias( $role, true );

				if( $database_row->$current_language_element_id_alias !== $database_row->$default_language_element_id_alias ) {
					// We finally have two different element IDs - the default language and its translation.
					$id_column_map[ $role->get_name() ] = array(
						$current_language => $current_language_element_id_alias,
						$default_language => $default_language_element_id_alias
					);
					continue;
				}
			}

			// If we fall through to this point, there is only one (default) language version.
			$id_column_map[ $role->get_name() ] = array(
				$default_language => $default_language_element_id_alias,
			);
		}

		$association = $this->association_translator->from_translated_database_row(
			$database_row, $id_column_map
		);

		return $association;

	}


	/**
	 * Talk to the element selector so that it includes only elements that are actually needed.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return void
	 * @since 2.5.10
	 */
	public function request_element_selection( IToolset_Association_Query_Element_Selector $element_selector ) {
		// We totally need the association and relationship ID:
		$element_selector->request_association_and_relationship_in_results();

		// Request all element IDs so that we can instantiate the association object.
		foreach( Toolset_Relationship_Role::all() as $role ) {
			$element_selector->request_element_in_results( $role );
		}
	}

}