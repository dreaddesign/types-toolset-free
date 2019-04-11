<?php

final class Toolset_Field_Type_Definition_Checkboxes extends Toolset_Field_Type_Definition_Singular {

	public function __construct( $args ) {
		parent::__construct( Toolset_Field_Type_Definition_Factory::CHECKBOXES, $args );
	}

	/**
	 * @inheritdoc
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {

		$definition_array['type'] = Toolset_Field_Type_Definition_Factory::CHECKBOXES;

		$definition_array = $this->sanitize_element_isset( $definition_array, 'save_empty', 'no', array( 'yes', 'no' ), 'data' );

		$options = toolset_ensarr( toolset_getnest( $definition_array, array( 'data', 'options' ) ) );

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
		$option = $this->sanitize_element_isset( toolset_ensarr( $option ), 'set_value' );
		$option = $this->sanitize_element_isset( $option, 'title', $option['set_value'] );
		$option = $this->sanitize_element_isset( $option, 'display', 'db', array( 'db', 'value' ) );
		$option = $this->sanitize_element_isset( $option, 'display_value_selected' );
		$option = $this->sanitize_element_isset( $option, 'display_value_not_selected' );
		return $option;
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
				return new Toolset_Field_Renderer_Preview_Checkboxes( $field, $renderer_args );
			default:
				return parent::get_renderer( $purpose, $environment, $field, $renderer_args );
		}

	}

}