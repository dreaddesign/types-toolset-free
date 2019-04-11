<?php

/**
 * Preview renderer for Skype fields.
 * 
 * @since 1.9.1
 */
final class WPCF_Field_Renderer_Preview_Skype extends WPCF_Field_Renderer_Preview_Base {


	/**
	 * @param mixed $value Single field value in the intermediate format (see data mappers for details)
	 *
	 * @return string Rendered HTML
	 */
	protected function render_single( $value ) {
		// Simply get the Skype name.
		$skype_name = wpcf_getarr( $value, 'skypename' );
		$skype_name = is_string( $skype_name ) ? $skype_name : '';
		return sanitize_text_field( $skype_name );
	}


}