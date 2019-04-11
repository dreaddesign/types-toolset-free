<?php

/**
 * Types_Helper_Condition_Cred_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Cred_Missing extends Types_Helper_Condition_Cred_Active {

	public function valid() {
		return ! parent::valid();
	}

}