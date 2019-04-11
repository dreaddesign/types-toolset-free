<?php

/**
 * Types_Helper_Condition_Single_Exists
 *
 * @since 2.0
 */
class Types_Helper_Condition_Single_Exists extends Types_Helper_Condition_Template {
	
	public function __construct() {
		$cpt = Types_Helper_Condition::get_post_type();

		$this->templates = array(
			'single-' . $cpt->name . '.php',
			'single.php'
		);
	}
}