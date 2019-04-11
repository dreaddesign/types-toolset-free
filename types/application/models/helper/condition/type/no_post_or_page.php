<?php

class Types_Helper_Condition_Type_No_Post_Or_Page extends Types_Helper_Condition_Type_Post_Or_Page {

	public function valid() {
		return ! parent::valid();
	}
}