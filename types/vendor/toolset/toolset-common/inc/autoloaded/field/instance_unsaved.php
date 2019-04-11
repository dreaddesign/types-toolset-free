<?php

class Toolset_Field_Instance_Unsaved extends Toolset_Field_Instance_Abstract {

	/**
	 * Get the accessor to the field value.
	 *
	 * We can't access it directly because we don't know how - it can be stored in post meta, user meta or term meta.
	 *
	 * @return Toolset_Field_Accessor_Abstract
	 */
	protected function get_accessor() {
		return new Toolset_Field_Accessor_Dummy();
	}

	/**
	 * @return int ID of the object that owns the field. In this case it returns zero.
	 */
	public function get_object_id() {
		return 0;
	}
}