<?php

/**
 * Checkbox field preview renderer.
 *
 * Displays a check mark for checked field (when it has the proper value, not just nonzero one). Otherwise, display nothing.
 *
 * @since 1.9.1
 */
final class Toolset_Field_Renderer_Preview_Checkbox extends Toolset_Field_Renderer_Preview_Base {


	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		$field_definition = $this->field->get_definition();
		$value_of_checked = $field_definition->get_forced_value();

		if( null != $value_of_checked && $value_of_checked == $value ) {
			return '&#10004;'; // ballot box with check - checkbox - checkmark - miscellaneous symbols
		}

		// We may not even get here without the checkbox being checked (when no value is saved to database)
		return '';
	}


	protected function get_maximum_total_length() {
		return 0;
	}

}