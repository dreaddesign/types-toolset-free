<?php

use OTGS\Toolset\Common\M2M\PotentialAssociation as potentialAssociation;
/**
 * Factory for instantiating query classes.
 *
 * Should be extendended for association query and all others within the m2m project.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Factory {

	/**
	 * @param $args
	 *
	 * @return Toolset_Relationship_Query
	 * @deprecated Use Toolset_Relationship_Query_V2 instead.
	 */
	public function relationships( $args ) {
		return new Toolset_Relationship_Query( $args );
	}


	/**
	 * @return Toolset_Relationship_Query_V2
	 *
	 * @return Toolset_Relationship_Query_V2
	 */
	public function relationships_v2() {
		return new Toolset_Relationship_Query_V2();
	}


	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param IToolset_Relationship_Role_Parent_Child $target_role Target role of the relationships (future role of
	 *     the posts that are being queried)
	 * @param IToolset_Element $for_element ID of the element to check against.
	 * @param potentialAssociation\JoinManager $join_manager
	 * @param Toolset_Relationship_Table_Name|null $table_names_di
	 * @param wpdb|null $wpdb_di
	 *
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 *
	 * @return Toolset_Relationship_Distinct_Post_Query
	 */
	public function distinct_relationship_posts(
		IToolset_Relationship_Definition $relationship,
		IToolset_Relationship_Role_Parent_Child $target_role,
		IToolset_Element $for_element,
		potentialAssociation\JoinManager $join_manager,
		Toolset_Relationship_Table_Name $table_names_di = null,
		wpdb $wpdb_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		return new Toolset_Relationship_Distinct_Post_Query(
			$relationship,
			$target_role,
			$for_element,
			$join_manager,
			$wpml_service_di,
			$table_names_di,
			$wpdb_di
		);
	}


	/**
	 * @param IToolset_Relationship_Definition $relationship
	 * @param IToolset_Relationship_Role_Parent_Child $target_role
	 * @param IToolset_Element $for_element
	 * @param potentialAssociation\JoinManager $join_manager
	 * @param Toolset_Relationship_Table_Name|null $table_names_di
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 *
	 * @return potentialAssociation\CardinalityPostQuery
	 */
	public function cardinality_query_posts(
		IToolset_Relationship_Definition $relationship,
		IToolset_Relationship_Role_Parent_Child $target_role,
		IToolset_Element $for_element,
		potentialAssociation\JoinManager $join_manager,
		Toolset_Relationship_Table_Name $table_names_di = null,
		wpdb $wpdb_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		return new potentialAssociation\CardinalityPostQuery(
			$relationship,
			$target_role,
			$for_element,
			$join_manager,
			$table_names_di,
			$wpdb_di,
			$wpml_service_di
		);
	}


	/**
	 * @param array $args Query arguments.
	 *
	 * @return WP_Query
	 */
	public function wp_query( $args ) {
		return new WP_Query( $args );
	}


	/**
	 * @param $args
	 *
	 * @return Toolset_Association_Query
	 * @deprecated Use associations_v2() instead.
	 */
	public function associations( $args ) {
		return new Toolset_Association_Query( $args );
	}


	/**
	 * @return Toolset_Association_Query_V2
	 */
	public function associations_v2() {
		return new Toolset_Association_Query_V2();
	}
}
