<?php

/**
 * Preview renderer for checkboxes field.
 * 
 * @since 1.9.1 
 */
final class Toolset_Field_Renderer_Preview_Checkboxes extends Toolset_Field_Renderer_Preview_Base {

	/**
	 * Render preview for whole checkboxes field. Slightly confusingly, checkboxes fields are allways single
	 * and all options are part of one field value.
	 *
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {

		$options = $this->field->get_definition()->get_field_options();
		$output = array();

		// Let each checkbox option definition handle how it should be displayed.
		foreach( $options as $option ) {
			$display_value = $option->get_display_value( $option->is_option_checked( $value ) );
			if( !empty( $display_value ) ) {
				$output[] = $display_value;
			}
		}

		// Apply maximum count here
		$max_item_count = $this->get_maximum_item_count();
		$is_limited_by_max_count = ( 0 < $max_item_count && $max_item_count < count( $output ) );
		if( $is_limited_by_max_count ) {
			$output = array_slice( $output, 0, $this->get_maximum_item_count() );
		}
		
		$output = implode( $this->get_value_separator(), $output );

		// We need to additionally apply a limit for maximum total length
		// because if we only apply item length and count, and perhaps add separator and ellipsis at the end,
		// the parent::render() method will see it as one item and it might add another ellipsis if
		// output of this function hits the maximum total length limit there.
		//
		// Note: limit_by_maximum_total_length() should subtract the length of the ellipsis from the resulting
		// total length, so we should be completely covered here.
		$is_limited_by_max_total_length = $this->limit_by_maximum_total_length( $output );

		$needs_separator = $is_limited_by_max_count && ! $is_limited_by_max_total_length;
		$needs_ellipsis = ( $is_limited_by_max_count || $is_limited_by_max_total_length );

		if( $needs_separator ) {
			$output .= $this->get_value_separator();
		}
		if( $needs_ellipsis ) {
			$output .= $this->get_ellipsis();
		}

		return sanitize_text_field( $output );
	}


}