<?php

/**
 * Manages the way element IDs are obtained when building the MySQL query for associations.
 *
 * Generates SELECT clauses for the element IDs. Allows for injecting additional JOIN clauses
 * into the final query.
 *
 * @since 2.5.10
 */
interface IToolset_Association_Query_Element_Selector {


	/**
	 * The element selector needs to be initialized early so that it can interact
	 * with the join manager object, if needed.
	 *
	 * See Toolset_Association_Query_Sql_Expression_Builder::build() for detailed information.
	 *
	 * @return void
	 */
	public function initialize();


	/**
	 * Get an alias for an element ID that will be used in the SELECT clause.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $translate_if_possible
	 *
	 * @return string
	 */
	public function get_element_id_alias(
		IToolset_Relationship_Role $for_role, $translate_if_possible = true
	);


	/**
	 * Tell whether there may be a different element ID value for the current and the default language.
	 *
	 * @param IToolset_Relationship_Role $role
	 *
	 * @return mixed
	 */
	public function has_element_id_translated( IToolset_Relationship_Role $role );


	/**
	 * Get a name of the table and the column that contains an element ID.
	 *
	 * This is different from the alias because it can be used within the query itself
	 * for other purposes.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @param bool $translate_if_possible
	 *
	 * @return string Unambiguous "column" or "table.column" that contains ID of the element.
	 */
	public function get_element_id_value(
		IToolset_Relationship_Role $for_role, $translate_if_possible = true
	);


	/**
	 * Get all the select clauses for all the element IDs.
	 *
	 * Individual clauses must be connected with a comma, but there must not be
	 * a trailing comma present.
	 *
	 * @return string
	 */
	public function get_select_clauses();


	/**
	 * Get all JOIN clauses that need to be included in the query.
	 *
	 * The only assumption these JOINs can make is that there might be the relationships table joined
	 * first (if the element selector requires it). Anything else coming from the join manager
	 * will be joined after.
	 *
	 * @return string
	 */
	public function get_join_clauses();


	/**
	 * @param IToolset_Relationship_Role $role
	 *
	 * @return void
	 */
	public function request_element_in_results( IToolset_Relationship_Role $role );


	/**
	 * Call this to make sure the association ID and relationship ID will be included in the SELECT clause.
	 *
	 * @return void
	 * @since 2.6.1
	 */
	public function request_association_and_relationship_in_results();


	/**
	 * Call this to make sure the DISTINCT keyword will be used.
	 *
	 * @return void
	 * @since 2.6.1
	 */
	public function request_distinct_query();


	/**
	 * Get the DISTINCT keyword or an empty string.
	 *
	 * @return string
	 * @since 2.6.1
	 */
	public function maybe_get_distinct_modifier();
}