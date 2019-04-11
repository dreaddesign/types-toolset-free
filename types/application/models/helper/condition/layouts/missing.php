<?php

/**
 * Types_Helper_Condition_Layouts_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Missing extends Types_Helper_Condition_Layouts_Active {

	public function valid() {
		return ! parent::valid();
	}

}