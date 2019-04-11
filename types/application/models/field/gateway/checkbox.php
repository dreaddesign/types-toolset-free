<?php

/**
 * Checkbox field type.
 *
 * @since 2.0
 */
final class Types_Field_Type_Definition_Checkbox extends Types_Field_Type_Definition_Singular {


	/**
	 * Types_Field_Type_Definition_Checkbox constructor.
	 *
	 * @param array $args
	 * @since 2.0
	 */
	public function __construct( $args ) {
		parent::__construct( Types_Field_Type_Definition_Factory::CHECKBOX, $args );
	}


	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.0
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Types_Field_Type_Definition_Factory::CHECKBOX;

		$definition_array = $this->sanitize_element_isset( $definition_array, 'display', 'db', array( 'db', 'value' ), 'data' );
		$definition_array = $this->sanitize_element_isset( $definition_array, 'display_value_selected', '', null, 'data' );
		$definition_array = $this->sanitize_element_isset( $definition_array, 'display_value_not_selected', '', null, 'data' );
		$definition_array = $this->sanitize_element_isset( $definition_array, 'save_empty', 'no', array( 'yes', 'no' ), 'data' );
				
		$set_value = wpcf_getnest( $definition_array, array( 'data', 'set_value' ) );
		if( !is_string( $set_value ) && !is_numeric( $set_value ) ) {
			$set_value = '1';
		}
		$definition_array['data']['set_value'] = $set_value;
		
		return $definition_array;
	}

}