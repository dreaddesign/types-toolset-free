<?php


/**
 * Preview renderer for radio fields.
 * 
 * @since 1.9.1
 */
final class Toolset_Field_Renderer_Preview_Radio extends Toolset_Field_Renderer_Preview_Base {

	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		$option = $this->get_option_for_value( $value );
		if( null == $option ) {
			return '';
		}

		$output = $option->get_display_value( true );
		
		return sanitize_text_field( $output );
	}


	/**
	 * Get radio field option definition from field value.
	 * 
	 * @param string $value Value stored in the database.
	 * @return Toolset_Field_Option_Radio Corresponding option definition.
	 */
	private function get_option_for_value( $value ) {
		$options = $this->field->get_definition()->get_field_options();
		foreach( $options as $option ) {
			if( $value == $option->get_value_to_store() ) {
				return $option;
			}
		}
		return null;
	}

}