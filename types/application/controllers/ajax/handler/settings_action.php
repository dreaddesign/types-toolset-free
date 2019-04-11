<?php

/**
 * Save handler for types settings
 * Settings are defined in Controller/Page/Extension/Settings
 *
 * @since 2.1
 */
final class Types_Ajax_Handler_Settings_Action extends Types_Ajax_Handler_Abstract {


	/**
	 * @inheritdoc
	 *
	 * @param array $arguments
	 */
	public function process_call( $arguments ) {

		$am = $this->get_am();

		$am->ajax_begin( array( 'nonce' => $am->get_action_js_name( Types_Ajax::CALLBACK_SETTINGS_ACTION ) ) );

		$setting = sanitize_text_field( wpcf_getpost( 'setting' ) );
		$setting_value = wpcf_getpost( 'setting_value' );

		if( !is_array( $setting_value ) ) {
			parse_str( $setting_value, $setting_value );
			$setting_value = array_pop( $setting_value );
		}

		$sanitized_value = array();
		foreach( $setting_value as $key => $value ) {
			$sanitized_key = sanitize_title( $key );
			$sanitized_value[ $sanitized_key ] = sanitize_text_field( $value );
		}

		// use toolset settings if available
		if( class_exists( 'Toolset_Settings' )
		    && method_exists( 'Toolset_Settings', 'get_instance' ) ) {
			$toolset_settings = Toolset_Settings::get_instance();

			if( method_exists( $toolset_settings, 'save' ) ) {
				$toolset_settings[ $setting ] = $sanitized_value;
				$toolset_settings->save();
				$am->ajax_finish( 'success', true );
			}
		} else {
			update_option( $setting, $sanitized_value );
			$am->ajax_finish( 'success', true );
		}

		// default toolset setting error will be used
		// todo throw specific error
		$am->ajax_finish( array('error'), false );
	}
}