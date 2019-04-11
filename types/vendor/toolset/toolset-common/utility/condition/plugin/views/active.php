<?php

/**
 * Toolset_Condition_Plugin_Views_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Views_Active implements Toolset_Condition_Interface {

	public function is_met() {
		if( defined( 'WPV_VERSION' ) )
			return true;

		return false;
	}

}