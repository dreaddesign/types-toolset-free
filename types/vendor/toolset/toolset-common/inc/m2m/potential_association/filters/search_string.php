<?php

/**
 * Filter the potential association query by a given string.
 *
 * @since m2m
 */
class Toolset_Potential_Association_Query_Filter_Search_String
	implements Toolset_Potential_Association_Query_Filter_Interface {
	
	/**
	 * Maybe filter the list of options by a given string.
	 *
	 * @param array $query_arguments The potential association query arguments.
	 *
	 * @return array
	 *
	 * @since m2m
	 */
	public function filter( array $query_arguments ) {
		if ( $search_string = toolset_getpost( 'q' ) ) {
			$query_arguments['search_string'] = $search_string;
		}

		return $query_arguments;
	}
	
}