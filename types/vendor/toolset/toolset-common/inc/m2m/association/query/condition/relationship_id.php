<?php

/**
 * Condition to query associations by a specific relationship (row) ID.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Relationship_Id extends Toolset_Association_Query_Condition {


	/** @var int */
	private $relationship_id;


	/**
	 * Toolset_Association_Query_Condition_Relationship_Id constructor.
	 *
	 * @param int $relationship_id
	 * @throws InvalidArgumentException
	 */
	public function __construct( $relationship_id ) {
		if( ! Toolset_Utils::is_nonnegative_integer( $relationship_id ) ) {
			throw new InvalidArgumentException();
		}

		$this->relationship_id = (int) $relationship_id;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( 'associations.relationship_id %s %d', $this->get_operator(), $this->relationship_id );
	}


	/**
	 * Returns condition operator
	 *
	 * @return string
	 * @since m2m
	 */
	protected function get_operator() {
		return '=';
	}

}
