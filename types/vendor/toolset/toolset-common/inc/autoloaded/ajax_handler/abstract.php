<?php

/**
 * Abstract AJAX call handler.
 *
 * @since 2.1
 */
abstract class Toolset_Ajax_Handler_Abstract implements Toolset_Ajax_Handler_Interface {


	/** @var Toolset_Ajax */
	private $ajax_manager;


	/**
	 * Toolset_Ajax_Handler_Abstract constructor.
	 *
	 * @param Toolset_Ajax $ajax_manager
	 * @since 2.1
	 */
	public function __construct( Toolset_Ajax $ajax_manager ) {
		$this->ajax_manager = $ajax_manager;
	}


	/**
	 * Get the AJAX manager.
	 *
	 * @return Toolset_Ajax
	 * @deprecated Since 2.3, use $this->get_ajax_manager() instead
	 */
	protected function get_am() {
		return $this->ajax_manager;
	}

	/**
	 * Get the AJAX manager.
	 *
	 * @return Toolset_Ajax
	 * @since 2.3
	 */
	protected function get_ajax_manager() {
		return $this->ajax_manager;
	}
	
	
	protected function ajax_begin( $arguments ) {
		$am = $this->get_am();
		return $am->ajax_begin( $arguments );
	}
	
	
	protected function ajax_finish( $response, $is_success = true ) {
		$am = $this->get_am();
		$am->ajax_finish( $response, $is_success );
	}
	
	
}