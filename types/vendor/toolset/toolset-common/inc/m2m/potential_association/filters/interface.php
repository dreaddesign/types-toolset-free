<?php

/**
 * Interface for all potential association query filters.
 *
 * @since m2m
 */
interface Toolset_Potential_Association_Query_Filter_Interface {
	
	/**
	 * Main method to modiy the query arguments.
	 *
	 * @since m2m
	 */
	public function filter( array $query_arguments);
	
}