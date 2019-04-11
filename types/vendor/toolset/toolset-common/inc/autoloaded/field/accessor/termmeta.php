<?php

/**
 * Generic accessor to term meta.
 *
 * It can be used to provide access to generic term meta, but it never should be used for term fields (take
 * a look at Toolset_Field_Accessor_Termmeta_Field instead).
 *
 * @since 1.9
 */
class Toolset_Field_Accessor_Termmeta extends Toolset_Field_Accessor_Abstract {


	public function get_raw_value() {
		// Since meta data (for posts and users, anyway) was historically loaded by get_*_meta() with $single = false,
	    // it always returned an array even for single fields. Keeping that for compatibility with toolset-forms and
		// simplicity.
		return get_term_meta( $this->object_id, $this->meta_key, false );
	}


	public function update_raw_value( $value, $prev_value = '' ) {
		return update_term_meta( $this->object_id, $this->meta_key, $value, $prev_value );
	}


	/**
	 * Add new metadata.
	 *
	 * @param mixed $value New value to be saved to the database
	 * @return mixed
	 */
	public function add_raw_value( $value ) {
		return add_term_meta( $this->object_id, $this->meta_key, $value, $this->is_single );
	}


	/**
	 * Delete field value from the database.
	 *
	 * @param string $value Specific value to be deleted.
	 * @return mixed
	 */
	public function delete_raw_value( $value = '' ) {
		return delete_term_meta( $this->object_id, $this->meta_key, $value );
	}

}