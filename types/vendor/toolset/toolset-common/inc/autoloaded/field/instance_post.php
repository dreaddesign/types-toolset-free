<?php

/**
 * Post field instance.
 *
 * This class exists to ensure that post field-specific operations will be performed consistently.
 *
 * TODO Work in progress, methods for storing data are missing. Check out the terms counterpart.
 *
 * @since 1.9
 */
class Toolset_Field_Instance_Post extends Toolset_Field_Instance {

	/**
	 * Overwrite current field values with new ones.
	 *
	 * @param array $values Array of values. For non-repetitive field there must be exactly one value. Order of values
	 *     in this array will be stored as sort order.
	 *
	 * @return bool True on success, false if some error has occured.
	 */
	public function update_all_values( $values ) {
		throw new RuntimeException( 'Not implemented' );
	}

	/**
	 * Add a single field value to the database.
	 *
	 * The value will be passed through filters as needed and stored, based on field configuration.
	 *
	 * @param mixed $value Raw value, which MUST be validated already.
	 *
	 * @return mixed
	 */
	public function add_value( $value ) {
		throw new RuntimeException( 'Not implemented' );
	}


	/**
	 * @return Toolset_Field_Accessor_Abstract An accessor to get the sort order for repetitive fields.
	 */
	protected function get_order_accessor() {
		return new Toolset_Field_Accessor_Postmeta( $this->get_object_id(), $this->get_order_meta_name(), false );
	}

}