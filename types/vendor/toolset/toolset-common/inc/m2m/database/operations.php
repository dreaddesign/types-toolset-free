<?php

/**
 * Holds helper methods related to native Toolset associations.
 *
 * Throughout m2m API, only these classes should directly touch the database:
 *
 * - Toolset_Relationship_Database_Operations
 * - Toolset_Relationship_Migration_Controller
 * - Toolset_Relationship_Driver
 * - Toolset_Relationship_Translation_View_Management
 * - Toolset_Association_Query
 *
 * @since m2m
 */
class Toolset_Relationship_Database_Operations {

	/**
	 * Warning: Changing this value in any way may break existing sites.
	 *
	 * @since m2m
	 */
	const MAXIMUM_RELATIONSHIP_SLUG_LENGTH = 255;


	/**
	 * Delimiter used in GROUP_CONCAT MySQL function.
	 */
	const GROUP_CONCAT_DELIMITER = ',';


	private static $instance;


	/** @var wpdb */
	private $wpdb;


	private $table_name;


	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Table_Name $table_name_di = null
	) {

		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}
		$this->table_name = ( null === $table_name_di ? new Toolset_Relationship_Table_Name() : $table_name_di );
	}


	/**
	 * Careful. This class is NOT meant to be a singleton. This is a temporary solution for easier transition
	 * from using static methods.
	 *
	 * @return Toolset_Relationship_Database_Operations
	 */
	public static function get_instance() {
		if( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Create new association and persist it.
	 *
	 * From outside of the m2m API, use Toolset_Relationship_Definition::create_association().
	 *
	 * @param Toolset_Relationship_Definition|string $relationship_definition_source Can also contain slug of
	 *     existing relationship definition.
	 * @param int|Toolset_Element|WP_Post $parent_source
	 * @param int|Toolset_Element|WP_Post $child_source
	 * @param int $intermediary_id
	 * @param bool $instantiate Whether to create an instance of the newly created association
	 *     or only return a result on success
	 *
	 * @return IToolset_Association|Toolset_Result
	 * @since m2m
	 * @throws Toolset_Element_Exception_Element_Doesnt_Exist
	 */
	public static function create_association( $relationship_definition_source, $parent_source, $child_source, $intermediary_id, $instantiate = true ) {

		$relationship_definition = Toolset_Relationship_Utils::get_relationship_definition( $relationship_definition_source );

		if ( ! $relationship_definition instanceof Toolset_Relationship_Definition ) {
			throw new InvalidArgumentException(
				sprintf(
					__( 'Relationship definition "%s" doesn\'t exist.', 'wpcf' ),
					is_string( $relationship_definition_source ) ? $relationship_definition_source : print_r( $relationship_definition_source, true )
				)
			);
		}

		$driver = $relationship_definition->get_driver();

		$result = $driver->create_association(
			$parent_source,
			$child_source,
			array(
				'intermediary_id' => $intermediary_id,
				'instantiate' => (bool) $instantiate
			)
		);

		return $result;
	}


	// The _id columns in the associations table
	const COLUMN_ID = '_id';

	// Columns in the relationships table
	const COLUMN_DOMAIN = '_domain';
	const COLUMN_TYPES = '_types';
	const COLUMN_CARDINALITY_MAX = 'cardinality_%s_max';
	const COLUMN_CARDINALITY_MIN = 'cardinality_%s_min';


	/**
	 * For a given role name, return the corresponding column in the associations table.
	 *
	 * @param string|IToolset_Relationship_Role $role
	 * @param string $column
	 *
	 * @return string
	 * @since m2m
	 */
	public function role_to_column( $role, $column = self::COLUMN_ID ) {

		if( $role instanceof IToolset_Relationship_Role ) {
			$role_name = $role->get_name();
		} else {
			$role_name = $role;
		}

		// Special cases
		if( in_array( $column, array( self::COLUMN_CARDINALITY_MAX, self::COLUMN_CARDINALITY_MIN ) ) ) {
			return sprintf( $column, $role_name );
		}

		return $role_name . $column;
	}


	/**
	 * Update the database to support the native m2m implementation.
	 *
	 * Practically that means creating the wp_toolset_associations table.
	 *
	 * @since m2m
	 *
	 * TODO is it possible to reliably detect dbDelta failure?
	 */
	public function do_native_dbdelta() {
		$this->create_associations_table();
		$this->create_relationship_table();
		$this->create_type_set_table();
		return true;
	}


	/**
	 * Execute a dbDelta() query, ensuring that the function is available.
	 *
	 * @param string $query MySQL query.
	 *
	 * @return array dbDelta return value.
	 */
	private static function dbdelta( $query ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		return dbDelta( $query );
	}


	/**
	 * Determine if a table exists in the database.
	 *
	 * @param string $table_name
	 *
	 * @return bool
	 * @since m2m
	 */
	public function table_exists( $table_name ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		return ( $wpdb->get_var( $query ) == $table_name );
	}


	private function get_charset_collate() {
		global $wpdb;
		return $wpdb->get_charset_collate();
	}


	/**
	 * Create the table for storing associations.
	 *
	 * Note: It is assumed that the table doesn't exist.
	 *
	 * @since m2m
	 */
	private function create_associations_table() {

		$association_table_name = $this->table_name->association_table();

		if ( $this->table_exists( $association_table_name ) ) {
			return;
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$association_table_name} (
				id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
				relationship_id bigint(20) UNSIGNED NOT NULL,
				parent_id bigint(20) UNSIGNED NOT NULL,
				child_id bigint(20) UNSIGNED NOT NULL,
				intermediary_id bigint(20) UNSIGNED NOT NULL,
				PRIMARY KEY  id (id),
				KEY relationship_id (relationship_id),
				KEY parent_id (parent_id, relationship_id),
				KEY child_id (child_id, relationship_id)
			) " . $this->get_charset_collate() . ";";

		self::dbdelta( $query );
	}


	/**
	 * Create the table for the relationship definitions.
	 *
	 * Note: It is assumed that the table doesn't exist.
	 *
	 * @since m2m
	 */
	private function create_relationship_table() {

		$table_name = $this->table_name->relationship_table();

		if ( $this->table_exists( $table_name ) ) {
			return;
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			slug varchar(" . self::MAXIMUM_RELATIONSHIP_SLUG_LENGTH . ") NOT NULL DEFAULT '',
			display_name_plural varchar(255) NOT NULL DEFAULT '',
			display_name_singular varchar(255) NOT NULL DEFAULT '',
			driver varchar(50) NOT NULL DEFAULT '',
			parent_domain varchar(20) NOT NULL DEFAULT '',
			parent_types bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			child_domain varchar(20) NOT NULL DEFAULT '',
			child_types bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			intermediary_type varchar(20) NOT NULL DEFAULT '',
			ownership varchar(8) NOT NULL DEFAULT 'none',
			cardinality_parent_max int(10) NOT NULL DEFAULT -1,
			cardinality_parent_min int(10) NOT NULL DEFAULT 0,
			cardinality_child_max int(10) NOT NULL DEFAULT -1,
			cardinality_child_min int(10) NOT NULL DEFAULT 0,
			is_distinct tinyint(1) NOT NULL DEFAULT 0,
			scope longtext NOT NULL DEFAULT '',
			origin varchar(50) NOT NULL DEFAULT '',
			role_name_parent varchar(255) NOT NULL DEFAULT '',
			role_name_child varchar(255) NOT NULL DEFAULT '',
			role_name_intermediary varchar(255) NOT NULL DEFAULT '',
			role_label_parent_singular VARCHAR(255) NOT NULL DEFAULT '',
			role_label_child_singular VARCHAR(255) NOT NULL DEFAULT '',
			role_label_parent_plural VARCHAR(255) NOT NULL DEFAULT '',
			role_label_child_plural VARCHAR(255) NOT NULL DEFAULT '',
			needs_legacy_support tinyint(1) NOT NULL DEFAULT 0,
			is_active tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY  id (id),
			KEY slug (slug),
			KEY is_active (is_active),
			KEY needs_legacy_support (needs_legacy_support),
			KEY parent_type (parent_domain, parent_types),
			KEY child_type (child_domain, child_types)
		) " . $this->get_charset_collate() . ";";

		self::dbdelta( $query );

	}


	private function create_type_set_table() {
		$table_name = $this->table_name->type_set_table();
		if ( $this->table_exists( $table_name ) ) {
			return;
		}

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			set_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			type varchar(20) NOT NULL DEFAULT '',
			PRIMARY KEY  id (id),
			KEY set_id (set_id),
			KEY type (type)
		) " . $this->get_charset_collate() . ";";

		self::dbdelta( $query );
	}



	/**
	 * When a relationship definition slug is renamed, update the association table (where the slug is used as a foreign key).
	 *
	 * The usage of this method is strictly limited to the m2m API, always change the slug via
	 * Toolset_Relationship_Definition_Repository::change_definition_slug().
	 *
	 * @param Toolset_Relationship_Definition $old_definition
	 * @param Toolset_Relationship_Definition $new_definition
	 *
	 * @return Toolset_Result
	 *
	 * @since m2m
	 */
	public function update_associations_on_definition_renaming(
		Toolset_Relationship_Definition $old_definition,
		Toolset_Relationship_Definition $new_definition
	) {
		$associations_table = new Toolset_Relationship_Table_Name;

		$rows_updated = $this->wpdb->update(
			$associations_table->association_table(),
			array( 'relationship_id' => $new_definition->get_row_id() ),
			array( 'relationship_id' => $old_definition->get_row_id() ),
			'%d',
			'%d'
		);

		$is_success = ( false !== $rows_updated );

		$message = (
		$is_success
			? sprintf(
			__( 'The association table has been updated with the new relationship slug "%s". %d rows have been updated.', 'wpcf' ),
			$new_definition->get_slug(),
			$rows_updated
		)
			: sprintf(
			__( 'There has been an error when updating the association table with the new relationship slug: %s', 'wpcf' ),
			$this->wpdb->last_error
		)
		);

		return new Toolset_Result( $is_success, $message );
	}


	/**
	 * Delete all associations from a given relationship.
	 *
	 * @param int $relationship_row_id
	 *
	 * @return Toolset_Result_Updated
	 */
	public function delete_associations_by_relationship( $relationship_row_id ) {

		$associations_table = $this->table_name->association_table();

		$result = $this->wpdb->delete(
			$associations_table,
			array( 'relationship_id' => $relationship_row_id ),
			array( '%d' )
		);

		if( false === $result ) {
			return new Toolset_Result_Updated(
				false, 0,
				sprintf( __( 'Database error when deleting associations: "%s"', 'wpcf' ), $this->wpdb->last_error )
			);
		} else {
			return new Toolset_Result_Updated(
				true, $result,
				sprintf( __( 'Deleted all associations for the relationship #%d', 'wpcf'), $relationship_row_id )
			);
		}
	}


	/**
	 * Build the part of the SELECT clause that is required for proper loading of a relationship definition.
	 *
	 * @param string $relationships_table_alias
	 * @param string $parent_types_table_alias
	 * @param string $child_types_table_alias
	 *
	 * @return string
	 * @since 2.5.4
	 */
	public function get_standard_relationships_select_clause(
		$relationships_table_alias = 'relationships',
		$parent_types_table_alias = 'parent_types_table',
		$child_types_table_alias = 'child_types_table'
	) {
		return "
			$relationships_table_alias.id AS id,
			$relationships_table_alias.slug AS slug,
			$relationships_table_alias.display_name_plural AS display_name_plural,
			$relationships_table_alias.display_name_singular AS display_name_singular,
			$relationships_table_alias.driver AS driver,
			$relationships_table_alias.parent_domain AS parent_domain,
			$relationships_table_alias.child_domain AS child_domain,
			$relationships_table_alias.intermediary_type AS intermediary_type,
			$relationships_table_alias.ownership AS ownership,
			$relationships_table_alias.cardinality_parent_max AS cardinality_parent_max,
			$relationships_table_alias.cardinality_parent_min AS cardinality_parent_min,
			$relationships_table_alias.cardinality_child_max AS cardinality_child_max,
			$relationships_table_alias.cardinality_child_min AS cardinality_child_min,
			$relationships_table_alias.is_distinct AS is_distinct,
			$relationships_table_alias.scope AS scope,
			$relationships_table_alias.origin AS origin,
			$relationships_table_alias.role_name_parent AS role_name_parent,
			$relationships_table_alias.role_name_child AS role_name_child,
			$relationships_table_alias.role_name_intermediary AS role_name_intermediary,
			$relationships_table_alias.role_label_parent_singular AS role_label_parent_singular,
			$relationships_table_alias.role_label_child_singular AS role_label_child_singular,
			$relationships_table_alias.role_label_parent_plural AS role_label_parent_plural,
			$relationships_table_alias.role_label_child_plural AS role_label_child_plural,
			$relationships_table_alias.needs_legacy_support AS needs_legacy_support,
			$relationships_table_alias.is_active AS is_active,
			$relationships_table_alias.parent_types AS parent_types_set_id,
			$relationships_table_alias.child_types AS child_types_set_id,
			GROUP_CONCAT(DISTINCT $parent_types_table_alias.type) AS parent_types,
			GROUP_CONCAT(DISTINCT $child_types_table_alias.type) AS child_types";
	}


	/**
	 * Build the part of the JOIN clause that is required for proper loading of a relationship definition.
	 *
	 * @param $type_set_table_name
	 * @param string $relationships_table_alias
	 * @param string $parent_types_table_alias
	 * @param string $child_types_table_alias
	 *
	 * @return string
	 * @since 2.5.4
	 */
	public function get_standard_relationships_join_clause(
		$type_set_table_name,
		$relationships_table_alias = 'relationships',
		$parent_types_table_alias = 'parent_types_table',
		$child_types_table_alias = 'child_types_table'
	) {
		return "
			JOIN {$type_set_table_name} AS {$parent_types_table_alias}
				ON ({$relationships_table_alias}.parent_types = {$parent_types_table_alias}.set_id )
			JOIN {$type_set_table_name} AS {$child_types_table_alias}
				ON ({$relationships_table_alias}.child_types = {$child_types_table_alias}.set_id )";
	}


	/**
	 * Build the part of the GROUP BY clause that is required for proper loading of a relationship definition.
	 *
	 * @param string $relationships_table_alias
	 *
	 * @return string
	 * @since 2.5.4
	 */
	public function get_standards_relationship_group_by_clause( $relationships_table_alias = 'relationships' ) {
		return "{$relationships_table_alias}.id";
	}


	public function load_all_relationships() {
		$relationship_table = $this->table_name->relationship_table();
		$type_set_table = $this->table_name->type_set_table();

		// The query is so complex because it needs to bring in data from the type set tables. But
		// those two joins are very cheap because we don't expect many records here.
		$query = "
			SELECT {$this->get_standard_relationships_select_clause()}
			FROM {$relationship_table} AS relationships
				{$this->get_standard_relationships_join_clause( $type_set_table )}
			GROUP BY {$this->get_standards_relationship_group_by_clause()}";

		$rows = toolset_ensarr( $this->wpdb->get_results( $query ) );
		return $rows;
	}


	/**
	 * Update 'type' on 'toolset_type_sets'
	 *
	 * @param string $new_type
	 * @param string $old_type
	 *
	 * @return Toolset_Result
	 */
	public function update_type_on_type_sets( $new_type, $old_type ) {
		$rows_updated = $this->wpdb->update(
			$this->table_name->type_set_table(),
			array( 'type' => $new_type ),
			array( 'type' => $old_type ),
			'%s',
			'%s'
		);

		$is_success = ( false !== $rows_updated );

		$message = $is_success
			? sprintf(
				__( 'The type_sets table has been updated with the new type "%s". %d rows have been updated.', 'wpcf' ),
				$new_type,
				$rows_updated
			)
			: sprintf(
				__( 'There has been an error when updating the type_sets table with the new type "%s": %s', 'wpcf' ),
				$new_type,
				$this->wpdb->last_error
			);

		return new Toolset_Result( $is_success, $message );
	}


	/**
	 * Queries all post's associations and delete them.
	 *
	 * That should trigger deleting the intermediary posts and owned elements.
	 *
	 * @param IToolset_Element $element
	 * @deprecated Should be unused since 2.5.10. Replaced by Toolset_Association_Cleanup_Post.
	 */
	public function delete_associations_involving_element( $element ) {

		trigger_error(
			'Toolset_Relationship_Database_Operations::delete_associations_involving_element() is deprecated and should not be used anymore.',
			E_USER_NOTICE
		);

		$query_parent = new Toolset_Association_Query( array(
			Toolset_Association_Query::QUERY_PARENT_DOMAIN => $element->get_domain(),
			Toolset_Association_Query::QUERY_PARENT_ID => $element->get_id(),
			Toolset_Association_Query::OPTION_RETURN => Toolset_Association_Query::RETURN_ASSOCIATIONS
		) );

		$associations = $query_parent->get_results();

		$query_child = new Toolset_Association_Query( array(
			Toolset_Association_Query::QUERY_PARENT_DOMAIN => $element->get_domain(),
			Toolset_Association_Query::QUERY_PARENT_ID => $element->get_id(),
			Toolset_Association_Query::OPTION_RETURN => Toolset_Association_Query::RETURN_ASSOCIATIONS
		) );

		/** @var Toolset_Association[] $associations */
		$associations = array_merge( $associations, $query_child->get_results() );

		foreach( $associations as $association ) {
			$definition = $association->get_definition();
			$driver = $definition->get_driver();
			$driver->delete_association( $association );
		}

	}


	/**
	 * Updates association intermediary post
	 *
	 * @param int $association_id Association trID
	 * @param int $intermediary_id New intermediary ID
	 * @since m2m
	 */
	public function update_association_intermediary_id( $association_id, $intermediary_id ) {
		$this->wpdb->update(
			$this->table_name->association_table(),
			array(
				'intermediary_id' => $intermediary_id,
			),
			array(
				'id' => $association_id,
			),
			array( '%d' )
		);
	}


	/**
	 * Returns the maximun number of associations of a relationship for a parent id and a child id
	 *
	 * @param int    $relationship_id Relationship ID.
	 * @param string $role_name Role name.
	 * @return int
	 * @throws InvalidArgumentException In case of error.
	 */
	public function count_max_associations( $relationship_id, $role_name ) {
		if ( ! in_array( $role_name, Toolset_Relationship_Role::parent_child_role_names() ) ) {
			throw new InvalidArgumentException( 'Wrong role name' );
		}
		$associations_table = Toolset_Relationship_Table_Name::associations();
		$count = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT max(n) count
					FROM (
						SELECT count(*) n
							FROM {$associations_table}
							WHERE relationship_id = %d
							GROUP BY {$role_name}_id
					) count", $relationship_id ) );
		return $count;
	}
}
