<?php

/**
 * Class Types_Setting
 *
 * @since 2.1
 */
class Types_Setting implements Types_Setting_Interface {

	private $id;
	private $options = array();
	
	public function __construct( $id ) {
		$this->id = $id;
	}

	public function get_id() {
		return $this->id;
	}

	public function add_option( Types_Setting_Option_Interface $option ) {
		$this->options[$option->get_id()] = $option;
	}

	public function get_options() {
		return $this->options;
	}

	public function get_value( $option_id ) {
		return $this->options[$option_id]->get_stored_value( $this );
	}

	public function checked( $value, $option_id ) {
		return checked( $value == $this->get_value( $option_id ), true, false );
	}
}