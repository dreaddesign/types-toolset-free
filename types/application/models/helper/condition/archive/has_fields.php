<?php

/**
 * Types_Helper_Condition_Archive_Has_Fields
 *
 * @since 2.0
 */
class Types_Helper_Condition_Archive_Has_Fields extends Types_Helper_Condition_Archive_No_Fields {

	public function valid() {
		if( ! $this->has_archive() )
			return false;

		$template = $this->find_template();

		// no template available
		if( empty( $template ) )
			return false;

		// opposite of parents "No Fields".
		return ! parent::valid();
	}
}