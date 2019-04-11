<?php

/**
 * Definition of a single option in checkboxes field.
 *
 * This should be exclusively used for accessing option properties and determining display value.
 *
 * @since 1.9.1
 */
class Toolset_Field_Option_Checkboxes {


	/** @var string Unique ID of the option. */
	private $option_id;

	/**
	 * @var string[] Option configuration:
	 *
	 * - title => Label for the option (when entering data)
	 * - set_value => Value to be stored in database when this option is selected
	 * - display => Display mode, either 'db' for displaying raw database value or 'value' for displaying
	 *       custom values (below)
	 * - display_value_selected
	 * - display_value_not_selected
	 */
	protected $config;


	/**
	 * WPCF_Field_Value_Option constructor.
	 *
	 * @param string $option_id Unique ID of the option.
	 * @param string[] $config Option configuration.
	 * @param string $default Default field value
	 */
	public function __construct( $option_id, $config, $default ) {
		
		if( !is_string( $option_id ) || empty( $option_id ) ) {
			throw new InvalidArgumentException( 'Invalid option ID.' );
		}
		$this->option_id = $option_id;
		
		if( !is_array( $config ) ) {
			throw new InvalidArgumentException( 'Invalid option configuration.' );
		}
		$this->config = $config;
	}


	/**
	 * @param array $field_value Checkboxes field value in the "intermediate" format.
	 * @return bool Whether this option is checked in the field whose value is provided.
	 * @since 1.9.1
	 */
	public function is_option_checked( $field_value ) {
		// Value that should be stored in database if this option is checked
		$option_value = toolset_getnest( $field_value, array( $this->option_id, 0 ), null );
		$is_checked = ( null !== $option_value && $this->get_value_to_store() == $option_value );
		return $is_checked;
	}


	/**
	 * @return string Option label.
	 * @since 1.9.1
	 */
	public function get_label() {
		$value = toolset_getarr( $this->config, 'title' );
		if( !is_string( $value ) ) {
			$value = '';
		}

		return sanitize_text_field( $value );
	}


	/**
	 * Determine value to be displayed for this option.
	 *
	 * @param bool $is_checked For which value should the output be rendered.
	 * @return string Display value depending on option definition. For unselected options in 'db' mode, empty string
	 *     will be returned disregarding the field's "save zero to database" option.
	 * @since 1.9.1
	 */
	public function get_display_value( $is_checked = true ) {
		$display_mode = toolset_getarr( $this->config, 'display', 'db', array( 'value', 'db' ) );
		if( 'db' == $display_mode ) {
			return ( $is_checked ? $this->get_value_to_store() : '' );
		} else {
			if ( $is_checked ) {
				return toolset_getarr( $this->config, 'display_value_selected' );
			} else {
				return toolset_getarr( $this->config, 'display_value_not_selected' );
			}
		}
	}


	/**
	 * @return string Value that should be stored to database when this option is selected.
	 * @since 1.9.1
	 */
	public function get_value_to_store() {
		$value = toolset_getarr( $this->config, 'set_value' );
		if( !is_string( $value ) ) {
			return '';
		}
		
		return $value;
	}

}