<?php

/**
 * Factory for the purpose of dependency injection.
 *
 * @since m2m
 */
class Toolset_Post_Type_Query_Factory {

	public function create( $query_args ) {
		return new Toolset_Post_Type_Query( $query_args );
	}

}