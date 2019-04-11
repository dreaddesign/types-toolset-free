<?php

final class Types_Field_Type_Definition_Checkboxes extends Types_Field_Type_Definition_Singular {

	public function __construct( $args ) {
		parent::__construct( Types_Field_Type_Definition_Factory::CHECKBOXES, $args );
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Types_Field_Type_Definition_Factory::CHECKBOXES;

		$definition_array = $this->sanitize_element_isset( $definition_array, 'save_empty', 'no', array( 'yes', 'no' ), 'data' );

		$options = wpcf_ensarr( wpcf_getnest( $definition_array, array( 'data', 'options' ) ) );

		foreach( $options as $key => $option ) {
			$options[ $key ] = $this->sanitize_single_option( $option );
		}

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
		$option = $this->sanitize_element_isset( wpcf_ensarr( $option ), 'set_value' );
		$option = $this->sanitize_element_isset( $option, 'title', $option['set_value'] );
		$option = $this->sanitize_element_isset( $option, 'display', 'db', array( 'db', 'value' ) );
		$option = $this->sanitize_element_isset( $option, 'display_value_selected' );
		$option = $this->sanitize_element_isset( $option, 'display_value_not_selected' );
		return $option;
	}

}