<?php

/**
 * Dummy field accessor that does nothing and returns an empty string.
 *
 * Useful for unsaved field instances.
 */
final class Toolset_Field_Accessor_Dummy extends Toolset_Field_Accessor_Abstract {


	public function __construct() {
		parent::__construct( 0, '', false );
	}

	/**
	 * @return mixed Field value from the database.
	 */
	public function get_raw_value() {
		return '';
	}

	/**
	 * @param mixed $value New value to be saved to the database.
	 * @param mixed $prev_value Previous field value. Use if updating an item in a repetitive field.
	 *
	 * @return mixed
	 */
	public function update_raw_value( $value, $prev_value = '' ) {
		// Nothing to do here, although this being called might indicate an error.
		return true;
	}

	/**
	 * Delete field value from the database.
	 *
	 * @param string $value Specific value to be deleted. Use if deleting an item in a repetitive field.
	 *
	 * @return mixed
	 */
	public function delete_raw_value( $value = '' ) {
		// Nothing to do here, although this being called might indicate an error.
		return true;
	}

	/**
	 * Add new metadata. Note that if the accessor is set up for a repetitive field, the is_unique argument
	 * of add_*_meta should be false and otherwise it should be true.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_term_meta/
	 *
	 * @param mixed $value New value to be saved to the database
	 *
	 * @return mixed
	 */
	public function add_raw_value( $value ) {
		// Nothing to do here, although this being called might indicate an error.
		return true;
	}

}
