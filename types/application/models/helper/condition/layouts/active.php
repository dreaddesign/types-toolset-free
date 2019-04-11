<?php

/**
 * Types_Helper_Condition_Layouts_Active
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Active extends Types_Helper_Condition {

	public function valid() {
		if( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) )
			return true;

		return false;
	}

}