<?php

/**
 * Condition that a relationship involves a certain intermediary post type.
 *
 * @since 2.6.7
 */
class Toolset_Relationship_Query_Condition_Intermediary_Type extends Toolset_Relationship_Query_Condition {


	/** @var string */
	private $intermediary_type;


	/**
	 * Toolset_Relationship_Query_Condition_Intermediary_Type constructor.
	 *
	 * @param string
	 * @throws InvalidArgumentException
	 */
	public function __construct( $intermediary_type ) {
		if( null !== $intermediary_type && ( ! is_string( $intermediary_type ) ||  empty( $intermediary_type ) ) ) {
			throw new InvalidArgumentException();
		}

		$this->intermediary_type = $intermediary_type;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_where_clause() {
		if( null === $this->intermediary_type ) {
			return '1 = 1';
		}

		return sprintf(
			"relationships.intermediary_type = '%s'",
			esc_sql( $this->intermediary_type )
		);
	}
}