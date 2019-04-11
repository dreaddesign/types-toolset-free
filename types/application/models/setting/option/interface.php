<?php

/**
 * Interface Types_Setting_Option_Interface
 *
 * @since 2.1
 */
interface Types_Setting_Option_Interface {
	public function __construct( $id );
	public function get_id();
	public function get_value();
	public function set_description( $description );
	public function get_description();
}