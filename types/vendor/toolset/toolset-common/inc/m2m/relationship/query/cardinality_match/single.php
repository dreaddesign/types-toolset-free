<?php

/**
 * Cardinality matcher that holds a single rule for one of the four cardinality values.
 *
 * These can be conjuncted together via Toolset_Relationship_Query_Cardinality_Match_Conjunction.
 * @since 2.5.5
 */
class Toolset_Relationship_Query_Cardinality_Match_Single implements IToolset_Relationship_Query_Cardinality_Match {


	/** @var IToolset_Relationship_Role_Parent_Child */
	private $role;

	/** @var string */
	private $boundary;

	/** @var string */
	private $operator;

	/** @var int */
	private $value;


	/**
	 * Toolset_Relationship_Query_Cardinality_Match_Single constructor.
	 *
	 * @param IToolset_Relationship_Role_Parent_Child $role
	 * @param string $boundary Which cardinality boundary this involves (use MIN or MAX constants
	 *     on the Toolset_Relationship_Cardinality class).
	 * @param string $operator Operator to compare the cardinality with given value (use one of the
	 *     operators defined in Toolset_Relationship_Query_Cardinality_Match_Operators).
	 * @param int $value Value to compare the cardinality to.
	 */
	public function __construct(
		IToolset_Relationship_Role_Parent_Child $role,
		$boundary,
		$operator,
		$value
	) {
		if (
			! in_array(
				$boundary,
				array( Toolset_Relationship_Cardinality::MAX, Toolset_Relationship_Cardinality::MIN )
			)
			|| ! in_array( $operator, Toolset_Relationship_Query_Cardinality_Match_Operators::all() )
			|| ! Toolset_Utils::is_integer( $value )
			|| $value <= Toolset_Relationship_Cardinality::INVALID_VALUE
		) {
			throw new InvalidArgumentException();
		}

		$this->role = $role;
		$this->boundary = $boundary;
		$this->operator = $operator;
		$this->value = (int) $value;
	}


	/**
	 * @return IToolset_Relationship_Role_Parent_Child
	 */
	public function get_role() {
		return $this->role;
	}


	/**
	 * @return string
	 */
	public function get_boundary() {
		return $this->boundary;
	}


	/**
	 * @return string
	 */
	public function get_operator() {
		return $this->operator;
	}


	/**
	 * @return int
	 */
	public function get_value() {
		return $this->value;
	}
}