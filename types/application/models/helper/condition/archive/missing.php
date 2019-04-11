<?php

/**
 * Types_Helper_Condition_Archive_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Archive_Missing extends Types_Helper_Condition_Archive_Exists {
	// this is valid if no template is found
	public function valid() {
		if( ! $this->has_archive() )
			return false;

		// opposite of parents "Archive Exists"
		return ! parent::valid();
	}
}