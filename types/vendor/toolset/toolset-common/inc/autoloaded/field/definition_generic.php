<?php

/**
 * Generic field definition.
 *
 * Represents basically a meta key that doesn't belong to any field known to Types.
 */
class Toolset_Field_Definition_Generic extends Toolset_Field_Definition_Abstract {


	private $meta_key = '';


	public function __construct( $meta_key ) {
		$this->meta_key = $meta_key;
	}


	public function get_slug() {
		return $this->meta_key;
	}

	public function get_name() {
		return $this->meta_key;
	}

	public function get_description() {
		return '';
	}

	public function get_meta_key() {
		return $this->meta_key;
	}

	public function is_under_types_control() {
		return false;
	}

	public function get_associated_groups() {
		return array();
	}
}