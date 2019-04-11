<?php

/**
 * Render preview of a single field or other field type that doesn't require any additional processing.
 *
 * @since 1.9.1
 */
class WPCF_Field_Renderer_Preview_Textfield extends WPCF_Field_Renderer_Preview_Base {


	/**
	 * @param mixed $value Single field value.
	 * @return string Sanitized field value. If the value is not a string, this will result into an empty string.
	 */
	protected function render_single( $value ) {
		if( !is_string( $value ) ) {
			$value = '';
		}

		$value = sanitize_text_field( $value );

		// Keep maximum length per item
		$max_length = $this->get_maximum_item_length();
		if( 0 < $max_length && $max_length < strlen( $value ) ) {
			$ellipsis = $this->get_ellipsis();
			$value = substr( $value, 0, $max_length - strlen( $ellipsis ) ) . $ellipsis;
		}

		return $value;
	}

}
