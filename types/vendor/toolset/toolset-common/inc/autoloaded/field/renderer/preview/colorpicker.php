<?php

/**
 * Preview renderer for colorpicker fields.
 * 
 * @since 1.9.1
 */
final class Toolset_Field_Renderer_Preview_Colorpicker extends Toolset_Field_Renderer_Preview_Base {


	/**
	 * Display a small box filled with selected colour and colour hex code as a title on hover.
	 *
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		$is_valid_hex_color = preg_match('/^#([a-f0-9]{3}){1,2}$/i', $value );
		if( ! $is_valid_hex_color ) {
			return '';
		}

		$result = sprintf(
			'<div style="background-color: %s; display: inline-block; width: 1em; height: 1em; border: 1px grey solid;" title="%s"></div>',
			$value,
			$value
		);

		return $result;
	}


	protected function get_value_separator() {
		return '&nbsp;';
	}


	/**
	 * @inheritdoc
	 * @return int
	 */
	protected function get_maximum_total_length() {
		return 0;
	}

}