<?php

/**
 * Condition that a relationship cardinality matches certain constraints.
 *
 * The constraits are defined by a "matcher" object. See the "has_cardinality" method on the
 * query class for more information.
 *
 * @since 2.5.5
 */
class Toolset_Relationship_Query_Condition_Has_Cardinality extends Toolset_Relationship_Query_Condition {

	/** @var IToolset_Relationship_Query_Cardinality_Match */
	private $cardinality_match;


	/** @var Toolset_Relationship_Database_Operations */
	private $database_operations;


	/**
	 * Toolset_Relationship_Query_Condition_Has_Cardinality constructor.
	 *
	 * @param IToolset_Relationship_Query_Cardinality_Match $cardinality_match
	 * @param Toolset_Relationship_Database_Operations|null $database_operations_di
	 */
	public function __construct(
		IToolset_Relationship_Query_Cardinality_Match $cardinality_match,
		Toolset_Relationship_Database_Operations $database_operations_di = null
	) {
		$this->cardinality_match = $cardinality_match;
		$this->database_operations = (
			null === $database_operations_di
				? new Toolset_Relationship_Database_Operations()
				: $database_operations_di
		);
	}


	/**
	 * Get a part of the WHERE clause that applies the condition.
	 *
	 * @return string Valid part of a MySQL query, so that it can be
	 *     used in WHERE ( $condition1 ) AND ( $condition2 ) AND ( $condition3 ) ...
	 */
	public function get_where_clause() {
		if( $this->cardinality_match instanceof Toolset_Relationship_Query_Cardinality_Match_Single ) {
			return $this->process_matcher_set( array( $this->cardinality_match ) );
		} elseif( $this->cardinality_match instanceof Toolset_Relationship_Query_Cardinality_Match_Conjunction ) {
			return $this->process_matcher_set( $this->cardinality_match->get_matchers() );
		} else {
			throw new RuntimeException( 'Unsupported cardinality matcher.' );
		}
	}


	/**
	 * Process a set of single matchers into a single WHERE clause (conjunction).
	 *
	 * @param Toolset_Relationship_Query_Cardinality_Match_Single[] $matchers
	 *
	 * @return string
	 */
	private function process_matcher_set( $matchers ) {
		$where_clauses = array();
		foreach( $matchers as $matcher ) {
			$where_clauses[] = $this->process_single_matcher_rule( $matcher );
		}

		$result = ' ( ' . implode( ' ) AND ( ', $where_clauses ) . ' ) ';

		return $result;
	}


	/**
	 * Turn a single cardinality matcher into a MySQL WHERE clause.
	 *
	 * @param Toolset_Relationship_Query_Cardinality_Match_Single $matcher
	 *
	 * @return string
	 */
	private function process_single_matcher_rule( $matcher ) {

		$column_id = (
			$matcher->get_boundary() === Toolset_Relationship_Cardinality::MAX
				? Toolset_Relationship_Database_Operations::COLUMN_CARDINALITY_MAX
				: Toolset_Relationship_Database_Operations::COLUMN_CARDINALITY_MIN
		);

		$column_name = $this->database_operations->role_to_column(
			$matcher->get_role(),
			$column_id
		);

		$where = sprintf(
			'relationships.%s %s %d',
			$column_name,
			$matcher->get_operator(),
			$matcher->get_value()
		);

		return $where;
	}


}