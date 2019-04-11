<?php

/**
 * Types_Helper_Condition_Screen
 *
 * @since 2.0
 */
class Types_Helper_Condition_Screen extends Types_Helper_Condition {

	// check if current screen is screen
	public function valid() {
		global $pagenow;

		if( empty( $pagenow ) )
			return false;

		if( $this->condition == $pagenow )
			return true;

		return false;
	}
}