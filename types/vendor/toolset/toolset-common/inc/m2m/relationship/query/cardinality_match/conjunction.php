<?php

/**
 * Cardinality matcher that holds a set of single matchers.
 *
 * @since 2.5.5
 */
class Toolset_Relationship_Query_Cardinality_Match_Conjunction implements IToolset_Relationship_Query_Cardinality_Match {

	/** @var Toolset_Relationship_Query_Cardinality_Match_Single[] */
	private $matchers;


	public function __construct( $matchers ) {
		foreach( $matchers as $matcher ) {
			if( ! $matcher instanceof Toolset_Relationship_Query_Cardinality_Match_Single ) {
				throw new InvalidArgumentException();
			}
		}

		$this->matchers = $matchers;
	}


	public function get_matchers() {
		return $this->matchers;
	}

}