<?php

/**
 * Condition to query associations by a particular element involved in a particular role.
 *
 * Warning: This is not WPML-aware query. It simply compares the provided ID with the ID in
 * the correct column in the associations table. In most cases, you will need the translation
 * mechanism to be involved and use Toolset_Association_Query_Condition_Element_Id_And_Domain
 * instead.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Element_Id extends Toolset_Association_Query_Condition {


	/** @var int */
	private $element_id;


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var Toolset_Association_Query_Element_Selector_Provider */
	private $element_selector_provider;


	/**
	 * Toolset_Association_Query_Condition_Element_Id constructor.
	 *
	 * @param int $element_id
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$element_id,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	) {
		if( ! Toolset_Utils::is_nonnegative_integer( $element_id ) ) {
			throw new InvalidArgumentException();
		}

		$this->element_id = (int) $element_id;
		$this->for_role = $for_role;
		$this->element_selector_provider = $element_selector_provider;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		$column_name = $this->element_selector_provider
			->get_selector()
			->get_element_id_value( $this->for_role, false );

		return sprintf( '%s = %d', $column_name, $this->element_id );
	}

}