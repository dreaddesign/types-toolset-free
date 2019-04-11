<?php

/**
 * Interface for handlers of hook API calls.
 */
interface Types_Api_Handler_Interface {
	
	function __construct();

	/**
	 * @param array $arguments Original action/filter arguments.
	 * @return mixed
	 */
	function process_call( $arguments );

}