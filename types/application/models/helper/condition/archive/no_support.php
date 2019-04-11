<?php

/**
 * Types_Helper_Condition_Archive_No_Support
 *
 * @since 2.0
 */
class Types_Helper_Condition_Archive_No_Support extends Types_Helper_Condition_Archive_Support {

	public function __construct() {}

	public function valid() {
		return ! parent::valid();
	}

}