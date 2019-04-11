<?php

/**
 * Transform the m2m table structures and data from the m2m-v1 beta release to be up-to-date with changes
 * implemented in toolsetcommon-305 (aimed for the m2m-v2 beta release).
 *
 * Obviously, the upgrade will run only if m2m is enabled at the time of the upgrade.
 *
 * Note: We're hardcoding a bunch of things here and the code is not really DRY. That's on purpose: This upgrade
 * command aims at a specific database structure and by using hardcoded values (e.g. for table names)
 * we're becoming immune even to unlikely changes like future renaming of tables or columns.
 *
 * Note: This might fail terribly if executed for the second time.
 *
 * @since 2.5.4
 */
class Toolset_Upgrade_Command_M2M_V1_Database_Structure_Upgrade implements IToolset_Upgrade_Command {


	/** @var wpdb */
	private $wpdb;


	/** @var Toolset_Relationship_Database_Operations|null */
	private $_database_operations;


	/**
	 * @var int Next free set_id value for the type set table. We know it starts at 1 because when this command
	 *     runs, the table doesn't exist yet.
	 */
	private $next_type_set_id = 1;


	private $constants;


	/**
	 * Toolset_Upgrade_Command_M2M_V1_Database_Structure_Upgrade constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Database_Operations|null $relationship_database_operations_di
	 * @param Toolset_Constants|null $constants_di
	 */
	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Database_Operations $relationship_database_operations_di = null,
		Toolset_Constants $constants_di = null
	) {
		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->_database_operations = $relationship_database_operations_di;
		$this->constants = $constants_di ?: new Toolset_Constants();
	}


	/**
	 * Run the command.
	 *
	 * @return Toolset_Result|Toolset_Result_Set
	 */
	public function run() {

		if( ! apply_filters( 'toolset_is_m2m_enabled', false ) ) {
			// Nothing to do here: The tables will be created as soon as m2m is activated for the first time.
			return new Toolset_Result( true );
		}

		if( $this->is_database_already_up_to_date() ) {
			// Nothing to do here: This happens when Types is activated on a fresh site: It creates
			// the tables according to the new structure but runs the upgrade routine at the same time.
			return new Toolset_Result( true );
		}

		error_log( 'The routine Toolset_Upgrade_Command_M2M_V1_Database_Structure_Upgrade::run() is starting' );

		$results = new Toolset_Result_Set();

		$results->add( $this->create_post_type_set_table() );
		$results->add( $this->transform_post_type_sets() );
		$results->add( $this->change_relationship_type_column_datatypes() );

		$results->add( $this->add_extra_relationship_columns() );
		$results->add( $this->transform_extra_relationship_data() );
		$results->add( $this->drop_relationship_extra_column() );

		$results->add( $this->add_indexes_for_relationships_table() );

		$results->add( $this->add_relationship_id_column_for_associations() );
		$results->add( $this->transform_relationship_references_for_associations() );
		$results->add( $this->remove_relationship_slug_column_for_associations() );

		$results->add( $this->add_indexes_for_associations_table() );

		error_log( 'The routine Toolset_Upgrade_Command_M2M_V1_Database_Structure_Upgrade::run() has finished' );

		// This is a very basic check since dbDelta has a very bad way of reporting operation results.
		// But if this detects a failure, we *know* for sure something went really wrong.
		if( ! $this->is_database_already_up_to_date() ) {
			$results->add( false, 'The m2m-v1 database structure upgrade to m2m-v2 seems to have failed.' );
		}

		return $results;
	}


	/**
	 * If the type set table exists, it means that we're dealing with a more recent database structure than this
	 * command aims to improve.
	 *
	 * @return bool
	 */
	private function is_database_already_up_to_date() {
		return (
			$this->is_type_set_table_present()
			&& $this->are_new_relationship_columns_present()
		);
	}


	private function is_type_set_table_present() {
		$table_name = $this->get_type_set_table_name();
		$type_set_table_query = $this->wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name );
		$type_set_table_already_exists = ( $this->wpdb->get_var( $type_set_table_query ) == $table_name );

		return $type_set_table_already_exists;
	}


	private function are_new_relationship_columns_present() {
		$relationship_column_query = "SELECT * 
			FROM information_schema.COLUMNS 
			WHERE 
				TABLE_NAME = '{$this->get_relationships_table_name()}'
				AND TABLE_SCHEMA = '{$this->constants->constant( 'DB_NAME' )}' 
			AND COLUMN_NAME IN ( 
				'display_name_plural', 'display_name_singular', 'role_name_parent', 'role_name_child',
				'role_name_intermediary', 'needs_legacy_support', 'is_active'
			)";

		$results = $this->wpdb->get_results( $relationship_column_query );
		return ( count( $results ) === 7 );
	}


	private function create_post_type_set_table() {

		// Note that dbDelta is very sensitive about details, almost nothing here is arbitrary.
		$query = "CREATE TABLE {$this->get_type_set_table_name()} (
				id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    			set_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    			type VARCHAR(20) NOT NULL DEFAULT '',
    			PRIMARY KEY  id (id),
			    KEY set_id (set_id),
			    KEY type (type)
			) {$this->wpdb->get_charset_collate()};";

		$output = self::dbdelta( $query );

		return new Toolset_Result( true, implode( "\n", $output ) );
	}


	private function transform_post_type_sets() {
		$relationships = $this->wpdb->get_results(
			"SELECT id, parent_types, child_types FROM {$this->get_relationships_table_name()}"
		);

		$results = new Toolset_Result_Set();

		foreach( $relationships as $relationship ) {
			$parent_types = $this->save_post_type_set( maybe_unserialize( $relationship->parent_types ) );
			$child_types = $this->save_post_type_set( maybe_unserialize( $relationship->child_types ) );

			$output = $this->wpdb->update(
				$this->get_relationships_table_name(),
				array(
					'parent_types' => $parent_types,
					'child_types' => $child_types,
				),
				array(
					'id' => (int) $relationship->id,
				),
				'%s',
				'%d'
			);

			$results->add( false !== $output, $this->wpdb->last_error );
		}

		return $results;
	}


	private function change_relationship_type_column_datatypes() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_relationships_table_name()}
			MODIFY parent_types bigint(20) UNSIGNED NOT NULL DEFAULT 0,
			MODIFY child_types bigint(20) UNSIGNED NOT NULL DEFAULT 0"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
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


	private function get_type_set_table_name() {
		return $this->wpdb->prefix . 'toolset_type_sets';
	}


	private function get_relationships_table_name() {
		return $this->wpdb->prefix . 'toolset_relationships';
	}


	private function get_associations_table_name() {
		return $this->wpdb->prefix . 'toolset_associations';
	}


	private function save_post_type_set( $post_types ) {
		$set_id = $this->next_type_set_id++;

		foreach( $post_types as $post_type ) {
			$this->wpdb->insert(
				$this->get_type_set_table_name(),
				array(
					'set_id' => $set_id,
					'type' => $post_type,
				),
				array( '%d', '%s' )
			);
		}

		return $set_id;
	}


	private function add_extra_relationship_columns() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_relationships_table_name()}
			CHANGE COLUMN display_name display_name_plural varchar(255) NOT NULL DEFAULT '',
			ADD COLUMN display_name_singular varchar(255) NOT NULL DEFAULT '',
			ADD COLUMN role_name_parent varchar(255) NOT NULL DEFAULT '',
    		ADD COLUMN role_name_child varchar(255) NOT NULL DEFAULT '',
    		ADD COLUMN role_name_intermediary varchar(255) NOT NULL DEFAULT '',
    		ADD COLUMN needs_legacy_support tinyint(1) NOT NULL DEFAULT 0,
    		ADD COLUMN is_active tinyint(1) NOT NULL DEFAULT 0"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
	}


	private function transform_extra_relationship_data() {
		$relationships = $this->wpdb->get_results(
			"SELECT id, extra FROM {$this->get_relationships_table_name()}"
		);

		$results = new Toolset_Result_Set();

		foreach( $relationships as $relationship ) {
			$extra_data = maybe_unserialize( $relationship->extra );

			$output = $this->wpdb->update(
				$this->get_relationships_table_name(),
				array(
					'role_name_parent' => 'parent',
					'role_name_child' => 'child',
					'role_name_intermediary' => 'association',
					'needs_legacy_support' => ( toolset_getarr( $extra_data, 'needs_legacy_support', 0 ) ? 1 : 0 ),
					'is_active' => ( toolset_getarr( $extra_data, 'is_active', 1 ) ? 1 : 0 ),
					'display_name_singular' => toolset_getarr( $extra_data, 'display_name_singular', '' ),
				),
				array( 'id' => (int) $relationship->id ),
				array( '%s', '%s', '%s', '%d', '%d', '%s' ),
				'%d'
			);

			$results->add( false !== $output, $this->wpdb->last_error );
		}

		return $results;
	}


	private function drop_relationship_extra_column() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_relationships_table_name()}
			DROP COLUMN extra"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
	}


	private function add_indexes_for_relationships_table() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_relationships_table_name()}
			ADD INDEX is_active (is_active),
			ADD INDEX needs_legacy_support (needs_legacy_support),
			ADD INDEX parent_type (parent_domain, parent_types),
			ADD INDEX child_type (child_domain, child_types)"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
	}


	private function add_relationship_id_column_for_associations() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_associations_table_name()}
			ADD COLUMN relationship_id bigint(20) UNSIGNED NOT NULL"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
	}


	private function transform_relationship_references_for_associations() {
		$relationships = $this->wpdb->get_results(
			"SELECT id, slug FROM {$this->get_relationships_table_name()}"
		);

		$results = new Toolset_Result_Set();

		foreach( $relationships as $relationship ) {
			$output = $this->wpdb->update(
				$this->get_associations_table_name(),
				array( 'relationship_id' => $relationship->id ),
				array( 'relationship' => $relationship->slug ),
				'%d',
				'%s'
			);

			$results->add( false !== $output, $this->wpdb->last_error );
		}

		return $results;
	}


	private function remove_relationship_slug_column_for_associations() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_associations_table_name()}
			DROP COLUMN relationship"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
	}


	private function add_indexes_for_associations_table() {
		$output = $this->wpdb->query(
			"ALTER TABLE {$this->get_associations_table_name()}
			ADD INDEX relationship_id (relationship_id),
			ADD INDEX parent_id (parent_id, relationship_id),
			ADD INDEX child_id (child_id, relationship_id)"
		);

		return new Toolset_Result( false !== $output, $this->wpdb->last_error );
	}
}