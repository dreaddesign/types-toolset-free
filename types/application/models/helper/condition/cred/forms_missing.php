<?php

/**
 * Types_Helper_Condition_Cred_Forms_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Cred_Forms_Missing extends Types_Helper_Condition_Cred_Forms_Exist {

	public function valid() {
		// if views not active
		if( ! defined( 'CRED_FE_VERSION' ) )
			return false;

		// opposite of parent "forms exists"
		return ! parent::valid();
	}

}