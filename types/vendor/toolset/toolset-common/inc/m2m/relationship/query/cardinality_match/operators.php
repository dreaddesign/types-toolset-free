<?php

/**
 * Enum class with accepted operators for Toolset_Relationship_Query_Cardinality_Match_Single.
 *
 * @since 2.5.5
 */
class Toolset_Relationship_Query_Cardinality_Match_Operators {

	// these must be valid MySQL operators
	const EQUAL = '=';
	const LOWER_THAN = '<';
	const LOWER_OR_EQUAL = '<=';
	const HIGHER_THAN = '>';
	const HIGHER_OR_EQUAL = '>=';
	const NOT_EQUAL = '!=';


	/**
	 * @return string[] All valid operators.
	 */
	public static function all() {
		return array(
			self::EQUAL,
			self::LOWER_THAN,
			self::LOWER_OR_EQUAL,
			self::HIGHER_THAN,
			self::HIGHER_OR_EQUAL,
			self::NOT_EQUAL
		);
	}
}