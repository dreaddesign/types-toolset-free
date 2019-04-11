<?php

/**
 * Definition of a single option in radio field.
 *
 * This should be exclusively used for accessing option properties and determining display value.
 *
 * @since 1.9.1
 */
class WPCF_Field_Option_Radio extends WPCF_Field_Option_Checkboxes {

	/* $config difference in comparison with checkboxes field option:
	 * - instead of 'set_value', the value to be stored in database for selected option has the key 'value'
	 */

	/** @var WPCF_Field_Definition */
	private $field_definition;


	public function __construct( $option_id, $config, $default, $field_definition ) {
		parent::__construct( $option_id, $config, $default );
		$this->field_definition = $field_definition;
	}

	/**
	 * @return string Value that should be stored to database when this option is selected.
	 * @since 1.9.1
	 */
	public function get_value_to_store() {
		$value = wpcf_getarr( $this->config, 'value' );
		if( !is_string( $value ) ) {
			return '';
		}

		return $value;
	}


	/**
	 * Determine value to be displayed for this option.
	 *
	 * @param bool $is_checked For which value should the output be rendered.
	 * @return string Display value depending on option definition and field display mode 
	 * @since 1.9.1
	 */
	public function get_display_value( $is_checked = true ) {
		$field_definition_array = $this->field_definition->get_definition_array();
		$display_mode = wpcf_getnest( $field_definition_array, array( 'data', 'display' ), 'db' );
		$display_mode = ( 'value' == $display_mode ? 'value' : 'db' );

		if( 'db' == $display_mode ) {
			return ( $is_checked ? $this->get_value_to_store() : '' );
		} else {
			if ( $is_checked ) {
				return wpcf_getarr( $this->config, 'display_value' );
			} else {
				return '';
			}
		}
	}

}