<?php

/**
 * Abstract condition for querying by a boolean flag stored as 1/0.
 *
 * @since 2.5.4
 */
abstract class Toolset_Relationship_Query_Condition_Is_Boolean_Flag extends Toolset_Relationship_Query_Condition {


	private $flag_value;


	/**
	 * Toolset_Relationship_Query_Condition_Is_Active constructor.
	 *
	 * @param bool|string $flag_value '*' will match anything.
	 */
	public function __construct( $flag_value ) {

		if( ! is_bool( $flag_value ) && '*' !== $flag_value ) {
			throw new InvalidArgumentException();
		}

		$this->flag_value = $flag_value;
	}


	/**
	 * @inheritdoc
	 * @return string
	 */
	public function get_where_clause() {

		if( '*' === $this->flag_value ) {
			return '1 = 1';
		}

		$required_value = ( $this->flag_value ? 1 : 0 );

		$condition = sprintf(
			"relationships.{$this->get_flag_column_name()} = %d",
			$required_value
		);

		return $condition;
	}


	/**
	 * @return string Name of the database column to query by.
	 */
	protected abstract function get_flag_column_name();

}