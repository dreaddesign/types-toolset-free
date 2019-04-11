<?php

/**
 * Types_Helper_Condition_Single_Has_Fields
 *
 * @since 2.0
 */
class Types_Helper_Condition_Single_Has_Fields extends Types_Helper_Condition_Single_No_Fields {

	public function valid() {

		$template = $this->find_template();

		// no template available
		if( empty( $template ) )
			return false;

		// opposite of parents "No Fields".
		return ! parent::valid();
	}
}