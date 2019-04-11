<?php

/**
 * Manages JOIN clauses shared between different conditions within one association query.
 *
 * Use methods in this class to obtain aliases for the tables you need. By doing that,
 * those tables will be added to the final JOIN clause. There is no risk of alias
 * conflicts as long as all conditions use the same instance of
 * Toolset_Relationship_Database_Unique_Table_Alias as is provided here in the constructor.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Table_Join_Manager {


	/** @var Toolset_Relationship_Database_Unique_Table_Alias */
	private $unique_table_alias;


	/** @var wpdb */
	private $wpdb;


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var string[] Mapping of role names to aliases of JOINed wp_posts table. */
	private $registered_wp_posts_joins = array();


	/** @var string[][] Mapping of role names and meta_keys to aliases of JOINed wp_postmeta table. */
	private $registered_wp_postmeta_joins = array();


	/** @var bool Flag indicating that a relationships table also needs to be JOINed. */
	private $join_relationships = false;


	/**
	 * Toolset_Association_Query_Table_Join_Manager constructor.
	 *
	 * @param Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param wpdb|null $wpdb_di
	 */
	public function __construct(
		Toolset_Relationship_Database_Unique_Table_Alias $unique_table_alias,
		Toolset_Relationship_Database_Operations $database_operations_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null,
		wpdb $wpdb_di = null
	) {
		$this->unique_table_alias = $unique_table_alias;

		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->database_operations = ( null === $database_operations_di ? new Toolset_Relationship_Database_Operations() : $database_operations_di );
		$this->table_name = ( null === $table_name_di ? new Toolset_Relationship_Table_Name() : $table_name_di );
	}


	/**
	 * Get an alias for a wp_posts table JOINed on a particular element role.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @return string Table alias.
	 */
	public function wp_posts( IToolset_Relationship_Role $for_role ) {
		if( ! array_key_exists( $for_role->get_name(), $this->registered_wp_posts_joins ) ) {
			$table_alias = $this->unique_table_alias->generate( $this->wpdb->posts, true );
			$this->registered_wp_posts_joins[ $for_role->get_name() ] = $table_alias;
		}

		return $this->registered_wp_posts_joins[ $for_role->get_name() ];
	}


	/**
	 * Get an alias for a wp_postmeta table JOINed on a particular element role and a meta_key value.
	 *
	 * This creates LEFT JOIN clauses, so that even with missing postmeta, the end results are not affected.
	 *
	 * @param IToolset_Relationship_Role $for_role
	 * @param string $meta_key
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function wp_postmeta( IToolset_Relationship_Role $for_role, $meta_key ) {
		if( ! is_string( $meta_key ) || empty( $meta_key ) ) {
			throw new InvalidArgumentException();
		}

		$role_name = $for_role->get_name();

		if(
			null === toolset_getnest(
				$this->registered_wp_postmeta_joins, array( $role_name, $meta_key ), null
			)
		) {
			$table_alias = $this->unique_table_alias->generate( $this->wpdb->postmeta, true );

			if( ! isset( $this->registered_wp_postmeta_joins[ $role_name ] ) ) {
				$this->registered_wp_postmeta_joins[ $role_name ] = array();
			}

			$this->registered_wp_postmeta_joins[ $role_name ][ $meta_key ] = $table_alias;
		}

		return $this->registered_wp_postmeta_joins[ $role_name ][ $meta_key ];
	}


	/**
	 * Get an alias for a relationships table JOINed on the relationships_id column.
	 *
	 * @return string
	 */
	public function relationships() {
		$this->join_relationships = true;
		return 'relationships';
	}


	/**
	 * Build the final MySQL query part containing all requested JOIN clauses.
	 *
	 * @param IToolset_Association_Query_Element_Selector $element_selector
	 *
	 * @return string
	 */
	public function get_join_clause( IToolset_Association_Query_Element_Selector $element_selector ) {

		// The order of JOINing is very important here:
		//
		// The JOINs coming from the element selector might reference the relationships table.
		//
		// Any other JOINs will be most probably referencing the elements, so they
		// must be added only after the JOINs from the element selector.
		//
		// However, we first resolve those additional joins and only after that add the joins
		// from the element selector. This way, the element selector will know exactly what
		// element roles it can skip entirely (and save a lot of database performance if we have
		// WPML active).
		$results = array();

		// JOINs that come after the relationships table and element selector JOINs
		// but need to be determined in advance.
		$additional_joins = array();

		if( $this->join_relationships ) {
			$results[] = sprintf(
				' JOIN %s AS relationships ON ( associations.relationship_id = relationships.id ) ',
				$this->table_name->relationship_table()
			);
		}

		foreach( $this->registered_wp_posts_joins as $role_name => $table_alias ) {
			$id_column_alias = $element_selector->get_element_id_value(
				Toolset_Relationship_Role::role_from_name( $role_name )
			);

			$additional_joins[] = sprintf(
				' JOIN %s AS %s ON (%s.ID = %s) ',
				$this->wpdb->posts,
				$table_alias,
				$table_alias,
				$id_column_alias
			);
		}

		foreach( $this->registered_wp_postmeta_joins as $role_name => $postmeta_list ) {
			foreach( $postmeta_list as $meta_key => $table_alias ) {
				$id_column_alias = $element_selector->get_element_id_value(
					Toolset_Relationship_Role::role_from_name( $role_name )
				);

				$additional_joins[] = sprintf(
					" LEFT JOIN %s AS %s ON (%s.post_id = %s AND %s.meta_key = '%s') ",
					$this->wpdb->postmeta,
					$table_alias,
					$table_alias,
					$id_column_alias,
					$table_alias,
					esc_sql( $meta_key )
				);
			}
		}

		$results[] = $element_selector->get_join_clauses();

		// Append the additonal JOINs after the relationships table and tables
		// for the element ID resolution.
		$results = array_merge( $results, $additional_joins );

		return ' ' . implode( "\n", $results ) . ' ';
	}

}