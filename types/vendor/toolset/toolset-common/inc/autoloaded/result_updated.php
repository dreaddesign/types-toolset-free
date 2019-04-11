<?php

/**
 * Represents a result of a (database) update operation.
 *
 * Compared to its superclass, this one adds the ability to hold the information about number of updated
 * records.
 *
 * @since 2.3
 */
class Toolset_Result_Updated extends Toolset_Result {


	/** @var int */
	private $items_updated;


	/**
	 * Toolset_Result_Updated constructor.
	 * 
	 * @inheritdoc
	 *
	 * @param bool|Exception|WP_Error $value Result value.
	 * @param int $items_updated Nonnegative number of items updated in an operation.
	 * @param null|string $display_message Optional display message for a boolean result value.
	 * @since 2.3
	 */
	public function __construct( $value, $items_updated = 0, $display_message = null ) {
		parent::__construct( $value, $display_message );

		if( ! is_numeric( $items_updated ) || 0 > $items_updated ) {
			throw new InvalidArgumentException( 'Negative or non-numeric count of updated items.' );
		}

		$this->items_updated = (int) $items_updated;
	}


	/**
	 * Determine whether any items have been updated during the operation.
	 * 
	 * @return bool
	 * @since 2.3
	 */
	public function has_items_updated() { return ( 0 < $this->items_updated ); }


	/**
	 * Get a number of items updated in an operation.
	 * 
	 * @return int
	 * @since 2.3
	 */
	public function get_updated_item_count() { return $this->items_updated; }

}