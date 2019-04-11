<?php

/**
 * Relationship scope model.
 *
 * Handles the parsing of scope conditions for a particular relationship definition and evaluating them.
 *
 * Stub only, until a proper implementation is decided.
 *
 * Notes for the future implementation:
 *  - scope_data should be an array of conditions
 *  - we might support different types of conditions, be prepared for that
 *  - Toolset_Element models should eventually get magic properties/property discovery mechanism so that these properties
 *    can be used for conditions.
 *
 * @since m2m
 */
class Toolset_Relationship_Scope {

	private $scope_data;

	/**
	 * Toolset_Relationship_Scope constructor.
	 *
	 * @param mixed $scope_data
	 * @param Toolset_Relationship_Definition $relationship_definition
	 */
	public function __construct( $scope_data, $relationship_definition ) {
		$this->scope_data = $scope_data;
	}


	public function can_associate( $elements ) {
		return true;
	}


	public function query_possible_associations( $element, $side ) {
		throw new RuntimeException( 'Not implemented.' );
	}


	public function get_scope_data() {
		return $this->scope_data;
	}

}