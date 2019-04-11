<?php


abstract class WPCF_Field_Renderer_Abstract {

	/** @var null|WPCF_Field_Instance */
	protected $field = null;

	public function __construct( $field ) {

		// todo sanitize
		$this->field = $field;
	}


	/**
	 * @param bool $echo
	 *
	 * @return string
	 */
	public abstract function render( $echo = false );

}