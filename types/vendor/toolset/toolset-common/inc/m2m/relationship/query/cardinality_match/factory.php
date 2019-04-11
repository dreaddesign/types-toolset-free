<?php

/**
 * Factory for building cardinality matchers, especially for the most common cases.
 *
 * Do not use this directly outside of the m2m API, but go through
 * Toolset_Relationship_Query_V2::cardinality().
 *
 * Hide away the complexity involving cardinalities and comparing, especially if there are custom limits.
 *
 * @since 2.5.5
 */
class Toolset_Relationship_Query_Cardinality_Match_Factory {


	private function create( IToolset_Relationship_Role_Parent_Child $role, $boundary, $operator, $value ) {
		return new Toolset_Relationship_Query_Cardinality_Match_Single( $role, $boundary, $operator, $value );
	}


	/**
	 * Matches all one-to-many relationships.
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Conjunction
	 */
	public function one_to_many() {
		return new Toolset_Relationship_Query_Cardinality_Match_Conjunction( array(
			$this->create(
				new Toolset_Relationship_Role_Parent(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				1
			),
			$this->create(
				new Toolset_Relationship_Role_Child(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::NOT_EQUAL,
				1
			)
		) );
	}


	/**
	 * Matches all one-to-one relationships.
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Conjunction
	 */
	public function one_to_one() {
		return new Toolset_Relationship_Query_Cardinality_Match_Conjunction( array(
			$this->create(
				new Toolset_Relationship_Role_Parent(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				1
			),
			$this->create(
				new Toolset_Relationship_Role_Child(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				1
			)
		) );

	}


	/**
	 * Matches all one-to-one and one-to-many relationships.
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Single
	 */
	public function one_to_something() {
		return $this->create(
			new Toolset_Relationship_Role_Parent(),
			Toolset_Relationship_Cardinality::MAX,
			Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
			1
		);
	}


	/**
	 * Matches all many-to-many relationships.
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Conjunction
	 */
	public function many_to_many() {
		return new Toolset_Relationship_Query_Cardinality_Match_Conjunction( array(
			$this->create(
				new Toolset_Relationship_Role_Parent(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::NOT_EQUAL,
				1
			),
			$this->create(
				new Toolset_Relationship_Role_Child(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::NOT_EQUAL,
				1
			)
		) );
	}


	/**
	 * Matches all relationships with the exact cardinality.
	 *
	 * Keep in mind the implications for relationships with custom limits.
	 * Always prefer another method if you can.
	 *
	 * @param Toolset_Relationship_Cardinality $cardinality
	 *
	 * @return Toolset_Relationship_Query_Cardinality_Match_Conjunction
	 */
	public function by_cardinality( Toolset_Relationship_Cardinality $cardinality ) {
		return new Toolset_Relationship_Query_Cardinality_Match_Conjunction( array(
			$this->create(
				new Toolset_Relationship_Role_Parent(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				$cardinality->get_limit(
					Toolset_Relationship_Role::PARENT,
					Toolset_Relationship_Cardinality::MAX
				)
			),
			$this->create(
				new Toolset_Relationship_Role_Parent(),
				Toolset_Relationship_Cardinality::MIN,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				$cardinality->get_limit(
					Toolset_Relationship_Role::PARENT,
					Toolset_Relationship_Cardinality::MIN
				)
			),
			$this->create(
				new Toolset_Relationship_Role_Child(),
				Toolset_Relationship_Cardinality::MAX,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				$cardinality->get_limit(
					Toolset_Relationship_Role::CHILD,
					Toolset_Relationship_Cardinality::MAX
				)
			),
			$this->create(
				new Toolset_Relationship_Role_Child(),
				Toolset_Relationship_Cardinality::MIN,
				Toolset_Relationship_Query_Cardinality_Match_Operators::EQUAL,
				$cardinality->get_limit(
					Toolset_Relationship_Role::CHILD,
					Toolset_Relationship_Cardinality::MIN
				)
			),
		) );
	}

}