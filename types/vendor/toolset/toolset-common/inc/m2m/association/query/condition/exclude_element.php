<?php

/**
 * Condition to exclude a particular element from the results.
 *
 * See the parent class for details.
 *
 * @since 2.5.10
 */
class Toolset_Association_Query_Condition_Exclude_Element extends Toolset_Association_Query_Condition_Element_Id_And_Domain {

	protected function get_operator() {
		return '!=';
	}

}