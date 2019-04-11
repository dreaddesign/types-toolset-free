<?php

/**
 * Condition that excludes a relationship.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Exclude_Relationship extends Toolset_Relationship_Query_Condition {


	/** @var Toolset_Relationship_Definition */
	private $relationship;


	/**
	 * Toolset_Relationship_Query_Condition_Exclude_Relationship constructor.
	 *
	 * @param Toolset_Relationship_Definition $relationship Relationship to be excluded.
	 */
	public function __construct( Toolset_Relationship_Definition $relationship ) {

		$this->relationship = $relationship;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {

		return sprintf(
			"relationships.slug != '%s'",
			esc_sql( $this->relationship->get_slug() )
		);
	}
}
