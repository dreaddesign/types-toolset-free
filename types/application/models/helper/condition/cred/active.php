<?php


/**
 * Types_Helper_Condition_Cred_Active
 *
 * @since 2.0
 */
class Types_Helper_Condition_Cred_Active extends Types_Helper_Condition {

	public function valid() {
		if( defined( 'CRED_FE_VERSION' ) )
			return true;

		return false;
	}

}