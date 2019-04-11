<?php

/**
 * Field type definition.
 *
 * This represents a single field type like "email", "audio", "checkbox" and so on. This class must be instantiated
 * exclusively through Types_Field_Type_Definition_Factory.
 *
 * @since 2.0
 */
class Types_Field_Type_Definition {


	/**
	 * @var string Slug of the registered field type.
	 */
	private $field_type_slug;


	/**
	 * @var string Name of the field type that can be displayed to the user.
	 */
	private $display_name;


	/**
	 * @var string Field description entered by the user.
	 */
	private $description;


	/**
	 * @var array Arguments defining the field type. Can contain some legacy values.
	 */
	private $args;


	/**
	 * Types_Field_Type_Definition constructor.
	 *
	 * @param string $field_type_slug Field type slug.
	 * @param array $args Additional array of arguments which should contain at least 'display_name' (or 'title')
	 * and 'description' elements, but omitting them is not critical.
	 */
	public function __construct( $field_type_slug, $args ) {

		if( sanitize_title( $field_type_slug ) != $field_type_slug ) {
			throw new InvalidArgumentException( 'Invalid field type slug.' );
		}

		if( ! is_array( $args ) ) {
			throw new InvalidArgumentException( 'Wrong arguments provided.' );
		}

		$this->field_type_slug = $field_type_slug;

		// Try to fall back to legacy "title", and if even that fails, use id instead.
		$this->display_name = sanitize_text_field( wpcf_getarr( $args, 'display_name', wpcf_getarr( $args, 'title', $field_type_slug ) ) );

		$this->description = wpcf_getarr( $args, 'description', '' );
		$this->args = $args;
	}


	public function get_slug() { return $this->field_type_slug; }

	public function get_display_name() { return $this->display_name; }

	public function get_description() { return $this->description; }


	/**
	 * Determine if the fields of this type can be repetitive.
	 *
	 * @return bool
	 * @since 2.0
	 */
	public function can_be_repetitive() { return true; }


	/**
	 * Direct access to the field type configuration.
	 *
	 * It is strongly encouraged to write custom (and safe) getters for anything you need to get from it.
	 *
	 * @param null|string $argument_name Specific argument name or null to return all arguments.
	 * @param string $default Default value when a specific argument is not set.
	 * @return array|mixed
	 * @since 2.0
	 */
	public function get_args( $argument_name = null, $default = '' ) {
		if( null == $argument_name ) {
			return $this->args;
		} else {
			return wpcf_getarr( $this->args, $argument_name, $default );
		}
	}


	/**
	 * Retrieve CSS classes for a field type icon.
	 * 
	 * To be placed in the i tag.
	 * 
	 * @return string One or more CSS classes separated by spaces.
	 * @since 2.0
	 */
	public function get_icon_classes() {
		$fa_class = $this->get_args( 'font-awesome', null );
		if( null != $fa_class ) {
			return sprintf( 'fa fa-%s', esc_attr( $fa_class ) );
		}
		
		$types_class = $this->get_args( 'types-field-image', null );
		if( null != $types_class ) {
			return sprintf( 'types-field-icon types-field-icon-%s', esc_attr( $types_class ) );
		}
		
		return '';		
	}


	/**
	 * Perform field type-specific sanitization of the field definition array.
	 * 
	 * @link https://git.onthegosystems.com/toolset/types/wikis/database-layer/field-definition-arrays
	 * @param $definition_array
	 * @return array Sanitized definition array
	 * @since 2.1
	 */
	protected function sanitize_field_definition_array_type_specific( $definition_array ) {
		return $definition_array;
	}


	/**
	 * Perform a very generic sanitization of the field definition array.
	 *
	 * Should be used only by sanitize_field_definition_array().
	 *
	 * @param array $definition_array
	 * @return array
	 * @since 2.1
	 */
	private function sanitize_field_definition_array_generic( $definition_array ) {
		// slug: sanitize_title
		$definition_array['slug'] = sanitize_title( wpcf_getarr( $definition_array, 'slug' ) );

		// type: default to textfield
		$definition_array['type'] = wpcf_getarr( $definition_array, 'type', Types_Field_Type_Definition_Factory::TEXTFIELD );

		// name: sanitize_text_field
		$definition_array['name'] = sanitize_text_field( wpcf_getarr( $definition_array, 'name' ) );

		// description: ensure it is set
		$definition_array['description'] = wpcf_getarr( $definition_array, 'description' );

		// meta_key: default to wpcf-{$slug}
		$definition_array['meta_key'] = wpcf_getarr( $definition_array, 'meta_key', WPCF_Field_Definition::FIELD_META_KEY_PREFIX . $definition_array['slug'] );

		// data: must be an array
		$definition_array['data'] = wpcf_ensarr( wpcf_getarr( $definition_array, 'data' ) );

		// data[conditional_display]: must be an array
		$definition_array['data']['conditional_display'] = wpcf_ensarr( wpcf_getarr( $definition_array['data'], 'conditional_display' ) );

		// data[validate]: must be an array
		$definition_array['data']['validate'] = wpcf_ensarr( wpcf_getarr( $definition_array['data'], 'validate' ) );

		return $definition_array;
	}


	/**
	 * Make sure that the field definition array contains all necessary information.
	 * 
	 * Note: This is a WIP, currently it sanitizes only very specific cases. It should be extended in the future.
	 *
	 * @link https://git.onthegosystems.com/toolset/types/wikis/database-layer/field-definition-arrays
	 * @param array $definition_array Field definition array
	 * @return array Field definition array that is safe to be used even with legacy code.
	 * @since 2.0
	 */
	public final function sanitize_field_definition_array( $definition_array ) {

		/**
		 * types_pre_sanitize_field_definition_array
		 *
		 * Allow for additional field definition array sanitization before the standard one runs.
		 *
		 * @param mixed $definition_array
		 * @return array
		 * @since 2.1
		 */
		$definition_array = wpcf_ensarr( apply_filters( 'types_pre_sanitize_field_definition_array', $definition_array ) );

		$definition_array = $this->sanitize_field_definition_array_generic( $definition_array );

		$definition_array = $this->sanitize_numeric_validation( $definition_array );
		
		$definition_array = $this->sanitize_field_definition_array_type_specific( $definition_array );
		
		/**
		 * types_post_sanitize_field_definition_array
		 *
		 * Allow for additional field definition array sanitization after the standard one runs.
		 *
		 * @param array $definition_array
		 * @return array
		 * @since 2.1
		 */
		$definition_array = wpcf_ensarr( apply_filters( 'types_post_sanitize_field_definition_array', $definition_array ) );
		
		return $definition_array;
	}


	/**
	 * For all fields, remove the "number" validation option.
	 * 
	 * Numeric field will override this and do the opposite instead.
	 * 
	 * @param array $definition_array
	 * @return array
	 * @since 2.0
	 */
	protected function sanitize_numeric_validation( $definition_array ) {

		// This is what wpcf_admin_custom_fields_change_type() was doing.
		if( isset( $definition_array['data']['validate']['number'] ) ) {
			unset( $definition_array['data']['validate']['number'] );
		}

		return $definition_array;
	}


	/**
	 * Perform a basic "isset" sanitization of an array element.
	 * 
	 * @param array $source 
	 * @param string $element_name Name of the element to sanitize.
	 * @param string $default Default value for the element if not set or invalid.
	 * @param null|array $allowed If an array, defines the set of allowed values for the element. 
	 * @param null|string $nested_key If not null, the element will be taken from $source[$nested_key][$element_name].
	 * @return array Updated source array.
	 * @since 2.1
	 */
	protected function sanitize_element_isset( $source, $element_name, $default = '', $allowed = null, $nested_key = null ) {
		$src_array = ( null == $nested_key ? $source : $source[ $nested_key ] );
		$value = wpcf_getarr( $src_array, $element_name, $default, $allowed );
		
		if( null == $nested_key ) {
			$source[ $element_name ] = $value;
		} else {
			$source[ $nested_key ][ $element_name ] = $value;
		}
		
		return $source;
	}

}
