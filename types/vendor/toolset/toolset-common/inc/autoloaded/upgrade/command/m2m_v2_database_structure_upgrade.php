<?php

/**
 * Transform the m2m table structures and data from the m2m-v2 beta release to m2m-v3 beta.
 *
 * Note: This will fail terribly if executed for the second time.
 *
 * @since m2m
 */
class Toolset_Upgrade_Command_M2M_V2_Database_Structure_Upgrade implements IToolset_Upgrade_Command {


	/** @var wpdb */
	private $wpdb;


	/** @var Toolset_Relationship_Database_Operations|null */
	private $_database_operations;


	/**
	 * Toolset_Upgrade_Command_M2M_V2_Database_Structure_Upgrade constructor.
	 *
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Database_Operations|null $relationship_database_operations_di
	 */
	public function __construct(
		wpdb $wpdb_di = null,
		Toolset_Relationship_Database_Operations $relationship_database_operations_di = null
	) {
		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->_database_operations = $relationship_database_operations_di;
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

		error_log( 'The routine Toolset_Upgrade_Command_M2M_V2_Database_Structure_Upgrade::run() is starting' );

		$results = new Toolset_Result_Set();

		$results->add( $this->update_relationships_table() );

		error_log( 'The routine Toolset_Upgrade_Command_M2M_V2_Database_Structure_Upgrade::run() has finished' );

		return $results;
	}


	/**
	 * If role_name_parent_plural exists in relationship database, it means that we're dealing with a more recent database structure than this
	 * command aims to improve.
	 *
	 * @return bool
	 * @since m2m
	 */
	private function is_database_already_up_to_date() {
		$table_name = $this->get_relationships_table_name();
		$query = $this->wpdb->prepare( "SHOW COLUMNS FROM {$table_name} WHERE field = %s", 'role_label_parent_plural' );
		$row = $this->wpdb->get_row( $query );
		$is_updated = ! empty( $row );
		return $is_updated;
	}


	private function update_relationships_table() {

		$query = "ALTER TABLE `{$this->get_relationships_table_name()}`
			ADD `role_label_parent_singular` VARCHAR(255) NOT NULL AFTER `role_name_intermediary`,
			ADD `role_label_child_singular` VARCHAR(255) NOT NULL AFTER `role_label_parent_singular`,
			ADD `role_label_parent_plural` VARCHAR(255) NOT NULL AFTER `role_label_child_singular`,
			ADD `role_label_child_plural` VARCHAR(255) NOT NULL AFTER `role_label_parent_plural`";

		$this->wpdb->query( $query );

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


	private function get_relationships_table_name() {
		return $this->wpdb->prefix . 'toolset_relationships';
	}
}
