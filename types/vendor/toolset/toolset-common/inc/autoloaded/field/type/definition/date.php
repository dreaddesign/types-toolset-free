<?php

final class Toolset_Field_Type_Definition_Date extends Toolset_Field_Type_Definition {

	/**
	 * Toolset_Field_Type_Definition_Date constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args ) {
		parent::__construct( Toolset_Field_Type_Definition_Factory::DATE, $args );
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	public function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Toolset_Field_Type_Definition_Factory::DATE;

		$definition_array = $this->sanitize_element_isset( $definition_array, 'date_and_time', 'date', array( 'date', 'and_time' ), 'data' );
		
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
				return new Toolset_Field_Renderer_Preview_Date( $field, $renderer_args );
			default:
				return parent::get_renderer( $purpose, $environment, $field, $renderer_args );
		}

	}
}