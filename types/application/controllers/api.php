<?php

/**
 * Public Types hook API.
 *
 * This should be the only point where other plugins (incl. Toolset) interact with Types directly.
 * Always use as a singleton in production code.
 *
 * Note: Types_Api is initialized on after_setup_theme with priority 10.
 *
 * When implementing filter hooks, please follow these rules:
 *
 * 1.  All filter names are automatically prefixed with 'types_'. Only lowercase characters and underscores
 *     can be used.
 * 2.  Filter names (without a prefix) should be defined in self::$callbacks.
 * 3.  For each filter, there should be a dedicated class implementing the Types_Api_Handler_Interface. Name of the class
 *     must be Types_Api_Handler_{$capitalized_filter_name}. So for example, for a hook to
 *     'types_import_from_zip_file' you need to create a class 'Types_Api_Handler_Import_From_Zip_File'.
 *
 * @since 2.2
 */
final class Types_Api {

	private static $instance;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	public static function initialize() {
		$instance = self::get_instance();

		$instance->register_callbacks();
	}


	/** Prefix for the callback method name */
	const CALLBACK_PREFIX = 'callback_';

	/** Prefix for the handler class name */
	const HANDLER_CLASS_PREFIX = 'Types_Api_Handler_';

	const DELIMITER = '_';


	private $callbacks_registered = false;


	/**
	 * @var array Filter names (without prefix) as keys, filter parameters as values:
	 *     - int $args: Number of arguments of the filter
	 */
	private static $callbacks = array(

		/**
		 * types_import_from_zip_file
		 *
		 * Run an import operation from given ZIP file.
		 *
		 * The file will, unlike in normal Types import, NOT be deleted.
		 *
		 * The import will be performed in the context of Framework Installer Views demo, so everything existing
		 * will be either overwritten or deleted.
		 *
		 * @param mixed $default Should be false/null value to indicate that the hook didn't run.
		 * @param string $path Absolute path to the ZIP file with Types import data
		 * @param array|null $args Optional array with arguments for the underlying legacy import routine.
		 * @return true|WP_Error
		 * @since 2.2
		 */
		'import_from_zip_file' => array( 'args' => 3 ),


		/**
		 * types_query_groups
		 *
		 * Query field groups of one or more domains.
		 *
		 * @param mixed $ignored
		 * @param array $query Field group query.
		 *     - 'domain': A single field domain (see Types_Field_Utils). Legacy domain names are also accepted:
		 *         'posts'|'users'|'terms'|'postmeta'|'usermeta'|'termmeta'|'all'. For 'all',
		 *         the method returns a multidimensional arrays with results for individual domains:
		 *         array( 'posts' => array( ... ), 'users' => array( ... ),  ... ).
		 *     - For the rest of the query arguments, see Types_Field_Group_Factory::query_groups().
		 *
		 * @return null|Types_Field_Group[] Groups, if any, or empty otherwise. Null is returned when the query
		 *     is invalid.
		 *
		 * @since 2.2
		 */
		'query_groups' => array( 'args' => 2 ),


		/**
		 * types_filter_get_field_group_ids_by_post_type
		 *
		 * Returns all ids of field groups assigned to the given post type.
		 *
		 * Note: This is used by CRED (auto complete form).
		 *
		 * @param mixed $ignored
		 * @param string $post_type_slug
		 *
		 * @return array Post field group IDs (may come as numeric strings).
		 *
		 * @throws InvalidArgumentException when presented with a non-string post type.
		 *
		 * @since 2.2.14
		 */
		'filter_get_field_group_ids_by_post_type' => array( 'args' => 2 )

	);


	/**
	 * Add API filter hooks (if that wasn't done before).
	 *
	 * Reads self::$callbacks for hook definitions and adds older/special hooks.
	 */
	private function register_callbacks() {

		if( $this->callbacks_registered ) {
			return;
		}

		foreach( self::$callbacks as $callback_name => $args ) {
			
			$argument_count = wpcf_getarr( $args, 'args', 1 );
			
			add_filter( 'types_' . $callback_name, array( $this, self::CALLBACK_PREFIX . $callback_name ), 10, $argument_count );
		}

		$this->callbacks_registered = true;

		/**
		 * types_filter_query_field_definitions
		 *
		 * @param mixed $default
		 * @param array $query Field definition query. See Types_Field_Definition_Factory::query() for allowed arguments.
		 *     Additionally, you can specify:
		 *     - 'domain': Field domain (see Types_Field_Utils; legacy domain names are also accepted):
		 *       'posts'|'users'|'terms'|'postmeta'|'usermeta'|'termmeta'|'all'. For 'all',
		 *       the method returns a multidimensional arrays with results for individual domains:
		 *       array( 'posts' => array( ... ), 'users' => array( ... ),  ... ).
		 * @return null|array Field definition arrays, sanitized as per field type, or null if an error has occurred.
		 * @since 2.1
		 */
		add_filter( 'types_filter_query_field_definitions', array( $this, 'query_field_definitions' ), 10, 2 );


		/**
		 * types_is_active
		 *
		 * Indicate that Types is active. :-)
		 *
		 * @since 2.2
		 * @return bool
		 */
		add_filter( 'types_is_active', '__return_true' );

	}


	/**
	 * Handle a call to undefined method on this class, hopefully an action/filter call.
	 *
	 * @param string $name Method name.
	 * @param array $parameters Method parameters.
	 * @since 2.1
	 * @return mixed
	 */
	public function __call( $name, $parameters ) {
		
		$default_return_value = wpcf_getarr( $parameters, 0, null );
		
		// Check for the callback prefix in the method name
		$name_parts = explode( self::DELIMITER, $name );
		if( 0 !== strcmp( $name_parts[0] . self::DELIMITER, self::CALLBACK_PREFIX ) ) {
			// Not a callback, resign.
			return $default_return_value;
		}

		// Deduct the handler class name from the callback name
		unset( $name_parts[0] );
		$class_name = implode( self::DELIMITER, $name_parts );
		$class_name = strtolower( $class_name );
		$class_name = mb_convert_case( $class_name, MB_CASE_TITLE );
		$class_name = self::HANDLER_CLASS_PREFIX . $class_name;

		// Obtain an instance of the handler class.
		try {
			/** @var Types_Api_Handler_Interface $handler */
			$handler = new $class_name();
		} catch( Exception $e ) {
			// The handler class could not have been instantiated, resign.
			return $default_return_value;
		}

		// Success
		return $handler->process_call( $parameters );
	}



	/**
	 * Hook for types_filter_query_field_definitions.
	 *
	 * @param mixed $ignored
	 * @param array $query Field definition query. See Types_Field_Definition_Factory::query() for supported arguments.
	 *     Additionally, you can specify:
	 *     - 'domain': A single field domain (see Types_Field_Utils) or 'all'. Legacy domain names are also accepted.
	 *       For 'all', the method returns a multidimensional arrays with results for individual domains:
	 *     - 'refresh': A boolean to refresh the definitions, useful when getting data after saving fields
	 *       array( 'posts' => array( ... ), 'users' => array( ... ),  ... ).
	 *
	 * @note The 'refresh' parameter is temporal and might dissapear without prior notice when the groups and fields saving gets integrated in the fields definition factory
	 *
	 * @return null|array Field definition arrays, sanitized as per field type, or null if an error has occurred.
	 * @since 2.1
	 */
	public function query_field_definitions(
		/** @noinspection PhpUnusedParameterInspection */ $ignored, $query
	) {
		$domain = wpcf_getarr( $query, 'domain', 'all' );

		if( 'all' == $domain ) {

			// Call itself for each available domain.
			$results_by_domain = array();
			$domains = Types_Field_Utils::get_domains();
			foreach( $domains as $field_domain ) {
				$per_domain_query = $query;
				$per_domain_query['domain'] = $field_domain;
				$results_by_domain[ $field_domain ] = $this->query_field_definitions( null, $per_domain_query );
			}

			return $results_by_domain;

		} else {

			// Sanitize input
			if( ! is_string( $domain ) || ! is_array( $query ) ) {
				return null;
			}

			// Get the factory by domain, and if it fails, try to convert from legacy meta_type value.
			try {
				$definition_factory = Types_Field_Utils::get_definition_factory_by_domain( $domain );
			} catch( InvalidArgumentException $e ) {
				$definition_factory = null;
			}

			if ( null == $definition_factory ) {
				try {
					$definition_factory = Types_Field_Utils::get_definition_factory_by_domain( Types_Field_Utils::legacy_meta_type_to_domain( $domain ) );
				} catch( InvalidArgumentException $e ) {
					return null;
				}
			}

			// Allways query only Types fields.
			$query['filter'] = 'types';
			
			if ( 
				isset( $query['refresh'] ) 
				&& $query['refresh']
			) {
				$definition_factory->clear_definition_storage();
			}
			
			/** @var WPCF_Field_Definition[] $definitions */
			$definitions = $definition_factory->query_definitions( $query );
			$definition_arrays = array();
			foreach( $definitions as $definition ) {
				$definition_arrays[] = $definition->get_definition_array();
			}

			return $definition_arrays;
		}
	}
	
}
