<?php

final class Types_Field_Type_Definition_Date extends Types_Field_Type_Definition {

	/**
	 * Types_Field_Type_Definition_Date constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args ) {
		parent::__construct( Types_Field_Type_Definition_Factory::DATE, $args );
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	public function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Types_Field_Type_Definition_Factory::DATE;

		$definition_array = $this->sanitize_element_isset( $definition_array, 'date_and_time', 'date', array( 'date', 'and_time' ), 'data' );
		
		return $definition_array;
	}

}