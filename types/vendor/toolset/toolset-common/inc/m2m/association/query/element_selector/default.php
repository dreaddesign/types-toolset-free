<?php

/**
 * Trivial element selector that works in the standard mode,
 * if there is no need to translate anything.
 *
 * @since 2.5.10
 */
class Toolset_Association_Query_Element_Selector_Default
	extends Toolset_Association_Query_Element_Selector_Abstract {


	/**
	 * @inheritdoc
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $translate_if_possible
	 *
	 * @return string
	 */
	public function get_element_id_alias(
		IToolset_Relationship_Role $for_role, $translate_if_possible = true
	) {
		return $this->get_id_column( $for_role );
	}


	/**
	 * @inheritdoc
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $translate_if_possible
	 *
	 * @return string
	 */
	public function get_element_id_value(
		IToolset_Relationship_Role $for_role, $translate_if_possible = true
	) {
		return $this->get_element_id_alias( $for_role );
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_select_clauses() {
		$results = $this->maybe_get_association_and_relationship();
		foreach( $this->requested_roles as $role ) {
			$results[] = $this->get_select_clause_for_role( $role );
		}

		return ' ' . implode( ', ', $results ) . ' ';
	}


	/**
	 * Generate a SELECT clause for a single role.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 *
	 * @return string
	 */
	protected function get_select_clause_for_role( IToolset_Relationship_Role $for_role ) {
		return sprintf(
			'associations.%s AS %s',
			$this->get_id_column( $for_role ),
			$this->get_element_id_alias( $for_role )
		);
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_join_clauses() {
		return '';
	}


	/**
	 * Tell whether there may be a different element ID value for the current and the default language.
	 *
	 * @param IToolset_Relationship_Role $role
	 *
	 * @return mixed
	 */
	public function has_element_id_translated( IToolset_Relationship_Role $role ) {
		return false;
	}
}