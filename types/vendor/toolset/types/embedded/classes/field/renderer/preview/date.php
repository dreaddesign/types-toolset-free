<?php

final class WPCF_Field_Renderer_Preview_Date extends WPCF_Field_Renderer_Preview_Base {

	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		if( !is_string( $value ) ) {
			return '';
		}

		$timestamp = (int) $value;

		// Skip empty values
		if( 0 == $timestamp ) {
			return '';
		}

		$output = date( get_option( 'date_format' ), $timestamp );

		$add_time = ( $this->field->get_definition()->get_datetime_option() == 'date_and_time' );
		if( $add_time ) {
			$output .=  ' ' . date( get_option( 'time_format' ), $timestamp );
		}

		return sanitize_text_field( $output );
	}


	/**
	 * @inheritdoc
	 *
	 * @return string
	 */
	protected function get_value_separator() {
		// Semicolon is less likely to cause conflicts with date and time formats.
		return '; ';
	}

}
