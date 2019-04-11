<?php

/**
 * Condition that a relationship involves a certain type in a certain relationship role.
 *
 * @since m2m
 */
class Toolset_Relationship_Query_Condition_Type extends Toolset_Relationship_Query_Condition {


	/** @var IToolset_Relationship_Role_Parent_Child */
	protected $role;

	/** @var string */
	protected $type;


	/**
	 * Toolset_Relationship_Query_Condition_Type constructor.
	 *
	 * @param string $type
	 * @param IToolset_Relationship_Role_Parent_Child $role What relationship role to query for
	 */
	public function __construct(
		$type, IToolset_Relationship_Role_Parent_Child $role
	) {
		if( ( ! is_string( $type ) && ! is_int( $type ) ) || empty( $type ) || sanitize_text_field( $type ) != $type  ) {
			throw new InvalidArgumentException();
		}

		$this->type = $type;
		$this->role = $role;
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	public function get_where_clause() {
		$where = sprintf(
			"{$this->get_type_set_table_alias( $this->role )}.type = '%s'",
			esc_sql( $this->type )
		);

		return $where;
	}

}
