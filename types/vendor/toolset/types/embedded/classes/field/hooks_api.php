<?php

/**
 * This should contain all API filter hooks related to field types, field groups, field definitions and field instances.
 *
 * @todo Ensure initialization at proper time.
 */
final class WPCF_Field_Hooks_API {


	private static $instance = null;


	/**
	 * Initialize the hooks API related to fields.
	 */
	public static function initialize() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
	}


	private function __clone() { }


	private function __construct() {
		// Put your add_filter here.
	}

}