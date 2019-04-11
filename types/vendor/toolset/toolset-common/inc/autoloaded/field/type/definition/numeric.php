<?php

/**
 * Numeric field type.
 * 
 * @since 2.0
 */
final class Toolset_Field_Type_Definition_Numeric extends Toolset_Field_Type_Definition {


	/**
	 * Toolset_Field_Type_Definition_Numeric constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args ) {
		parent::__construct( Toolset_Field_Type_Definition_Factory::NUMERIC, $args );
	}


	/**
	 * @inheritdoc
	 * 
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {
		
		$definition_array['type'] = Toolset_Field_Type_Definition_Factory::NUMERIC;
		
		return $definition_array;
	}


	/**
	 * Add the 'number' validation if it was not already there, and activate it.
	 * 
	 * @param array $definition_array
	 * @return array
	 * @since 2.0
	 */
	protected function sanitize_numeric_validation( $definition_array ) {
		
		// Get the original setting or a default one.
		$validation_setting = toolset_ensarr(
			toolset_getnest( $definition_array, array( 'data', 'validate', 'number' ) ),
			array( 'active' => true, 'message' => __( 'Please enter numeric data', 'wpcf' ) ) 
		);
		
		// Force the activation of this validation.
		$validation_setting['active'] = true;
		
		// Store the setting.
		$definition_array['data']['validate']['number'] = $validation_setting;
		
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
				return new Toolset_Field_Renderer_Preview_Textfield( $field, $renderer_args );
			default:
				return parent::get_renderer( $purpose, $environment, $field, $renderer_args );
		}

	}
}