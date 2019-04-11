<?php

/**
 * Interface for an AJAX call handler.
 *
 * @since m2m
 */
interface Toolset_Ajax_Handler_Interface {


	/**
	 * Processes the Ajax call
	 *
	 * @param array $arguments Original action arguments.
	 * @return void
	 */
	function process_call( $arguments );

}
