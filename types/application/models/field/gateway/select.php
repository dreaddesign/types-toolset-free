<?php

final class Types_Field_Type_Definition_Select extends Types_Field_Type_Definition_Singular {


	public function __construct( $args ) {
		parent::__construct( Types_Field_Type_Definition_Factory::SELECT, $args );
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Types_Field_Type_Definition_Factory::SELECT;

		$options = wpcf_ensarr( wpcf_getnest( $definition_array, array( 'data', 'options' ) ) );

		foreach( $options as $key => $option ) {
			if( 'default' == $key ) {
				continue;
			}

			$options[ $key ] = $this->sanitize_single_option( $option );
		}

		$default_option = wpcf_getarr( $options, 'default' );
		if( !is_string( $default_option ) || !array_key_exists( $default_option, $options ) ) {
			$default_option = 'no-default';
		}
		$options['default'] = $default_option;

		$definition_array['data']['options'] = $options;

		return $definition_array;
	}


	/**
	 * Sanitize single checkboxes option definition.
	 *
	 * @param array $option
	 * @return array Sanitized option.
	 * @since 2.1
	 */
	private function sanitize_single_option( $option ) {
		$option = $this->sanitize_element_isset( wpcf_ensarr( $option ), 'value' );
		$option = $this->sanitize_element_isset( $option, 'title', $option['value'] );
		return $option;
	}

}