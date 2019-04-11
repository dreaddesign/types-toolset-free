<?php

class Types_Helper_Condition_Type_Post_Or_Page extends Types_Helper_Condition {

	public function valid() {
		if( self::get_type_name() == 'post' || self::get_type_name() == 'page' ) {
			return true;
		}

		return false;
	}
}