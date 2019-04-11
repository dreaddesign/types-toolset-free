<?php

/**
 * Condition to query associations by a specific association ID.
 *
 * @since 2.5.8
 */
class Toolset_Association_Query_Condition_Association_Id extends Toolset_Association_Query_Condition {


	/** @var int */
	private $association_id;


	/**
	 * Toolset_Association_Query_Condition_Association_Id constructor.
	 *
	 * @param int $association_id
	 * @throws InvalidArgumentException
	 */
	public function __construct( $association_id ) {
		if( ! Toolset_Utils::is_natural_numeric( $association_id ) ) {
			throw new InvalidArgumentException( 'Invalid association ID.' );
		}

		$this->association_id = (int) $association_id;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( 'associations.id = %d', $this->association_id );
	}
}