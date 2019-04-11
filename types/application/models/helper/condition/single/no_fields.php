<?php

/**
 * Types_Helper_Condition_Single_No_Fields
 *
 * @since 2.0
 */
class Types_Helper_Condition_Single_No_Fields extends Types_Helper_Condition_Single_Missing {

	public function valid() {
		$template = $this->find_template();

		// no template available
		if( empty( $template ) )
			return false;

		// do not check if there are no fields assigned to current post
		// this check is not placed very well as it's out of class scope
		$check_fields_assigned = new Types_Helper_Condition_Type_Fields_Assigned();
		if( ! $check_fields_assigned->valid() )
			return false;

		$file = new Toolset_Filesystem_File();

		// abort if file can't be found
		if( ! $file->open( $template ) )
			return false;

		// check for fields, abort if there results in true
		if( $file->search( array( 'types_render_field', 'wpcf-' ) ) )
			return false;

		return true;
	}
}