<?php

// WIP
final class Types_Field_Type_Converter {

	private static $instance;

	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	private function __clone() { }

	private function __construct() {
		// Toolset Maps uses priority 10 for this filter, we need to run later and override.
		add_filter( 'wpcf_filter_field_control_change_type_allowed_types_from', array( $this, 'google_address_field_control_change_type_allowed_from' ), 15, 2 );
	}


	private $conversion_matrix = null;


	/**
	 * Construct a matrix of allowed field type conversions. 
	 * 
	 * For each field type as key it contains an array of types it can be converted into.
	 * 
	 * Usually, you shouldn't use this method directly, but instead use dedicated methods for a specific field type.
	 * 
	 * Note: An empty row for a field type can be interpreted in a way that any conversion is allowed. If that's not
	 * desired, make sure each type can be converted to itself.
	 * 
	 * Note: The conversion matrix is cached, so any used filters must be hooked into soon enough.
	 *
	 * @return string[][]|mixed
	 * @since 2.0 
	 */
	public function get_conversion_matrix() {

		if( null == $this->conversion_matrix ) {

			$simple_string_types = array(
				'audio', 'email', 'embed', 'file', 'google_address', 'image', 'numeric', 'phone', 'textfield',
				'url', 'video', 'colorpicker', 'textarea'
			);

			$allowed_conversion_source = array(
				'audio' => $simple_string_types,
				'colorpicker' => $simple_string_types,
				'checkbox' => array( 'checkbox' ),
				'checkboxes' => array( 'checkboxes' ),
				'date' => array( 'date' ),
				'email' => $simple_string_types,
				'embed' => $simple_string_types,
				'file' => $simple_string_types,
				'google_address' => $simple_string_types,
				'image' => $simple_string_types,
				'numeric' => $simple_string_types,
				'phone' => $simple_string_types,
				'radio' => array( 'radio' ),
				'select' => array( 'select' ),
				'skype' => array( 'skype' ),
				'textarea' => $simple_string_types,
				'textfield' => $simple_string_types,
				'url' => $simple_string_types,
				'video' => $simple_string_types,
				'wysiwyg' => array( 'wysiwyg' ),
			);


			$filtered_conversion_matrix = array();
			foreach( $allowed_conversion_source as $field_type_slug => $allowed_conversions ) {

				/**
				 * wpcf_filter_field_control_change_type_allowed_types_from
				 *
				 * Filter the field types that you can switch to, given a type field
				 *
				 * @param string[] $allowed_conversions Valid targets for a given origin type.
				 * @param string $field_type_slug Field type to switch from.
				 *
				 * @since 1.8.9
				 */
				$filtered_conversion_matrix[ $field_type_slug ] = apply_filters( 'wpcf_filter_field_control_change_type_allowed_types_from', $allowed_conversions, $field_type_slug );
			}


			/**
			 * wpcf_filter_field_control_change_type_allowed_types
			 *
			 * Filter the pairs field type origin -> valid field type targets when using the fields control change field type feature
			 *
			 * @param array $allowed_conversion_matrix Valid correspondence between field types and target field types
			 *
			 * @since 1.8.9
			 */
			$result = apply_filters( 'wpcf_filter_field_control_change_type_allowed_types', $filtered_conversion_matrix );


			$this->conversion_matrix = $result;
		}

		return $this->conversion_matrix;
	}


	/**
	 * @param Types_Field_Type_Definition $type
	 * @return Types_Field_Type_Definition[]
	 */
	public function get_possible_conversions( $type ) {

		if( ! $type instanceof Types_Field_Type_Definition ) {
			throw new InvalidArgumentException( 'Not a field type definition' );
		}
		
		$matrix = $this->get_conversion_matrix();
		$allowed_slugs = wpcf_ensarr( wpcf_getarr( $matrix, $type->get_slug() ) );
		$allowed_types = Types_Field_Type_Definition_Factory::get_instance()->load_multiple_definitions( $allowed_slugs );
		return $allowed_types;
	}


	/**
	 * @param Types_Field_Type_Definition $from_type
	 * @param Types_Field_Type_Definition $to_type
	 *
	 * @return bool
	 */
	public function is_conversion_possible( $from_type, $to_type ) {
		
		if( ! $from_type instanceof Types_Field_Type_Definition || ! $to_type instanceof Types_Field_Type_Definition ) {
			throw new InvalidArgumentException( 'Not a field type definition' );
		}
		
		$possible_conversions = $this->get_possible_conversions( $from_type );
		
		return in_array( $to_type->get_slug(), array_keys( $possible_conversions ) );
	}


	public function is_conversion_two_way( $type_1, $type_2 ) {
		if( ! $type_1 instanceof Types_Field_Type_Definition || ! $type_2 instanceof Types_Field_Type_Definition ) {
			throw new InvalidArgumentException( 'Not a field type definition' );
		}
		
		return ( $this->is_conversion_possible( $type_1, $type_2 ) && $this->is_conversion_possible( $type_2, $type_1 ) );
	}


	/**
	 * Disallow certain conversion from or to google address field.
	 *
	 * This is hooked into the wpcf_filter_field_control_change_type_allowed_types_from filter with higher priority
	 * than the Toolset Maps hook that adds the google_address field type to the conversion matrix.
	 *
	 * This way we can properly handle the allowed conversions not depending on the particular Toolset Maps version
	 * that is active.
	 *
	 * @param string[] $targets Field type slugs TO which the conversion is allowed from $origin.
	 * @param string $origin Field type slug FROM which the conversion can happen.
	 *
	 * @return string[] Updated $targets.
	 * @since 2.0
	 */
	public function google_address_field_control_change_type_allowed_from( $targets, $origin ) {

		$disallowed_targets = array(
			Types_Field_Type_Definition_Factory::DATE,
			Types_Field_Type_Definition_Factory::CHECKBOX,
			Types_Field_Type_Definition_Factory::CHECKBOXES,
			Types_Field_Type_Definition_Factory::RADIO,
			Types_Field_Type_Definition_Factory::SELECT,
			Types_Field_Type_Definition_Factory::SKYPE,
			Types_Field_Type_Definition_Factory::WYSIWYG
		);

		if( Types_Field_Type_Definition_Factory::GOOGLE_ADDRESS == $origin ) {
			// Here we're filtering possible conversions FROM google address field
			$safe_targets = array_diff( $targets, $disallowed_targets );
			$targets = $safe_targets;
		} else if( in_array( $origin, $disallowed_targets ) ) {
			// Here we're filtering possible conversions TO google address field from an undesired field type
			if( in_array( Types_Field_Type_Definition_Factory::GOOGLE_ADDRESS, $targets ) ) {
				$item_pos = array_search( Types_Field_Type_Definition_Factory::GOOGLE_ADDRESS, $targets );
				unset( $targets[ $item_pos ] );
			}
		}
		return $targets;
	}
	
}