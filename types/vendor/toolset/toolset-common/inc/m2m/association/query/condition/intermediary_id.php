<?php

/**
 * Condition to query associations by a specific intermediary post (row) ID.
 *
 * @since 2.6.7
 */
class Toolset_Association_Query_Condition_Intermediary_Id extends Toolset_Association_Query_Condition {


	/** @var int */
	private $intermediary_id;


	/**
	 * Toolset_Association_Query_Condition_Intermediary_Id constructor.
	 *
	 * @param int $intermediary_id
	 * @throws InvalidArgumentException
	 */
	public function __construct( $intermediary_id ) {
		if( ! Toolset_Utils::is_nonnegative_integer( $intermediary_id ) ) {
			throw new InvalidArgumentException();
		}

		$this->intermediary_id = (int) $intermediary_id;
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		return sprintf( 'associations.intermediary_id = %d', $this->intermediary_id );
	}


}