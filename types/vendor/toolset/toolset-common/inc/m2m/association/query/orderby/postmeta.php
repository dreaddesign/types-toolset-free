<?php

/**
 * Order associations by a postmeta value of an (post) element of given role.
 *
 * Note: Using this on an element of a wrong domain will exclude all associations from the results.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Orderby_Postmeta extends Toolset_Association_Query_Orderby {


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/** @var string */
	private $meta_key;


	/**
	 * If the metakey needs to be casted into a different type (UNSIGNED, DATE, ..)
	 *
	 * @var string
	 */
	private $cast_to;


	/**
	 * List of allowed casting types
	 *
	 * @var array
	 */
	private $allowed_mysql_types = array( 'SIGNED', 'UNSIGNED', 'DATE', 'DATETIME', 'CHAR' );


	/**
	 * Toolset_Association_Query_Orderby_Postmeta constructor.
	 *
	 * @param string $meta_key
	 * @param IToolset_Relationship_Role $role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param string $cast_to If the metakey needs to be casted into a different type
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		$meta_key,
		IToolset_Relationship_Role $role,
		Toolset_Association_Query_Table_Join_Manager $join_manager,
		$cast_to = null
	) {
		parent::__construct( $join_manager );

		if ( ! is_string( $meta_key ) || empty( $meta_key ) ) {
			throw new InvalidArgumentException();
		}

		$this->meta_key = $meta_key;
		$this->for_role = $role;
		if ( null !== $cast_to && ! in_array( strtoupper( $cast_to ), $this->allowed_mysql_types, true ) ) {
			throw new InvalidArgumentException();
		}
		$this->cast_to = $cast_to;
	}


	/**
	 * @inheritdoc
	 */
	public function register_joins() {
		$this->join_manager->wp_postmeta( $this->for_role, $this->meta_key );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_orderby_clause() {
		$postmeta_table_alias = $this->join_manager->wp_postmeta( $this->for_role, $this->meta_key );
		if ( $this->cast_to ) {
			return "CAST({$postmeta_table_alias}.meta_value AS {$this->cast_to}) {$this->order}";
		} else {
			return "{$postmeta_table_alias}.meta_value {$this->order}";
		}
	}
}
