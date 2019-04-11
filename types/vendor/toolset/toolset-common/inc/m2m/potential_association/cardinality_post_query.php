<?php

namespace OTGS\Toolset\Common\M2M\PotentialAssociation;

/**
 * Augments WP_Query to check whether the posts can accept another association according to the relationship
 * cardinality.
 *
 * This is used in Toolset_Potential_Association_Query_Posts.
 *
 * Both before_query() and after_query() methods need to be called as close to the actual
 * querying as possible, otherwise things will get broken.
 *
 * @package OTGS\Toolset\Common\M2M\PotentialAssociation
 * @since 2.8
 */
class CardinalityPostQuery extends WpQueryAdjustment {

	/**
	 * CardinalityPostQuery constructor.
	 *
	 * @param \IToolset_Relationship_Definition $relationship
	 * @param \IToolset_Relationship_Role_Parent_Child $target_role
	 * @param \IToolset_Element $for_element
	 * @param JoinManager $join_manager
	 * @param \Toolset_Relationship_Table_Name|null $table_names_di
	 * @param \wpdb|null $wpdb_di
	 * @param \Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		\IToolset_Relationship_Definition $relationship,
		\IToolset_Relationship_Role_Parent_Child $target_role,
		\IToolset_Element $for_element,
		JoinManager $join_manager,
		\Toolset_Relationship_Table_Name $table_names_di = null,
		\wpdb $wpdb_di = null,
		\Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		parent::__construct( $relationship, $target_role, $for_element, $join_manager, $wpml_service_di, $table_names_di, $wpdb_di );
	}


	public function is_actionable() {
		return true;
	}


	/**
	 * Add a JOIN clause to the WP_Query's MySQL query string.
	 *
	 * If WPML is active, we just need to make sure that we'll have the default language version of the posts available.
	 *
	 * @param string $join
	 * @return string
	 */
	public function add_join_clauses( $join ) {

		if( $this->wpml_service->is_wpml_active_and_configured() ) {
			$this->join_manager->register_join( JoinManager::JOIN_DEFAULT_POST_TRANSLATION );
		}

		return $join;
	}


	/**
	 * Add a WHERE clause to the WP_Query's MySQL query string.
	 *
	 * Excludes elements that have already reached the cardinality limit.
	 *
	 * @param string $where
	 * @return string
	 */
	public function add_where_clauses( $where ) {

		if( $this->needs_cardinality_limit_check() ) {
			// This means we have an association where the source element (for_element) has a cardinality limit (lower
			// than infinity) and we need to check how many associations each potential target element has.
			//
			// In case of WPML being active, we also need to handle post translations - we reuse the translation table
			// which is already JOINed.
			$association_table = $this->get_table_names()->association_table();
			$posts_table_name = $this->get_wpdb()->posts;
			$target_element_column = $this->target_role->get_name() . '_id';
			$relationship_id = (int) $this->relationship->get_row_id();

			if( $this->wpml_service->is_wpml_active_and_configured() ) {
				$inner_where = " ( cch_associations.$target_element_column = $posts_table_name.ID
					OR cch_associations.$target_element_column = default_lang_translation.element_id )
					AND cch_associations.relationship_id = {$relationship_id} ";
			} else {
				$inner_where = " cch_associations.$target_element_column = $posts_table_name.ID AND cch_associations.relationship_id = {$relationship_id} ";
			}

			$where .= $this->get_wpdb()->prepare(
				" AND ( %d > ( 
					SELECT COUNT(*) FROM {$association_table} AS cch_associations
					WHERE ( $inner_where )  
				) )",
				$this->get_for_element_max_cardinality()
			);

		}

		return $where;
	}


	private function get_for_element_max_cardinality() {
		return $this->relationship->get_cardinality()->get_limit( $this->target_role->other() );
	}


	private function needs_cardinality_limit_check() {
		if( ! $this->is_actionable() ) {
			return false;
		}

		if( \Toolset_Relationship_Cardinality::INFINITY === $this->get_for_element_max_cardinality() ) {
			return false;
		}

		return true;
	}

}