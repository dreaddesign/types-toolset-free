<?php

use OTGS\Toolset\Common\M2M\PotentialAssociation as potentialAssociation;

/**
 * Augments WP_Query to check whether posts are associated with a particular other element ID,
 * and dismisses those posts.
 *
 * This is used in Toolset_Potential_Association_Query_Posts to handle distinct relationships.
 *
 * Both before_query() and after_query() methods need to be called as close to the actual
 * querying as possible, otherwise things will get broken.
 *
 * @since m2m
 */
class Toolset_Relationship_Distinct_Post_Query extends potentialAssociation\WpQueryAdjustment {


	protected function is_actionable() {
		return $this->relationship->is_distinct();
	}



	/**
	 * Add a JOIN clause to the WP_Query's MySQL query string.
	 *
	 * That will connect the row from the associations table, if there is an association
	 * with the correct relationship and the $for_element.
	 *
	 * Otherwise, those columns will be NULL, because we're doing a LEFT JOIN here.
	 *
	 * If WPML is active, we also do the same comparison for the default language version of the
	 * queried post, if it exists.
	 *
	 * @param string $join
	 *
	 * @return string
	 */
	public function add_join_clauses( $join ) {
		$this->join_manager->register_join( potentialAssociation\JoinManager::JOIN_ASSOCIATIONS_TABLE );

		if( $this->wpml_service->is_wpml_active_and_configured() ) {
			$this->join_manager->register_join( potentialAssociation\JoinManager::JOIN_DEFAULT_LANG_ASSOCIATIONS );
		}

		return $join;
	}


	/**
	 * Add a WHERE clause to the WP_Query's MySQL query string.
	 *
	 * After adding the JOIN, we only need to check that there's not an ID of the
	 * column with $for_element: That means there's no association between the queried
	 * post and $for_element, and we can offer the post as a result.
	 *
	 * If WPML is active, we also have to check that there's no default language translation
	 * of the queried post that would be part of such an association.
	 *
	 * @param string $where
	 *
	 * @return string
	 */
	public function add_where_clauses( $where ) {
		$for_element_column = $this->target_role->other() . '_id';
		$where .= " AND ( toolset_associations.{$for_element_column} IS NULL ) ";

		if( $this->wpml_service->is_wpml_active_and_configured() ) {
			$where .= " AND ( default_lang_association.{$for_element_column} IS NULL ) ";
		}

		return $where;
	}

}