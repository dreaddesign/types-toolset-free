<?php

/**
 * Abstract singleton representing an admin page.
 */
abstract class WPCF_Page_Abstract {

	/**
	 * Singleton parent.
	 *
	 * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
	 * @return WPCF_Page_Abstract Instance of calling class.
	 */
	public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}


	protected function __construct() { }


	final private function __clone() { }

}