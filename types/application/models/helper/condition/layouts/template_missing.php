<?php

/**
 * Types_Helper_Condition_Layouts_Template_Missing
 *
 * @since 2.0
 */
class Types_Helper_Condition_Layouts_Template_Missing extends Types_Helper_Condition_Layouts_Template_Exists {

	public function valid() {
		$type = self::get_type_name();
		if( isset( parent::$layout_id[$type] )
		    && parent::$layout_id[$type] !== null
		    && parent::$layout_id[$type] !== false ) {
			return false;
		}


		return ! parent::valid();
	}

}