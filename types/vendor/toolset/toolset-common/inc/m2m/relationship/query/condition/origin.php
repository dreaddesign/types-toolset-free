<?php

/**
 * Condition that a relationship has a certain origin (was created through a wizard or as a
 * post reference field or a repeatable field group).
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Origin extends Toolset_Relationship_Query_Condition {


	/** @var string */
	private $origin;


	/**
	 * Toolset_Relationship_Query_Condition_Origin constructor.
	 *
	 * @param string|null $origin Null value to return all origins.
	 * @throws InvalidArgumentException
	 */
	public function __construct( $origin ) {
		if( null !== $origin && ( ! is_string( $origin ) ||  empty( $origin ) ) ) {
			throw new InvalidArgumentException();
		}

		$this->origin = $origin;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_where_clause() {
		if( null === $this->origin ) {
			return '1 = 1';
		}

		return sprintf(
			"relationships.origin = '%s'",
			esc_sql( $this->origin )
		);
	}
}