<?php

/**
 * Checkbox field type.
 *
 * @since 2.0
 */
final class Toolset_Field_Type_Definition_Checkbox extends Toolset_Field_Type_Definition_Singular {


	/**
	 * Toolset_Field_Type_Definition_Checkbox constructor.
	 *
	 * @param array $args
	 * @since 2.0
	 */
	public function __construct( $args ) {
		parent::__construct( Toolset_Field_Type_Definition_Factory::CHECKBOX, $args );
	}


	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.0
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Toolset_Field_Type_Definition_Factory::CHECKBOX;

		$definition_array = $this->sanitize_element_isset( $definition_array, 'display', 'db', array( 'db', 'value' ), 'data' );
		$definition_array = $this->sanitize_element_isset( $definition_array, 'display_value_selected', '', null, 'data' );
		$definition_array = $this->sanitize_element_isset( $definition_array, 'display_value_not_selected', '', null, 'data' );
		$definition_array = $this->sanitize_element_isset( $definition_array, 'save_empty', 'no', array( 'yes', 'no' ), 'data' );
				
		$set_value = toolset_getnest( $definition_array, array( 'data', 'set_value' ) );
		if( !is_string( $set_value ) && !is_numeric( $set_value ) ) {
			$set_value = '1';
		}
		$definition_array['data']['set_value'] = $set_value;
		
		return $definition_array;
	}


	/**
	 * @inheritdoc
	 *
	 * @param string $purpose
	 * @param string $environment
	 * @param Toolset_Field_Instance $field
	 * @param array $renderer_args
	 *
	 * @return Toolset_Field_Renderer_Abstract
	 */
	public function get_renderer( $purpose, $environment, $field, $renderer_args = array() ) {

		switch( $purpose ) {
			case Toolset_Field_Renderer_Purpose::PREVIEW:
				return new Toolset_Field_Renderer_Preview_Checkbox( $field, $renderer_args );
			default:
				return parent::get_renderer( $purpose, $environment, $field, $renderer_args );
		}

	}

}