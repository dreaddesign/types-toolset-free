<?php

/**
 * Factory class for loading field type definitions.
 *
 * Handles creation of the objects as well as their caching.
 *
 * Currently it is only possible to load existing field types, not create new ones. We're depending on the legacy code
 * in WPCF_Fields and field types defined through specially named functions. But that is hidden from anyone who uses
 * this class.
 *
 * @since 1.9
 */
class Types_Field_Type_Definition_Factory {

	const AUDIO = 'audio';
	const COLORPICKER = 'colorpicker';
	const DATE = 'date';
	const EMBED = 'embed';
	const FILE = 'file';
	const GOOGLE_ADDRESS = 'google_address';
	const CHECKBOX = 'checkbox';
	const CHECKBOXES = 'checkboxes';
	const IMAGE = 'image';
	const NUMERIC = 'numeric';
	const RADIO = 'radio';
	const SELECT = 'select';
	const SKYPE = 'skype';
	const TEXTAREA = 'textarea';
	const TEXTFIELD = 'textfield';
	const URL = 'url';
	const VIDEO = 'video';
	const WYSIWYG = 'wysiwyg';
	
	private static $instance = null;

	private function __construct() { }

	private function __clone() { }


	public static function get_instance() {
		if( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * @var array Associative array of instantiated field type definitions, indexed by field type slugs ("checkbox",
	 * "email" and such).
	 */
	private $field_type_definitions = array();


	/**
	 * @var null|array Cached array containing path to files (!) that contain specially named functions (!) that
	 * can be used to return configuration array for a field type (!). We're caching it because
	 * WPCF_Fields::getFieldsTypes() applies a filter each time it is called.
	 */
	private $legacy_field_types = null;


	/**
	 * @return array See $legacy_field_types.
	 */
	private function get_legacy_field_types() {
		if( null == $this->legacy_field_types ) {
			$this->legacy_field_types = WPCF_Fields::getFieldsTypes();
		}
		return $this->legacy_field_types;
	}


	/**
	 * Load a field type definition.
	 *
	 * @param string $field_type_slug Slug of the field type. If the function fails to find the field type and the slug
	 * starts with a "wpcf-" prefix, it attempts to remove it and search again. This way, passing a field type ID,
	 * which usually has this form, is also supported.
	 * @return null|Types_Field_Type_Definition Field type definition or null if it can't be loaded.
	 */
	public function load_field_type_definition( $field_type_slug ) {

		if( !is_string( $field_type_slug ) ) {
			return null;
		}

		// Check if we can use cached version.
		if( !array_key_exists( $field_type_slug, $this->field_type_definitions ) ) {

			// now it gets hacky
			$field_types = $this->get_legacy_field_types();
			if( !array_key_exists( $field_type_slug, $field_types ) ) {
				// Field slug not recognized. Maybe we got a field identifier instead. Check if we can remove
				// the wpcf- prefix and try again.
				$prefix = 'wpcf-';
				if( substr( $field_type_slug, 0, strlen( $prefix ) ) == $prefix ) {
					$field_type_slug = substr( $field_type_slug, strlen( $prefix ) );
					if( !array_key_exists( $field_type_slug, $field_types ) ) {
						// Removing prefix didn't help
						return null;
					}
					// Check the cache again (now with correct slug).
					if( array_key_exists( $field_type_slug, $this->field_type_definitions ) ) {
						return $this->field_type_definitions[ $field_type_slug ];
					}
				} else {
					// There was no prefix to remove.
					return null;
				}
			}

			// Not using getFieldTypeData() directly to avoid unnecessary getFieldsTypes() and filter applying.
			$field_type_configuration_path = $field_types[ $field_type_slug ];
			$field_type_configuration = WPCF_Fields::getFieldTypeConfig( $field_type_configuration_path );

			$field_type_id = wpcf_getarr( $field_type_configuration, 'id', null );
			if( null == $field_type_id ) {
				return null;
			}

			try {
				$field_type_definition = $this->create_definition_instance( $field_type_slug, $field_type_configuration );
			} catch( Exception $e ) {
				return null;
			}

			// Save new instance to cache.
			$this->field_type_definitions[ $field_type_slug ] = $field_type_definition;
		}

		// Use cache.
		return $this->field_type_definitions[ $field_type_slug ];
	}


	/**
	 * Create the proper instance of a type definition class, based on the type slug.
	 * 
	 * @param string $field_type_slug
	 * @param array $field_type_configuration Legacy configuration array
	 * @return Types_Field_Type_Definition
	 * @since 2.0
	 * @throws Exception
	 */
	private function create_definition_instance( $field_type_slug, $field_type_configuration ) {
		switch( $field_type_slug ) {
			case self::DATE:
				return new Types_Field_Type_Definition_Date( $field_type_configuration );
			case self::CHECKBOX:
				return new Types_Field_Type_Definition_Checkbox( $field_type_configuration );
			case self::CHECKBOXES:
				return new Types_Field_Type_Definition_Checkboxes( $field_type_configuration );
			case self::NUMERIC:
				return new Types_Field_Type_Definition_Numeric( $field_type_configuration );
			case self::RADIO:
				return new Types_Field_Type_Definition_Radio( $field_type_configuration );
			case self::SELECT:
				return new Types_Field_Type_Definition_Select( $field_type_configuration );
			case self::WYSIWYG:
				return new Types_Field_Type_Definition_Singular( $field_type_slug, $field_type_configuration );
			default:
				return new Types_Field_Type_Definition( $field_type_slug, $field_type_configuration );
		}
	}


	/**
	 * Get field type definitions from an array of slugs.
	 *
	 * If a definition cannot be loaded for a given slug, the slug is skipped without reporting an error in any other way.
	 *
	 * @param string[] $field_type_slugs
	 * @return Types_Field_Type_Definition[]
	 * @since 2.0
	 */
	public function load_multiple_definitions( $field_type_slugs ) {
		$results = array();
		$field_type_slugs = wpcf_ensarr( $field_type_slugs );
		foreach( $field_type_slugs as $field_type_slug ) {
			$type_definition = $this->load_field_type_definition( $field_type_slug );
			if( null != $type_definition ) {
				$results[ $field_type_slug ] = $type_definition;
			}
		}
		return $results;
	}


	/**
	 * Get all field type definitions available.
	 *
	 * @return Types_Field_Type_Definition[]
	 * @since 2.0
	 */
	public function get_all_definitions() {
		$legacy_types = $this->get_legacy_field_types();
		$definitions = array();
		foreach( $legacy_types as $type_slug => $ignored ) {
			$defintion = $this->load_field_type_definition( $type_slug );
			if( null != $defintion ) {
				$definitions[] = $defintion;
			}
		}
		return $definitions;
	}


	/**
	 * Get a map of all field definition slugs to their properties.
	 * 
	 * @return string[]
	 * @since 2.0
	 */
	public function get_field_type_definitions() {
		$field_types = $this->get_all_definitions();
		$field_type_names = array();
		foreach( $field_types as $field_type ) {
			$field_type_names[ $field_type->get_slug() ] = array(
				'slug' => $field_type->get_slug(),
				'displayName' => stripslashes( $field_type->get_display_name() ),
				'canBeRepetitive' => $field_type->can_be_repetitive(),
				'iconClasses' => $field_type->get_icon_classes()
			);
		}

		return $field_type_names;
	}


	/**
	 * Static shortcut to load_field_type_definition.
	 *
	 * @param string $field_type_slug
	 * @return null|Types_Field_Type_Definition
	 */
	public static function load( $field_type_slug ) {
		// we cannot use self::get_instance here, because of low PHP requirements and missing get_called_class function
		// we have a fallback class for get_called_class but that scans files by debug_backtrace and return 'self'
		//   instead of Types_Field_Type_Definition_Factory like the original get_called_class() function does
		// ends in an error because of parents (abstract) $var = new self();
		return Types_Field_Type_Definition_Factory::get_instance()->load_field_type_definition( $field_type_slug );
	}
}