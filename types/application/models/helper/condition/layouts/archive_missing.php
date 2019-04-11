<?php

/**
 * Types_Helper_Condition_Layouts_Archive_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Archive_Missing extends Types_Helper_Condition_Layouts_Archive_Exists {

	public function valid() {
		if( ! defined( 'WPDDL_GENERAL_OPTIONS' ) )
			return false;

		// opposite of parents "exists"
		return ! parent::valid();
	}

}