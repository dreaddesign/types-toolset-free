<?php

/**
 * Types_Helper_Condition_Views_Views_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Views_Views_Missing extends Types_Helper_Condition_Views_Views_Exist {

	public function valid() {
		// if views not active
		if( ! defined( 'WPV_VERSION' ) )
			return false;

		// opposite of parent "Views exists"
		return ! parent::valid();
	}

}