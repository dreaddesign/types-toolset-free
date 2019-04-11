<?php

/**
 * Handle a missing element that might be involved in a number of associations.
 *
 * This will delete all affected associations and also intermediary posts of such associations.
 * If invalid parameters are provided, the method does nothing.
 *
 * @since 2.5.6
 */
class Toolset_Relationship_Database_Issue_Missing_Element implements IToolset_Relationship_Database_Issue {


	/** @var wpdb  */
	private $wpdb;

	/** @var Toolset_Relationship_Table_Name */
	private $table_name;

	/** @var Toolset_Relationship_Query_Factory */
	private $query_factory;

	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;

	/** @var string */
	private $domain;

	/** @var int */
	private $element_id;


	/**
	 * Toolset_Relationship_Database_Issue_Missing_Element constructor.
	 *
	 * @param string $domain Element domain.
	 * @param int $element_id ID of the missing element.
	 * @param wpdb|null $wpdb_di
	 * @param Toolset_Relationship_Table_Name|null $table_name_di
	 * @param Toolset_Relationship_Query_Factory|null $query_factory_di
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 */
	public function __construct(
		$domain, $element_id,
		wpdb $wpdb_di = null, Toolset_Relationship_Table_Name $table_name_di = null,
		Toolset_Relationship_Query_Factory $query_factory_di = null,
		Toolset_Relationship_Database_Operations $database_operations_di = null
	) {
		if( null === $wpdb_di ) {
			global $wpdb;
			$this->wpdb = $wpdb;
		} else {
			$this->wpdb = $wpdb_di;
		}

		$this->table_name = (
		null === $table_name_di
			? new Toolset_Relationship_Table_Name()
			: $table_name_di
		);

		$this->query_factory = (
		null === $query_factory_di
			? new Toolset_Relationship_Query_Factory()
			: $query_factory_di
		);

		$this->database_operations = (
		null === $database_operations_di
			? new Toolset_Relationship_Database_Operations()
			: $database_operations_di
		);

		if(
			! in_array( $domain,  Toolset_Element_Domain::all(), true )
			|| ! Toolset_Utils::is_natural_numeric( $element_id )
		) {
			throw new InvalidArgumentException();
		}

		$this->domain = $domain;
		$this->element_id = $element_id;
	}


	/**
	 * Handle the issue.
	 */
	public function handle() {

		// Gather in relationships that have the correct domain in one and in the other role.
		$results = array();
		foreach( Toolset_Relationship_Role::parent_child() as $role ) {
			$query = $this->query_factory->relationships_v2();
			$relationships = $query->do_not_add_default_conditions()
				->add( $query->has_domain( $this->domain, $role ) )
				->get_results();

			$results[ $role->get_name() ] = $relationships;
		}

		// Delete what needs to be deleted. Note that we might be performing a lot of MySQL queries here,
		// but it's an one-time thing, so I prefer a cleaner, safer implementation over performance.
		foreach( $results as $role_name => $relationships ) {
			/** @var Toolset_Relationship_Definition $relationship */
			foreach( $relationships as $relationship ) {
				$this->delete_intermediary_posts( $relationship, $role_name, $this->element_id );
				$this->delete_associations( $relationship, $role_name, $this->element_id );
			}
		}
	}


	/**
	 * Delete intermediary posts from all associations in a given relationship that have
	 * the given element in the given role.
	 *
	 * @param Toolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	private function delete_intermediary_posts( $relationship, $element_role_name, $element_id ) {
		$element_id_column = $this->database_operations->role_to_column( $element_role_name, Toolset_Relationship_Database_Operations::COLUMN_ID );

		$intermediary_post_ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT intermediary_id FROM {$this->table_name->association_table()} 
				WHERE relationship_id = %d AND {$element_id_column} = %d",
				$relationship->get_row_id(),
				$element_id
			)
		);

		foreach( $intermediary_post_ids as $post_id ) {
			wp_delete_post( $post_id );
		}
	}


	/**
	 * Delete all associations of a given relationships that have the given element in the given role.
	 *
	 * @param Toolset_Relationship_Definition $relationship
	 * @param string $element_role_name
	 * @param int $element_id
	 */
	private function delete_associations( $relationship, $element_role_name, $element_id ) {
		$element_id_column = $this->database_operations->role_to_column( $element_role_name, Toolset_Relationship_Database_Operations::COLUMN_ID );

		$this->wpdb->delete(
			$this->table_name->association_table(),
			array(
				'relationship_id' => $relationship->get_row_id(),
				$element_id_column => $element_id
			),
			array( '%d', '%d' )
		);
	}

}