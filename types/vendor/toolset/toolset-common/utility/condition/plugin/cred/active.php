<?php

/**
 * Toolset_Condition_Plugin_Cred_Active
 *
 * @since 2.3.0
 */
class Toolset_Condition_Plugin_Cred_Active implements Toolset_Condition_Interface {

	public function is_met() {
		if( defined( 'CRED_FE_VERSION' ) )
			return true;

		return false;
	}

}