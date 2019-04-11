<?php

/**
 * Types_Helper_Condition_Archive_Exists
 *
 * @since 2.0
 */
class Types_Helper_Condition_Archive_Exists extends Types_Helper_Condition_Template {

	public function __construct() {
		$cpt = Types_Helper_Condition::get_post_type();

		$this->templates = array(
			'archive-' . $cpt->name . '.php',
			'archive.php'
		);
	}

	protected function has_archive() {
		$cpt = Types_Helper_Condition::get_post_type();
		if( ! get_post_type_archive_link( $cpt->name ) )
			return false;

		return true;
	}


	public function valid() {
		if( ! $this->has_archive() )
			return false;

		return parent::valid();
	}

}