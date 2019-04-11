<?php

/**
 * Interface Types_Setting_Interface
 *
 * @since 2.1
 */
interface Types_Setting_Interface {
	public function __construct( $id );
	public function get_id();

	public function add_option( Types_Setting_Option_Interface $option );
	public function get_options();

	public function get_value( $option_id );
}