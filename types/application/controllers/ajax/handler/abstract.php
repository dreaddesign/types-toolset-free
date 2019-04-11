<?php

/**
 * Abstract AJAX call handler.
 *
 * @since 2.1
 */
abstract class Types_Ajax_Handler_Abstract implements Types_Ajax_Handler_Interface {


	/** @var Types_Ajax */
	private $ajax_manager;


	/**
	 * Types_Ajax_Handler_Abstract constructor.
	 *
	 * @param Types_Ajax $ajax_manager
	 * @since 2.1
	 */
	public function __construct( $ajax_manager ) {
		$this->ajax_manager = $ajax_manager;
	}


	/**
	 * Get the Types AJAX manager.
	 *
	 * @return Types_Ajax
	 */
	protected function get_am() {
		return $this->ajax_manager;
	}
	
	
	protected function ajax_begin( $arguments ) {
		$am = $this->get_am();
		return $am->ajax_begin( $arguments );
	}
	
	
	protected function ajax_finish( $response, $is_success = true ) {
		$am = $this->get_am();
		return $am->ajax_finish( $response, $is_success );
	}
	
	
}