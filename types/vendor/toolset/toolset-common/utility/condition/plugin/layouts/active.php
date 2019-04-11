<?php

/**
 * Toolset_Condition_Plugin_Layouts_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Layouts_Active implements Toolset_Condition_Interface {

	public function is_met() {
		if( defined( 'WPDDL_DEVELOPMENT' ) || defined( 'WPDDL_PRODUCTION' ) ) {
			return true;
		}

		return false;
	}

}