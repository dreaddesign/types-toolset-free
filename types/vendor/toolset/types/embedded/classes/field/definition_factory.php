<?php

/**
 * Abstract factory for field definitions.
 *
 * Handles creation of the objects as well as their caching.
 *
 * @since 1.9
 */
abstract class WPCF_Field_Definition_Factory {

	/**
	 * Singleton parent.
	 *
	 * @link http://stackoverflow.com/questions/3126130/extending-singletons-in-php
	 * @return WPCF_Field_Definition_Factory Instance of calling class.
	 */
	final public static function get_instance() {
		static $instances = array();
		$called_class = get_called_class();
		if( !isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}
		return $instances[ $called_class ];
	}


	final private function __construct() { }


	final private function __clone() { }


	/**
	 * Get a field definition foctory for given field domain.
	 *
	 * @param string $domain Valid field domain.
	 * @return WPCF_Field_Definition_Factory Factory instance for given domain.
	 * @throws InvalidArgumentException when the domain is invalid
	 * @since 2.1
	 */
	public static function get_factory_by_domain( $domain ) {
		switch( $domain ) {
			case Types_Field_Utils::DOMAIN_POSTS:
				return WPCF_Field_Definition_Factory_Post::get_instance();
			case Types_Field_Utils::DOMAIN_USERS:
				return WPCF_Field_Definition_Factory_User::get_instance();
			case Types_Field_Utils::DOMAIN_TERMS:
				return WPCF_Field_Definition_Factory_Term::get_instance();
			default:
				throw new InvalidArgumentException( 'Invalid field domain.' );
		}
	}


	/**
	 * @return string Name of the option that should be used to obtain field definitions.
	 */
	protected abstract function get_option_name();


	/**
	 * @return string Name of the class that should be instantiated as a field definition. It MUST inherit from
	 * WPCF_Field_Definition.
	 */
	protected abstract function get_class_name();


	public abstract function get_domain();


	/**
	 * @var array Existing instances of field definitions indexed by field slugs.
	 */
	private $field_definitions = array();


	/**
	 * Load an existing field definition.
	 *
	 * For now, we're using legacy code to read fields from the options table.
	 *
	 * Note that field definitions for fields not currently managed by Types may be loaded as well.
	 *
	 * @param string $field_key Key used to store the field configuration in options, or field slug (which should be
	 * equal to the key).
	 * @return null|WPCF_Field_Definition Field definition or null if it can't be loaded.
	 */
	final public function load_field_definition( $field_key ) {

		if( !is_string( $field_key ) || empty( $field_key ) ) {
			return null;
		}

		// Can we use cached version?
		if( !array_key_exists( $field_key, $this->field_definitions ) ) {

			// Get all field definitions for the option name we're using. No performance worries, it uses caching.
			$fields_from_options = $this->get_fields_from_options();
			$field_configuration = null;
			if( in_array( $field_key, array_keys( $fields_from_options ) ) ) {
				$field_configuration = $fields_from_options[ $field_key ];
			} else {

				// Theoretically, the field key may differ from the field slug (we have no invariants defined anywhere).
				// We can search the array and look for slugs.
				foreach( $fields_from_options as $field_from_options ) {
					if( wpcf_getarr( $field_from_options, 'slug' ) == $field_key ) {
						$field_configuration = $fields_from_options;
						break;
					}
				}

				if( null == $field_configuration ) {
					// No such field is defined.
					return null;
				}
			}

			// Prepare the field type information, fail if we can't.
			$field_type_slug = wpcf_getarr( $field_configuration, 'type', null );
			$field_type = Types_Field_Type_Definition_Factory::load( $field_type_slug );
			if( null == $field_type ) {
				return null;
			}

			// Create the object and save it to cache.
			try {
				$class_name = $this->get_class_name();
				/** @var WPCF_Field_Definition $field_definition */
				$field_definition = new $class_name( $field_type, $field_configuration, $this );
			} catch( Exception $e ) {
				return null;
			}

			$this->field_definitions[ $field_key ] = $field_definition;
		}

		return $this->field_definitions[ $field_key ];
	}


	/**
	 * This method is to be used only for bringing existing fields under Types control.
	 *
	 * At this point it is assumed that there doesn't exist any field definition for given meta_key.
	 * See Types_Field_Utils::start_managing_field() for details.
	 *
	 * Maybe the usage could be wider, but that is not yet clear from the legacy code. The behaviour is slightly
	 * different for meta_keys with the wpcf- prefix from the ones without it. More details in the code.
	 *
	 * The field will be created as a text field.
	 *
	 * @param string $meta_key Field meta key.
	 *
	 * @return string|false New field slug on success, false otherwise.
	 * @since 2.0
	 */
	final public function create_field_definition_for_existing_fields( $meta_key ) {

		// If the meta_key has our wpcf- prefix, we will not use it in the slug.
		$field_slug = preg_replace( '/^wpcf\-/', '', $meta_key );

		$definition_array = array(
			'slug' => $field_slug,
			'meta_key' => $meta_key,
			'meta_type' => Types_Field_Utils::domain_to_legacy_meta_type( $this->get_domain() ),
			'type' => Types_Field_Type_Definition_Factory::TEXTFIELD,

			// Use slug as name as well if we don't have anything better.
			'name' => $field_slug,

			'description' => '',

			'data' => array(),

			// This is not used anywhere but the sanitization in legacy code will exclude fields without this key.
			'id' => $field_slug
		);


		// Now comes a funny part that I don't fully understand. When the field's meta_key does contain the Types
		// prefix ('wpcf-'), we assume that this was most probably a Types field whose definition got lost. If not,
		// it's a completely "foreign" field that we'll only manage from now on.
		//
		// In the first case, the legacy code says "let's take full control" and that means setting 'controlled' to 0,
		// while for other fields (apparently without "full control") we set 'controlled' to 1... well, ok...
		//
		// But it also says "WATCH THIS! MUST NOT BE DROPPED IN ANY CASE", so let's not drop it.
		//
		// Name of the legacy function is: wpcf_types_cf_under_control().
		//
		// I assume this setting is somehow related to toolset-forms.
		$adding_field_with_prefix = (
			substr( $meta_key, 0, strlen( WPCF_Field_Definition::FIELD_META_KEY_PREFIX ) ) == WPCF_Field_Definition::FIELD_META_KEY_PREFIX
		);

		$definition_array['data']['controlled'] = ( $adding_field_with_prefix ? 0 : 1 );


		// Sanitize the definition array by type
		$textfield_type = Types_Field_Type_Definition_Factory::get_instance()->load_field_type_definition( Types_Field_Type_Definition_Factory::TEXTFIELD );
		if( null == $textfield_type ) {
			return false;
		}
		$definition_array = $textfield_type->sanitize_field_definition_array( $definition_array );

		// Save the data
		$this->set_field_definition( $field_slug, $definition_array );

		// Indicate success
		return $field_slug;

	}


	/**
	 * @return array Raw field definition data from the options, including definitions of fields not currently
	 *     managed by Types.
	 */
	private function get_fields_from_options() {
		return wpcf_admin_fields_get_fields( false, false, false, $this->get_option_name() );
	}


	/**
	 * Removes a single field definition from the storage of existing instances.
	 *
	 * It also completely clears the cache of the (legacy) wpcf_admin_fields_get_fields.
	 * Note that this method is public only temporarily and that this is not a mere cache clearing.
	 *
	 * @param string|null $field_slug If null, the cache will be emptied completely.
	 */
	public function clear_definition_storage( $field_slug = null ) {
		if( null == $field_slug ) {
			$this->field_definitions = array();
		} else {
			unset( $this->field_definitions[ $field_slug ] );
		}

		wpcf_admin_fields_get_fields( false, false, false, $this->get_option_name(), false, true );
	}


	/**
	 * Completely erase field definition from options and clear cache.
	 *
	 * @param string $field_slug
	 * @since 1.9
	 */
	private function erase_field_definition_from_options( $field_slug ) {

		$fields_from_options = $this->get_fields_from_options();
		unset( $fields_from_options[ $field_slug ] );
		$this->update_field_definition_option( $fields_from_options );

		$this->clear_definition_storage( $field_slug );
	}


	/**
	 * Update the option that stores field definitions for current domain with new value.
	 * 
	 * This is a low-level method. No validation or sanitization is performed whatsoever.
	 * 
	 * @param array $updated_value New value to be stored.
	 * @since 2.0
	 */
	private function update_field_definition_option( $updated_value ) {
		update_option( $this->get_option_name(), $updated_value );
	}


	/**
	 * @return string[] Slugs of fields that have a definition in Types.
	 */
	private function get_types_field_slugs() {
		$fields_from_options = $this->get_fields_from_options();
		$field_slugs = array();
		foreach ( $fields_from_options as $field_configuration ) {
			$slug = wpcf_getarr( $field_configuration, 'slug' );
			if( !empty( $slug ) ) {
				$field_slugs[] = $slug;
			}
		}
		return array_unique( $field_slugs );
	}


	/**
	 * Determine if there exists any Types field definition (within the domain) that uses this key.
	 *
	 * @param string $meta_key
	 * @param string [$return='boolean'] For 'boolean', the method simply returns true/false answer, for 'definition'
	 *     it returns either the field definition instance or null if no such one exists.
	 * @return bool|WPCF_Field_Definition|null
	 * @since 1.9
	 */
	public function meta_key_belongs_to_types_field( $meta_key, $return = 'boolean' ) {
		$field_definitions = $this->load_types_field_definitions();
		foreach( $field_definitions as $field_definition ) {
			if( $field_definition->get_meta_key() == $meta_key ) {
				return ( 'boolean' == $return ? true : $field_definition );
			}
		}
		return ( 'boolean' == $return ? false : null );
	}


	/**
	 * @return WPCF_Field_Definition[] All existing Types field definitions.
	 */
	private function load_types_field_definitions() {
		$field_slugs = $this->get_types_field_slugs();
		$field_definitions = array();

		foreach ( $field_slugs as $slug ) {
			$field_definition = $this->load_field_definition( $slug );
			if ( null != $field_definition ) {
				$field_definitions[] = $field_definition;
			}
		}

		return $field_definitions;
	}


	/**
	 * @return string[] All meta keys that occur in the database (within the domain).
	 */
	protected abstract function get_existing_meta_keys();


	/**
	 * @return Types_Field_Group_Factory
	 * @since 2.0
	 */
	public abstract function get_group_factory();


	/**
	 * @var null|WPCF_Field_Definition_Generic[] Cache.
	 */
	private $generic_field_definitions = null;


	/**
	 * @return WPCF_Field_Definition_Generic[] Definitions of all generic fields that exist in the database within
	 *     current domain.
	 */
	public function load_generic_field_definitions() {

		if( null == $this->generic_field_definitions ) {
			$existing_meta_keys = $this->get_existing_meta_keys();

			$results = array();
			foreach( $existing_meta_keys as $meta_key ) {

				if( $this->meta_key_belongs_to_types_field( $meta_key ) ) {
					continue;
				}

				$results[] = new WPCF_Field_Definition_Generic( $meta_key );
			}

			$this->generic_field_definitions = $results;

		}

		return $this->generic_field_definitions;
	}


	/**
	 * @return WPCF_Field_Definition_Abstract[] All field definitions (generic and Types-controlled).
	 */
	public function load_all_definitions() {
		return array_merge( $this->load_types_field_definitions(), $this->load_generic_field_definitions() );
	}


	/**
	 * Reorder an array of field definitions.
	 *
	 * @param WPCF_Field_Definition_Abstract[] $definitions
	 * @param string $orderby 'name'|'slug'|'is_under_types_control'|'field_type'
	 * @param string $order 'asc'|'desc'
	 *
	 * @return WPCF_Field_Definition_Abstract[] Reordered array.
	 */
	public function order_definitions( $definitions, $orderby = 'name', $order = 'asc' ) {

		$orderby_to_comparison_function = array(
			'name' => 'compare_definitions_by_name',
			'slug' => 'compare_definitions_by_slug',
			'is_under_types_control' => 'compare_definition_by_types_control',
			'field_type' => 'compare_definitions_by_field_type'
		);

		usort( $definitions, array( $this, wpcf_getarr( $orderby_to_comparison_function, $orderby, 'compare_definitions_by_name' ) ) );

		if( 'desc' == $order ) {
			$definitions = array_reverse( $definitions );
		}

		return $definitions;
	}


	/**
	 * Compare function for ordering by name in order_definitions().
	 *
	 * @param $first WPCF_Field_Definition_Abstract
	 * @param $second WPCF_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definitions_by_name( $first, $second ) {
		return strcoll( strtolower( $first->get_name() ), strtolower( $second->get_name() ) );
	}


	/**
	 * Compare function for ordering by slug in order_definitions().
	 *
	 * @param $first WPCF_Field_Definition_Abstract
	 * @param $second WPCF_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definitions_by_slug( $first, $second ) {
		return strcmp( $first->get_slug(), $second->get_slug() );
	}


	/**
	 * Compare function for ordering by the Types control status in order_definitions().
	 *
	 * @param $first WPCF_Field_Definition_Abstract
	 * @param $second WPCF_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definition_by_types_control( $first, $second ) {
		if( $first->is_under_types_control() == $second->is_under_types_control() ) {
			return 0;
		} else {
			return $first->is_under_types_control() ? 1 : -1;
		}
	}


	/**
	 * Compare function for ordering by field type in order_definitions().
	 *
	 * @param $first WPCF_Field_Definition_Abstract
	 * @param $second WPCF_Field_Definition_Abstract
	 *
	 * @return int
	 */
	public function compare_definitions_by_field_type( $first, $second ) {
		if( $first->is_under_types_control() == $second->is_under_types_control() ) {
			if( $first->is_under_types_control() ) {
				// Both are under Types control, compare their field types
				/** @var WPCF_Field_Definition $first_t */
				$first_t = $first;
				/** @var WPCF_Field_Definition $second_t */
				$second_t = $second;
				return strcmp( $first_t->get_type()->get_slug(), $second_t->get_type()->get_slug() );
			} else {
				// None are under Types control
				return 0;
			}
		} else {
			// The one that is under Types control wins.
			return $first->is_under_types_control() ? -1 : 1;
		}
	}


	/**
	 * Query field definitions.
	 *
	 * @param array $args Following arguments are recognized:
	 *
	 *     - filter: What field definitions should be retrieved: 'types'|'generic'|'all'
	 *     - orderby: 'name'|'slug'|'is_under_types_control'|'field_type'
	 *     - order: 'asc'|'desc'
	 *     - search: String for fulltext search.
	 *     - field_type: string|array Field type slug(s). Allowed only for Types fields.
	 *     - group_id: int Field group ID where this field belongs to. Allowed only for Types fields.
	 *     - group_slug: string Slug of an existing firld group where this field belongs to. If defined, overrides
	 *           the group_id argument. Allowed only for Types fields.
	 *
	 * @return WPCF_Field_Definition_Abstract[] Field definitions that match query arguments.
	 *
	 * @since 1.9
	 */
	public function query_definitions( $args ) {

		$args = wp_parse_args( $args,  array( 'filter' => 'all' ) );

		// Get only certain type of field definitions (generic, Types or both)
		$filter = wpcf_getarr( $args, 'filter' );
		if( 'types' == $filter ) {
			$results = $this->load_types_field_definitions();
		} else if( 'generic' == $filter ) {
			$results = $this->load_generic_field_definitions();
		} else if( 'all' == $filter ) {
			$results = $this->load_all_definitions();
		} else {
			$results = array();
		}

		// Save us some work if there will be no results at all
		if( empty( $results ) ) {
			return array();
		}

		// Perform fulltext search if needed
		$search_string = wpcf_getarr( $args, 'search', '' );
		if( !empty( $search_string ) ) {
			$matches = array();
			foreach( $results as $definition ) {
				if( $definition->is_match( $search_string ) ) {
					$matches[] = $definition;
				}
			}
			$results = $matches;
		}

		// Select only fields of desired type
		$field_type = wpcf_getarr( $args, 'field_type', array() );
		$field_type = empty( $field_type ) ? array() : $field_type;
		$field_type = is_array( $field_type ) ? $field_type : array( $field_type );
		if( !empty( $field_type ) ) {
			$type_matches = array();
			foreach( $results as $definition ) {
				if( $definition instanceof WPCF_Field_Definition ) {
					$type = $definition->get_type();
					if( in_array( $type->get_slug(), $field_type ) ) {
						$type_matches[] = $definition;
					}
				}
			}
			$results = $type_matches;
		}

		// Select fields by field group.
		$group_source = wpcf_getarr( $args, 'group_slug', (int) wpcf_getarr( $args, 'group_id' ) );
		if( !empty( $group_source ) ) {
			$group_factory = $this->get_group_factory();
			$group = $group_factory->load_field_group( $group_source );
			$group_matches = array();
			if( null != $group ) {
				foreach( $results as $field_definition ) {
					if( $field_definition instanceof WPCF_Field_Definition
						&& $field_definition->belongs_to_group( $group ) )
					{
						$group_matches[] = $field_definition;
					}
				}
			}
			$results = $group_matches;
		}

		// Sort results
		$orderby = wpcf_getarr( $args, 'orderby', 'name' );
		$order = wpcf_getarr( $args, 'order', 'asc', array( 'asc', 'desc' ) );

		$results = $this->order_definitions( $results, $orderby, $order );

		return $results;
	}


	/**
	 * Permanently delete field definition.
	 *
	 * That means:
	 * - remove it from all field groups,
	 * - delete field data from the database (sic!) and
	 * - delete the definition itself.
	 *
	 * After calling this method, the field definition object passed as parameter should never be used again.
	 *
	 * @param WPCF_Field_Definition $field_definiton
	 * @return bool
	 */
	public function delete_definition( $field_definiton ) {

		// We accept only fields that are under Types control
		if( ! $field_definiton instanceof WPCF_Field_Definition ) {
			return false;
		}

		if( ! $field_definiton->is_under_types_control() ) {
			return false;
		}

		// Remove field from all groups
		$associated_groups = $field_definiton->get_associated_groups();
		foreach( $associated_groups as $group ) {
			$group->remove_field_definition( $field_definiton );
		}

		// Delete field data
		$is_success = $field_definiton->delete_all_fields();

		// Delete the definition
		if( $is_success ) {
			$slug_to_delete = $field_definiton->get_slug();
			$this->erase_field_definition_from_options( $slug_to_delete );
		}
		
		return $is_success;
	}


	/**
	 * Update existing field definition.
	 *
	 * @param WPCF_Field_Definition $field_definition
	 * @throws InvalidArgumentException
	 * @return bool True when the update was successful, false otherwise.
	 * @since 2.0
	 */
	public function update_definition( $field_definition ) {

		if( ! $field_definition instanceof WPCF_Field_Definition ) {
			throw new InvalidArgumentException( 'Not a Types field definition.' );
		}

		$fields_from_options = $this->get_fields_from_options();
		if( ! array_key_exists( $field_definition->get_slug(), $fields_from_options ) ) {
			return false;
		}

		$this->set_field_definition( $field_definition->get_slug(), $field_definition->get_definition_array() );

		return true;
	}


	/**
	 * Store field definition array in the options.
	 *
	 * This is a low-level method that doesn't perform any kind of validation or sanitization. Use with care.
	 *
	 * @param string $field_slug
	 * @param array $definition_array
	 * @since 2.0
	 */
	private function set_field_definition( $field_slug, $definition_array ) {

		$fields_from_options = $this->get_fields_from_options();

		$fields_from_options[ $field_slug ] = $definition_array;

		$this->update_field_definition_option( $fields_from_options );

		// This can be called during an AJAX call, in which case the legacy code is not loaded
		Types_Main::get_instance()->require_legacy_functions();

		// Clear the underlying legacy cache.
		wpcf_admin_fields_get_fields( false, false, false, $this->get_option_name(), false, true );
	}


	/**
	 * Temporary workaround to access field definitions on a very deep level. 
	 * 
	 * @param $field_slug
	 * @param $definition_array
	 * @deprecated Do not use, it will be removed.
	 * @since 2.1
	 */
	public function set_field_definition_workaround( $field_slug, $definition_array ) {
		$this->set_field_definition( $field_slug, $definition_array );
	}


	/**
	 * Temporary workaround to access field definitions on a very deep level. Do not use, it will be removed.
	 * 
	 * @return string
	 * @deprecated Do not use, it will be removed.
	 * @since 2.1
	 */
	public function get_option_name_workaround() {
		return $this->get_option_name();
	}


}