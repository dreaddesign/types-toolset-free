<?php

/**
 * Toolset_Condition_Plugin_Types_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Types_Active implements Toolset_Condition_Interface {

	public function is_met() {
		if( defined( 'TYPES_VERSION' ) )
			return true;

		return false;
	}

}