<?php

/**
 * Abstract class for accessing a specific field value.
 *
 * It works with the raw value and performs no validation or sanitization whatsoever. The actual behaviour depends on
 * the actual implementation, but it is safe to assume get_*_meta (& co.) functions will be used.
 */
abstract class WPCF_Field_Accessor_Abstract {

	/** @var int */
	protected $object_id;

	/** @var string */
	protected $meta_key;

	/** @var bool */
	protected $is_single;


	/**
	 * WPCF_Field_Accessor_Abstract constructor.
	 *
	 * @param int $object_id ID of the object to which the field belongs.
	 * @param string $meta_key Meta key used to store field value.
	 * @param bool $is_repetitive
	 */
	public function __construct( $object_id, $meta_key, $is_repetitive = false ) {
		$this->object_id = $object_id;
		$this->meta_key = $meta_key;
		$this->is_single = (bool) !$is_repetitive;
	}


	/**
	 * @return mixed Field value from the database.
	 */
	public abstract function get_raw_value();


	/**
	 * @param mixed $value New value to be saved to the database.
	 * @param mixed $prev_value Previous field value. Use if updating an item in a repetitive field.
	 * @return mixed
	 */
	public abstract function update_raw_value( $value, $prev_value = '' );


	/**
	 * Add new metadata. Note that if the accessor is set up for a repetitive field, the is_unique argument
	 * of add_*_meta should be false and otherwise it should be true.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_term_meta/
	 *
	 * @param mixed $value New value to be saved to the database
	 * @return mixed
	 */
	public abstract function add_raw_value( $value );


	/**
	 * @return bool True, if the value is not empty.
	 */
	public function has_raw_value() {
		$has_raw_value = $this->get_raw_value();
		$has_raw_value = ! empty( $has_raw_value )
			? true
			: false;
		
		return $has_raw_value;
	}


	/**
	 * Delete field value from the database.
	 *
	 * @param string $value Specific value to be deleted. Use if deleting an item in a repetitive field.
	 * @return mixed
	 */
	public abstract function delete_raw_value( $value = '' );
}