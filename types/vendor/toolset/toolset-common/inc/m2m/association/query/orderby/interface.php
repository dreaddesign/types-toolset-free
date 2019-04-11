<?php

/**
 * Interface for objects that handle the ORDER BY clause when building the association query.
 *
 * A dedicated set of classes is needed because sometimes, this also involves joining additional tables.
 *
 * @since 2.5.8
 */
interface IToolset_Association_Query_Orderby {


	/**
	 * Set the order direction.
	 *
	 * @param string $order 'ASC'|'DESC'
	 * @return void
	 */
	public function set_order( $order );


	/**
	 * Build the ORDER BY clause (not including the "ORDER BY" keyword).
	 *
	 * @return string
	 */
	public function get_orderby_clause();


	/**
	 * If the class uses a join manager, request all needed joins now.
	 *
	 * @return void
	 */
	public function register_joins();
}