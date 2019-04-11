<?php

/**
 * Interface for an AJAX call handler.
 *
 * @since 2.1
 */
interface Types_Ajax_Handler_Interface {

	/**
	 * Types_Ajax_Handler_Interface constructor.
	 *
	 * @param $ajax_manager Types_Ajax instance
	 */
	function __construct( $ajax_manager );


	/**
	 * @param array $arguments Original action arguments.
	 * @return void
	 */
	function process_call( $arguments );
	
}