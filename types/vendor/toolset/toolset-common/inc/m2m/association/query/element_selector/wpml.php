<?php

/**
 * Element selector that translates post elements and chooses the best element ID
 * (the translated one, but defaults to the original if the translation doesn't exist).
 *
 * @since 2.5.10
 */
class Toolset_Association_Query_Element_Selector_Wpml
	extends Toolset_Association_Query_Element_Selector_Abstract {


	/** @var bool */
	private $is_ready = false;


	/** @var string[] Indexed by role names. */
	private $select_clauses = array();


	/** @var string[] Indexed by role names. */
	private $join_clauses = array();


	/**
	 * @var string[] Aliases for original element IDs that will be used in the SELECT clause.
	 *     Indexed by role names.
	 */
	private $original_element_id_select_aliases = array();


	/**
	 * @var string[] Unambiguous column names for original element IDs that can be used
	 *    within the rest of the MySQL query. Indexed by role names.
	 */
	private $original_element_id_values = array();


	/**
	 * @var string[] Aliases for translated element IDs that will be used in the SELECT clause.
	 *     Indexed by role names.
	 */
	private $translated_element_id_select_aliases = array();


	/**
	 * @var string[] Expressions for translated element IDs that can be used
	 *    within the rest of the MySQL query. Indexed by role names.
	 */
	private $translated_element_id_values = array();


	/** @var IToolset_Relationship_Role[] */
	private $requested_roles_in_join = array();


	/**
	 * @inheritdoc
	 */
	public function initialize() {
		if( $this->is_ready ) {
			return;
		}

		foreach( Toolset_Relationship_Role::all() as $role ) {
			$this->build_data_for_role( $role );
		}

		$this->is_ready = true;
	}


	/**
	 * Build all parts of the query and other values needed for a single element role.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 */
	private function build_data_for_role( IToolset_Relationship_Role $for_role ) {

		// This is hardcoded across the association query classes.
		$association_alias = 'associations';

		$element_id_column = $this->get_id_column( $for_role );

		// Require the JOIN of the relationships table.
		$relationships_table_alias = $this->join_manager->relationships();

		// Make sure that we translate only posts.
		// No check for the intermediary ID because those are always posts by definition.
		if( $for_role->is_parent_child() ) {
			$element_domain_column = $this->database_operations->role_to_column(
				$for_role, Toolset_Relationship_Database_Operations::COLUMN_DOMAIN
			);
			$posts_domain = esc_sql( Toolset_Element_Domain::POSTS );
			// Note: Must end with "AND" so that another query can be concatenated immediately after.
			$domain_query = "$relationships_table_alias.$element_domain_column = '$posts_domain' AND";
		} else {
			$domain_query = "";
		}

		$original_element_id_alias = 'original_' . $element_id_column;

		// This is, however, usable only for the final SELECT clause as it will not an alias for
		// an existing column, but for a COALESCE() result.
		$translated_element_id_alias = 'translated_' . $element_id_column;

		// Generate safe aliases for the icl_translations table.
		// We need to JOIN it twice to get from the original element ID to the translated one.
		$icl_translations = $this->wpdb->prefix . 'icl_translations';
		$icl_translations_for_original = $this->table_alias->generate( $icl_translations, true );
		$icl_translations_for_translation = $this->table_alias->generate( $icl_translations, true );

		// In most cases, this will be the current language, but there are special cases,
		// like if displaying all languages - the get_translation_language() method may be overridden.
		$translation_language = esc_sql( $this->get_translation_language() );

		// Generate expressions with element IDs. The translated one will default to the original
		// if no translation is available. This will be also extremely important for domains different
		// than posts.
		$translated_or_original_element_id = "COALESCE( 
				$icl_translations_for_translation.element_id, $association_alias.$element_id_column 
			)";
		$original_element_id = "$association_alias.$element_id_column";

		$this->select_clauses[ $for_role->get_name() ] =
			"$original_element_id AS $original_element_id_alias,
			$translated_or_original_element_id AS $translated_element_id_alias";

		// LEFT joins are extremely important here.
		$this->join_clauses[ $for_role->get_name() ] =
			"LEFT JOIN $icl_translations AS $icl_translations_for_original
				ON ( 
					$domain_query 
					$icl_translations_for_original.element_id = $original_element_id
					AND $icl_translations_for_original.element_type LIKE 'post_%' 
				)
			LEFT JOIN $icl_translations AS $icl_translations_for_translation
				ON (
					$icl_translations_for_original.trid = $icl_translations_for_translation.trid
					AND $icl_translations_for_translation.language_code = '$translation_language'
				)";

		$this->original_element_id_select_aliases[ $for_role->get_name() ] = $original_element_id_alias;
		$this->original_element_id_values[ $for_role->get_name() ] = $original_element_id;
		$this->translated_element_id_select_aliases[ $for_role->get_name() ] = $translated_element_id_alias;
		$this->translated_element_id_values[ $for_role->get_name() ] = $translated_or_original_element_id;
	}


	/**
	 * Get the language that will be used for the query results (besides the default language).
	 *
	 * @return string
	 * @since 2.6.8
	 */
	protected function get_translation_language() {
		return $this->wpml_service->get_current_language();
	}


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
		$this->initialize();
		$this->request_element_in_results( $for_role );

		if( $translate_if_possible ) {
			return $this->translated_element_id_select_aliases[ $for_role->get_name() ];
		} else {
			return $this->original_element_id_select_aliases[ $for_role->get_name() ];
		}
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
		$this->initialize();

		// The element value is used only within the query itself, but not within the SELECT clause.
		$this->request_element_in_join_only( $for_role );

		if( $translate_if_possible ) {
			return $this->translated_element_id_values[ $for_role->get_name() ];
		} else {
			return $this->original_element_id_values[ $for_role->get_name() ];
		}
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_join_clauses() {
		$this->initialize();

		$requested_join_clauses = array();
		foreach( $this->requested_roles_in_join as $role ) {
			$requested_join_clauses[] = $this->join_clauses[ $role->get_name() ];
		}
		return ' ' . implode( ' ', $requested_join_clauses ) . ' ';
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_select_clauses() {
		$this->initialize();

		$requested_select_clauses = $this->maybe_get_association_and_relationship();
		foreach( $this->requested_roles as $role ) {
			$requested_select_clauses[] = $this->select_clauses[ $role->get_name() ];
		}
		return ' ' . implode( ', ', $requested_select_clauses ) . ' ';
	}


	/**
	 * @inheritdoc
	 *
	 * @param IToolset_Relationship_Role $role
	 */
	public function request_element_in_results( IToolset_Relationship_Role $role ) {
		parent::request_element_in_results( $role );

		// Make sure that requested elements in results are superset of those requested in JOINs.
		$this->request_element_in_join_only( $role );
	}


	/**
	 * @param IToolset_Relationship_Role $role
	 */
	public function request_element_in_join_only( IToolset_Relationship_Role $role ) {
		$this->requested_roles_in_join[ $role->get_name() ] = $role;
	}


	/**
	 * Tell whether there may be a different element ID value for the current and the default language.
	 *
	 * @param IToolset_Relationship_Role $role
	 *
	 * @return mixed
	 */
	public function has_element_id_translated( IToolset_Relationship_Role $role ) {
		return true; // This can be optimized in the future.
	}
}