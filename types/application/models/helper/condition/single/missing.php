<?php

/**
 * Types_Helper_Condition_Single_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Single_Missing extends Types_Helper_Condition_Single_Exists {
	// this is valid if no template is found
	public function valid() {
		// opposite of parents "Single Exists"
		return ! parent::valid();
	}
}