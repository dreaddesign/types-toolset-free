<?php

/**
 * A factory for IToolset_Association_Query_Condition implementations.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Factory {


	/**
	 * Chain multiple conditions with OR.
	 *
	 * The whole statement will evaluate to true if at least one of provided conditions is true.
	 *
	 * @param IToolset_Association_Query_Condition[] $operands
	 * @return IToolset_Association_Query_Condition
	 */
	public function do_or( $operands ) {
		return new Toolset_Query_Condition_Or( $operands );
	}


	/**
	 * Chain multiple conditions with AN.
	 *
	 * The whole statement will evaluate to true if all provided conditions are true.
	 *
	 * @param IToolset_Association_Query_Condition[] $operands
	 * @return IToolset_Association_Query_Condition
	 */
	public function do_and( $operands ) {
		return new Toolset_Query_Condition_And( $operands );
	}


	/**
	 * A condition that is always true.
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function tautology() {
		return new Toolset_Query_Condition_Tautology();
	}


	/**
	 * A condition that is always false.
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function contradiction() {
		return new Toolset_Query_Condition_Contradiction();
	}


	/**
	 * Condition to query associations by a specific relationship (row) ID.
	 *
	 * @param int $relationship_id
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function relationship_id( $relationship_id ) {
		return new Toolset_Association_Query_Condition_Relationship_Id( $relationship_id );
	}

	/**
	 * Condition to query associations by a specific intermediary (row) ID.
	 *
	 * @param int $intermediary_id
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function intermediary_id( $intermediary_id ) {
		return new Toolset_Association_Query_Condition_Intermediary_Id( $intermediary_id );
	}


	/**
	 * Condition to query associations having intermediary id.
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_intermediary_id() {
		return new Toolset_Association_Query_Condition_Has_Intermediary_Id();
	}


	/**
	 * Condition to query associations by a particular element involved in a particular role.
	 *
	 * Warning: WPML-unaware implementation.
	 *
	 * @param int $element_id
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function element_id(
		$element_id, IToolset_Relationship_Role_Parent_Child $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	) {
		return new Toolset_Association_Query_Condition_Element_Id( $element_id, $for_role, $element_selector_provider );
	}


	/**
	 * Condition to query associations by a particular element involved in a particular role.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param $query_original_element
	 * @param $translate_provided_id
	 *
	 * @return Toolset_Association_Query_Condition_Element_Id_And_Domain
	 */
	public function element_id_and_domain(
		$element_id,
		$domain,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_id
	) {
		return new Toolset_Association_Query_Condition_Element_Id_And_Domain(
			$element_id, $domain, $for_role, $element_selector_provider, $query_original_element, $translate_provided_id
		);
	}



	/**
	 * Condition to query associations that do not contain a particular element in a particular role.
	 *
	 * @param int $element_id
	 * @param string $domain
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 * @param $query_original_element
	 * @param $translate_provided_id
	 *
	 * @return Toolset_Association_Query_Condition_Element_Id_And_Domain
	 */
	public function exclude_element(
		$element_id,
		$domain,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider,
		$query_original_element,
		$translate_provided_id
	) {
		return new Toolset_Association_Query_Condition_Exclude_Element(
			$element_id, $domain, $for_role, $element_selector_provider, $query_original_element, $translate_provided_id
		);
	}


	/**
	 * Condition to query associations by a status of an element in a particular role.
	 *
	 * @param string $status
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function element_status(
		$status, IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Association_Query_Element_Selector_Provider $element_selector_provider
	) {
		return new Toolset_Association_Query_Condition_Element_Status(
			$status, $for_role, $join_manager, $element_selector_provider
		);
	}


	/**
	 * Query associations by the activity status of the relationship.
	 *
	 * @param bool $is_active
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_active_relationship( $is_active, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Condition_Has_Active_Relationship( $is_active, $join_manager );
	}


	/**
	 * Query associations by the element domain on a specified role.
	 *
	 * @param string $domain Domain name.
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_domain(
		$domain, IToolset_Relationship_Role_Parent_Child $for_role, Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		return new Toolset_Association_Query_Condition_Has_Domain( $domain, $for_role, $join_manager );
	}


	/**
	 * @param bool $needs_legacy_support
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_legacy_relationship( $needs_legacy_support, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Condition_Has_Legacy_Relationship( $needs_legacy_support, $join_manager );
	}


	/**
	 * Query associations by element type on a given role.
	 *
	 * Warning: This doesn't query for the domain. Make sure you at least add
	 * a separate element domain condition. Otherwise, the results will be unpredictable.
	 *
	 * The best way is to use the has_domain_and_type() condition instead, which whill allow
	 * for some more advanced optimizations.
	 *
	 * @param string $type
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function has_type(
		$type,
		IToolset_Relationship_Role_Parent_Child $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	) {
		return new Toolset_Association_Query_Condition_Has_Type( $for_role, $type, $join_manager, $unique_table_alias );
	}


	/**
	 * @param string $domain
	 * @param string $type
	 * @param IToolset_Relationship_Role_Parent_Child $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 *
	 * @return Toolset_Association_Query_Condition_Has_Domain_And_Type
	 */
	public function has_domain_and_type(
		$domain,
		$type,
		IToolset_Relationship_Role_Parent_Child $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	) {
		return new Toolset_Association_Query_Condition_Has_Domain_And_Type(
			$for_role, $domain, $type, $join_manager, $unique_table_alias, $this
		);
	}


	/**
	 * @param IToolset_Relationship_Role $for_role
	 * @param array $query_args
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function wp_query(
		IToolset_Relationship_Role $for_role,
		$query_args,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		Toolset_Relationship_Database_Unique_Table_Alias $table_alias
	) {
		return new Toolset_Association_Query_Condition_Wp_Query( $for_role, $query_args, $join_manager, $table_alias );
	}


	/**
	 * @param string $search_string
	 * @param bool $is_exact_search
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function search(
		$search_string,
		$is_exact_search,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		return new Toolset_Association_Query_Condition_Search(
			$search_string, $is_exact_search, $for_role, $join_manager
		);
	}


	/**
	 * @param int $association_id
	 *
	 * @return IToolset_Association_Query_Condition
	 */
	public function association_id( $association_id ) {
		return new Toolset_Association_Query_Condition_Association_Id( $association_id );
	}


	/**
	 * @param string $meta_key
	 * @param string $meta_value
	 * @param string $comparison_operator
	 * @param IToolset_Relationship_Role $for_role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return Toolset_Association_Query_Condition_Postmeta
	 */
	public function postmeta(
		$meta_key,
		$meta_value,
		$comparison_operator,
		IToolset_Relationship_Role $for_role,
		Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		return new Toolset_Association_Query_Condition_Postmeta(
			$meta_key,
			$meta_value,
			$comparison_operator,
			$for_role,
			$join_manager
		);
	}


	/**
	 * Condition that a relationship has a certain origin.
	 *
	 * @param string                                       $origin Origin: wizard, ...
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager Join manager.
	 *
	 * @return IToolset_Relationship_Query_Condition
	 */
	public function has_origin( $origin, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Condition_Relationship_Origin( $origin, $join_manager );
	}


	/**
	 * @param IToolset_Association_Query_Condition $condition
	 *
	 * @return Toolset_Query_Condition_Not
	 */
	public function not( IToolset_Association_Query_Condition $condition ) {
		return new Toolset_Query_Condition_Not( $condition );
 }
}
