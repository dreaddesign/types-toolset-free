<?php

/**
 * Collect the JOINed tables in Toolset_Wp_Query_Adjustments_M2m and generate the JOIN clause.
 *
 * @since 2.6.1
 */
class Toolset_Wp_Query_Adjustments_Table_Join_Manager extends Toolset_Wpdb_User {


	/**
	 * @var string[][][][] Unique aliases for the associations table, indexed by:
	 *     1. relationship slug,
	 *     2. role name to join the table on (to wp_posts.ID) ("role to return")
	 *     3. role name to constrain the results with ("role to query by")
	 *     4. ID of the associated element in the role to query by
	 */
	private $joins = array();


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_Relationship_Database_Unique_Table_Alias */
	private $uniqe_table_alias;


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/** @var Toolset_Relationship_Definition_Repository */
	private $definition_repository;


	/** @var Toolset_WPML_Compatibility */
	private $wpml_service;


	/**
	 * Toolset_Wp_Query_Adjustments_Table_Join_Manager constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Database_Unique_Table_Alias|null $unique_table_alias_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Relationship_Definition_Repository|null $definition_repository_di
	 * @param Toolset_WPML_Compatibility|null $wpml_service_di
	 */
	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Definition_Repository $definition_repository_di = null,
		Toolset_WPML_Compatibility $wpml_service_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->uniqe_table_alias = $unique_table_alias_di ?: new Toolset_Relationship_Database_Unique_Table_Alias();
		$this->table_name = $table_name_di ?: new Toolset_Relationship_Table_Name();
		$this->database_operations = $database_operations_di ?: Toolset_Relationship_Database_Operations::get_instance();
		$this->definition_repository = $definition_repository_di ?: Toolset_Relationship_Definition_Repository::get_instance();
		$this->wpml_service = $wpml_service_di ?: Toolset_WPML_Compatibility::get_instance();
	}


	/**
	 * Generate the JOIN clause based on previously made requests for table aliases.
	 *
	 * @return string
	 */
	public function get_join_clauses() {
		$results = '';

		foreach( $this->joins as $relationship_slug => $data_by_relationship_slug ) {
			foreach( $data_by_relationship_slug as $role_to_return_name => $data_by_role_to_return ) {
				foreach( $data_by_role_to_return as $role_to_query_by => $data_by_role_to_query ) {
					foreach( $data_by_role_to_query as $post_to_query_by => $table_alias ) {

						$results .= $this->get_single_join_clause(
							$relationship_slug,
							$role_to_return_name,
							$role_to_query_by,
							$post_to_query_by,
							$table_alias
						);

					}
				}
			}
		}

		return $results;
	}


	/**
	 * Build a JOIN clause for one table alias.
	 *
	 * Note: We need to limit the query by a particular associated element, otherwise we couldn't
	 * work with multiple JOINs in one query. This is not a problem because of how this class is used -
	 * to query only posts that have associations to all the requested elements (always AND, no OR).
	 *
	 * @param string $relationship_slug
	 * @param string $role_to_return_name
	 * @param string $role_to_query_by
	 * @param int $element_to_query_by
	 * @param string $associations_table_alias
	 *
	 * @return string
	 */
	private function get_single_join_clause(
		$relationship_slug, $role_to_return_name, $role_to_query_by, $element_to_query_by, $associations_table_alias
	) {

		$role_to_return_id_column = $this->database_operations->role_to_column( $role_to_return_name );
		$role_to_query_by_id_column = $this->database_operations->role_to_column( $role_to_query_by );
		$relationship_definition = $this->definition_repository->get_definition( $relationship_slug );

		if( null === $relationship_definition ) {
			// This should have failed already during the WHERE clause processing and never get to this point.
			throw new InvalidArgumentException( 'Unknown relationship "' . sanitize_text_field( $relationship_slug ) . '".' );
		}

		$relationship_id = $relationship_definition->get_row_id();

		if( $this->wpml_service->is_wpml_active_and_configured() ) {
			return $this->get_single_join_clause_for_wpml(
				$relationship_id, $associations_table_alias, $role_to_return_id_column, $role_to_query_by_id_column, $element_to_query_by
			);
		}

		return $this->wpdb->prepare(
			"JOIN {$this->table_name->association_table()} AS {$associations_table_alias} ON (
				wp_posts.ID = {$associations_table_alias}.{$role_to_return_id_column}
				AND {$associations_table_alias}.relationship_id = %d
				AND {$associations_table_alias}.{$role_to_query_by_id_column} = %d
			) ",
			$relationship_id,
			$element_to_query_by
		);
	}


	/**
	 * Join the associations table by either using the wp_posts.ID directly or by
	 * translating it to the default language first.
	 *
	 * @param int $relationship_id
	 * @param string $associations_table_alias
	 * @param string $role_to_return_column
	 * @param string $role_to_query_by_column
	 * @param int $element_to_query_by
	 *
	 * @return string
	 */
	private function get_single_join_clause_for_wpml(
		$relationship_id,
		$associations_table_alias,
		$role_to_return_column,
		$role_to_query_by_column,
		$element_to_query_by
	) {

		$alias_translation = 'toolset_t_' . $this->uniqe_table_alias->generate(
			$this->wpml_service->icl_translations_table_name(),
			true
		);

		$alias_default_lang = 'toolset_dl_' . $this->uniqe_table_alias->generate(
				$this->wpml_service->icl_translations_table_name(),
				true
			);

		$clause = $this->wpdb->prepare(
			"
			# join the icl_translations table independently from WPML's 't'
			# because that one may not be joined at all time, but we 
			# need it always - this is safer than trying to reuse the 't' one
			LEFT JOIN {$this->wpml_service->icl_translations_table_name()} AS {$alias_translation} ON (
				wp_posts.ID = {$alias_translation}.element_id
				AND {$alias_translation}.element_type = CONCAT('post_', wp_posts.post_type)
			) LEFT JOIN {$this->wpml_service->icl_translations_table_name()} AS {$alias_default_lang} ON (
			    {$alias_translation}.trid = {$alias_default_lang}.trid
			    AND {$alias_default_lang}.language_code = %s
			) JOIN {$this->table_name->association_table()} AS {$associations_table_alias} ON (
				(
					# join the association row if either the post ID matches the
					# proper column in the associations table or if the ID of the default
					# language version of the post matches it
					wp_posts.ID = {$associations_table_alias}.{$role_to_return_column}
					OR {$alias_default_lang}.element_id = {$associations_table_alias}.{$role_to_return_column}
				)
				AND {$associations_table_alias}.relationship_id = %d
				AND {$associations_table_alias}.{$role_to_query_by_column} = %d
			)",
			$this->wpml_service->get_default_language(),
			$relationship_id,
			$element_to_query_by
		);

		return $clause;
	}


	/**
	 * Request an alias for the associations table.
	 *
	 * Each call will cause a new JOIN and return a new unique table alias.
	 *
	 * The table will be JOINed on wp_posts.ID by a given relationship slug and element role.
	 *
	 * @param string $relationship_slug
	 * @param IToolset_Relationship_Role $role_to_return
	 *
	 * @param IToolset_Relationship_Role $role_to_query_by
	 * @param $query_by_element_id
	 *
	 * @return string
	 */
	public function associations_table(
		$relationship_slug, IToolset_Relationship_Role $role_to_return,
		IToolset_Relationship_Role $role_to_query_by,
		$query_by_element_id
	) {
		$path_to_value = array( $relationship_slug, $role_to_return->get_name(), $role_to_query_by->get_name(), (int) $query_by_element_id );
		$stored_alias = toolset_getnest( $this->joins, $path_to_value, null );

		if( null !== $stored_alias ) {
			return $stored_alias;
		}

		$unique_alias = $this->uniqe_table_alias->generate( $this->table_name->association_table(), true );
		$this->joins = Toolset_Utils::set_nested_value( $this->joins, $path_to_value, $unique_alias );

		return $unique_alias;
	}

}