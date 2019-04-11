<?php

/**
 * Exception indicating that an element (post, user, term) doesn't exist when it should.
 *
 * It can be used to catch and safely handle a case of data corruption (e.g. post being deleted
 * while Toolset plugins are inactive).
 *
 * @since 2.5.6
 */
class Toolset_Element_Exception_Element_Doesnt_Exist extends Exception {


	/** @var int */
	private $element_id;


	/** @var string */
	private $domain;


	/**
	 * Toolset_Element_Exception_Element_Doesnt_Exist constructor.
	 *
	 * @param string $domain One of the Toolset_Element_Domain values.
	 * @param int|mixed $element_source This should be an ID of the element, but other value
	 *     will be also accepted for the purpose of rendering an error message.
	 */
	public function __construct( $domain, $element_source ) {

		if( Toolset_Utils::is_natural_numeric( $element_source ) ) {
			$this->element_id = (int) $element_source;
		} else {
			$this->element_id = 0;
		}

		$this->domain = $domain;

		parent::__construct(
			sprintf(
				__( 'Unable to load %s %d (%s).', 'wpcf' ),
				$domain,
				$this->element_id,
				esc_html( print_r( $element_source, true ) )
			)
		);
	}


	/**
	 * @return int Element ID or zero if it wasn't provided.
	 */
	public function get_element_id() {
		return $this->element_id;
	}


	/**
	 * @return string Element domain (needs to be validated).
	 */
	public function get_domain() {
		return $this->domain;
	}

}