<?php

/**
 * Factory for IToolset_Association_Query_Orderby.
 */
class Toolset_Association_Query_Orderby_Factory {


	/**
	 * @return IToolset_Association_Query_Orderby
	 */
	public function nothing() {
		return new Toolset_Association_Query_Orderby_Nothing();
	}


	/**
	 * @param IToolset_Relationship_Role $role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 *
	 * @return IToolset_Association_Query_Orderby
	 */
	public function title( IToolset_Relationship_Role $role, Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		return new Toolset_Association_Query_Orderby_Title( $role, $join_manager );
	}


	/**
	 * @param string $meta_key
	 * @param IToolset_Relationship_Role $role
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 * @param string|null $cast_to If the metakey needs to be casted into a different type
	 *
	 * @return IToolset_Association_Query_Orderby
	 */
	public function postmeta( $meta_key, IToolset_Relationship_Role $role, Toolset_Association_Query_Table_Join_Manager $join_manager, $cast_to = null ) {
		return new Toolset_Association_Query_Orderby_Postmeta( $meta_key, $role, $join_manager, $cast_to );
	}

}
