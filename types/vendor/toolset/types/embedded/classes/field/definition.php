<?php

/**
 * Definition of a field.
 *
 * Children of this class must be instantiated exclusively through a factory class inherited from WPCF_Field_Definition_Factory.
 *
 * Note about field definition identification: For historical reasons, there are several, possibly equal, values available:
 * The field key (key in the associative array of field definitions), slug, id and meta_key. While the meta_key's role
 * is clear - it determines how field's data are stored in postmeta -, the first three values seem to be completely
 * identical. Since the slug is used to organize fields into groups, we will use that as the main unique identifier
 * from now on.
 *
 * Note: id is actually equal to slug, there was "$field['id'] = $field['slug'];" in the legacy code.
 *
 * @since 1.9
 */
abstract class WPCF_Field_Definition extends WPCF_Field_Definition_Abstract {


	/**
	 * For a Types field, this is a default prefix to it's slug that defines the meta_key for storing this field's values.
	 * Note that other custom fields that are brought under Types control additionally don't have to use this prefix
	 * and can have completely arbitrary meta_keys.
	 */
	const FIELD_META_KEY_PREFIX = 'wpcf-';


	/**
	 * @var Types_Field_Type_Definition Type definition.
	 */
	private $type;


	/**
	 * @var array The underlying array with complete information about this field. Must be kept sanitized at all times.
	 * @link https://git.onthegosystems.com/toolset/types/wikis/database-layer/field-definition-arrays
	 */
	private $definition_array;


	/** @var string Field slug. */
	private $slug;


	/** @var string Name of the field that can be displayed to the user. */
	private $name;


	/** @var WPCF_Field_Definition_Factory The factory object that manages this field definition */
	private $factory;


	/**
	 * WPCF_Field_Definition constructor.
	 *
	 * @param Types_Field_Type_Definition $type Field type definition.
	 * @param array $definition_array The underlying array with complete information about this field.
	 * @param WPCF_Field_Definition_Factory $factory
	 *
	 * @since 1.9
	 */
	public function __construct( $type, $definition_array, $factory ) {
		
		if( ! $type instanceof Types_Field_Type_Definition ) {
			throw new InvalidArgumentException( 'Invalid field type.' );
		}
		
		$this->type = $type;
		
		if( ! $factory instanceof WPCF_Field_Definition_Factory ) {
			throw new InvalidArgumentException( 'Invalid field definition factory object.' );
		}
		
		$this->factory = $factory;

		$this->definition_array = $this->get_type()->sanitize_field_definition_array( wpcf_ensarr( $definition_array ) );

		$this->slug = wpcf_getarr( $this->definition_array, 'slug' );

		if( sanitize_title( $this->slug ) != $this->slug ) {
			throw new InvalidArgumentException( 'Invalid slug.' );
		}

		$this->name = sanitize_text_field( wpcf_getarr( $this->definition_array, 'name', $this->get_slug() ) );

	}



	public function get_slug() { return $this->slug; }


	public function get_name() { return $this->name; }


	/**
	 * @return string Proper display name suitable for direct displaying to the user.
	 *
	 * Handles string translation and adds an asterisk if the field is required.
	 *
	 * @since 1.9
	 */
	public function get_display_name() {

		// Try to translate through standard toolset-forms method
		$string_name = sprintf( 'field %s name', $this->get_slug() );
		$display_name = WPToolset_Types::translate( $string_name, $this->get_name() );

		// Add an asterisk if the field is required
		if( $this->get_is_required() && !empty( $display_name ) ) {
			$display_name .= '&#42;';
		}
		return $display_name;
	}


	/**
	 * @return string Field description as provided by the user. Sanitized.
	 */
	public function get_description() { return sanitize_text_field( wpcf_getarr( $this->definition_array, 'description' ) ); }


	/**
	 * @return bool
	 * @deprecated
	 */
	public function is_under_types_control() {
		$is_disabled = (bool) wpcf_getnest( $this->definition_array, array( 'data', 'disabled' ), false );
		return !$is_disabled;
	}


	/**
	 * Determine whether the field is currently under Types control.
	 *
	 * If it's not, we are only holding on to this definition in case user decides to return it to Types control in the
	 * future. In all other regards, such field definition should be handled as a generic one.
	 *
	 * @return bool
	 * @since 2.0
	 */
	public function is_managed_by_types() {
		return $this->is_under_types_control();
	}


	private $meta_key = null;


	/**
	 * @return string Meta_key use to store this field's values. Defaults to wpcf-$slug.
	 */
	public function get_meta_key() {
		if( null == $this->meta_key ) {
			$this->meta_key = sanitize_title(
				wpcf_getarr( $this->definition_array, 'meta_key', self::FIELD_META_KEY_PREFIX . $this->get_slug() )
			);
		}
		return $this->meta_key;
	}


	private $is_repetitive = null;


	/**
	 * @return bool True if the field is repetitive, false otherwise.
	 */
	public function get_is_repetitive() {
		if( null === $this->is_repetitive ) {
			$this->is_repetitive = ( wpcf_getnest( $this->definition_array, array( 'data', 'repetitive' ), 0 ) != 0 );
		}
		return $this->is_repetitive;
	}


	/**
	 * Get the underlying field definition array.
	 *
	 * Usage of this method is strongly discouraged, consider writing a custom (and safe) getter instead.
	 *
	 * @return array
	 */
	public function get_definition_array() { return $this->definition_array; }


	public function get_type() { return $this->type; }
	

	/**
	 * For binary fields (like checkbox), it is possible to specify a value that will be saved to the database
	 * if the field is checked/selected/whatever.
	 *
	 * Stored in $cf['data']['set_save'].
	 *
	 * @return mixed|null The value or null if none is defined (make sure to compare with ===).
	 * @since 1.9
	 */
	public function get_forced_value() {
		return wpcf_getnest( $this->definition_array, array( 'data', 'set_value' ), null );
	}


	public function has_forced_value() {
		return ( null !== $this->get_forced_value() );
	}


	public function get_should_save_empty_value() {
		return ( 'yes' == wpcf_getnest( $this->definition_array, array( 'data', 'save_empty' ), 'no' ) );
	}


	public function get_is_required() {
		return ( 1 == wpcf_getnest( $this->definition_array, array( 'data', 'validate', 'required', 'active' ), 0 ) );
	}


	/**
	 * Retrieve an array of option definitions.
	 * 
	 * Allowed only for the checkboxes, radio and select field types.
	 * 
	 * @throws RuntimeException when the field type is invalid
	 * @throws InvalidArgumentException when option definitions are corrupted
	 * @return WPCF_Field_Option_Checkboxes[] An option_id => option_data array.
	 * @since 1.9
	 */
	public function get_field_options() {
		$this->check_allowed_types( 
			array( 
				Types_Field_Type_Definition_Factory::CHECKBOXES,
				Types_Field_Type_Definition_Factory::RADIO,
				Types_Field_Type_Definition_Factory::SELECT
			) 
		);
		$options_definition = wpcf_ensarr( wpcf_getnest( $this->definition_array, array( 'data', 'options' ) ) );
		$results = array();

		// The 'default' key can be present, we have to remove it so it's not handled as another option.
		$has_default = array_key_exists( 'default', $options_definition );
		$default = wpcf_getarr( $options_definition, 'default', 'no-default' );
		if( $has_default ) {
			unset( $options_definition[ 'default' ] );
		}

		foreach( $options_definition as $option_id => $option_config ) {
			try {
				switch( $this->get_type()->get_slug() ) {
					case Types_Field_Type_Definition_Factory::RADIO:
						$option = new WPCF_Field_Option_Radio( $option_id, $option_config, $default, $this );
						break;
					case Types_Field_Type_Definition_Factory::SELECT:
						$option = new WPCF_Field_Option_Select( $option_id, $option_config, $default, $this );
						break;
					case Types_Field_Type_Definition_Factory::CHECKBOXES:
						$option = new WPCF_Field_Option_Checkboxes( $option_id, $option_config, $default );
						break;
					default:
						throw new InvalidArgumentException( 'Invalid field type' );
				}
				$results[ $option_id ] = $option;
			} catch( Exception $e ) {
				// Corrupted data, can't do anything but skip the option.
			}
		}
		return $results;
	}


	/**
	 * Determines whether the field should display both time and date or date only.
	 *
	 * Allowed field type: date.
	 *
	 * @throws RuntimeException
	 * @return string 'date'|'date_and_time' (note that for 'date_and_time' the actual value stored is 'and_time',
	 *     we're translating it to sound more sensible)
	 * @since 1.9.1
	 */
	public function get_datetime_option() {
		$this->check_allowed_types( Types_Field_Type_Definition_Factory::DATE );
		$value = wpcf_getnest( $this->definition_array, array( 'data', 'date_and_time' ) );
		return ( 'and_time' == $value ? 'date_and_time' : 'date' );
	}



	/**
	 * Get an accessor for a specific field instance.
	 *
	 * @param WPCF_Field_Instance $field_instance Instance of the field the accessor should access.
	 * @return WPCF_Field_Accessor_Abstract
	 */
	public abstract function get_accessor( $field_instance );


	/**
	 * @return Types_Field_Group[]
	 */
	public function get_associated_groups() {
		$field_groups = $this->get_factory()->get_group_factory()->query_groups();
		$associated_groups = array();
		foreach ( $field_groups as $field_group ) {
			if ( $field_group->contains_field_definition( $this ) ) {
				$associated_groups[] = $field_group;
			}
		}

		return $associated_groups;
	}


	/**
	 * Determine whether this field belongs to a specific group.
	 * 
	 * @param Types_Field_Group $field_group
	 * @return bool
	 * @since 2.1
	 */
	public function belongs_to_group( $field_group ) {
		$associated_groups = $this->get_associated_groups();
		foreach( $associated_groups as $associated_group ) {
			if( $associated_group === $field_group ) {
				return true;
			}
		}
		
		return false;
	}



	/**
	 * Get a mapper object that helps translating field data between database and rest of Types.
	 *
	 * Note: This happens here and not in field type definition because the information about field type might not
	 * be enough in the future.
	 *
	 * @todo This should probably be provided by type definition, no switch should be here.
	 *
	 * @return WPCF_Field_DataMapper_Abstract
	 */
	public function get_data_mapper() {
		switch( $this->get_type()->get_slug() ) {
			case Types_Field_Type_Definition_Factory::CHECKBOXES:
				return new WPCF_Field_DataMapper_Checkboxes( $this );
			case Types_Field_Type_Definition_Factory::CHECKBOX:
				return new WPCF_Field_DataMapper_Checkbox( $this );
			default:
				return new WPCF_Field_DataMapper_Identity( $this );
		}
	}


	/**
	 * Delete all field values!
	 *
	 * @return bool
	 */
	public abstract function delete_all_fields();


	/**
	 * Throw a RuntimeException if current field type doesn't match the list of allowed ones.
	 *
	 * @param string|string[] $allowed_field_types Field type slugs
	 * @throws RuntimeException
	 * @since 1.9.1
	 */
	protected function check_allowed_types( $allowed_field_types ) {
		
		$allowed_field_types = wpcf_wraparr( $allowed_field_types );
		
		if( !in_array( $this->type->get_slug(), $allowed_field_types ) ) {
			throw new RuntimeException(
				sprintf(
					'Invalid operation for this field type "%s", expected one of the following: %s.',
					$this->type->get_slug(),
					implode( ', ', $allowed_field_types )
				)
			);
		}
	}


	/**
	 * @inheritdoc
	 *
	 * Adds properties: type, isRepetitive
	 * 
	 * @return array
	 * @since 2.0
	 */
	public function to_json() {
		$object_data = parent::to_json();

		$additions = array(
			'type' => $this->get_type()->get_slug(),
			'isRepetitive' => $this->get_is_repetitive(),
			'metaKey' => $this->get_meta_key()
		);

		return array_merge( $object_data, $additions );
	}

	
	const XML_KEY_ID = 'id';
	const XML_KEY_MENU_ICON = 'menu_icon';
	const XML_KEY_WPML_ACTION = 'wpml_action';
	const XML_KEY_DATA = 'data';
	const XML_KEY_DATA_CONDITIONAL_DISPLAY = 'conditional_display';
	const XML_KEY_DATA_IS_REPEATING = 'repetitive';
	const XML_KEY_DATA_SUBMIT_KEY = 'submit-key';


	/**
	 * Add checksum to an export object, with some custom adjustments for compatibility with
	 * older Module Manager versions.
	 *
	 * @param array $data
	 * @return array Updated $data with checksum information.
	 * @since 2.1
	 */
	private function add_checksum_to_export_object( $data ) {

		$checksum_source = $data;

		$ie_controller = Types_Import_Export::get_instance();

		// Consider if this should go into the generic sanitization:

		// Remove *empty* conditional_display for consistent checksum computation with Module manager 1.1 during import.
		$conditional_display = wpcf_getnest( $checksum_source, array( self::XML_KEY_DATA, self::XML_KEY_DATA_CONDITIONAL_DISPLAY ), null );
		if( null !== $conditional_display && empty( $conditional_display ) ) {
			unset( $checksum_source[ self::XML_KEY_DATA ][ self::XML_KEY_DATA_CONDITIONAL_DISPLAY ] );
		}

		// Convert to integer value to provide correct checksum computation of this field during Module manager 1.1. import.
		$checksum_source[ self::XML_KEY_DATA ][ self::XML_KEY_DATA_IS_REPEATING ] =
			(int) wpcf_getnest( $checksum_source, array( self::XML_KEY_DATA, self::XML_KEY_DATA_IS_REPEATING ), 0 );

		$checksum_source = $ie_controller->add_checksum_to_object(
			$checksum_source,
			null,
			array(
				self::XML_KEY_ID, self::XML_KEY_MENU_ICON, self::XML_KEY_WPML_ACTION,
				self::XML_KEY_DATA => array( self::XML_KEY_DATA_SUBMIT_KEY )
			)
		);

		$data[ Types_Import_Export::XML_KEY_CHECKSUM ] = $checksum_source[ Types_Import_Export::XML_KEY_CHECKSUM ];
		$data[ Types_Import_Export::XML_KEY_HASH ] = $checksum_source[ Types_Import_Export::XML_KEY_HASH ];

		return $data;
	}
	
	
	/**
	 * Create an export object for this field definition, including checksums and annotations.
	 * 
	 * @return array
	 * @since 2.1
	 */
	public function get_export_object() {
		
		$data = $this->get_definition_array();

		// legacy filter
		$data = apply_filters( 'wpcf_export_field', $data );

		$ie_controller = Types_Import_Export::get_instance();

		$data = $this->add_checksum_to_export_object( $data );

		$data = $ie_controller->annotate_object( $data, $this->get_name(), $this->get_slug() );
		
		// Export WPML TM setting for this field's translation, if available.
		$wpml_tm_settings = apply_filters( 'wpml_setting', null, 'translation-management' );
		$custom_field_translation_setting = wpcf_getnest( $wpml_tm_settings, array( 'custom_fields_translation', $this->get_meta_key() ), null );
		if( null !== $custom_field_translation_setting ) {
			$data[ self::XML_KEY_WPML_ACTION ] = $custom_field_translation_setting;
		}

		return $data;
	}


	/**
	 * @return WPCF_Field_Definition_Factory Factory object that is responsible for creating this object.
	 * @since 2.0
	 */
	public function get_factory() {
		return $this->factory;
	}


	/**
	 * Change type of this field definition.
	 *
	 * @param Types_Field_Type_Definition $target_type One of the allowed field types to convert to.
	 * @throws InvalidArgumentException when $target_type is not a field type definition.
	 * @return bool True if the conversion was successful, false otherwise.
	 * @since 2.0
	 */
	public function change_type( $target_type ) {

		if( ! $target_type instanceof Types_Field_Type_Definition ) {
			throw new InvalidArgumentException( 'Not a field type definition' );
		}
		
		if( ! Types_Field_Type_Converter::get_instance()->is_conversion_possible( $this->get_type(), $target_type ) ) {
			return false;
		}

		if( $this->get_is_repetitive() && !$target_type->can_be_repetitive() ) {
			return false;
		}

		// We need to immediately update this model.
		$this->type = $target_type;

		$this->definition_array['type'] = $target_type->get_slug();

		return $this->update_self();
	}


	/**
	 * Convenience method for updating field definition in database after making some changes.
	 * 
	 * @return bool True on success.
	 * @since 2.0
	 */
	private function update_self() {
		$this->definition_array = $this->get_type()->sanitize_field_definition_array( $this->get_definition_array() );
		$is_success = $this->get_factory()->update_definition( $this );
		return $is_success;
	}


	/**
	 * Convenience method for setting a 'data' attribute to the definition array safely.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @since 2.0
	 */
	private function set_data_key_safely( $key, $value ) {
		if( !is_array( wpcf_getarr( $this->definition_array, 'data' ) ) ) {
			$this->definition_array['data'] = array();
		}
		$this->definition_array['data'][ $key ] = $value;
	}


	/**
	 * Set whether this field definition will be managed by Types or not.
	 * 
	 * @param bool $is_managed
	 * @return bool True if the update was successful.
	 * @since 2.0
	 */
	public function set_types_management_status( $is_managed ) {
		$this->set_data_key_safely( 'disabled', ( $is_managed ? 0 : 1 ) );
		return $this->update_self();
	}


	/**
	 * Set whether this will be a repeating or single field.
	 * 
	 * Note that this has serious implications for already stored values.
	 * 
	 * @param bool $is_repetitive
	 * @return bool True if the update was successful
	 * @since 2.0
	 */
	public function set_is_repetitive( $is_repetitive ) {
		$this->set_data_key_safely( 'repetitive', ( $is_repetitive ? 1 : 0 ) );
		return $this->update_self();
	}

}