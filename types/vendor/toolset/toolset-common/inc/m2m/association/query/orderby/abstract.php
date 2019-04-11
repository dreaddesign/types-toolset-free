<?php

/**
 * Shared functionality for most IToolset_Association_Query_Orderby classes.
 *
 * @since 2.5.8
 */
abstract class Toolset_Association_Query_Orderby implements IToolset_Association_Query_Orderby {


	/** @var string */
	protected $order = 'ASC';


	/** @var Toolset_Association_Query_Table_Join_Manager */
	protected $join_manager;


	/**
	 * Toolset_Association_Query_Orderby constructor.
	 *
	 * @param Toolset_Association_Query_Table_Join_Manager $join_manager
	 */
	public function __construct( Toolset_Association_Query_Table_Join_Manager $join_manager ) {
		$this->join_manager = $join_manager;
	}


	/**
	 * Set the direction of sorting.
	 *
	 * @param string $order 'ASC'|'DESC'
	 * @throws InvalidArgumentException
	 */
	public function set_order( $order ) {
		$normalized_value = strtoupper( $order );
		if( ! in_array( $normalized_value, array( 'ASC', 'DESC' ), true ) ) {
			throw new InvalidArgumentException( 'Invalid order value.' );
		}

		$this->order = $normalized_value;
	}


	/**
	 * @inheritdoc
	 */
	public function register_joins() { }

}