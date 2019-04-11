<?php

class WPCF_Field_DataMapper_Checkboxes extends WPCF_Field_DataMapper_Abstract {


	/** @var WPCF_Field_Definition */
	protected $field_definition;


	/**
	 * @param WPCF_Field_Definition $field_definition Must be a definition of a checkboxes field.
	 */
	public function __construct( $field_definition ) {
		parent::__construct( $field_definition );

		if( $field_definition->get_type()->get_slug() != Types_Field_Type_Definition_Factory::CHECKBOXES ) {
			throw new InvalidArgumentException( 'Wrong field definition type.' );
		}
	}


	/**
	 * Theoretically this should be placed in self::post_to_intermediate(), but toolset-forms & legacy code
	 * might expect the POST data instead what I think should be the intermediate format.
	 *
	 * First of all, $value is expected to be an array. If checkbox is checked, an element with its id as key and value
	 * will be present. In that case it will be stored in the same way. If it's not checked, we either store nothing
	 * or a zero, depending on the field definition setting "Save empty value".
	 *
	 * @param array|mixed $value
	 *
	 * @return array
	 */
	public function intermediate_to_database( $value ) {

		$options = $this->field_definition->get_field_options();

		$result = array();

		foreach ( $options as $option_id => $ignored ) {
			$option_is_checked = isset( $value[ $option_id ] );

			if ( $option_is_checked ) {
				$result[ $option_id ] = $value[ $option_id ];
			} else if ( $this->field_definition->get_should_save_empty_value() ) {
				$result[ $option_id ] = 0;
			}

		}

		return $result;
	}

}