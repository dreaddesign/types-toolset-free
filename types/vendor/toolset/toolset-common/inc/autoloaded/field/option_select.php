<?php

/**
 * Definition of a single option in select field.
 *
 * This should be exclusively used for accessing option properties and determining display value.
 *
 * @since 1.9.1
 */
final class Toolset_Field_Option_Select extends Toolset_Field_Option_Radio {


	/**
	 * Determine value to be displayed for this option.
	 *
	 * @param bool $is_checked For which value should the output be rendered.
	 * @return string Display value depending on option definition and field display mode
	 * @since 1.9.1
	 */
	public function get_display_value( $is_checked = true ) {
		if( $is_checked ) {
			return $this->get_value_to_store();
		} else {
			return '';
		}
	}
}