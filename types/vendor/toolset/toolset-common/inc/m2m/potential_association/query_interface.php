<?php

/**
 * When you have a relationship and a specific element in one role, this
 * query class will help you to find elements that can be associated with it.
 *
 * It takes into account all the aspects, like whether the relationship is distinct or not.
 *
 * @since m2m
 */
interface IToolset_Potential_Association_Query {

	/**
	 * IToolset_Potential_Association_Query constructor.
	 *
	 * @param IToolset_Relationship_Definition $relationship
	 * @param IToolset_Relationship_Role_Parent_Child $target_role
	 * @param IToolset_Element $for_element
	 * @param array $args
	 * @param Toolset_Relationship_Query_Factory|null $query_factory_di
	 */
	public function __construct(
		IToolset_Relationship_Definition $relationship,
		IToolset_Relationship_Role_Parent_Child $target_role,
		IToolset_Element $for_element,
		$args,
		Toolset_Relationship_Query_Factory $query_factory_di = null
	);


	/**
 	 * @param bool $check_can_connect_another_element Check wheter it is possible to connect any other element at all,
	 *     and return an empty result if not.
	 * @param bool $check_distinct_relationships Exclude elements that would break the "distinct" property of a relationship.
	 *     You can set this to false if you're overwriting an existing association.
	 * @return IToolset_Element[]
	 */
	public function get_results( $check_can_connect_another_element = true, $check_distinct_relationships = true );


	/**
	 * @return int
	 */
	public function get_found_elements();


	/**
	 * Check whether a specific single element can be associated.
	 *
	 * The relationship, target role and the other element are those provided in the constructor.
	 *
	 * @param IToolset_Element $association_candidate Element that wants to be associated.
	 * @param bool $check_is_already_associated Perform the check that the element is already associated for distinct
	 *     relationships. Default is true. Set to false only if the check was performed manually before.
	 * @return Toolset_Result Result with an user-friendly message in case the association is denied.
	 * @since 2.5.6
	 */
	public function check_single_element( IToolset_Element $association_candidate, $check_is_already_associated = true );


	/**
	 * Check whether the element provided in the constructor can accept any new association whatsoever.
	 *
	 * @return Toolset_Result Result with an user-friendly message in case the association is denied.
	 * @since 2.5.6
	 */
	public function can_connect_another_element();


	/**
	 * Check whether there already exists an association between the the target element and the provided one.
	 *
	 * Note that it doesn't always have to be a problem, it depends on whether the relationship is distinct or not.
	 * This was made public to optimize performance during the m2m migration process.
	 *
	 * @param IToolset_Element $element
	 *
	 * @return bool
	 */
	public function is_element_already_associated( IToolset_Element $element );

}