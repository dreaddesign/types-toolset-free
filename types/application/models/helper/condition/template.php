<?php

/**
 * Types_Helper_Condition_Template
 *
 * @since 2.0
 */
abstract class Types_Helper_Condition_Template extends Types_Helper_Condition {

	protected $templates;

	public function find_template() {
		$template = locate_template( $this->templates ) ;

		return $template;
	}

	// check if current screen is screen
	public function valid() {
		$template = $this->find_template();

		if( empty( $template ) )
			return false;

		return true;
	}
}