<?php

/**
 * Order associations by title of an element of given role.
 *
 * Note: Currently, only the posts domain is supported.
 *
 * Note: Ordering by intermediary posts will exclude associations that don't have one.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Orderby_Title extends Toolset_Association_Query_Orderby {


	/** @var IToolset_Relationship_Role */
	private $for_role;


	/**
	 * Toolset_Association_Query_Orderby_Title constructor.
	 *
	 * @param IToolset_Relationship_Role $role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 */
	public function __construct(
		IToolset_Relationship_Role $role,
		Toolset_Association_Query_Table_Join_Manager $join_manager
	) {
		parent::__construct( $join_manager );

		$this->for_role = $role;
	}


	/**
	 * @inheritdoc
	 */
	public function register_joins() {
		$this->join_manager->wp_posts( $this->for_role );
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_orderby_clause() {
		$posts_table_alias = $this->join_manager->wp_posts( $this->for_role );

		return "{$posts_table_alias}.post_title {$this->order}";
	}
}