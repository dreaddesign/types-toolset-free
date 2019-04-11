<?php

/**
 * Removes a single association from the database and cleans up after.
 *
 * That means also deleting the intermediary post, if it exists.
 *
 * @since 2.5.10
 */
class Toolset_Association_Cleanup_Association extends Toolset_Wpdb_User {


	/** @var Toolset_Relationship_Table_Name */
	private $table_name;


	/** @var Toolset_Association_Intermediary_Post_Persistence|null */
	private $_intermediary_post_persistence;


	/**
	 * Toolset_Association_Cleanup_Association constructor.
	 *
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Association_Intermediary_Post_Persistence|null $intermediary_post_persistence_di
	 */
	public function __construct(
		Toolset_Relationship_Table_Name $table_name_di = null,
		wpdb $wpdb_di = null,
		Toolset_Association_Intermediary_Post_Persistence $intermediary_post_persistence_di = null
	) {
		parent::__construct( $wpdb_di );
		$this->table_name = $table_name_di ?: new Toolset_Relationship_Table_Name();
		$this->_intermediary_post_persistence = $intermediary_post_persistence_di;
	}


	/**
	 * @return Toolset_Association_Intermediary_Post_Persistence
	 */
	private function get_intermediary_post_persistence() {
		if( null === $this->_intermediary_post_persistence ) {
			$this->_intermediary_post_persistence = new Toolset_Association_Intermediary_Post_Persistence();
		}
		return $this->_intermediary_post_persistence;
	}



	/**
	 * Permanently delete the provided association.
	 *
	 * @param IToolset_Association $association Association to delete. Do not use the instance
	 *     after passing it to this method.
	 *
	 * @return Toolset_Result
	 */
	public function delete( IToolset_Association $association ) {
		$this->get_intermediary_post_persistence()->maybe_delete_intermediary_post( $association );

		$rows_updated = $this->wpdb->delete(
			$this->table_name->association_table(),
			array( 'id' => $association->get_uid() ),
			'%d'
		);

		$is_success = ( false !== $rows_updated || 1 === $rows_updated );

		return new Toolset_Result( $is_success );
	}

}